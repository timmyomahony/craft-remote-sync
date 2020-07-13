<?php

namespace weareferal\RemoteSync\models;

use weareferal\remotecore\models\Settings as BaseSettings;


class Settings extends BaseSettings
{
    public $keepEmergencyBackup = true;
    public $prune = false;
    public $pruneLimit = 10;

    public function rules(): array
    {
        $rules = parent::rules();
        return $rules + [
            [
                ['keepEmergencyBackup', 'prune'],
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
        ];
    }
}
