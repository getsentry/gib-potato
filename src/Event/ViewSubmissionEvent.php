<?php
declare(strict_types=1);

namespace App\Event;

use App\Service\UserService;

class ViewSubmissionEvent extends AbstractEvent
{
    protected string $user;
    protected string $callbackId;
    /** @var array<string, string> */
    protected array $values;

    /**
     * Constructor
     *
     * @param array $event Event data.
     */
    public function __construct(array $event)
    {
        parent::__construct();

        $this->type = self::TYPE_VIEW_SUBMISSION;
        $this->user = $event['user'];
        $this->callbackId = $event['callback_id'];
        $this->values = $event['values'] ?? [];
    }

    /**
     * @inheritDoc
     */
    public function process(): void
    {
        if ($this->callbackId === 'birthday_setup') {
            $this->saveBirthday();
        }
    }

    protected function saveBirthday(): void
    {
        $userService = new UserService();
        $user = $userService->getOrCreateUser($this->user);

        $usersTable = $this->fetchTable('Users');

        $day = isset($this->values['birthday_day.day'])
            ? (int)$this->values['birthday_day.day']
            : null;
        $month = isset($this->values['birthday_month.month'])
            ? (int)$this->values['birthday_month.month']
            : null;
        $hub = $this->values['hub.hub'] ?? null;

        $user = $usersTable->patchEntity($user, [
            'birthday_day' => $day,
            'birthday_month' => $month,
            'hub' => $hub,
        ], [
            'accessibleFields' => [
                'birthday_day' => true,
                'birthday_month' => true,
                'hub' => true,
            ],
        ]);
        $usersTable->saveOrFail($user);

        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
        ];

        $message = 'Your birthday has been set to *' . $months[$month] . ' ' . $day . '* ';
        $message .= 'and your hub to *' . strtoupper($hub) . '* :tada:' . PHP_EOL;
        $message .= 'We\'ll make sure to celebrate you when the day comes! :potato:';

        $this->slackClient->postMessage(
            channel: $user->slack_user_id,
            text: $message,
        );
    }
}
