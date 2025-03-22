# Changelog
All notable changes to the MG Asset Download plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.2] - 2025-03-22
### Added
- Locking mechanism to prevent conflicts between WP Cron and manual processing
- Stale lock detection and ability to clear stuck locks
- Browser warning when leaving page during active processing

## [1.0.1] - 2025-03-22
### Added
- AJAX-based manual processing to prevent timeouts with large numbers of posts
- Real-time progress updates during manual processing
- Visual progress bar for manual processing

## [1.0.0] - 2025-03-22
### Added
- Initial release
- Admin page showing progress of asset downloading
- Background processing via WordPress cron
- Support for downloading images, PDFs, DOCXs, and other file types
- Manual processing button
- Progress tracking via custom post meta