<?php
declare(strict_types=1);

namespace App\Service;

use App\Event\MessageEvent;
use App\Event\ReactionAddedEvent;
use App\Model\Entity\User;
use Cake\ORM\Locator\LocatorAwareTrait;

class AwardService
{
    use LocatorAwareTrait;

    public function gib(
        User $fromUser,
        array $toUsers,
        MessageEvent|ReactionAddedEvent $event,
    ) {
        foreach ($toUsers as $toUser) {
            $this->gibToUser(
                fromUser: $fromUser,
                toUser: $toUser,
                event: $event,
            );
        }
    }

    private function gibToUser(
        User $fromUser,
        User $toUser,
        MessageEvent|ReactionAddedEvent $event,
    ) {
        $messagesTable = $this->fetchTable('Messages');

        $message = $messagesTable->newEntity([
            'sender_user_id' => $fromUser->id,
            'receiver_user_id' => $toUser->id,
            'amount' => $event->amount,
            'type' => str_replace(':', '', $event->reaction),
        ], [
            'accessibleFields' => [
                'sender_user_id' => true,
                'receiver_user_id' => true,
                'amount' => true,
                'type' => true,
            ],
        ]);
        $messagesTable->saveOrFail($message);
    }
}
