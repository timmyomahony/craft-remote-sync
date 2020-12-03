<?php

namespace weareferal\remotesync\queue;

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
        return 'Push volumes';
    }
    
    public function canRetry($attempt, $error)
    {
        return true;
    }
}
