<?php

namespace weareferal\remotesync\console\controllers;

use Craft;
use yii\console\Controller;
use yii\helpers\Console;
use yii\console\ExitCode;

use weareferal\remotesync\RemoteSync;

/**
 * Manage remote databases
 */
class DatabaseController extends Controller
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
     * List remote databases
     */
    public function actionList()
    {
        try {
            $this->requirePluginEnabled();
            $this->requirePluginConfigured();
            $startTime = microtime(true);
            $remoteFiles = RemoteSync::getInstance()->provider->listDatabases();
            if (count($remoteFiles) <= 0) {
                $this->stdout("No remote databases" . PHP_EOL, Console::FG_YELLOW);
            } else {
                $this->stdout("Remote databases:" . PHP_EOL, Console::FG_GREEN);
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
     * Push local database to remote destination
     */
    public function actionPush()
    {
        try {
            $this->requirePluginEnabled();
            $this->requirePluginConfigured();
            $startTime = microtime(true);
            $filename = RemoteSync::getInstance()->provider->pushDatabase();
            $this->stdout("Pushed local database to remote destination:" . PHP_EOL, Console::FG_GREEN);
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
     * Prune remote database files
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
                $filenames = $plugin->prune->pruneDatabases();
                if (count($filenames) <= 0) {
                    $this->stdout("No database files deleted" . PHP_EOL, Console::FG_YELLOW);
                } else {
                    $this->stdout("Deleted database files:" . PHP_EOL, Console::FG_GREEN);
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
     * Pull remote database and restore it locally
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
            RemoteSync::getInstance()->provider->pullDatabase($filename);
            $this->stdout("Pulled and restored remote database:" . PHP_EOL, Console::FG_GREEN);
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
     * Delete a remote database
     */
    public function actionDelete($filename)
    {
        try {
            $this->requirePluginEnabled();
            $this->requirePluginConfigured();
            $startTime = microtime(true);
            RemoteSync::getInstance()->provider->deleteDatabase($filename);
            $this->stdout("Deleted remote database:" . PHP_EOL, Console::FG_GREEN);
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
