<?php

/**
 * Craft Remote Sync plugin for Craft CMS 3.x
 *
 * @link      https://weareferal.com
 * @copyright Copyright (c) 2020 Timmy O'Mahony
 */

namespace weareferal\remotesync;

use Craft;
use craft\console\Application as ConsoleApplication;
use craft\base\Plugin;
use craft\web\UrlManager;
use craft\services\Utilities;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\services\UserPermissions;

use yii\base\Event;

use weareferal\remotesync\utilities\RemoteSyncUtility;
use weareferal\remotesync\models\Settings;
use weareferal\remotesync\services\PruneService;

use weareferal\remotecore\RemoteCoreHelper;
use weareferal\remotecore\assets\remotecoresettings\RemoteCoreSettingsAsset;


class RemoteSync extends Plugin
{

    public bool $hasCpSettings = true;

    public static $plugin;

    public string $schemaVersion = '1.0.0';

    public function init()
    {
        parent::init();
        self::$plugin = $this;

        RemoteCoreHelper::registerModule();

        $this->registerServices();
        $this->registerURLs();
        $this->registerConsoleControllers();
        $this->registerPermissions();
        $this->registerUtilties();
    }

    /**
     * Register Permissions
     * 
     */
    public function registerPermissions()
    {
        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function (RegisterUserPermissionsEvent $event) {
                $event->permissions[] = [
                    'heading' => 'Remote Sync',
                    'permissions' => [
                        'permissionName' => [
                            'label' => 'Push and pull/restore database and volume assets',
                        ],
                    ],
                ];
            }
        );
    }

    /**
     * Register URLs
     * 
     */
    public function registerURLs()
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['remote-sync/google-drive/auth'] = 'remote-sync/google-drive/auth';
                $event->rules['remote-sync/google-drive/auth-redirect'] = 'remote-sync/google-drive/auth-redirect';
            }
        );
    }

    /**
     * Register Console Controllers
     * 
     */
    public function registerConsoleControllers()
    {
        if (Craft::$app instanceof ConsoleApplication) {
            $this->controllerNamespace = 'weareferal\remotesync\console\controllers';
        }
    }

    /**
     * Register Services
     * 
     */
    public function registerServices()
    {
        $this->setComponents([
            'provider' => Craft::$app->getModule('remote-core')->providerFactory->create($this),
            'prune' => PruneService::class
        ]);
    }

    /**
     * Register Utilities
     * 
     */
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

    protected function createSettingsModel(): ?\craft\base\Model
    {
        return new Settings();
    }

    protected function settingsHtml(): ?string
    {
        $view = Craft::$app->getView();
        $view->registerAssetBundle(RemoteCoreSettingsAsset::class);
        $view->registerJs("new Craft.RemoteCoreSettings('main-form');");

        $isAuthenticated = $this->provider->isAuthenticated();
        $isConfigured = $this->provider->isConfigured();

        return $view->renderTemplate(
            'remote-sync/settings',
            [
                'plugin' => $this,
                'pluginHandle' => $this->getHandle(),
                'settings' => $this->getSettings(),
                'isConfigured' => $isConfigured,
                'isAuthenticated' => $isAuthenticated
            ]
        );
    }
}
