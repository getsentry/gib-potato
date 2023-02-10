<?php
declare(strict_types=1);

namespace App\Command;

use App\Http\SlackClient;
use App\Model\Entity\Message;
use App\Service\UserService;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

/**
 * ShowAndTell command.
 */
class ShowAndTellCommand extends Command
{
    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/4/en/console-commands/commands.html#defining-arguments-and-options
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);

        $parser->addArguments([
            'users' => ['help' => 'The users to receive 10 🥔', 'required' => true],
            'channel' => ['help' => 'The channel to post the message to', 'required' => false],
        ]);

        return $parser;
    }

    /**
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return null|void|int The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        $channel = $args->getArgument('channel') ?? env('POTATO_CHANNEL');
        $users = explode(';', $args->getArgument('users'));

        $usersTable = $this->fetchTable('Users');
        $messagesTable = $this->fetchTable('Messages');

        $slackClient = new SlackClient();
        $userService = new UserService();

        $gibPotatoUserId = $usersTable->findBySlackUserId(env('POTATO_SLACK_USER_ID'))->first()->id;

        foreach ($users as $user) {
            $user = $userService->getOrCreateUser($user);

            $message = $messagesTable->newEntity([
                'sender_user_id' => $gibPotatoUserId,
                'receiver_user_id' => $user->id,
                'amount' => 10,
                'type' => Message::TYPE_POTATO,
            ], [
                'accessibleFields' => [
                    'sender_user_id' => true,
                    'receiver_user_id' => true,
                    'amount' => true,
                    'type' => true,
                ],
            ]);
            $messagesTable->saveOrFail($message);

            if ($user->notifications['received'] === true) {
                $message = 'Uhhh, look at that, you presented something awesome during Show & Tell 🚀' . PHP_EOL;
                $message .= 'Great stuff, we just sent you *10* 🥔 🙌' . PHP_EOL;
                $message .= 'Thank you for sharing what you\'ve been working on with the team!';

                $slackClient->postMessage(
                    channel: $user->slack_user_id,
                    text: $message,
                );
            }
        }

        $users = array_map(fn ($user) => '<@' . $user . '>', $users);

        $channelMessage = '<!channel> 🚨 *Show & Tell* potato awards are happening 🚨' . PHP_EOL . PHP_EOL;
        $channelMessage .= 'We just gib *10* 🥔 to the following lovely people, saying a lot of thank you for submitting a video' . PHP_EOL . PHP_EOL;
        $channelMessage .= implode(' ', $users) . PHP_EOL . PHP_EOL;
        $channelMessage .= 'Jealous 😏? You know what to do 🎥! Until next time 👋';

        $slackClient->postMessage(
            channel: $channel,
            text: $channelMessage,
        );
    }
}
