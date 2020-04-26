<?php

namespace weareferal\RemoteSync\queue;

use craft\queue\BaseJob;

use weareferal\RemoteSync\RemoteSync;

class PruneVolumesJob extends BaseJob
{
    public function execute($queue)
    {
        RemoteSync::getInstance()->remotesync->pruneVolumes();
    }

    protected function defaultDescription()
    {
        return 'Prune volumes';
    }
}
