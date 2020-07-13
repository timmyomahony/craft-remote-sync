<?php

namespace weareferal\RemoteSync\queue;

use craft\queue\BaseJob;

use weareferal\RemoteSync\RemoteSync;

class DeleteDatabaseJob extends BaseJob
{
    public $filename;

    public function execute($queue)
    {
        RemoteSync::getInstance()->provider->deleteDatabase($this->filename);
    }

    protected function defaultDescription()
    {
        return 'Delete remote database';
    }
}
