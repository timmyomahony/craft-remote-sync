<?php

namespace weareferal\remotesync\queue;

use craft\queue\BaseJob;
use yii\queue\RetryableJobInterface;

use weareferal\remotesync\RemoteSync;


class PruneDatabasesJob extends BaseJob implements RetryableJobInterface
{
    public function getTtr()
    {
        return RemoteSync::getInstance()->getSettings()->queueTtr;
    }

    public function execute($queue)
    {
        RemoteSync::getInstance()->prune->pruneDatabases();
    }

    protected function defaultDescription()
    {
        return 'Prune databases';
    }
    
    public function canRetry($attempt, $error)
    {
        return true;
    }
}
