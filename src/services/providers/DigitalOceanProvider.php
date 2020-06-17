<?php

namespace weareferal\remotesync\services\providers;

use Craft;

use weareferal\remotesync\RemoteSync;
use weareferal\remotesync\services\providers\AWSS3Provider;


/**
 * Digital Ocean Spaces provider
 * 
 * Because the Spaces API is based on AWS S3, we can use the same PHP SDK
 * library and simply point to a different endpoint:
 * 
 * https://www.digitalocean.com/docs/spaces/resources/s3-sdk-examples/
 */
class DigitalOceanProvider extends AWSS3Provider
{
    private $name = "Digital Ocean Spaces";

    /**
     * Get API endpoint
     * 
     * If using a non-AWS endpoint (like Digital Ocean) we specify the 
     * endpoint used in the client here
     */
    protected function getEndpoint()
    {
        $settings = $this->getSettings();
        return "https://{$settings['doRegionName']}.digitaloceanspaces.com";
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
            'accessKey' => Craft::parseEnv($settings->doAccessKey),
            'secretKey' => Craft::parseEnv($settings->doSecretKey),
            // For whatever reason, to use the AWS SDK with Digital Ocena
            // you set the region name in the API options to us-east-1 while
            // adding the actual Digital Ocean region to the endpoint URL
            // 
            // See for more:
            // https://www.digitalocean.com/docs/spaces/resources/s3-sdk-examples/
            'doRegionName' => Craft::parseEnv($settings->doRegionName),
            'regionName' => 'us-east-1',
            'bucketName' => Craft::parseEnv($settings->doSpacesName),
            'bucketPath' => Craft::parseEnv($settings->doSpacesPath)
        ];
    }
}
