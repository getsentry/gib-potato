<?php
declare(strict_types=1);

namespace App\Service;

use App\Http\SlackClient;
use App\Model\Entity\User;
use App\Model\Table\ApiTokensTable;
use App\Model\Table\UsersTable;
use Cake\ORM\Locator\LocatorAwareTrait;
use Exception;
use function Cake\Core\env;

class UserService
{
    use LocatorAwareTrait;

    protected SlackClient $slackClient;
    protected UsersTable $Users;
    protected ApiTokensTable $ApiTokens;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->slackClient = new SlackClient();
        $this->Users = $this->fetchTable('Users');
        $this->ApiTokens = $this->fetchTable('ApiTokens');
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

        $user = $this->Users->saveOrFail($user);

        $this->ApiTokens->generateApiToken($user);

        $this->sendWelcomeNotification($user);

        return $user;
    }
    
    /**
     * Get or create multiple users in a batch operation
     *
     * @param array $slackUserIds Array of Slack user IDs.
     * @return array Map of slack_user_id => User entity
     * @throws \Exception
     */
    public function getOrCreateUsers(array $slackUserIds): array
    {
        if (empty($slackUserIds)) {
            return [];
        }
        
        // Fetch existing users in one query
        $existingUsers = $this->Users->find()
            ->where(['slack_user_id IN' => $slackUserIds])
            ->contain('Progression')
            ->all()
            ->toList();
            
        // Map existing users by their Slack user ID
        $usersMap = [];
        foreach ($existingUsers as $user) {
            $usersMap[$user->slack_user_id] = $user;
        }
        
        // Identify which users are missing and need to be created
        $missingSlackUserIds = array_diff($slackUserIds, array_keys($usersMap));
        
        if (!empty($missingSlackUserIds)) {
            $newUsersData = [];
            $createdUsers = [];
            
            // Fetch user details from Slack for all missing users
            foreach ($missingSlackUserIds as $slackUserId) {
                $slackUser = $this->slackClient->getUser($slackUserId);
                if (empty($slackUser)) {
                    continue; // Skip if user not found in Slack
                }
                
                $newUsersData[] = [
                    'status' => User::STATUS_ACTIVE,
                    'role' => User::ROLE_USER,
                    'slack_user_id' => $slackUser['id'],
                    'slack_name' => $slackUser['real_name'],
                    'slack_picture' => $slackUser['profile']['image_72'],
                    'slack_is_bot' => $slackUser['is_bot'] ?? false,
                ];
            }
            
            if (!empty($newUsersData)) {
                // Create multiple user entities at once
                $entities = $this->Users->newEntities($newUsersData, [
                    'accessibleFields' => [
                        'status' => true,
                        'role' => true,
                        'slack_user_id' => true,
                        'slack_name' => true,
                        'slack_picture' => true,
                        'slack_is_bot' => true,
                    ],
                ]);
                
                // Save all entities in a batch
                $savedEntities = $this->Users->saveMany($entities);
                
                // Generate API tokens and send welcome notifications for new users
                foreach ($savedEntities as $user) {
                    $this->ApiTokens->generateApiToken($user);
                    $this->sendWelcomeNotification($user);
                    $createdUsers[$user->slack_user_id] = $user;
                }
                
                // Fetch all created users with Progression to ensure they have the same data structure as existing users
                if (!empty($createdUsers)) {
                    $newUserIds = array_map(function ($user) {
                        return $user->id;
                    }, $createdUsers);
                    
                    $refreshedUsers = $this->Users->find()
                        ->where(['id IN' => $newUserIds])
                        ->contain('Progression')
                        ->all();
                        
                    foreach ($refreshedUsers as $user) {
                        $usersMap[$user->slack_user_id] = $user;
                    }
                }
            }
        }
        
        return $usersMap;
    }

    /**
     * @param \App\Model\Entity\User $user The user.
     * @return void
     */
    protected function sendWelcomeNotification(User $user): void
    {
        $welcomeMessage = 'Hello there 👋' . PHP_EOL;
        $welcomeMessage .= PHP_EOL;
        $welcomeMessage .= '*Welcome to GibPotato!*' . PHP_EOL;
        $welcomeMessage .= PHP_EOL;
        $welcomeMessage .= ' - Every day, you get five 🥔' . PHP_EOL;
        $welcomeMessage .= ' - You can gib them to people as a token of appreciation. '
            . 'Simply @ mention them and add a 🥔 to your message.' . PHP_EOL;
        $welcomeMessage .= ' - Alternatively, you can also react to a message with a 🥔. '
            . 'They either go to the people mentioned in the message or, '
            . 'if nobody was mentioned, to the author of the message.' . PHP_EOL;
        $welcomeMessage .= PHP_EOL;
        $welcomeMessage .= 'Hope you\'ll enjoy using GibPotato. '
            . 'Make sure to join <#' . env('POTATO_CHANNEL') . '> as well.';

        $this->slackClient->postMessage(
            channel: $user->slack_user_id,
            text: $welcomeMessage,
        );
    }
}
