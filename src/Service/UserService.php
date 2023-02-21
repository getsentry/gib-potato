<?php
declare(strict_types=1);

namespace App\Service;

use App\Http\SlackClient;
use App\Model\Entity\User;
use App\Model\Table\UsersTable;
use Cake\ORM\Locator\LocatorAwareTrait;
use Exception;

class UserService
{
    use LocatorAwareTrait;

    protected SlackClient $slackClient;
    protected UsersTable $Users;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->slackClient = new SlackClient();
        $this->Users = $this->fetchTable('Users');
    }

    /**
     * @param string $slackUserId Slack user ID.
     * @return \App\Model\Entity\User|null
     * @throws \Exception
     */
    public function getOrCreateUser(string $slackUserId): ?User
    {
        $user = $this->Users
            ->findBySlackUserId($slackUserId)
            ->contain('Progression')
            ->first();

        if ($user instanceof User) {
            return $user;
        }

        $slackUser = $this->slackClient->getUser($slackUserId);
        if (empty($slackUser)) {
            throw new Exception('Slack API: User not found');
        }

        $user = $this->Users->newEntity([
            'status' => User::STATUS_ACTIVE,
            'role' => User::ROLE_USER,
            'slack_user_id' => $slackUser['id'],
            'slack_name' => $slackUser['real_name'],
            'slack_picture' => $slackUser['profile']['image_72'],
            'slack_is_bot' => $slackUser['is_bot'] ?? false,
        ], [
            'accessibleFields' => [
                'status' => true,
                'role' => true,
                'slack_user_id' => true,
                'slack_name' => true,
                'slack_picture' => true,
                'slack_is_bot' => true,
            ],
        ]);

        return $this->Users->saveOrFail($user);
    }
}
