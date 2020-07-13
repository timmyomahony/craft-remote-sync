<?php

namespace weareferal\RemoteSync\queue;

use craft\queue\BaseJob;

use weareferal\RemoteSync\RemoteSync;

class PruneDatabasesJob extends BaseJob
{
    public function execute($queue)
    {
        RemoteSync::getInstance()->prune->pruneDatabases();
    }

    protected function defaultDescription()
    {
        return 'Prune databases';
    }
}
