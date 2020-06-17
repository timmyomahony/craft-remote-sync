<?php

namespace weareferal\remotesync\queue;

use craft\queue\BaseJob;

use weareferal\remotesync\RemoteSync;

class PruneVolumesJob extends BaseJob
{
    public function execute($queue)
    {
        RemoteSync::getInstance()->pruneservice->pruneVolumes();
    }

    protected function defaultDescription()
    {
        return 'Prune volumes';
    }
}
