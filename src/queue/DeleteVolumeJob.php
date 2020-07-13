<?php

namespace weareferal\RemoteSync\queue;

use craft\queue\BaseJob;

use weareferal\RemoteSync\RemoteSync;

class DeleteVolumeJob extends BaseJob
{
    public $filename;

    public function execute($queue)
    {
        RemoteSync::getInstance()->provider->deleteVolume($this->filename);
    }

    protected function defaultDescription()
    {
        return 'Delete remote volumes';
    }
}
