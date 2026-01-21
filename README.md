# Public Upload Detector

Detect form fields that upload to a public folder

## Requirements

This plugin requires Craft CMS 4.3.5 or later, and PHP 7.4 or later.

## Installation

```bash
ddev composer require digitalpulsebe/craft-public-upload-detector
ddev craft plugin/install public-upload-detector
```

## Config

create a file called `public-upload-detector.php` in your config folder

```php
<?php

return [
    'restrictFormie' => true,
    'restrictFreeform' => true,
    'allowedPublicVolumeHandles' => [
        'myPublicVolumeHandle'
    ]
];
```

## Usage

### Detecting form fields that upload to a public folder

Run the command `php craft public-upload-detector/check` to detect form fields that upload to a public folder

### Enforce data retention on forms

Run the command `php craft public-upload-detector/data-retention/enforce` to enforce data retention on forms

Options:

- `--days=90` - Number of days to retain data (default: 90)
- `--keep=false` - Keep or delete file uploads (default: false)

For example: `php craft public-upload-detector/data-retention/enforce --days=30 --keep=true`
