<?php
declare(strict_types=1);

namespace App\Event;

use App\Http\SlackClient;

abstract class AbstractEvent
{
    public const TYPE_MESSAGE = 'message';
    public const TYPE_DIRECT_MESSAGE = 'direct_message';
    public const TYPE_REACTION_ADDED = 'reaction_added';
    public const TYPE_APP_HOME_OPENED = 'app_home_opened';
    public const TYPE_APP_MENTION = 'app_mention';

    protected SlackClient $slackClient;

    public string $type;
    public string $eventTimestamp;

    /**
     * @return void
     */
    public function __construct()
    {
        $this->slackClient = new SlackClient();
    }

    /**
     * @return void
     */
    abstract public function process(): void;
}
