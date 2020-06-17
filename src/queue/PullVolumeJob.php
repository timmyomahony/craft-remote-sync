<?php

namespace weareferal\remotesync\queue;

use craft\queue\BaseJob;

use weareferal\remotesync\RemoteSync;

class PullVolumeJob extends BaseJob
{
    public $filename;

    public function execute($queue)
    {
        RemoteSync::getInstance()->remotesync->pullVolume($this->filename);
    }

    protected function defaultDescription()
    {
        return 'Pull and restore remote volumes';
    }
}
