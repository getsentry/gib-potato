<?php
declare(strict_types=1);

namespace App\Command;

use App\Http\SlackClient;
use App\Model\Entity\User;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\I18n\FrozenTime;
use Sentry\CheckInStatus;
use Sentry\MonitorConfig;
use Sentry\MonitorSchedule;
use function Sentry\captureCheckIn;

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
     * @return null|void|int The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        $io->out('Sending out Weekly Report');

        $checkInId = captureCheckIn(
            slug: 'weekly-report',
            status: CheckInStatus::inProgress(),
            monitorConfig: new MonitorConfig(
                schedule: MonitorSchedule::crontab('15 23 * * 5'),
                checkinMargin: 60,
                maxRuntime: 320,
                timezone: 'UTC',
            ),
        );

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
                'created >=' => new FrozenTime('1 week ago'),
            ]);

        $reivedCountQuery = $messagesTable->find()
            ->select([
                'amount' => $messagesTable->find()->func()->sum('amount'),
            ])
            ->where([
                'receiver_user_id = Users.id',
                'created >=' => new FrozenTime('1 week ago'),
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
            ->group(['Users.id'])
            ->order(['sent_count' => $topSendersQuery->newExpr('DESC NULLS LAST')])
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
            ->group(['Users.id'])
            ->order(['received_count' => $topReceiversQuery->newExpr('DESC NULLS LAST')])
            ->limit(5)
            ->enableAutoFields(true);

        $channelMessage = '🥔 *Weekly Potato Report* 🥔';
        $channelMessage .= PHP_EOL . PHP_EOL;
        $channelMessage .= 'This weeks top taters are';
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

        captureCheckIn(
            slug: 'weekly-report',
            status: CheckInStatus::ok(),
            checkInId: $checkInId,
        );

        $io->success("\n[DONE]");
    }
}
