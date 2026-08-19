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

    /**
     * Slackbot is a bot, but the Slack API reports is_bot false for it.
     */
    public const SLACKBOT_USER_ID = 'USLACKBOT';

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
            'slack_email' => $slackUser['profile']['email'] ?? null,
            'slack_picture' => $slackUser['profile']['image_72'],
            'slack_is_bot' => $slackUser['is_bot'] ?? false,
        ], [
            'accessibleFields' => [
                'status' => true,
                'role' => true,
                'slack_user_id' => true,
                'slack_name' => true,
                'slack_email' => true,
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
     * Drops bots from the Slack users mentioned in a message.
     *
     * potal filters out bot *senders* before forwarding an event; bot
     * *receivers* are filtered here, where the slack_is_bot column already
     * answers for everyone we've seen before.
     *
     * Only filters, never creates: a bot must not reach getOrCreateUser(),
     * which would give it a user record, an API token and a welcome message.
     *
     * @param array<string> $slackUserIds Slack user IDs mentioned in the message.
     * @return array<string> The Slack user IDs that belong to people.
     * @throws \Exception
     */
    public function getHumanReceivers(array $slackUserIds): array
    {
        $receivers = [];
        foreach ($slackUserIds as $slackUserId) {
            if ($this->isBot($slackUserId)) {
                continue;
            }

            $receivers[] = $slackUserId;
        }

        return $receivers;
    }

    /**
     * @param string $slackUserId Slack user ID.
     * @return bool
     */
    protected function isBot(string $slackUserId): bool
    {
        // Checked before anything else, because neither the stored flag nor
        // the Slack API marks Slackbot as a bot. potal excludes it as a
        // sender for the same reason (potal/internal/event/message.go).
        if ($slackUserId === self::SLACKBOT_USER_ID) {
            return true;
        }

        $user = $this->Users->findBySlackUserId($slackUserId)->first();
        if ($user instanceof User) {
            // Nullable: users predating the slack_is_bot column keep null
            // until UpdateUsersCommand backfills them. Unknown means human,
            // so we never silently stop gibbing to a long-standing user.
            return $user->slack_is_bot === true;
        }

        // Someone we've never seen before, so ask Slack.
        return $this->slackClient->getUser($slackUserId)['is_bot'] ?? false;
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
