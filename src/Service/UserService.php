<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\User;
use App\Model\Table\UsersTable;
use Cake\ORM\Locator\LocatorAwareTrait;
use Exception;

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
    public function getOrCreateUser(string $slackUserId): ?User
    {
        $slackUser = $this->slackClient->getUser($slackUserId);
        if (empty($slackUser)) {
            throw new Exception('Slack API: User not found');
        }

        $user = $this->Users
            ->findBySlackUserId($slackUser['id'])
            ->first();

        if ($user instanceof User) {
            return $user;
        }

        $user = $this->Users->newEntity([
            'status' => User::STATUS_ACTIVE,
            'slack_user_id' => $slackUser['id'],
            'slack_name' => $slackUser['real_name'],
            'slack_picture' => $slackUser['profile']['image_72'],
            'slack_is_bot' => $slackUser['is_bot'] ?? false,
        ]);

        return $this->Users->saveOrFail($user);
    }
}