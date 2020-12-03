<?php

namespace weareferal\remotesync\queue;

use Craft;
use craft\queue\BaseJob;
use yii\queue\RetryableJobInterface;

use weareferal\remotesync\RemoteSync;


class PullVolumeJob extends BaseJob implements RetryableJobInterface
{
    public $filename;

    public function getTtr()
    {
        return RemoteSync::getInstance()->getSettings()->queueTtr;
    }

    public function execute($queue)
    {
        RemoteSync::getInstance()->provider->pullVolume($this->filename);
    }

    protected function defaultDescription()
    {
        return Craft::t('remote-sync', 'Pull and restore remote volumes');
    }
    
    public function canRetry($attempt, $error)
    {
        // If true, errors aren't reported in the Craft Utilities queue manager
        return true;
    }
}
