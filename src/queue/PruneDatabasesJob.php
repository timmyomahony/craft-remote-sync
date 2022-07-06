<?php

namespace weareferal\remotesync\queue;

use Craft;
use craft\queue\BaseJob;
use yii\queue\RetryableJobInterface;

use weareferal\remotesync\RemoteSync;


class PruneDatabasesJob extends BaseJob implements RetryableJobInterface
{
    public function getTtr()
    {
        return RemoteSync::getInstance()->getSettings()->queueTtr;
    }

    public function execute($queue): void
    {
        RemoteSync::getInstance()->prune->pruneDatabases();
    }

    protected function defaultDescription(): string|null
    {
        return Craft::t('remote-sync', 'Prune databases');
    }
    
    public function canRetry($attempt, $error)
    {
        // If true, errors aren't reported in the Craft Utilities queue manager
        return true;
    }
}
