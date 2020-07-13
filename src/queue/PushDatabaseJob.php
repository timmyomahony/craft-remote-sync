<?php

namespace weareferal\RemoteSync\queue;

use craft\queue\BaseJob;

use weareferal\RemoteSync\RemoteSync;

class PushDatabaseJob extends BaseJob
{
    public function execute($queue)
    {
        RemoteSync::getInstance()->provider->pushDatabase();
    }

    protected function defaultDescription()
    {
        return 'Push database';
    }
}
