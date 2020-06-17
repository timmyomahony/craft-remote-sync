<?php

namespace weareferal\RemoteSync\utilities;

use Craft;
use craft\base\Utility;

use weareferal\RemoteSync\assets\RemoteSyncutility\RemoteSyncUtilityAsset;
use weareferal\RemoteSync\RemoteSync;

class RemoteSyncUtility extends Utility
{
    public static function displayName(): string
    {
        return Craft::t('app', 'Remote Sync');
    }

    public static function id(): string
    {
        return 'remote-sync';
    }

    public static function iconPath()
    {
        return RemoteSync::getInstance()->getBasePath() . DIRECTORY_SEPARATOR . 'utility-icon.svg';
    }

    public static function contentHtml(): string
    {
        $view = Craft::$app->getView();
        $view->registerAssetBundle(RemoteSyncUtilityAsset::class);
        $view->registerJs("new Craft.RemoteSyncUtility('rb-utilities-database')");
        $view->registerJs("new Craft.RemoteSyncUtility('rb-utilities-volumes')");

        $settings = RemoteSync::getInstance()->getSettings();
        $volumesConfigured = count(Craft::$app->getVolumes()->getAllVolumes()) > 0;
        $queueActive = Craft::$app->queue->getHasWaitingJobs();

        return $view->renderTemplate('remote-sync/utilities/remote-sync', [
            "settings" => $settings,
            "volumesConfigured" => $volumesConfigured,
            'queueActive' => $queueActive
        ]);
    }
}
