<?php

namespace weareferal\remotesync\utilities;

use Craft;
use craft\base\Utility;

use weareferal\remotesync\assets\remotesyncutility\RemoteSyncUtilityAsset;
use weareferal\remotesync\RemoteSync;

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
        $service = RemoteSync::getInstance()->provider;
        $haveVolumes = count(Craft::$app->getVolumes()->getAllVolumes()) > 0;
        $queueActive = Craft::$app->queue->getHasWaitingJobs();

        return $view->renderTemplate('remote-sync/utilities/remote-sync', [
            "isConfigured" => $service->isConfigured(),
            "isAuthenticated" => $service->isAuthenticated(),
            "hideDatabases" => $settings->hideDatabases,
            "hideVolumes" => $settings->hideVolumes,
            "haveVolumes" => !$settings->hideVolumes && $haveVolumes,
            'queueActive' => $queueActive
        ]);
    }
}
