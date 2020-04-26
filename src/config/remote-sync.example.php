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

        // Hide the database sync panel on the utilities page
        'hideDatabases' => true,

        // Hide the volume sync panel on the utilities page
        'hideVolumes' => true
    ],
    'dev' => [],
    'staging' => [],
    'production' => [],
];
