<?php

namespace weareferal\RemoteSync\queue;

use craft\queue\BaseJob;

use weareferal\RemoteSync\RemoteSync;

class PullDatabaseJob extends BaseJob
{
    public $filename;

    public function execute($queue)
    {
        RemoteSync::getInstance()->remotesync->pullDatabase($this->filename);
    }

    protected function defaultDescription()
    {
        return 'Pull and restore remote database';
    }
}
