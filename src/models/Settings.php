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

    public function rules(): array
    {
        return [
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
                ['useQueue',],
                'boolean'
            ]
        ];
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
