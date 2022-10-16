<?php
declare(strict_types=1);

use Cake\Log\Log;

if (!function_exists('dlog')) {
    /**
     * Debug Logging Helper
     *
     * @return void
     */
    function dlog(...$args): void
    {
        foreach ($args as $arg) {
            if (!\is_string($arg)) {
                $arg = \print_r($arg, true);
            }
            Log::write('debug', \print_r($arg, true));
        }
    }
}