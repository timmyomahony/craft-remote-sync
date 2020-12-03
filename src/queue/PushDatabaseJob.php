<?php

namespace weareferal\remotesync\queue;

use craft\queue\BaseJob;
use yii\queue\RetryableJobInterface;

use weareferal\remotesync\RemoteSync;


class PushDatabaseJob extends BaseJob implements RetryableJobInterface
{
    public function getTtr()
    {
        return RemoteSync::getInstance()->getSettings()->queueTtr;
    }

    public function execute($queue)
    {
        RemoteSync::getInstance()->provider->pushDatabase();
    }

    protected function defaultDescription()
    {
        return 'Push database';
    }
    
    public function canRetry($attempt, $error)
    {
        return true;
    }
}
