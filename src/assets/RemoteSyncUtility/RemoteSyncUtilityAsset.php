<?php

namespace weareferal\remotesync\assets\RemoteSyncUtility;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;


class RemoteSyncUtilityAsset extends AssetBundle
{
    public function init()
    {
        $this->sourcePath = '@weareferal/remotesync/assets/RemoteSyncUtility/dist';

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/RemoteSyncUtility.js'
        ];

        parent::init();
    }
}
