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

Run the command `php craft public-upload-detector/check` to detect form fields that upload to a public folder
