<?php

namespace weareferal\remotesync\assets\RemoteSyncUtility;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;


class RemoteSyncUtilityAsset extends AssetBundle
{
    public function init()
    {
        $this->sourcePath = __DIR__ . '/dist';

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/RemoteSyncUtility.js'
        ];

        $this->css = [
            'css/RemoteSyncUtility.css',
        ];

        parent::init();
    }
}
