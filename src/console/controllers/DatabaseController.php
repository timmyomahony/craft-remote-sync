<?php

namespace weareferal\RemoteSync\console\controllers;

use Craft;
use yii\console\Controller;
use yii\helpers\Console;
use yii\console\ExitCode;

use weareferal\RemoteSync\RemoteSync;

/**
 * Manage remote databases
 */
class DatabaseController extends Controller
{
    public function requirePluginEnabled()
    {
        if (!RemoteSync::getInstance()->getSettings()->enabled) {
            throw new \Exception('Remote Sync Plugin not enabled');
        }
    }

    public function requirePluginConfigured()
    {
        if (!RemoteSync::getInstance()->getSettings()->configured()) {
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

            $results = RemoteSync::getInstance()->remotesync->listDatabases();
            if (count($results) <= 0) {
                $this->stdout("No remote databases" . PHP_EOL, Console::FG_YELLOW);
            } else {
                $this->stdout("Remote databases:" . PHP_EOL, Console::FG_GREEN);
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
     * Push local database to remote destination
     */
    public function actionPush()
    {
        try {
            $this->requirePluginEnabled();
            $this->requirePluginConfigured();

            $filename = RemoteSync::getInstance()->remotesync->pushDatabase();
            $this->stdout("Pushed local database to remote destination:" . PHP_EOL, Console::FG_GREEN);
            $this->stdout(" " . $filename . PHP_EOL);
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

            if (!RemoteSync::getInstance()->getSettings()->prune) {
                $this->stderr("Pruning disabled. Please enable via the Remote Sync control panel settings" . PHP_EOL, Console::FG_YELLOW);
                return ExitCode::CONFIG;
            } else {
                $filenames = RemoteSync::getInstance()->remotesync->pruneDatabases();
                if (count($filenames) <= 0) {
                    $this->stdout("No database files deleted" . PHP_EOL, Console::FG_YELLOW);
                } else {
                    $this->stdout("Deleted database files:" . PHP_EOL, Console::FG_GREEN);
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
     * Pull remote database and restore it locally
     */
    public function actionPull($filename)
    {
        try {
            $this->requirePluginEnabled();
            $this->requirePluginConfigured();

            RemoteSync::getInstance()->remotesync->pullDatabase($filename);
            $this->stdout("Pulled and restored remote database:" . PHP_EOL, Console::FG_GREEN);
            $this->stdout(" " . $filename . PHP_EOL);
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

            RemoteSync::getInstance()->remotesync->deleteDatabase($filename);
            $this->stdout("Deleted remote database:" . PHP_EOL, Console::FG_GREEN);
            $this->stdout(" " . $filename . PHP_EOL);
        } catch (\Exception $e) {
            Craft::$app->getErrorHandler()->logException($e);
            $this->stderr('Error: ' . $e->getMessage() . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
        return ExitCode::OK;
    }
}
