<?php

namespace weareferal\RemoteSync\queue;

use craft\queue\BaseJob;

use weareferal\RemoteSync\RemoteSync;

class PushVolumeJob extends BaseJob
{
    public function execute($queue)
    {
        RemoteSync::getInstance()->remotesync->pushVolumes();
    }

    protected function defaultDescription()
    {
        return 'Push volumes';
    }
}
