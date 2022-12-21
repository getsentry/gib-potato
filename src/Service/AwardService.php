<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\User;
use Cake\Datasource\ModelAwareTrait;
use Cake\I18n\FrozenTime;

class AwardService
{
    use ModelAwareTrait;

    protected SlackClient $slackClient;

    public function __construct()
    {
        $this->slackClient = new SlackClient();

        $this->loadModel('Users');
        $this->loadModel('Messages');
    }

    public function gib(string $fromSlackUserId, string $toSlackUserId, int $amount, string $type)
    {
        $fromUser = $this->createOrUpdateUser($fromSlackUserId);
        $toUser = $this->createOrUpdateUser($toSlackUserId);

        if ($fromUser instanceof User && $toUser instanceof User) {
            // @FIXME do this counting stuff earlier and don't even try
            $givenOutAmount = $this->Messages->find()
                ->where([
                    'sender_user_id' => $fromUser->id,
                    'type' => 'potato',
                    'created >=' => new FrozenTime('24 hours ago'),
                ])
                ->count();

            // @FIXME make this 5 a const or move to DB
            if ($givenOutAmount >= 5) {
                return false;
            }

            $message = $this->Messages->newEntity([
                'sender_user_id' => $fromUser->id,
                'receiver_user_id' => $toUser->id,
                'amount' => $amount,
                'type' => $type,
            ]);

            $this->Messages->saveOrFail($message);

            // @FIXME Allow users to opt-out
            $this->slackClient->postMessage(
                $fromSlackUserId,
                sprintf('You did gib *%s* %s to <@%s>.%s', $amount, $type, $toSlackUserId, PHP_EOL) .
                sprintf('You have *%s* %s left.', 5 - $amount - $givenOutAmount, $type),
            );

            // @FIXME Allow users to opt-out
            $this->slackClient->postMessage(
                $toSlackUserId,
                sprintf('<@%s> did gib you *%s* %s ', $fromSlackUserId, $amount, $type),
            );
        }
    }

    /**
     * @FIXME Fetching the user every time might get us rate limited...
     */
    protected function createOrUpdateUser(string $slackUserId): ?User
    {
        $slackUser = $this->slackClient->getUser($slackUserId);

        // The user could not be found
        if (empty($slackUser)) {
            return null;
        }

        $user = $this->Users
            ->findBySlackUserId($slackUser['id'])
            ->first();

        if ($user === null) {
            // Create a new user
            $user = $this->Users->newEntity([
                'status' => 'active',
                'slack_user_id' => $slackUser['id'],
                'slack_name' => $slackUser['real_name'],
                'slack_picture' => $slackUser['profile']['image_72'],
            ]);
        } else {
            // Update the user
            $user = $this->Users->patchEntity($user, [
                'status' => 'active',
                'slack_name' => $slackUser['real_name'],
                'slack_picture' => $slackUser['profile']['image_72'],
            ]);
        }

        return $this->Users->saveOrFail($user);
    }
}
