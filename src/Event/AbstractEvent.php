<?php
declare(strict_types=1);

namespace App\Event;

use App\Http\DiscordClient;
use App\Http\SlackClient;
use Cake\ORM\Locator\LocatorAwareTrait;

abstract class AbstractEvent
{
    use LocatorAwareTrait;

    public const string TYPE_MESSAGE = 'message';
    public const string TYPE_DIRECT_MESSAGE = 'direct_message';
    public const string TYPE_REACTION_ADDED = 'reaction_added';
    public const string TYPE_APP_HOME_OPENED = 'app_home_opened';
    public const string TYPE_APP_MENTION = 'app_mention';
    public const string TYPE_SLASH_COMMAND = 'slash_command';
    public const string TYPE_INTERACTIONS_CALLBACK = 'interaction_callback';
    public const string TYPE_VIEW_SUBMISSION = 'view_submission';
    public const string TYPE_LINK_SHARED = 'link_shared';

    protected readonly SlackClient $slackClient;
    protected readonly DiscordClient $discordClient;

    public protected(set) string $type;
    public protected(set) string $eventTimestamp;

    /**
     * @return void
     */
    public function __construct()
    {
        $this->slackClient = new SlackClient();
        $this->discordClient = new DiscordClient();
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
