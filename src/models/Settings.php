<?php

namespace weareferal\remotesync\models;

use weareferal\remotecore\models\Settings as BaseSettings;


class Settings extends BaseSettings
{
    public $disableRestore = false;
    public $keepEmergencyBackup = true;
    public $prune = false;
    public $pruneLimit = 10;

    public function rules(): array
    {
        $rules = parent::rules();
        return $rules + [
            [
                ['disableRestore', 'keepEmergencyBackup', 'prune'],
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
