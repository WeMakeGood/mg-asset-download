# MG Asset Download

A WordPress plugin that downloads external assets from posts/pages, adds them to the Media Library, and updates the links.

## Developer Information

- Company: Make Good (https://wemakegood.org)
- Developer: Christopher Frazier (chris.frazier@wemakegood.org)

## Description

MG Asset Download is an admin plugin that enumerates the posts and pages on a site and, one-by-one, finds externally linked files (images, PDFs, DOCXs, etc.) and downloads them, adds them to the Media Library, attaches the asset to the post or page, and updates the link in the post to correctly point to the Media attachment file.

The plugin runs in the background using WordPress Cron, ensuring the process is handled efficiently without user intervention.

## Features

- Automatically scans posts and pages for external assets
- Downloads external images, PDFs, DOCXs, and other file types
- Adds downloaded files to the WordPress Media Library
- Attaches media files to their respective posts/pages
- Updates links in content to use local URLs
- Tracks progress with a custom admin page
- Runs in the background using WordPress Cron
- Includes manual processing option
- Prevents duplicate downloads of the same file

## Installation

1. Upload the `mg-asset-download` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Visit the 'Tools > MG Asset Download' page to monitor progress

## Usage

Once activated, the plugin will automatically start processing posts and pages. The plugin works in batches to prevent timeout issues and excessive server load.

You can monitor the progress on the admin page (Tools > MG Asset Download), which shows:
- The number of posts/pages processed
- The total number of posts/pages
- When the plugin last ran
- Option to manually trigger processing

## Important Notes

- The plugin will continue running as long as it is active
- Once all posts and pages have been processed, you should deactivate the plugin
- External files will only be downloaded if they exist and are accessible
- The plugin uses WordPress cron, which requires site traffic to trigger events

## Supported File Types

- Images (jpg, jpeg, png, gif, webp, etc.)
- Documents (pdf, doc, docx)
- Spreadsheets (xls, xlsx)
- Presentations (ppt, pptx)
- Archives (zip, rar)