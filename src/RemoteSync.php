<?php

/**
 * Craft Remote Sync plugin for Craft CMS 3.x
 *
 * @link      https://weareferal.com
 * @copyright Copyright (c) 2020 Timmy O'Mahony
 */

namespace weareferal\remotesync;

use Craft;
use craft\base\Plugin;
use craft\services\Utilities;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\services\UserPermissions;
use craft\web\UrlManager;

use yii\base\Event;

use weareferal\remotesync\utilities\RemoteSyncUtility;
use weareferal\remotesync\models\Settings;
use weareferal\remotesync\assets\remotesyncsettings\RemoteSyncSettingAsset;
use weareferal\remotesync\services\PruneService;

use weareferal\remotecore\services\ProviderService;


class RemoteSync extends Plugin
{
    public $hasCpSettings = true;

    public static $plugin;

    public $schemaVersion = '1.0.0';

    public function init()
    {
        parent::init();

        self::$plugin = $this;

        // via craft-remote-core
        $this->setComponents([
            'pruneservice' => PruneService::class,
            'provider' => ProviderService::create($this->getSettings(), 'remote-sync')
        ]);

        // Register console commands
        if (Craft::$app instanceof ConsoleApplication) {
            $this->controllerNamespace = 'weareferal\remotesync\console\controllers';
        }

        // Register permissions
        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function (RegisterUserPermissionsEvent $event) {
                $event->permissions['Remote Sync'] = [
                    'remotesync' => [
                        'label' => 'Push and pull/restore database and volume assets',
                    ],
                ];
            }
        );

        // Register with Utilities service
        if ($this->getSettings()->enabled) {
            Event::on(
                Utilities::class,
                Utilities::EVENT_REGISTER_UTILITY_TYPES,
                function (RegisterComponentTypesEvent $event) {
                    $event->types[] = RemoteSyncUtility::class;
                }
            );
        }

        // Extra urls
        if ($this->getSettings()->cloudProvider == "google") {
            Event::on(
                UrlManager::class,
                UrlManager::EVENT_REGISTER_CP_URL_RULES,
                function (RegisterUrlRulesEvent $event) {
                    $event->rules['remote-sync/google-drive/auth'] = 'remote-sync/google-drive/auth';
                    $event->rules['remote-sync/google-drive/auth-redirect'] = 'remote-sync/google-drive/auth-redirect';
                }
            );
        }
    }

    protected function createSettingsModel(): Settings
    {
        return new Settings();
    }

    protected function settingsHtml(): string
    {
        $view = Craft::$app->getView();
        $view->registerAssetBundle(RemoteSyncSettingAsset::class);
        $view->registerJs("new Craft.RemoteSyncSettings('main-form');");

        $isAuthenticated = $this->provider->isAuthenticated();
        $isConfigured = $this->provider->isConfigured();

        return $view->renderTemplate(
            'remote-sync/settings',
            [
                'settings' => $this->getSettings(),
                'isConfigured' => $isConfigured,
                'isAuthenticated' => $isAuthenticated
            ]
        );
    }
}
