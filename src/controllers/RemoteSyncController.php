<?php

namespace weareferal\remotesync\controllers;

use yii\web\BadRequestHttpException;

use Craft;
use craft\web\Controller;

use weareferal\remotesync\RemoteSync;
use weareferal\remotesync\queue\PullDatabaseJob;
use weareferal\remotesync\queue\PullVolumeJob;
use weareferal\remotesync\queue\PushDatabaseJob;
use weareferal\remotesync\queue\PushVolumeJob;
use weareferal\remotesync\queue\PruneDatabasesJob;
use weareferal\remotesync\queue\PruneVolumesJob;
use weareferal\remotesync\queue\DeleteDatabaseJob;
use weareferal\remotesync\queue\DeleteVolumeJob;

use weareferal\remotecore\helpers\RemoteFile;


class RemoteSyncController extends Controller
{
    public function requirePluginEnabled()
    {
        if (!RemoteSync::getInstance()->getSettings()->enabled) {
            throw new BadRequestHttpException('Plugin is not enabled');
        }
    }

    public function requirePluginConfigured()
    {
        if (!RemoteSync::getInstance()->provider->isConfigured()) {
            throw new BadRequestHttpException('Plugin is not correctly configured');
        }
    }

    /**
     * Test Provider
     * 
     * 
     * @since 4.1.0
     */
    public function actionTestProvider()
    {
        $this->requireCpRequest();
        $this->requirePermission('remotesync');
        $this->requirePluginEnabled();
        
        $plugin = RemoteSync::getInstance();

        try {
            // Simply attempt to list all files
            $plugin->provider->list('.sql');
            return $this->asJson([
                "success" => true
            ]);
        } catch (\Exception $e) {
            return $this->asFailure(
                Craft::t('remote-backup', 'Test failed'),
                [
                    "message" => $e->getMessage(),
                    "trace" => $e->getTraceAsString()
                ]);
        }
    }

    public function actionListDatabases()
    {
        $this->requireCpRequest();
        $this->requirePermission('remotesync');
        $this->requirePluginEnabled();
        $this->requirePluginConfigured();
        
        $plugin = RemoteSync::getInstance();
        $settings = $plugin->getSettings();

        try {
            $remoteFiles = RemoteSync::getInstance()->provider->listDatabases();
            $files = RemoteFile::serialize($remoteFiles, $settings->displayDateFormat);
            return $this->asJson([
                "files" => $files,
                "success" => true
            ]);
        } catch (\Exception $e) {
            Craft::$app->getErrorHandler()->logException($e);
            return $this->asErrorJson(Craft::t('remote-sync', 'Error getting remote database backups'));
        }
    }

    public function actionListVolumes()
    {
        $this->requireCpRequest();
        $this->requirePermission('remotesync');
        $this->requirePluginEnabled();
        $this->requirePluginConfigured();
        
        $plugin = RemoteSync::getInstance();
        $settings = $plugin->getSettings();
        
        try {
            $remoteFiles = RemoteSync::getInstance()->provider->listVolumes();
            $files = RemoteFile::serialize($remoteFiles, $settings->displayDateFormat);
            return $this->asJson([
                "files" => $files,
                "success" => true
            ]);
        } catch (\Exception $e) {
            Craft::$app->getErrorHandler()->logException($e);
            return $this->asErrorJson(Craft::t('remote-sync', 'Error listing volumes'));
        }
    }

    public function actionPushDatabase()
    {
        $this->requireCpRequest();
        $this->requirePermission('remotesync');
        $this->requirePluginEnabled();
        $this->requirePluginConfigured();

        $plugin = RemoteSync::getInstance();
        $settings = $plugin->getSettings();
        $queue = Craft::$app->queue;

        try {
            if ($settings->useQueue) {
                $queue->push(new PushDatabaseJob());
                Craft::$app->getSession()->setNotice(Craft::t('remote-sync', 'Job added to queue'));
            } else {
                $plugin->provider->pushDatabase();
                Craft::$app->getSession()->setNotice(Craft::t('remote-sync', 'Database pushed'));
            }

            if ($settings->prune) {
                if ($settings->useQueue) {
                    $queue->push(new PruneDatabasesJob());
                } else {
                    $plugin->prune->pruneDatabases();
                }
            }
        } catch (\Exception $e) {
            Craft::$app->getErrorHandler()->logException($e);
            return $this->asErrorJson(Craft::t('remote-sync', 'Error pushing database'));
        }

        return $this->asJson([
            "success" => true
        ]);
    }

    public function actionPushVolume()
    {
        $this->requirePostRequest();
        $this->requireCpRequest();
        $this->requirePermission('remotesync');
        $this->requirePluginEnabled();
        $this->requirePluginConfigured();

        $plugin = RemoteSync::getInstance();
        $settings = $plugin->getSettings();
        $queue = Craft::$app->queue;

        try {
            if ($settings->useQueue) {
                $queue->push(new PushVolumeJob());
                Craft::$app->getSession()->setNotice(Craft::t('remote-sync', 'Job added to queue'));
            } else {
                $plugin->provider->pushVolumes();
                Craft::$app->getSession()->setNotice(Craft::t('remote-sync', 'Volumes pushed'));
            }

            if ($settings->prune) {
                if ($settings->useQueue) {
                    $queue->push(new PruneVolumesJob());
                } else {
                    $plugin->prune->pruneVolumes();
                }
            }
        } catch (\Exception $e) {
            Craft::$app->getErrorHandler()->logException($e);
            return $this->asErrorJson(Craft::t('remote-sync', 'Error pushing volume'));
        }

        return $this->asJson([
            "success" => true
        ]);
    }

    public function actionPullDatabase()
    {
        $this->requireCpRequest();
        $this->requirePostRequest();
        $this->requirePermission('remotesync');
        $this->requirePluginEnabled();
        $this->requirePluginConfigured();

        $filename = Craft::$app->getRequest()->getRequiredBodyParam('filename');
        $plugin = RemoteSync::getInstance();
        $settings = $plugin->getSettings();

        if ($settings->disableRestore) {
            return $this->asErrorJson(Craft::t('remote-sync', 'Restore not enabled for this environment'));
        }

        try {
            if ($settings->useQueue) {
                Craft::$app->queue->push(new PullDatabaseJob([
                    'filename' => $filename
                ]));
                Craft::$app->getSession()->setNotice(Craft::t('remote-sync', 'Job added to queue'));
            } else {
                $plugin->provider->pullDatabase($filename);
                Craft::$app->getSession()->setNotice(Craft::t('remote-sync', 'Database pulled'));
            }
        } catch (\Exception $e) {
            Craft::$app->getErrorHandler()->logException($e);
            return $this->asErrorJson(Craft::t('remote-sync', 'Error pulling database'));
        }

        return $this->asJson([
            "success" => true
        ]);
    }

    public function actionPullVolume()
    {
        $this->requireCpRequest();
        $this->requirePostRequest();
        $this->requirePermission('remotesync');
        $this->requirePluginEnabled();
        $this->requirePluginConfigured();

        $filename = Craft::$app->getRequest()->getRequiredBodyParam('filename');
        $plugin = RemoteSync::getInstance();
        $settings = $plugin->getSettings();

        if ($settings->disableRestore) {
            return $this->asErrorJson(Craft::t('remote-sync', 'Restore not enabled for this environment'));
        }

        try {
            if ($settings->useQueue) {
                Craft::$app->queue->push(new PullVolumeJob([
                    'filename' => $filename
                ]));
                Craft::$app->getSession()->setNotice(Craft::t('remote-sync', 'Job added to queue'));
            } else {
                $plugin->provider->pullVolume($filename);
                Craft::$app->getSession()->setNotice(Craft::t('remote-sync', 'Volumes pulled'));
            }
        } catch (\Exception $e) {
            Craft::$app->getErrorHandler()->logException($e);
            return $this->asErrorJson(Craft::t('remote-sync', 'Error pulling volume'));
        }

        return $this->asJson([
            "success" => true
        ]);
    }


    public function actionDeleteDatabase()
    {
        $this->requireCpRequest();
        $this->requirePostRequest();
        $this->requirePermission('remotesync');
        $this->requirePluginEnabled();
        $this->requirePluginConfigured();

        $filename = Craft::$app->getRequest()->getRequiredBodyParam('filename');

        try {
            $useQueue = RemoteSync::getInstance()->getSettings()->useQueue;

            if ($useQueue) {
                Craft::$app->queue->push(new DeleteDatabaseJob([
                    'filename' => $filename
                ]));
                Craft::$app->getSession()->setNotice(Craft::t('remote-sync', 'Job added to queue'));
            } else {
                RemoteSync::getInstance()->provider->deleteDatabase($filename);
                Craft::$app->getSession()->setNotice(Craft::t('remote-sync', 'Database deleted'));
            }
        } catch (\Exception $e) {
            Craft::$app->getErrorHandler()->logException($e);
            return $this->asErrorJson(Craft::t('remote-sync', 'Error deleting database'));
        }

        return $this->asJson([
            "success" => true
        ]);
    }

    public function actionDeleteVolume()
    {
        $this->requireCpRequest();
        $this->requirePostRequest();
        $this->requirePermission('remotesync');
        $this->requirePluginEnabled();
        $this->requirePluginConfigured();

        $filename = Craft::$app->getRequest()->getRequiredBodyParam('filename');

        try {
            $useQueue = RemoteSync::getInstance()->getSettings()->useQueue;

            if ($useQueue) {
                Craft::$app->queue->push(new DeleteVolumeJob([
                    'filename' => $filename
                ]));
                Craft::$app->getSession()->setNotice(Craft::t('remote-sync', 'Job added to queue'));
            } else {
                RemoteSync::getInstance()->provider->deleteVolume($filename);
                Craft::$app->getSession()->setNotice(Craft::t('remote-sync', 'Volumes deleted'));
            }
        } catch (\Exception $e) {
            Craft::$app->getErrorHandler()->logException($e);
            return $this->asErrorJson(Craft::t('remote-sync', 'Error deleting volume'));
        }

        return $this->asJson([
            "success" => true
        ]);
    }
}
