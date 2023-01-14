<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\User;
use App\Model\Table\MessagesTable;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\I18n\FrozenTime;

class AwardService
{
    use LocatorAwareTrait;

    protected SlackClient $slackClient;
    protected MessagesTable $Messages;

    public function __construct()
    {
        $this->slackClient = new SlackClient();
        $this->Messages = $this->fetchTable('Messages');
    }

    public function gib(User $fromUser, User $toUser, int $amount, string $type)
    {
        $message = $this->Messages->newEntity([
            'sender_user_id' => $fromUser->id,
            'receiver_user_id' => $toUser->id,
            'amount' => $amount,
            'type' => $type,
        ]);
        $this->Messages->saveOrFail($message);

        // @FIXME Allow users to opt-out
        $this->slackClient->postMessage(
            channel: $fromUser->slack_user_id,
            text: sprintf('You did gib *%s* :%s: to <@%s>.', $amount, $type, $toUser->slack_user_id),
        );

        // @FIXME Allow users to opt-out
        $this->slackClient->postMessage(
            channel: $toUser->slack_user_id,
            text: sprintf('<@%s> did gib you *%s* :%s:.', $fromUser->slack_user_id, $amount, $type),
        );
    }
}
