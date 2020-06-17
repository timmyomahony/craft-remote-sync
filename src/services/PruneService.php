<?php

namespace weareferal\remotesync\services;

use Craft;
use craft\base\Component;

use weareferal\remotesync\RemoteSync;


/**
 * Prune service
 * 
 * @since 1.3.0
 */
class PruneService extends Component
{
    /**
     * Prune database files
     * 
     * Delete all "old" database files
     * 
     * @param boolean $dryRun if true do everything except actually deleting
     * @return array the deleted files
     * @since 1.3.0
     */
    public function pruneDatabases($dryRun = false)
    {
        $remoteFiles = RemoteSync::getInstance()->provider->listDatabases();
        return $this->prune($remoteFiles, $dryRun);
    }

    /**
     * Prune volume files
     * 
     * Delete all "old" database files
     * 
     * @param boolean $dryRun if true do everything except actually deleting
     * @return array the deleted files
     * @since 1.3.0
     */
    public function pruneVolumes($dryRun = false)
    {
        $remoteFiles = RemoteSync::getInstance()->provider->listVolumes();
        return $this->prune($remoteFiles, $dryRun);
    }

    /**
     * Prune files
     * 
     * Delete "old" remote files. This operation relies on "prune" from the
     * settings. The algorithm is simple, delete all files > than the sync
     * limit. In other words, if the sync limit is 5 and we have 9 backups,
     * delete the 6th-9th backups keeping the 5 most recent.
     * 
     * @param array $filenames an array of remote filenames
     * @param boolean $dryRun if true do everything except actually deleting
     * @return array the deleted files (or empty array)
     * @since 1.2.0
     */
    private function prune($remoteFiles, $dryRun = false)
    {
        $plugin = RemoteSync::getInstance();
        $settings = $plugin->getSettings();
        $deleted_filenames = [];

        if (!$settings->prune) {
            Craft::warning("Pruning disabled" . PHP_EOL, 'remote-sync');
            return $deleted_filenames;
        } else if (count($remoteFiles) < $settings->pruneLimit) {
            Craft::warning("Skipping file pruning: files < prune limit" . PHP_EOL, 'remote-sync');
            return $deleted_filenames;
        }

        $remoteFiles = array_slice($remoteFiles, $settings->pruneLimit, count($remoteFiles));
        foreach ($remoteFiles as $file) {
            $filename = $file->filename;
            if (!$dryRun) {
                $plugin->provider->delete($file->filename);
                array_push($deleted_filenames, $filename);
            }
        }

        return $deleted_filenames;
    }
}
