<?php

namespace weareferal\remotesync\queue;

use Craft;
use craft\queue\BaseJob;
use yii\queue\RetryableJobInterface;

use weareferal\remotesync\RemoteSync;


class PushVolumeJob extends BaseJob implements RetryableJobInterface
{
    public function getTtr()
    {
        return RemoteSync::getInstance()->getSettings()->queueTtr;
    }

    public function execute($queue)
    {
        RemoteSync::getInstance()->provider->pushVolumes();
    }

    protected function defaultDescription()
    {
        return Craft::t('remote-sync', 'Push volumes');
    }
    
    public function canRetry($attempt, $error)
    {
        // If true, errors aren't reported in the Craft Utilities queue manager
        return true;
    }
}
