<?php

namespace App\Support;

class DashboardReport
{
    public static function instance(): self
    {
        return new self();
    }
}
