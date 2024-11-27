<?php
declare(strict_types=1);

namespace App\Event;

use App\Http\SlackClient;
use Cake\ORM\Locator\LocatorAwareTrait;

abstract class AbstractEvent
{
    use LocatorAwareTrait;

    public const TYPE_MESSAGE = 'message';
    public const TYPE_DIRECT_MESSAGE = 'direct_message';
    public const TYPE_REACTION_ADDED = 'reaction_added';
    public const TYPE_APP_HOME_OPENED = 'app_home_opened';
    public const TYPE_APP_MENTION = 'app_mention';
    public const TYPE_SLASH_COMMAND = 'slash_command';
    public const TYPE_INTERACTIONS_CALLBACK = 'interaction_callback';
    public const TYPE_LINK_SHARED = 'link_shared';

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

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }
}
