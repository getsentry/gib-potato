<?php
declare(strict_types=1);

namespace App\Service;

use App\Http\SlackClient;
use App\Model\Entity\Progression;
use App\Model\Entity\User;
use Cake\ORM\Locator\LocatorAwareTrait;

class ProgressionService
{
    use LocatorAwareTrait;

    public function __construct()
    {
    }

    public function progress(User $user): void
    {
        $progression = $this->findNextProgression($user);
        if ($progression === null) {
            return;
        }

        $usersTable = $this->fetchTable('Users');

        $user = $usersTable->patchEntity($user, [
            'progression_id' => $progression->id,
        ], [
            'accessibleFields' => [
                'progression_id' => true,
            ],
        ]);
        $usersTable->saveOrFail($user);

        $this->sendProgressionNotification($user, $progression);
    }

    protected function findNextProgression(User $user): ?Progression
    {
        $sentCount = $user->potatoSent();
        $receivedCount = $user->potatoReceived();

        $progressionTable = $this->fetchTable('Progression');

        $progression = $progressionTable->find()
            ->where([
                'OR' => [
                    'sent_threshold <=' => $sentCount,
                    'received_threshold <=' => $receivedCount,
                ],
            ])
            ->order(['id' => 'DESC'])
            ->first();

        if ($progression === null) {
            return null;
        }

        if ($progression->id <= $user->progression_id) {
            return null;
        }

        if ($progression->operator === Progression::OPERATOR_AND) {
            if (
                $progression->sent_threshold <= $sentCount
                && $progression->received_threshold <= $receivedCount
            ) {
                return $progression;
            }
        }
        if ($progression->operator === Progression::OPERATOR_OR) {
            if (
                $progression->sent_threshold <= $sentCount
                || $progression->received_threshold <= $receivedCount
            ) {
                return $progression;
            }
        }

        return null;
    }

    protected function sendProgressionNotification(User $user, Progression $progression): void
    {
        $progressionMessage = 'Ohh dang, you just reached the next level of potato mastery 🤯' . PHP_EOL;
        $progressionMessage .= 'By sending *' . $progression->sent_threshold . '* :potato: and receiving *'
            . $progression->received_threshold . '* :potato:, you can proudly call yourself *' . $progression->name . '* 🥳.' . PHP_EOL;

        if ($user->notifications['received'] === true) {
            (new SlackClient())->postMessage(
                channel: $user->slack_user_id,
                text: $progressionMessage,
            );
        }
    }
}
