<?php
declare(strict_types=1);

namespace App\Command;

use App\Http\SlackClient;
use App\Model\Entity\User;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Exception;

/**
 * AnnounceStockMarket command.
 */
class AnnounceStockMarketCommand extends Command
{
    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/5/en/console-commands/commands.html#defining-arguments-and-options
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return parent::buildOptionParser($parser)
            ->setDescription(static::getDescription());
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null|void The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        $io->out('Announcing stock market');

        $configTable = $this->fetchTable('Config');
        $config = $configTable->find()->firstOrFail();
        if ($config->market_initalized === true) {
            throw new Exception('Market already initialized');
        }

        $usersTable = $this->fetchTable('Users');

        $slackClient = new SlackClient();

        $channelMessage = '<!channel> 🚨 *Announcing Sentry Stonks* 🚨' . PHP_EOL;

        $slackClient->postMessage(
            channel: env('POTATO_CHANNEL'),
            text: $channelMessage,
        );

        $io->out('Sent announcement to the GibPotato channel');

        $users = $usersTable->find()
            ->where([
                'Users.slack_is_bot' => false,
                'Users.status' => User::STATUS_ACTIVE,
                'Users.role !=' => User::ROLE_SERVICE,
            ])
            ->all();

        foreach ($users as $user) {
            $message = 'Your stock portfolio is ready 🤑' . PHP_EOL;

            $slackClient->postMessage(
                channel: $user->slack_user_id,
                text: $message,
            );

            $io->out(sprintf(
                'Sent announcement to %s',
                $user->slack_name,
            ));

            // Avoid getting rate limited by Slack
            usleep(200 * 1000); // 200ms
        }

        $io->success("\n[DONE]");
    }
}
