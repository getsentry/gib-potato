<?php
declare(strict_types=1);

namespace App\Command;

use App\Database\Log\SentryQueryLogger;
use App\Http\SlackClient;
use App\Model\Entity\User;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Datasource\ConnectionManager;
use Sentry\MonitorConfig;
use Sentry\MonitorSchedule;
use Sentry\SentrySdk;
use Sentry\Tracing\SpanContext;
use Sentry\Tracing\SpanStatus;
use Sentry\Tracing\TransactionContext;
use Sentry\Tracing\TransactionSource;
use Throwable;
use function Sentry\captureException;
use function Sentry\startTransaction;
use function Sentry\withMonitor;

/**
 * UpdateUsers command.
 */
class UpdateUsersCommand extends Command
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

        return $parser;
    }

    /**
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null|void The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        $transactionContext = TransactionContext::make()
            ->setOp('command')
            ->setName('COMMAND update_users')
            ->setSource(TransactionSource::task());

        $transaction = startTransaction($transactionContext);

        SentrySdk::getCurrentHub()->setSpan($transaction);

        try {
            withMonitor(
                slug: 'update-users',
                callback: fn() => $this->_execute($args, $io),
                monitorConfig: new MonitorConfig(
                    schedule: new MonitorSchedule(
                        type: MonitorSchedule::TYPE_CRONTAB,
                        value: '0 0 * * *',
                    ),
                    checkinMargin: 10,
                    maxRuntime: 15,
                    timezone: 'UTC',
                ),
            );

            $transaction->setStatus(SpanStatus::ok());
        } catch (Throwable $e) {
            $transaction->setStatus(SpanStatus::internalError());
            captureException($e);
        } finally {
            $transaction->finish();
        }
    }

    /**
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null|void The exit code or null for success
     */
    protected function _execute(Arguments $args, ConsoleIo $io)
    {
        $io->out('Updating all users from Slack');

        $slackClient = new SlackClient();

        $logger = new SentryQueryLogger();

        $connection = ConnectionManager::get('default');
        $connection->getDriver()->setLogger($logger);

        $usersTable = $this->fetchTable('Users');
        $users = $usersTable->find()->all();

        $io->comment($users->count() . ' users will be updated');

        /** @var \Cake\Command\Helper\ProgressHelper $progress */
        $progress = $io->helper('Progress');
        $progress->init([
            'total' => $users->count(),
        ]);

        $transaction = SentrySdk::getCurrentHub()->getSpan();

        foreach ($users as $user) {
            $spanContext = SpanContext::make()
                ->setOp('command')
                ->setDescription('Update user');
            $span = $transaction->startChild($spanContext);

            SentrySdk::getCurrentHub()->setSpan($span);

            $slackUser = $slackClient->getUser($user->slack_user_id);

            // Once a user is deleted, the data structure is different
            if ($slackUser['deleted'] === false) {
                $user = $usersTable->patchEntity($user, [
                    'status' => User::STATUS_ACTIVE,
                    'slack_user_id' => $slackUser['id'],
                    'slack_name' => $slackUser['real_name'],
                    'slack_picture' => $slackUser['profile']['image_72'],
                    'slack_time_zone' => $slackUser['tz'],
                    'slack_is_bot' => $slackUser['is_bot'] ?? false,
                ], [
                    'accessibleFields' => [
                        'status' => true,
                        'slack_user_id' => true,
                        'slack_name' => true,
                        'slack_picture' => true,
                        'slack_time_zone' => true,
                        'slack_is_bot' => true,
                    ],
                ]);
            } else {
                $user = $usersTable->patchEntity($user, [
                    'status' => User::STATUS_DELETED,
                    // Demote deleted users to regular users
                    'role' => User::ROLE_USER,
                ], [
                    'accessibleFields' => [
                        'status' => true,
                        'role' => true,
                    ],
                ]);
            }

            try {
                $usersTable->saveOrFail($user);

                $progress->increment(1);
                $progress->draw();

                $span->setStatus(SpanStatus::ok());
            } catch (Throwable $e) {
                captureException($e);
                $span->setStatus(SpanStatus::internalError());
            } finally {
                $span->finish();

                // Avoid getting rate limited by Slack
                usleep(200 * 1000); // 200ms
            }
        }
        SentrySdk::getCurrentHub()->setSpan($transaction);

        $io->success("\n[DONE]");
    }
}
