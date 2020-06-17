<?php

/**
 * Craft Remote Sync plugin for Craft CMS 3.x
 *
 * @link      https://weareferal.com
 * @copyright Copyright (c) 2020 Timmy O'Mahony
 */

namespace weareferal\RemoteSync;

use Craft;
use craft\base\Plugin;
use craft\services\Utilities;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\services\UserPermissions;

use yii\base\Event;

use weareferal\RemoteSync\utilities\RemoteSyncUtility;
use weareferal\RemoteSync\models\Settings;
use weareferal\RemoteSync\services\RemoteSyncService;
use weareferal\RemoteSync\assets\RemoteSyncsettings\RemoteSyncSettingAsset;


class RemoteSync extends Plugin
{
    public $hasCpSettings = true;

    public static $plugin;

    public $schemaVersion = '1.0.0';

    public function init()
    {
        parent::init();

        self::$plugin = $this;

        $this->setComponents([
            'remotesync' => RemoteSyncService::create($this->getSettings()->cloudProvider)
        ]);

        // Register console commands
        if (Craft::$app instanceof ConsoleApplication) {
            $this->controllerNamespace = 'weareferal\RemoteSync\console\controllers';
        }

        // Register permissions
        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function (RegisterUserPermissionsEvent $event) {
                $event->permissions['Remote Sync'] = [
                    'remote-sync' => [
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
        return $view->renderTemplate(
            'remote-sync/settings',
            [
                'settings' => $this->getSettings()
            ]
        );
    }
}
