<?php

namespace weareferal\remotesync\queue;

use Craft;
use craft\queue\BaseJob;
use yii\queue\RetryableJobInterface;

use weareferal\remotesync\RemoteSync;


class DeleteDatabaseJob extends BaseJob implements RetryableJobInterface
{
    public $filename;

    public function getTtr()
    {
        return RemoteSync::getInstance()->getSettings()->queueTtr;
    }

    public function execute($queue): void
    {
        RemoteSync::getInstance()->provider->deleteDatabase($this->filename);
    }

    protected function defaultDescription(): string|null
    {
        return Craft::t('remote-sync', 'Delete remote database');
    }

    public function canRetry($attempt, $error)
    {
        // If true, errors aren't reported in the Craft Utilities queue manager
        return true;
    }
}
