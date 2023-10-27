# Remote Sync Craft CMS Plugin

![Header image for plugin](https://craft-plugins-cdn.timmyomahony.com/website/remote-sync/remote-sync-plugin-header.png)

📓 [**Documentation**](https://craft-plugins.timmyomahony.com/remote-sync) | 💳 [**Purchase**](https://plugins.craftcms.com/remote-sync?craft4) | 🤷🏻‍♂️ [**Get help**](https://craft-plugins.timmyomahony.com/remote-sync/docs/get-help)

Remote Sync is a plugin for Craft CMS that helps you sync your database and assets across multiple Craft environments via cloud destinations like AWS, Digital Ocean & Backblaze.

This makes it easier to move from local development to staging and onto production and avoids the need to regularly SSH into servers to perform database dumps and restores.

Remote Sync provides a useful interface for manually syncing your data via the Craft CMS Control Panel utilites section:

![Craft Remote Sync Overview](https://craft-plugins-cdn.timmyomahony.com/website/remote-sync/CleanShot%202023-10-27%20at%2012.48.57@2x.png)

Remote Sync also lets you automate the process via CLI commands:

```sh
./craft remote-sync/database/push
./craft remote-sync/database/pull
./craft remote-sync/database/list
./craft remote-sync/database/delete ...
```

## Features

- **Database sync**: move the entire database from one environment to another without touching the CLI.
- **Asset sync**: copy all your asset folders without needing to FTP a file yourself.
- **Multiple cloud providers**: remote sync supports numerous cloud providers including AWS and Backblaze.
- **Background queue**: use the Craft queue to avoid hanging around for files to complete syncing.
- **Supports large files**: sync large multi-GB volumes and databases to remote destinations.
- **CLI commands**: automate syncing using the CLI commands and cron.
- **Prunes old files and folders**: automatically prune old files so you never run out of space.
- **Remote volumes**: sync remote volumes to other remote locations (i.e. S3 to Backblaze)

## Documentation

See [the full documentation website](https://craft-plugins.timmyomahony.com/remote-sync) for details on how to get started with the plugin.
