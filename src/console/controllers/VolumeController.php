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
    private function requirePluginEnabled()
    {
        if (!RemoteSync::getInstance()->getSettings()->enabled) {
            throw new \Exception('Remote Sync Plugin not enabled');
        }
    }

    private function requirePluginConfigured()
    {
        if (!RemoteSync::getInstance()->provider->isConfigured()) {
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
            $startTime = microtime(true);
            $remoteFiles = RemoteSync::getInstance()->provider->listVolumes();
            if (count($remoteFiles) <= 0) {
                $this->stdout("No remote volumes" . PHP_EOL, Console::FG_YELLOW);
            } else {
                $this->stdout("Remote volumes:" . PHP_EOL, Console::FG_GREEN);
                foreach ($remoteFiles as $remoteFile) {
                    $this->stdout("- " . $remoteFile->filename . PHP_EOL);
                }
            }
            $this->printTime($startTime);
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
            $startTime = microtime(true);
            $filename = RemoteSync::getInstance()->provider->pushVolumes();
            if (!$filename) {
                $this->stdout("No remote volumes" . PHP_EOL, Console::FG_YELLOW);
            } else {
                $this->stdout("Pushed local volume to remote destination:" . PHP_EOL, Console::FG_GREEN);
                $this->stdout("- " . $filename . PHP_EOL);
            }
            $this->printTime($startTime);
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
            $plugin = RemoteSync::getInstance();
            if (!$plugin->getSettings()->prune) {
                $this->stderr("Pruning disabled. Please enable via the Remote Sync control panel settings" . PHP_EOL, Console::FG_YELLOW);
                return ExitCode::CONFIG;
            } else {
                $startTime = microtime(true);
                $filenames = $plugin->prune->pruneVolumes();
                if (count($filenames) <= 0) {
                    $this->stdout("No volume files deleted" . PHP_EOL, Console::FG_YELLOW);
                } else {
                    $this->stdout("Deleted volume files:" . PHP_EOL, Console::FG_GREEN);
                    foreach ($filenames as $filename) {
                        $this->stdout("- " . $filename . PHP_EOL);
                    }
                }
                $this->printTime($startTime);
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
            $startTime = microtime(true);
            if (RemoteSync::getInstance()->getSettings()->disableRestore) {
                throw new \Exception(Craft::t('remote-sync', 'Restore not enabled for this environment'));
            }
            RemoteSync::getInstance()->provider->pullVolume($filename);
            $this->stdout("Pulled and restored remote volume:" . PHP_EOL, Console::FG_GREEN);
            $this->stdout("- " . $filename . PHP_EOL);
            $this->printTime($startTime);
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
            $startTime = microtime(true);
            RemoteSync::getInstance()->provider->deleteVolume($filename);
            $this->stdout("Deleted remote volume:" . PHP_EOL, Console::FG_GREEN);
            $this->stdout("- " . $filename . PHP_EOL);
            $this->printTime($startTime);
        } catch (\Exception $e) {
            Craft::$app->getErrorHandler()->logException($e);
            $this->stderr('Error: ' . $e->getMessage() . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
        return ExitCode::OK;
    }

    protected function printTime($startTime) {
        $this->stdout("Started at " . (string) date("Y-m-d H:i:s") .  ". Duration " . (string) number_format(microtime(true) - $startTime, 2)  . " seconds" . PHP_EOL);
    }
}
