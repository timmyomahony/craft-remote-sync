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
use craft\events\RegisterUserPermissionsEvent;
use craft\services\UserPermissions;

use yii\base\Event;

use weareferal\remotesync\utilities\RemoteSyncUtility;
use weareferal\remotesync\models\Settings;
use weareferal\remotesync\assets\remotesyncsettings\RemoteSyncSettingAsset;
use weareferal\remotesync\services\PruneService;

use weareferal\remotecore\RemoteCoreTrait;


class RemoteSync extends Plugin
{

    use RemoteCoreTrait;

    public $hasCpSettings = true;

    public static $plugin;

    public $schemaVersion = '1.0.0';

    public function init()
    {
        parent::init();
        self::$plugin = $this;

        $this->registerServices();
        $this->registerConsoleControllers();
        $this->registerPermissions();
        $this->registerUtilties();

        $this->registerCore();
    }

    public function registerPermissions()
    {
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
    }

    public function registerURLs()
    {
        parent::registerURLs();
    }

    public function registerConsoleControllers()
    {
        if (Craft::$app instanceof ConsoleApplication) {
            $this->controllerNamespace = 'weareferal\remotesync\console\controllers';
        }
    }

    public function registerServices()
    {
        parent::registerServices();
        $this->setComponents([
            'pruneservice' => PruneService::class
        ]);
    }

    public function registerUtilties()
    {
        if ($this->getSettings()->enabled) {
            Event::on(
                Utilities::class,
                Utilities::EVENT_REGISTER_UTILITY_TYPES,
                function (RegisterComponentTypesEvent $event) {
                    $event->types[] = RemoteSyncUtility::class;
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
