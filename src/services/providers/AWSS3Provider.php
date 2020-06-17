<?php

namespace weareferal\remotesync\services\providers;

use Craft;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

use weareferal\remotesync\RemoteSync;
use weareferal\remotesync\services\Provider;
use weareferal\remotesync\services\RemoteSyncService;
use weareferal\remotesync\exceptions\ProviderException;


/**
 * AWS Provider
 * 
 * A provider for use with Amazon AWS S3. This class can also be used to
 * implement other providers that use the S3 API footproint, like Digital
 * Ocean.
 */
class AWSS3Provider extends RemoteSyncService implements Provider
{
    private $name = "AWS";

    /**
     * Provider is configured
     * 
     * @return boolean whether this provider is properly configured
     * @since 1.1.0
     */
    public function isConfigured(): bool
    {
        $settings = $this->getSettings();
        return isset($settings['accessKey']) &&
            isset($settings['secretKey']) &&
            isset($settings['regionName']);
    }

    /**
     * User is authenticated with the provider
     * 
     * @return boolean
     * @since 1.1.0
     */
    public function isAuthenticated(): bool
    {
        // TODO: we should perform an actual authentication test
        return true;
    }

    /**
     * Return S3 keys
     * 
     * @param string $extension The file extension to filter the results by
     * @return array[string] An array of keys returned from S3
     * @since 1.0.0
     */
    public function list($filterExtension): array
    {
        $settings = $settings = $this->getSettings();
        $client = $this->getClient();
        $kwargs = [
            'Bucket' => $settings['bucketName'],
        ];
        if ($settings['bucketPath']) {
            $kwargs['Prefix'] = $settings['bucketPath'];
        }
        $response = $client->listObjects($kwargs);

        $objects = $response['Contents'];
        if (!$objects) {
            return [];
        }

        $keys = [];
        foreach ($objects as $object) {
            array_push($keys, basename($object['Key']));
        }

        if ($filterExtension) {
            return $this->filterByExtension($keys, $filterExtension);
        }

        return $keys;
    }

    /**
     * Push a file path to S3
     *  
     * @param string $path The full filesystem path to file
     * @since 1.0.0
     */
    public function push($path)
    {
        $settings = $this->getSettings();
        $client = $this->getClient();
        $pathInfo = pathinfo($path);
        $key = $this->getPrefixedKey($pathInfo['basename']);

        try {
            $client->putObject([
                'Bucket' => $settings['bucketName'],
                'Key' => $key,
                'SourceFile' => $path
            ]);
        } catch (AwsException $exception) {
            throw new ProviderException($this->createErrorMessage($exception));
        }
    }

    /**
     * Pull a remote S3 file
     * 
     * @since 1.0.0
     */
    public function pull($key, $localPath)
    {
        $settings = $settings = $this->getSettings();
        $client = $this->getClient();
        $key = $this->getPrefixedKey($key);

        try {
            $client->getObject([
                'Bucket' => $settings['bucketName'],
                'SaveAs' => $localPath,
                'Key' => $key,
            ]);
        } catch (AwsException $exception) {
            throw new ProviderException($this->createErrorMessage($exception));
        }

        return true;
    }

    /**
     * Delete a remote S3 key
     * 
     * @since 1.0.0
     */
    public function delete($key)
    {
        $settings = $this->getSettings();
        $client = $this->getClient();
        $key = $this->getPrefixedKey($key);
        $exists = $client->doesObjectExist($settings['bucketName'], $key);
        if (!$exists) {
            throw new ProviderException("AWS key does not exist");
        }

        try {
            $client->deleteObject([
                'Bucket' => $settings['bucketName'],
                'Key'    => $key
            ]);
        } catch (AwsException $exception) {
            throw new ProviderException($this->createErrorMessage($exception));
        }
    }

    /**
     * Return the AWS key, including any prefix folders
     * 
     * @param string $key The key for the key
     * @return string The prefixed key
     * @since 1.0.0
     */
    private function getPrefixedKey($key): string
    {
        $settings = $this->getSettings();
        if ($settings['bucketPath']) {
            return $settings['bucketPath'] . DIRECTORY_SEPARATOR . $key;
        }
        return $key;
    }

    /**
     * Return a useable S3 client object
     * 
     * @return S3Client The S3 client object
     * @since 1.0.0
     */
    private function getClient(): S3Client
    {
        $settings = $this->getSettings();
        $options = [
            'credentials' => array(
                'key'    => $settings['accessKey'],
                'secret' => $settings['secretKey']
            ),
            'version' => 'latest',
            'region'  => $settings['regionName']
        ];
        $endpoint = $this->getEndpoint();
        if ($endpoint) {
            $options['endpoint'] = $endpoint;
        }
        return S3Client::factory($options);
    }

    /**
     * Get endpoint
     * 
     * If using a non-AWS endpoint (like Digital Ocean) we specify the 
     * endpoint used in the client here
     */
    protected function getEndpoint()
    {
        return null;
    }

    /**
     * Get settings
     * 
     * This allows us to overwrite the class easily for other providers like
     * Digital Ocean that use the exact same API
     */
    protected function getSettings()
    {
        $settings = RemoteSync::getInstance()->settings;
        return [
            'accessKey' => Craft::parseEnv($settings->s3AccessKey),
            'secretKey' => Craft::parseEnv($settings->s3SecretKey),
            'regionName' => Craft::parseEnv($settings->s3RegionName),
            'bucketName' => Craft::parseEnv($settings->s3BucketName),
            'bucketPath' => Craft::parseEnv($settings->s3BucketPath)
        ];
    }

    /**
     * Create a more user-friendly error message from AWS
     * 
     * @param AwsException $exception The exception
     * @return string An client-friendly string
     * @since 1.0.0
     */
    private function createErrorMessage($exception)
    {
        Craft::$app->getErrorHandler()->logException($exception);
        $awsMessage = $exception->getAwsErrorMessage();
        $message = "{$this->name} Error";
        if ($awsMessage) {
            if (strpos($awsMessage, "The request signature we calculated does not match the signature you provided") !== false) {
                $message = $message . ' (Check secret key)';
            } else {
                $message = $message . ' ("' . $awsMessage . '")';
            }
        } else {
            $awsMessage = $exception->getMessage();
            if (strpos($awsMessage, 'Are you sure you are using the correct region for this bucket') !== false) {
                $message = $message . " (Check region credentials)";
            } else {
                $message = $message . " (Check credentials)";
            }
        }
        return $message;
    }
}
