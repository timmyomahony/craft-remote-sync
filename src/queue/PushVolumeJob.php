<?php

namespace weareferal\remotesync\queue;

use craft\queue\BaseJob;

use weareferal\remotesync\RemoteSync;

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
