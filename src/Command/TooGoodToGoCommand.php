<?php
declare(strict_types=1);

namespace App\Command;

use App\Database\Log\SentryQueryLogger;
use App\Http\SlackClient;
use Cake\Chronos\Chronos;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Datasource\ConnectionManager;
use DateTimeZone;
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
 * TooGoodToGo command.
 */
class TooGoodToGoCommand extends Command
{
    private const int TARGET_HOUR = 16;

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
        withMonitor(
            slug: 'too-good-to-go',
            callback: fn() => $this->_execute($args, $io),
            monitorConfig: new MonitorConfig(
                schedule: new MonitorSchedule(
                    type: MonitorSchedule::TYPE_CRONTAB,
                    value: '30 * * * 1-5',
                ),
                checkinMargin: 10,
                maxRuntime: 15,
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
        $io->out('Sending out Too Good To Go notifications');

        $slackClient = new SlackClient();

        $logger = new SentryQueryLogger();

        $connection = ConnectionManager::get('default');
        $connection->getDriver()->setLogger($logger);

        $transactionContext = TransactionContext::make()
            ->setOp('command')
            ->setName('COMMAND too_good_to_go')
            ->setSource(TransactionSource::task());

        $transaction = startTransaction($transactionContext);

        SentrySdk::getCurrentHub()->setSpan($transaction);

        $usersTable = $this->fetchTable('Users');
        $users = $usersTable->find()
            ->where([
                'slack_time_zone IN' => $this->_getApplicableTimeZones(),
            ])
            ->all();

        foreach ($users as $user) {
            if (
                (
                    !isset($user->notifications['too_good_to_go'])
                    || $user->notifications['too_good_to_go'] !== true
                )
                || $user->potatoLeftToday() <= 0
            ) {
                continue;
            }

            $spanContext = SpanContext::make()
                ->setOp('command')
                ->setDescription('Send notification');
            $span = $transaction->startChild($spanContext);

            SentrySdk::getCurrentHub()->setSpan($span);

            try {
                $message = 'Hallo, just letting you know that you have *' . $user->potatoLeftToday()
                    . '* 🥔 left to gib today 🌱' . PHP_EOL;
                $message .= 'Would be a bummer if they go to waste 😢' . PHP_EOL;
                $message .= 'If someone did something nice today, gib them 🥔😊!';

                $slackClient->postMessage(
                    channel: $user->slack_user_id,
                    text: $message,
                );

                $span->setStatus(SpanStatus::ok());
            } catch (Throwable $e) {
                captureException($e);
                $span->setStatus(SpanStatus::internalError());
            } finally {
                $span->finish();
            }
        }
        SentrySdk::getCurrentHub()->setSpan($transaction);

        $transaction->setStatus(SpanStatus::ok())
            ->finish();

        $io->success("\n[DONE]");
    }

    /**
     * @return array
     */
    protected function _getApplicableTimeZones(): array
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
