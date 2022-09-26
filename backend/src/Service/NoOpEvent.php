<?php
declare(strict_types=1);

namespace App\Service;

class NoOpEvent extends AbstractEvent
{
    public function __construct()
    {
    }

    public function process()
    {
        // Do nothing...
    }
}