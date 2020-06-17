<?php

namespace weareferal\remotesync\console\controllers;

use Craft;
use yii\console\Controller;
use yii\helpers\Console;
use yii\console\ExitCode;

use weareferal\remotesync\RemoteSync;

/**
 * Manage remote volumes
 */
class VolumeController extends Controller
{
    public function requirePluginEnabled()
    {
        if (!RemoteSync::getInstance()->getSettings()->enabled) {
            throw new \Exception('Remote Sync Plugin not enabled');
        }
    }

    public function requirePluginConfigured()
    {
        if (!RemoteSync::getInstance()->remotesync->isConfigured()) {
            throw new \Exception('Remote Sync Plugin not correctly configured');
        }
    }

    /**
     * List remote volumes
     */
    public function actionList()
    {
        try {
            $this->requirePluginEnabled();
            $this->requirePluginConfigured();

            $results = RemoteSync::getInstance()->remotesync->listVolumes();
            if (count($results) <= 0) {
                $this->stdout("No remote volumes" . PHP_EOL, Console::FG_YELLOW);
            } else {
                $this->stdout("Remote volumes:" . PHP_EOL, Console::FG_GREEN);
                foreach ($results as $result) {
                    $this->stdout(" " . $result['value'] . PHP_EOL);
                }
            }
        } catch (\Exception $e) {
            Craft::$app->getErrorHandler()->logException($e);
            $this->stderr('Error: ' . $e->getMessage() . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
        return ExitCode::OK;
    }

    /**
     * Push local volume to remote destination
     */
    public function actionPush()
    {
        try {
            $this->requirePluginEnabled();
            $this->requirePluginConfigured();

            $filename = RemoteSync::getInstance()->remotesync->pushVolumes();
            if (!$filename) {
                $this->stdout("No remote volumes" . PHP_EOL, Console::FG_YELLOW);
            } else {
                $this->stdout("Pushed local volume to remote destination:" . PHP_EOL, Console::FG_GREEN);
                $this->stdout(" " . $filename . PHP_EOL);
            }
        } catch (\Exception $e) {
            Craft::$app->getErrorHandler()->logException($e);
            $this->stderr('Error: ' . $e->getMessage() . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
        return ExitCode::OK;
    }

    /**
     * Prune remote volume files
     */
    public function actionPrune()
    {
        try {
            $this->requirePluginEnabled();
            $this->requirePluginConfigured();

            if (!RemoteSync::getInstance()->getSettings()->prune) {
                $this->stderr("Pruning disabled. Please enable via the Remote Sync control panel settings" . PHP_EOL, Console::FG_YELLOW);
                return ExitCode::CONFIG;
            } else {
                $filenames = RemoteSync::getInstance()->remotesync->pruneVolumes();
                if (count($filenames) <= 0) {
                    $this->stdout("No volume files deleted" . PHP_EOL, Console::FG_YELLOW);
                } else {
                    $this->stdout("Deleted volume files:" . PHP_EOL, Console::FG_GREEN);
                    foreach ($filenames as $filename) {
                        $this->stdout(" " . $filename . PHP_EOL);
                    }
                }
                return ExitCode::OK;
            }
        } catch (\Exception $e) {
            Craft::$app->getErrorHandler()->logException($e);
            $this->stderr('Error: ' . $e->getMessage() . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
        return ExitCode::OK;
    }

    /**
     * Pull remote volume and restore it locally
     */
    public function actionPull($filename)
    {
        try {
            $this->requirePluginEnabled();
            $this->requirePluginConfigured();

            RemoteSync::getInstance()->remotesync->pullVolume($filename);
            $this->stdout("Pulled and restored remote volume:" . PHP_EOL, Console::FG_GREEN);
            $this->stdout(" " . $filename . PHP_EOL);
        } catch (\Exception $e) {
            Craft::$app->getErrorHandler()->logException($e);
            $this->stderr('Error: ' . $e->getMessage() . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
        return ExitCode::OK;
    }

    /**
     * Delete a remote volume
     */
    public function actionDelete($filename)
    {
        try {
            $this->requirePluginEnabled();
            $this->requirePluginConfigured();

            RemoteSync::getInstance()->remotesync->deleteVolume($filename);
            $this->stdout("Deleted remote volume:" . PHP_EOL, Console::FG_GREEN);
            $this->stdout(" " . $filename . PHP_EOL);
        } catch (\Exception $e) {
            Craft::$app->getErrorHandler()->logException($e);
            $this->stderr('Error: ' . $e->getMessage() . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
        return ExitCode::OK;
    }
}
