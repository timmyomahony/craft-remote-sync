<?php

namespace weareferal\remotesync\utilities;

use Craft;
use craft\base\Utility;

use weareferal\remotesync\assets\RemoteSyncUtility\RemoteSyncUtilityAsset;
use weareferal\remotecore\assets\RemoteCoreUtility\RemoteCoreUtilityAsset;
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

    public static function icon(): string|null
    {
        return RemoteSync::getInstance()->getBasePath() . DIRECTORY_SEPARATOR . 'utility-icon.svg';
    }

    public static function contentHtml(): string
    {
        $view = Craft::$app->getView();
        $view->registerAssetBundle(RemoteCoreUtilityAsset::class);
        $view->registerAssetBundle(RemoteSyncUtilityAsset::class);
        $view->registerJs("new Craft.RemoteSyncUtility('rb-utilities-database')");
        $view->registerJs("new Craft.RemoteSyncUtility('rb-utilities-volumes')");

        $plugin = RemoteSync::getInstance();
        $settings = $plugin->getSettings();
        $provider = $plugin->provider;
        $haveVolumes = count(Craft::$app->getVolumes()->getAllVolumes()) > 0;
        $queueActive = Craft::$app->queue->getHasWaitingJobs();

        return $view->renderTemplate('remote-sync/utilities/remote-sync', [
            "cloudProvider" => $settings->cloudProvider,
            "isConfigured" => $provider->isConfigured(),
            "isAuthenticated" => $provider->isAuthenticated(),
            "hideDatabases" => $settings->hideDatabases,
            "hideVolumes" => $settings->hideVolumes,
            "haveVolumes" => !$settings->hideVolumes && $haveVolumes,
            "queueActive" => $queueActive
        ]);
    }
}
