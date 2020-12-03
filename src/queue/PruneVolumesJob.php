<?php

namespace weareferal\remotesync\queue;

use Craft;
use craft\queue\BaseJob;
use yii\queue\RetryableJobInterface;

use weareferal\remotesync\RemoteSync;


class PruneVolumesJob extends BaseJob implements RetryableJobInterface
{
    public function getTtr()
    {
        return RemoteSync::getInstance()->getSettings()->queueTtr;
    }

    public function execute($queue)
    {
        RemoteSync::getInstance()->prune->pruneVolumes();
    }

    protected function defaultDescription()
    {
        return Craft::t('remote-sync', 'Prune volumes');
    }
    
    public function canRetry($attempt, $error)
    {
        // If true, errors aren't reported in the Craft Utilities queue manager
        return true;
    }
}
