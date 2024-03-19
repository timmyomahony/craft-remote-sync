<?php
return [
    '*' => [
        // The provider you are using for syncing
        'cloudProvider' => 's3',

        // Provider-specific details can be added here

        //...

        // Use Craft's native queue when performing operations
        'useQueue' => false,

        // Keep a single emergency backup of the database/volumes when restoring
        'keepEmergencyBackup' => true,

        // Remove old files
        'prune' => false,

        // The number of recent files to keep if pruning is enabled
        'pruneLimit' => 10,

        // Hide the database sync panel on the utilities page
        'hideDatabases' => false,

        // Hide the volume sync panel on the utilities page
        'hideVolumes' => false,

        // Enable pull and restore functionality
        'disableRestore' => false,
    ],
    'dev' => [],
    'staging' => [],
    'production' => [
        // Disable pull and restore only in production environment
        'disableRestore' => true,
    ],
];
