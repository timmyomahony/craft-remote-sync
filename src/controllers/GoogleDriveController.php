<?php

namespace weareferal\remotesync\controllers;

use weareferal\remotecore\controllers\BaseGoogleDriveController;
use weareferal\remotesync\RemoteSync;

/**
 * Google Drive controller
 * 
 */
class GoogleDriveController extends BaseGoogleDriveController
{
    protected function pluginInstance() {
        return RemoteSync::getInstance();
    }
}
