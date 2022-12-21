<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Datasource\ModelAwareTrait;

abstract class AbstractEvent
{
    use ModelAwareTrait;

    public const TYPE_MESSAGE = 'message';
    public const TYPE_REACTION_ADDED = 'reaction_added';
    public const TYPE_APP_HOME_OPENED = 'app_home_opened';
    public const TYPE_APP_MENTION = 'app_mention';

    protected SlackClient $slackClient;

    protected string $type;

    public function __construct()
    {
        $this->slackClient = new SlackClient();
    }

    abstract public function process();
}
