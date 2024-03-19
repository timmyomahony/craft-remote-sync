<?php

namespace weareferal\remotesync\migrations;

use Craft;
use craft\db\Migration;

/**
 * m240319_070857_add_disable_restore_setting migration.
 */
class m240319_070857_add_disable_restore_setting extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        // Create the disableRestore setting, if it doesn't already exist
        $remoteSync = Craft::$app->plugins->getPlugin("remote-sync");
        $settings = $remoteSync->getSettings();
        if (!property_exists($settings, 'disableRestore')) {
            Craft::$app->getPlugins()->savePluginSettings($remoteSync, [
                'disableRestore' => 'boolean',
            ]);
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        return true;
    }
}
