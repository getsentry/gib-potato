<?php
declare(strict_types=1);

namespace App\Command;

use App\Http\SlackClient;
use App\Model\Entity\Message;
use App\Model\Entity\User;
use Cake\Chronos\Chronos;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use DateTimeZone;
use Sentry\MonitorConfig;
use Sentry\MonitorSchedule;
use Sentry\SentrySdk;
use Sentry\Tracing\SpanContext;
use Sentry\Tracing\SpanStatus;
use Sentry\Tracing\TransactionContext;
use Sentry\Tracing\TransactionSource;
use Throwable;
use function Cake\Core\env;
use function Sentry\captureException;
use function Sentry\startTransaction;
use function Sentry\withMonitor;

class BirthdayCommand extends Command
{
    private const int TARGET_HOUR = 10;
    private const int BIRTHDAY_POTATO_AMOUNT = 5;

    /**
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);

        return $parser;
    }

    /**
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null|void
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        $transactionContext = TransactionContext::make()
            ->setOp('command')
            ->setName('COMMAND birthday')
            ->setSource(TransactionSource::task());

        $transaction = startTransaction($transactionContext);

        SentrySdk::getCurrentHub()->setSpan($transaction);

        try {
            withMonitor(
                slug: 'birthday',
                callback: fn() => $this->_execute($args, $io),
                monitorConfig: new MonitorConfig(
                    schedule: new MonitorSchedule(
                        type: MonitorSchedule::TYPE_CRONTAB,
                        value: '0 * * * *',
                    ),
                    checkinMargin: 30,
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
     * @return void
     */
    protected function _execute(Arguments $args, ConsoleIo $io): void
    {
        $io->out('Checking for birthdays');

        $noonTimeZones = $this->_getNoonTimeZones();
        if (empty($noonTimeZones)) {
            $io->out('No timezones at noon. No birthdays to celebrate.');
            $io->success("\n[DONE]");

            return;
        }

        $usersTable = $this->fetchTable('Users');

        $localDate = new Chronos(timezone: $noonTimeZones[0]);
        $users = $usersTable->find()
            ->where([
                'Users.slack_is_bot' => false,
                'Users.status' => User::STATUS_ACTIVE,
                'Users.role !=' => User::ROLE_SERVICE,
                //'Users.slack_time_zone IN' => $noonTimeZones,
                'Users.birthday_day' => $localDate->day,
                'Users.birthday_month' => $localDate->month,
                'Users.hub IS NOT' => null,
            ])
            ->all();

        if ($users->isEmpty()) {
            $io->out('No birthdays to celebrate right now.');
            $io->success("\n[DONE]");

            return;
        }

        $messagesTable = $this->fetchTable('Messages');
        $slackClient = new SlackClient();

        $gibPotatoUserId = $usersTable->findBySlackUserId(env('POTATO_SLACK_USER_ID'))->first()->id;

        $transaction = SentrySdk::getCurrentHub()->getSpan();

        foreach ($users as $user) {
            $hubChannel = env('HUB_CHANNEL_' . strtoupper($user->hub));
            if ($hubChannel === null) {
                $io->out('No channel configured for hub: ' . $user->hub);
                continue;
            }

            $spanContext = SpanContext::make()
                ->setOp('birthday.celebrate')
                ->setDescription('Celebrate birthday for ' . $user->slack_name);
            $span = $transaction->startChild($spanContext);

            SentrySdk::getCurrentHub()->setSpan($span);

            try {
                $message = $messagesTable->newEntity([
                    'sender_user_id' => $gibPotatoUserId,
                    'receiver_user_id' => $user->id,
                    'amount' => self::BIRTHDAY_POTATO_AMOUNT,
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

                $channelMessage = 'Happy Birthday <@' . $user->slack_user_id . '>! :tada: :birthday:' . PHP_EOL;
                $channelMessage .= 'We gib you *' . self::BIRTHDAY_POTATO_AMOUNT . '* :potato: as a little birthday treat! Have a wonderful day! :sparkles:';

                $slackClient->postMessage(
                    channel: $hubChannel,
                    text: $channelMessage,
                );

                $io->out('Celebrated birthday for ' . $user->slack_name . ' in #' . $user->hub);

                $span->setStatus(SpanStatus::ok());
            } catch (Throwable $e) {
                captureException($e);
                $span->setStatus(SpanStatus::internalError());
            } finally {
                $span->finish();
            }
        }
        SentrySdk::getCurrentHub()->setSpan($transaction);

        $io->success("\n[DONE]");
    }

    /**
     * @return array
     */
    protected function _getNoonTimeZones(): array
    {
        $timeZones = DateTimeZone::listIdentifiers();
        $applicableTimeZones = [];

        foreach ($timeZones as $timezone) {
            $localNow = new Chronos(timezone: $timezone);
            if ($localNow->hour === self::TARGET_HOUR) {
                $applicableTimeZones[] = $timezone;
            }
        }

        return $applicableTimeZones;
    }
}
