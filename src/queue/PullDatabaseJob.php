<?php

namespace weareferal\remotesync\queue;

use craft\queue\BaseJob;

use weareferal\remotesync\RemoteSync;

class PullDatabaseJob extends BaseJob
{
    public $filename;

    public function execute($queue)
    {
        RemoteSync::getInstance()->provider->pullDatabase($this->filename);
    }

    protected function defaultDescription()
    {
        return 'Pull and restore remote database';
    }
}
