<?php
declare(strict_types=1);

namespace App\Service;

use App\Http\SlackClient;
use App\Model\Entity\Progression;
use App\Model\Entity\User;
use Cake\ORM\Locator\LocatorAwareTrait;
use function Sentry\logger;

class ProgressionService
{
    use LocatorAwareTrait;

    protected SlackClient $slackClient;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->slackClient = new SlackClient();
    }

    /**
     * @param \App\Model\Entity\User $user The user.
     * @return void
     */
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

        logger()->info(
            message: 'User progression unlocked',
            attributes: [
                'gibpotato.user.id' => $user->id,
                'gibpotato.progression.id' => $progression->id,
                'gibpotato.progression.name' => $progression->name,
            ],
        );

        $this->sendProgressionNotification($user, $progression);
    }

    /**
     * @param \App\Model\Entity\User $user The user.
     * @return \App\Model\Entity\Progression|null
     */
    protected function findNextProgression(User $user): ?Progression
    {
        $sentCount = $user->potatoSent();
        $receivedCount = $user->potatoReceived();

        $progressionTable = $this->fetchTable('Progression');

        $progression = $progressionTable->find()
            ->where([
                'sent_threshold <=' => $sentCount,
                'received_threshold <=' => $receivedCount,
            ])
            ->orderBy(['id' => 'DESC'])
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

    /**
     * @param \App\Model\Entity\User $user The user.
     * @param \App\Model\Entity\Progression $progression Progression.
     * @return void
     */
    protected function sendProgressionNotification(User $user, Progression $progression): void
    {
        if ($user->notifications['received'] === true) {
            $progressionMessage = 'Ohh dang, you just reached the next level of potato mastery 🤯' . PHP_EOL;
            $progressionMessage .= 'By sending *' . $progression->sent_threshold . '* :potato: and receiving *'
                . $progression->received_threshold . '* :potato:, you can proudly call yourself *'
                . $progression->name . '* 🥳.' . PHP_EOL;

            $this->slackClient->postMessage(
                channel: $user->slack_user_id,
                text: $progressionMessage,
            );
        }
    }
}
