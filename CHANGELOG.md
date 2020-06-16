# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## 1.0.0 - 2020-04-08

### Added

- Initial release

## 1.1.0 - 2020-04-08

### Changed

- Improved the messaging for pulling and restoring

### Added

- Added emergency backup feature

## 1.2.0 - 2020-04-26

### Added

- File pruning
- "Latest" badge on utilties page to easily identify the most recent synced file
- A "show more" dropdown when there are > 3 synced files
- A "hide" setting to remove either databases or volumes from the utilities panel

## 1.2.1 - 2020-05-23

### Changed

- Fixed issue (#15) with restoring via queue where a new database backup would be pushed automatically (and incorrectly)
- Fixed issue (#16) where not being logged out automatically via Ajax when restoring via the queue

## 1.3.0 - 2020-06-11

### Added

- Dropbox provider & documentation
- Google Drive provider & documentation
- Backblaze provider & documentation
