<?php

namespace weareferal\remotesync\migrations;

use Craft;
use craft\db\Migration;

/**
 * m221001_161023_update_date_format_settings_default migration.
 */
class m221001_161023_update_date_format_settings_default extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        // If the existing remote backup date format setting remains the default
        // setting from previous versions, then update it.
        $remoteSync = Craft::$app->plugins->getPlugin("remote-sync");
        $settings = $remoteSync->getSettings();
        if ($settings->displayDateFormat == "Y-m-d H:i:s") {
            Craft::$app->getPlugins()->savePluginSettings($remoteSync, [
                "displayDateFormat" => "Y-m-d"
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
