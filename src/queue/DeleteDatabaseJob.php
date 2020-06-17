<?php

namespace weareferal\remotesync\queue;

use craft\queue\BaseJob;

use weareferal\remotesync\RemoteSync;

class DeleteDatabaseJob extends BaseJob
{
    public $filename;

    public function execute($queue)
    {
        RemoteSync::getInstance()->remotesync->deleteDatabase($this->filename);
    }

    protected function defaultDescription()
    {
        return 'Delete remote database';
    }
}
