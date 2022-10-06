<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Datasource\ModelAwareTrait;

abstract class AbstractEvent
{
    use ModelAwareTrait;

    protected SlackClient $slackClient;

    public function __construct()
    {
        $this->slackClient = new SlackClient();
    }

    abstract public function process();
}
