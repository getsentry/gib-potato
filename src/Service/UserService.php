<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\User;
use App\Model\Table\UsersTable;
use Cake\ORM\Locator\LocatorAwareTrait;

class UserService {

    use LocatorAwareTrait;

    protected SlackClient $slackClient;
    protected UsersTable $Users;

    public function __construct()
    {
        $this->slackClient = new SlackClient();
        $this->Users = $this->fetchTable('Users');
    }

    /**
     * @FIXME Fetching the user every time might get us rate limited...
     */
    public function createOrUpdateUser(string $slackUserId): ?User
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
                'slack_is_bot' => $slackUser['is_bot'] ?? false,
            ]);
        } else {
            // Update the user
            $user = $this->Users->patchEntity($user, [
                'status' => !$slackUser['deleted'] ? User::STATUS_ACTIVE : User::STATUS_DELETED,
                'slack_name' => $slackUser['real_name'],
                'slack_picture' => $slackUser['profile']['image_72'],
            ]);
        }

        return $this->Users->saveOrFail($user);
    }
}