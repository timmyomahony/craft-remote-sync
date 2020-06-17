<?php

namespace weareferal\remotesync\queue;

use craft\queue\BaseJob;

use weareferal\remotesync\RemoteSync;

class PruneDatabasesJob extends BaseJob
{
    public function execute($queue)
    {
        RemoteSync::getInstance()->pruneservice->pruneDatabases();
    }

    protected function defaultDescription()
    {
        return 'Prune databases';
    }
}
