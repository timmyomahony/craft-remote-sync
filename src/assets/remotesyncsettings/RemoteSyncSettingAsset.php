<?php

namespace weareferal\remotesync\assets\remotesyncsettings;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;


class RemoteSyncSettingAsset extends AssetBundle
{
    public function init()
    {
        $this->sourcePath = __DIR__ . '/dist';

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/RemoteSyncSetting.js'
        ];

        parent::init();
    }
}
