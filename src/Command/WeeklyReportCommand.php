<?php
declare(strict_types=1);

namespace App\Command;

use App\Http\SlackClient;
use App\Model\Entity\User;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\I18n\DateTime;
use Sentry\MonitorConfig;
use Sentry\MonitorSchedule;
use function Cake\Core\env;
use function Sentry\withMonitor;

/**
 * WeeklyReport command.
 */
class WeeklyReportCommand extends Command
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
            'channel' => ['help' => 'The channel to post the message to', 'required' => false],
        ]);

        return $parser;
    }

    /**
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null|void The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        withMonitor(
            slug: 'weekly-report',
            callback: fn() => $this->_execute($args, $io),
            monitorConfig: new MonitorConfig(
                schedule: new MonitorSchedule(
                    type: MonitorSchedule::TYPE_CRONTAB,
                    value: '15 23 * * 5',
                ),
                checkinMargin: 5,
                maxRuntime: 10,
                timezone: 'UTC',
            ),
        );
    }

    /**
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null|void The exit code or null for success
     */
    protected function _execute(Arguments $args, ConsoleIo $io)
    {
        $io->out('Sending out Weekly Report');

        $channel = $args->getArgument('channel') ?? env('POTATO_CHANNEL');

        $usersTable = $this->fetchTable('Users');
        $messagesTable = $this->fetchTable('Messages');

        $slackClient = new SlackClient();

        $sentCountQuery = $messagesTable->find()
            ->select([
                'amount' => $messagesTable->find()->func()->sum('amount'),
            ])
            ->where([
                'sender_user_id = Users.id',
                'created >=' => new DateTime('1 week ago'),
            ]);

        $reivedCountQuery = $messagesTable->find()
            ->select([
                'amount' => $messagesTable->find()->func()->sum('amount'),
            ])
            ->where([
                'receiver_user_id = Users.id',
                'created >=' => new DateTime('1 week ago'),
            ]);

        $topSendersQuery = $usersTable->find();
        $topSenders = $topSendersQuery
            ->select([
                'sent_count' => $sentCountQuery,
            ])
            ->leftJoinWith('MessagesSent')
            ->where([
                'Users.slack_is_bot' => false,
                'Users.status' => User::STATUS_ACTIVE,
                'Users.role !=' => User::ROLE_SERVICE,
            ])
            ->groupBy(['Users.id'])
            ->orderBy(['sent_count' => $topSendersQuery->expr('DESC NULLS LAST')])
            ->limit(5)
            ->enableAutoFields(true);

        $topReceiversQuery = $usersTable->find();
        $topReceivers = $topReceiversQuery
            ->select([
                'received_count' => $reivedCountQuery,
            ])
            ->leftJoinWith('MessagesSent')
            ->where([
                'Users.slack_is_bot' => false,
                'Users.status' => User::STATUS_ACTIVE,
                'Users.role !=' => User::ROLE_SERVICE,
            ])
            ->groupBy(['Users.id'])
            ->orderBy(['received_count' => $topReceiversQuery->expr('DESC NULLS LAST')])
            ->limit(5)
            ->enableAutoFields(true);

        $channelMessage = '🥔 *Weekly Potato Report* 🥔';
        $channelMessage .= PHP_EOL . PHP_EOL;
        $channelMessage .= 'This week\'s top taters are';
        $channelMessage .= PHP_EOL . PHP_EOL;

        foreach ($topSenders as $index => $user) {
            $channelMessage .= '*#' . $index + 1 . '* <@' . $user->slack_user_id . '> - They did gib out *' .
                $user->sent_count . '* potato';
            $channelMessage .= PHP_EOL;
        }

        $channelMessage .= PHP_EOL . PHP_EOL;

        foreach ($topReceivers as $index => $user) {
            $channelMessage .= '*#' . $index + 1 . '* <@' . $user->slack_user_id . '> - They did receive *' .
                $user->received_count . '* potato';
            $channelMessage .= PHP_EOL;
        }

        $channelMessage .= PHP_EOL;
        $channelMessage .= 'Until next week 👋';

        $slackClient->postMessage(
            channel: $channel,
            text: $channelMessage,
        );

        $io->success("\n[DONE]");
    }
}
