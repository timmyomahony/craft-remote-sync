<?php

namespace weareferal\remotesync\models;

use craft\base\Model;

class Settings extends Model
{
    public $enabled = true;

    public $cloudProvider = 's3';

    // AWS
    public $s3AccessKey;
    public $s3SecretKey;
    public $s3RegionName;
    public $s3BucketName;
    public $s3BucketPath;

    // Backblaze
    public $b2MasterKeyID;
    public $b2MasterAppKey;
    public $b2BucketName;
    public $b2BucketPath;

    // Google
    public $googleProjectName;
    public $googleClientId;
    public $googleClientSecret;
    public $googleAuthRedirect;
    public $googleDriveFolderId;

    // Dropbox
    public $dropboxAppKey;
    public $dropboxSecretKey;
    public $dropboxAccessToken;
    public $dropboxFolder;

    // DO Spaces
    public $doAccessKey;
    public $doSecretKey;
    public $doRegionName;
    public $doBucketName;
    public $doBucketPath;

    public $useQueue = false;
    public $keepEmergencyBackup = true;

    public $prune = false;
    public $pruneLimit = 10;

    public $hideDatabases = false;
    public $hideVolumes = false;

    public function rules(): array
    {
        return [
            // Provider details should only run when that provider is selected
            [
                ['s3AccessKey', 's3SecretKey', 's3BucketName', 's3RegionName'],
                'required',
                'when' => function ($model) {
                    return $model->cloudProvider == 's3' & $model->enabled == 1;
                }
            ],
            [
                ['b2MasterKeyID', 'b2MasterAppKey', 'b2BucketName'],
                'required',
                'when' => function ($model) {
                    return $model->cloudProvider == 'b2' & $model->enabled == 1;
                }
            ],
            [
                [
                    'googleClientId', 'googleClientSecret', 'googleProjectName',
                    'googleAuthRedirect'
                ],
                'required',
                'when' => function ($model) {
                    return $model->cloudProvider == 'google' & $model->enabled == 1;
                }
            ],
            [
                [
                    'dropboxAppKey', 'dropboxSecretKey', 'dropboxAccessToken',
                ],
                'required',
                'when' => function ($model) {
                    return $model->cloudProvider == 'dropbox' & $model->enabled == 1;
                }
            ],
            [
                ['doAccessKey', 'doSecretKey', 'doBucketName', 'doRegionName'],
                'required',
                'when' => function ($model) {
                    return $model->cloudProvider == 'do' & $model->enabled == 1;
                }
            ],
            [
                [
                    'cloudProvider',
                    's3AccessKey', 's3SecretKey', 's3BucketName', 's3RegionName', 's3BucketPath',
                    'b2MasterKeyID', 'b2MasterAppKey', 'b2BucketName', 'b2BucketPath',
                    'googleClientId', 'googleClientSecret', 'googleProjectName', 'googleAuthRedirect', 'googleDriveFolderId',
                    'dropboxAppKey', 'dropboxSecretKey', 'dropboxAccessToken', 'dropboxFolder',
                    'doAccessKey', 'doSecretKey', 'doBucketName', 'doRegionName', 'doBucketPath',
                ],
                'string'
            ],
            [
                ['useQueue', 'keepEmergencyBackup', 'hideDatabases', 'hideVolumes', 'prune'],
                'boolean'
            ],
            [
                'pruneLimit', 'integer', 'min' => 1
            ],
            [
                'pruneLimit', 'required', 'when' => function ($model) {
                    return $model->prune;
                }
            ],
            // This seems like a poor API design in Yii 2. We want to show a 
            // validation when a user hides both the database and volumes. You
            //  can't create custom validators that run on two separate fields
            // (as it would run twice)
            //
            // https://www.yiiframework.com/doc/guide/2.0/en/input-validation#multiple-attributes-validation
            ['hideDatabases', 'validateHideRules'],
        ];
    }

    public function validateHideRules($attribute, $params)
    {
        if ($this->hideDatabases && $this->hideVolumes) {
            $this->addError('hideDatabases', 'You cannot hide both databases and volumes');
            $this->addError('hideVolumes', 'You cannot hide both databases and volumes');
        }
    }
}
