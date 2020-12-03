<?php

namespace weareferal\remotesync\queue;

use craft\queue\BaseJob;
use yii\queue\RetryableJobInterface;

use weareferal\remotesync\RemoteSync;


class DeleteVolumeJob extends BaseJob implements RetryableJobInterface
{
    public $filename;

    public function getTtr()
    {
        return RemoteSync::getInstance()->getSettings()->queueTtr;
    }

    public function execute($queue)
    {
        RemoteSync::getInstance()->provider->deleteVolume($this->filename);
    }

    protected function defaultDescription()
    {
        return 'Delete remote volumes';
    }
    
    public function canRetry($attempt, $error)
    {
        return true;
    }
}
