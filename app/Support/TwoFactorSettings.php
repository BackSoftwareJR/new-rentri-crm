<?php

namespace App\Support;

class TwoFactorSettings
{
    public static function instance(): self
    {
        return new self();
    }
}
