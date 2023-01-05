<?php
declare(strict_types=1);

namespace App\Service\Event;

class AppHomeOpenedEvent extends AbstractEvent
{
    protected string $user;
    protected string $tab;
    protected string $timestamp;

    public function __construct(array $event)
    {
        parent::__construct();

        $this->type = self::TYPE_APP_HOME_OPENED;
        $this->user = $event['user'];
        $this->tab = $event['tab'];
        $this->timestamp = $event['timestamp'];
    }

    public function process()
    {
        $this->slackClient->publishView(
            user: $this->user,
            view: [
                'type' => 'home',
                'blocks' => [
                    [
                        'type' => 'section',
                        'text' => [
                            'type' => 'mrkdwn',
                            'text' => 'Welcome home, <@' . $this->user . '> :house_with_garden:',
                        ],
                    ],
                ],
            ],
        );
    }
}
