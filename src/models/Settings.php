<?php

namespace digitalpulsebe\pud\models;

use craft\base\Model;

class Settings extends Model
{
    public bool $restrictFormie = true;
    public bool $restrictFreeform = true;

    public array $allowedPublicVolumeHandles = [];
}
