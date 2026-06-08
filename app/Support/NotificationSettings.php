<?php

namespace App\Support;

class NotificationSettings
{
    public static function instance(): self
    {
        return new self();
    }
}
