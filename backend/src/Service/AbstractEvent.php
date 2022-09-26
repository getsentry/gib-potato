<?php
declare(strict_types=1);

namespace App\Service;

abstract class AbstractEvent
{
    protected SlackClient $slackClient;

    public function __construct()
    {
        $this->slackClient = new SlackClient;
    }

    abstract public function process();
}