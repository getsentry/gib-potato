<?php
declare(strict_types=1);

namespace App\Service\Event;

use App\Service\SlackClient;

abstract class AbstractEvent
{

    public const TYPE_MESSAGE = 'message';
    public const TYPE_REACTION_ADDED = 'reaction_added';
    public const TYPE_APP_HOME_OPENED = 'app_home_opened';
    public const TYPE_APP_MENTION = 'app_mention';

    protected SlackClient $slackClient;

    public string $type;
    public string $eventTimestamp;

    public function __construct()
    {
        $this->slackClient = new SlackClient();
    }

    abstract public function process();
}
