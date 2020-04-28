<?php

namespace weareferal\RemoteSync\models;

use craft\base\Model;

class Settings extends Model
{
    public $enabled = true;

    public $cloudProvider = 's3';
    public $s3AccessKey;
    public $s3SecretKey;
    public $s3RegionName;
    public $s3BucketName;
    public $s3BucketPrefix;

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
                ['cloudProvider', 's3AccessKey', 's3SecretKey', 's3BucketName', 's3RegionName', 's3BucketPrefix'],
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

    public function configured(): bool
    {
        $vars = [
            $this->s3AccessKey,
            $this->s3SecretKey,
            $this->s3RegionName,
            $this->s3BucketName
        ];
        foreach ($vars as $var) {
            if (!$var || $var == '') {
                return false;
            }
        }
        return true;
    }
}
