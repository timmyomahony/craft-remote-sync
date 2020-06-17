<?php

namespace weareferal\remotesync\services\providers;

use Craft;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

use weareferal\remotesync\RemoteSync;
use weareferal\remotesync\services\Provider;
use weareferal\remotesync\services\RemoteSyncService;
use weareferal\remotesync\exceptions\ProviderException;


class AWSS3Provider extends RemoteSyncService implements Provider
{
    /**
     * Is Configured
     * 
     * @return boolean whether this provider is properly configured
     * @since 1.1.0
     */
    public function isConfigured(): bool
    {
        $settings = RemoteSync::getInstance()->settings;
        return isset($settings->s3AccessKey) &&
            isset($settings->s3SecretKey) &&
            isset($settings->s3RegionName);
    }

    /**
     * Is Authenticated
     * 
     * @return boolean whether this provider is properly authenticated
     * @todo currently we assume that if you have the keys you are 
     * authenitcated. We should do a check here
     * @since 1.1.0
     */
    public function isAuthenticated(): bool
    {
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
        $settings = RemoteSync::getInstance()->settings;
        $s3BucketName = Craft::parseEnv($settings->s3BucketName);
        $s3BucketPath = Craft::parseEnv($settings->s3BucketPath);
        $client = $this->getClient();
        $kwargs = [
            'Bucket' => $s3BucketName,
        ];
        if ($s3BucketPath) {
            $kwargs['Prefix'] = $s3BucketPath;
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
        $settings = RemoteSync::getInstance()->settings;
        $s3BucketName = Craft::parseEnv($settings->s3BucketName);
        $client = $this->getClient();
        $pathInfo = pathinfo($path);

        $key = $this->getPrefixedKey($pathInfo['basename']);

        try {
            $client->putObject([
                'Bucket' => $s3BucketName,
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
        $settings = RemoteSync::getInstance()->settings;
        $s3BucketName = Craft::parseEnv($settings->s3BucketName);
        $client = $this->getClient();
        $key = $this->getPrefixedKey($key);

        try {
            $client->getObject([
                'Bucket' => $s3BucketName,
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
        $settings = RemoteSync::getInstance()->settings;
        $s3BucketName = Craft::parseEnv($settings->s3BucketName);
        $client = $this->getClient();
        $key = $this->getPrefixedKey($key);

        $exists = $client->doesObjectExist($s3BucketName, $key);
        if (!$exists) {
            throw new ProviderException("AWS key does not exist");
        }

        try {
            $client->deleteObject([
                'Bucket' => $s3BucketName,
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
        $settings = RemoteSync::getInstance()->settings;
        $s3BucketPath = Craft::parseEnv($settings->s3BucketPath);
        if ($s3BucketPath) {
            return $s3BucketPath . DIRECTORY_SEPARATOR . $key;
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
        $settings = RemoteSync::getInstance()->settings;
        $s3AccessKey = Craft::parseEnv($settings->s3AccessKey);
        $s3SecretKey = Craft::parseEnv($settings->s3SecretKey);
        $s3RegionName = Craft::parseEnv($settings->s3RegionName);
        return S3Client::factory([
            'credentials' => array(
                'key'    => $s3AccessKey,
                'secret' => $s3SecretKey
            ),
            'version' => 'latest',
            'region'  => $s3RegionName
        ]);
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
        $message = "AWS Error";
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
