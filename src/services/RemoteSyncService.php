<?php

namespace weareferal\RemoteSync\services;

use yii\base\Component;
use Craft;
use Craft\helpers\FileHelper;
use Craft\helpers\StringHelper;

use weareferal\RemoteSync\RemoteSync;
use weareferal\RemoteSync\services\providers\S3Provider;
use weareferal\RemoteSync\helpers\ZipHelper;


interface Provider
{
    public function list($filterExtensions): array;
    public function push($path);
    public function pull($key, $path);
    public function delete($key);
}

class RemoteSyncInstance
{
    public $filename;
    public $datetime;
    public $label;
    public $env;

    // Regex to capture/match:
    // - Site name
    // - Environment (optional and captured)
    // - Date (required and captured)
    // - Random string
    // - Version
    // - Extension
    private static $regex = '/^(?:[a-zA-Z0-9\-]+)\_(?:([a-zA-Z0-9\-]+)\_)?(\d{6}\_\d{6})\_(?:[a-zA-Z0-9]+)\_(?:[v0-9\.]+)\.(?:\w{2,10})$/';

    public function __construct($_filename)
    {
        // Extract values from filename
        preg_match(RemoteSyncInstance::$regex, $_filename, $matches);
        $env = $matches[1];
        $date = $matches[2];
        $datetime = date_create_from_format('ymd_Gis', $date);
        $label = $datetime->format('Y-m-d H:i:s');
        if ($env) {
            $label = $label  . ' (' . $env . ')';
        }
        $this->filename = $_filename;
        $this->datetime = $datetime;
        $this->label = $label;
        $this->env = $env;
    }
}

class RemoteSyncService extends Component
{
    /**
     * Return the remote database filenames
     * 
     * @return array An array of label/filename objects
     * @since 1.0.0
     */
    public function listDatabases(): array
    {
        $filenames = $this->list(".sql");
        $backups = $this->parseFilenames($filenames);
        $options = [];
        foreach ($backups as $i => $backup) {
            $options[$i] = [
                "label" => $backup->label,
                "value" => $backup->filename
            ];
        }
        return $options;
    }

    /**
     * Return the remote volume filenames
     * 
     * @return array An array of label/filename objects
     * @since 1.0.0
     */
    public function listVolumes(): array
    {
        $filenames = $this->list('.zip');
        $backups = $this->parseFilenames($filenames);
        $options = [];
        foreach ($backups as $i => $backup) {
            $options[$i] = [
                "label" => $backup->label,
                "value" => $backup->filename
            ];
        }
        return $options;
    }

    /**
     * Push database to remote provider
     * 
     * @return string The filename of the newly created Remote Sync
     * @since 1.0.0
     */
    public function pushDatabase()
    {
        $filename = $this->getFilename();
        $path = $this->createDatabaseDump($filename);
        $this->push($path);
        unlink($path);
        return $filename;
    }

    /**
     * Push all volumes to remote provider
     * 
     * @return string The filename of the newly created Remote Sync
     * @return null If no volumes exist
     * @since 1.0.0
     */
    public function pushVolumes(): string
    {
        $filename = $this->getFilename();
        $path = $this->createVolumesZip($filename);
        $this->push($path);
        unlink($path);
        return $filename;
    }

    /**
     * Pull and restore remote database file
     * 
     * @param string $filename the file to restore
     */
    public function pullDatabase($filename)
    {
        // Before pulling a database, backup the local
        $settings = RemoteSync::getInstance()->getSettings();
        if ($settings->keepEmergencyBackup) {
            $this->createDatabaseDump("emergency-backup");
        }

        $path = $this->getLocalDir() . DIRECTORY_SEPARATOR . $filename;
        $this->pull($filename, $path);
        Craft::$app->getDb()->restore($path);
        unlink($path);
    }

    /**
     * Pull and restore a particular remote volume file.
     * 
     * @param string $filename the file to restore
     * @since 1.0.0
     */
    public function pullVolume($filename)
    {
        // Before pulling volumes, backup the local
        $settings = RemoteSync::getInstance()->getSettings();
        if ($settings->keepEmergencyBackup) {
            $this->createVolumesZip("emergency-backup");
        }

        $path = $this->getLocalDir() . DIRECTORY_SEPARATOR . $filename;
        $this->pull($filename, $path);
        $this->restoreVolumesZip($path);
        unlink($path);
    }

    /**
     * Delete file
     * 
     * Delete a single database file remotely.
     * 
     * @param string $filename the file to delete
     * @since 1.0.0
     */
    public function deleteDatabase($filename)
    {
        return $this->delete($filename);
    }

    /**
     * Delete file
     * 
     * Delete a single volume file remotely.
     * 
     * @param string $filename the file to delete
     * @since 1.0.0
     */
    public function deleteVolume($filename)
    {
        return $this->delete($filename);
    }

    /**
     * Prune database files
     * 
     * Delete all "old" database files
     * 
     * @param boolean $dryRun if true do everything except actually deleting
     * @return array the deleted files
     * @since 1.2.0
     */
    public function pruneDatabases($dryRun = false)
    {
        $filenames = $this->list(".sql");
        return $this->prune($filenames, $dryRun);
    }

    /**
     * Prune volume files
     * 
     * Delete all "old" database files
     * 
     * @param boolean $dryRun if true do everything except actually deleting
     * @return array the deleted files
     * @since 1.2.0
     */
    public function pruneVolumes($dryRun = false)
    {
        $filenames = $this->list(".zip");
        return $this->prune($filenames, $dryRun);
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
    private function prune($filenames, $dryRun = false)
    {
        $deleted = [];
        $backups = $this->parseFilenames($filenames);
        $settings = RemoteSync::getInstance()->getSettings();
        if (!$settings->prune) {
            Craft::warning("Pruning disabled" . PHP_EOL, 'remote-sync');
            return $deleted;
        } else if (count($backups) < $settings->pruneLimit) {
            Craft::warning("Skipping file pruning: files < prune limit" . PHP_EOL, 'remote-sync');
            return $deleted;
        }
        $backups = array_slice($backups, $settings->pruneLimit, count($backups));
        foreach ($backups as $backup) {
            $filename = $backup->filename;
            if (!$dryRun) {
                $this->delete($backup->filename);
                array_push($deleted, $filename);
            }
        }
        return $deleted;
    }

    /**
     * Create volumes zip
     * 
     * Generates a temporary zip file of all volumes
     * 
     * @param string $filename the filename to give the new zip
     * @return string $path the temporary path to the new zip file
     * @since 1.0.0
     */
    private function createVolumesZip($filename): string
    {
        $path = $this->getLocalDir() . DIRECTORY_SEPARATOR . $filename . '.zip';
        if (file_exists($path)) {
            unlink($path);
        }

        $volumes = Craft::$app->getVolumes()->getAllVolumes();
        $tmpDirName = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . strtolower(StringHelper::randomString(10));

        if (count($volumes) <= 0) {
            return null;
        }

        foreach ($volumes as $volume) {
            $tmpPath = $tmpDirName . DIRECTORY_SEPARATOR . $volume->handle;
            FileHelper::copyDirectory($volume->rootPath, $tmpPath);
        }

        ZipHelper::recursiveZip($tmpDirName, $path);
        FileHelper::clearDirectory(Craft::$app->getPath()->getTempPath());
        return $path;
    }

    /**
     * Restore volumes
     * 
     * Unzips volumes to a temporary path and then moves them to the "web" 
     * folder.
     * 
     * @param string $path the path to the zip file to restore
     * @since 1.0.0
     */
    private function restoreVolumesZip($path)
    {
        $volumes = Craft::$app->getVolumes()->getAllVolumes();
        $tmpDirName = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . strtolower(StringHelper::randomString(10));

        ZipHelper::unzip($path, $tmpDirName);

        $folders = array_diff(scandir($tmpDirName), array('.', '..'));
        foreach ($folders as $folder) {
            foreach ($volumes as $volume) {
                if ($folder == $volume->handle) {
                    $dest = $tmpDirName . DIRECTORY_SEPARATOR . $folder;
                    if (!file_exists($volume->rootPath)) {
                        FileHelper::createDirectory($volume->rootPath);
                    } else {
                        FileHelper::clearDirectory($volume->rootPath);
                    }
                    FileHelper::copyDirectory($dest, $volume->rootPath);
                }
            }
        }

        FileHelper::clearDirectory(Craft::$app->getPath()->getTempPath());
    }

    /**
     * Create database sql dump
     * 
     * Uses the underlying Craft 3 "backup/db" function to create a new database
     * backup in the sync folder.
     * 
     * @param string $filename the file name to give the new backup
     * @return string $path the 
     * @since 1.0.0
     */
    private function createDatabaseDump($filename): string
    {
        $path = $this->getLocalDir() . DIRECTORY_SEPARATOR . $filename . '.sql';
        Craft::$app->getDb()->backupTo($path);
        return $path;
    }

    /**
     * Return a unique filename for a backup file
     * 
     * Based on getBackupFilePath():
     * 
     * https://github.com/craftcms/cms/tree/master/src/db/Connection.php
     * 
     * @return string The unique backup filename
     * @since 1.0.0
     */
    private function getFilename(): string
    {
        $currentVersion = 'v' . Craft::$app->getVersion();
        $systemName = FileHelper::sanitizeFilename(Craft::$app->getInfo()->name, ['asciiOnly' => true]);
        $systemEnv = Craft::$app->env;
        $filename = ($systemName ? $systemName . '_' : '') . ($systemEnv ? $systemEnv . '_' : '') . gmdate('ymd_His') . '_' . strtolower(StringHelper::randomString(10)) . '_' . $currentVersion;
        return mb_strtolower($filename);
    }

    /**
     * Return a chronologically sorted array of Backup objects
     * 
     * @param string[] Array of filenames
     * @return array[] Array of Backup objects
     * @since 1.0.0
     */
    private function parseFilenames($filenames): array
    {
        $backups = [];

        foreach ($filenames as $filename) {
            array_push($backups, new RemoteSyncInstance($filename));
        }

        uasort($backups, function ($b1, $b2) {
            return $b1->datetime <=> $b2->datetime;
        });

        return array_reverse($backups);
    }

    /**
     * Get local directory
     * 
     * Return (or creates) the local "web/sync" directory we use for synced
     * files. This is a separate folder to the default Craft backup folder.
     * 
     * @return string $dir a path to the directory
     * @since 1.0.0
     */
    protected function getLocalDir()
    {
        $dir = Craft::$app->path->getStoragePath() . "/sync";
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        return $dir;
    }

    /**
     * Create provider
     * 
     * Factory method to return appropriate class depending on provider
     * settings
     * 
     * @param string $provider the provider (via settings)
     * @return class The provider class to be instantiated
     * @since 1.0.0
     */
    public static function create($provider)
    {
        switch ($provider) {
            case "s3":
                return S3Provider::class;
                break;
        }
    }
}
