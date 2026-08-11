<?php
declare(strict_types=1);

namespace App\Command;

use App\Http\SlackClient;
use App\Model\Entity\Message;
use App\Model\Entity\Voucher;
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
use function Sentry\captureException;
use function Sentry\logger;
use function Sentry\startTransaction;
use function Sentry\trace_metrics;
use function Sentry\withMonitor;

class RedeemVouchersCommand extends Command
{
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
            ->setName('COMMAND redeem_vouchers')
            ->setSource(TransactionSource::task());

        $transaction = startTransaction($transactionContext);

        SentrySdk::getCurrentHub()->setSpan($transaction);

        try {
            withMonitor(
                slug: 'redeem-vouchers',
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
        $io->out('Redeeming potato vouchers');

        $midnightTimeZones = $this->_getMidnightTimeZones();
        if (empty($midnightTimeZones)) {
            $io->out('No timezones at midnight. No vouchers to redeem.');
            $io->success("\n[DONE]");

            return;
        }

        $usersTable = $this->fetchTable('Users');
        $senderUsers = $usersTable->find()
            ->where([
                'slack_time_zone IN' => $midnightTimeZones,
            ])
            ->all()
            ->toArray();

        if (empty($senderUsers)) {
            $io->out('No users in midnight timezones.');
            $io->success("\n[DONE]");

            return;
        }

        $senderUserIds = array_map(fn($u) => $u->id, $senderUsers);

        $vouchersTable = $this->fetchTable('Vouchers');
        $vouchers = $vouchersTable->find()
            ->where([
                'Vouchers.sender_user_id IN' => $senderUserIds,
                'Vouchers.status' => Voucher::STATUS_PENDING,
            ])
            ->contain(['SenderUsers', 'ReceiverUsers'])
            ->all();

        if ($vouchers->isEmpty()) {
            $io->out('No pending vouchers to redeem.');
            $io->success("\n[DONE]");

            return;
        }

        $slackClient = new SlackClient();
        $messagesTable = $this->fetchTable('Messages');
        $transaction = SentrySdk::getCurrentHub()->getSpan();

        foreach ($vouchers as $voucher) {
            $spanContext = SpanContext::make()
                ->setOp('voucher.redeem')
                ->setDescription('Redeem voucher');
            $span = $transaction->startChild($spanContext);

            SentrySdk::getCurrentHub()->setSpan($span);

            try {
                $message = $messagesTable->newEntity([
                    'sender_user_id' => $voucher->sender_user_id,
                    'receiver_user_id' => $voucher->receiver_user_id,
                    'amount' => 1,
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

                $voucher = $vouchersTable->patchEntity($voucher, [
                    'status' => Voucher::STATUS_REDEEMED,
                ], [
                    'accessibleFields' => [
                        'status' => true,
                    ],
                ]);
                $vouchersTable->saveOrFail($voucher);

                if ($voucher->sender_user->notifications['vouchers'] === true) {
                    $slackClient->postMessage(
                        channel: $voucher->sender_user->slack_user_id,
                        text: sprintf(
                            'Your :admission_tickets: to <@%s> has been redeemed. They received *1* :potato:!' . PHP_EOL . '> %s',
                            $voucher->receiver_user->slack_user_id,
                            $voucher->permalink,
                        ),
                    );
                }

                trace_metrics()->count(
                    'gibpotato.vouchers.redeemed',
                    1.0,
                );

                logger()->info(
                    message: 'Redeemed voucher from "%s" to "%s"',
                    values: [
                        $voucher->sender_user->slack_name,
                        $voucher->receiver_user->slack_name,
                    ],
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

        $io->success("\n[DONE]");
    }

    /**
     * @return array
     */
    protected function _getMidnightTimeZones(): array
    {
        $timeZones = DateTimeZone::listIdentifiers();
        $applicableTimeZones = [];

        foreach ($timeZones as $timezone) {
            $localNow = new Chronos(timezone: $timezone);
            if ($localNow->hour === 0) {
                $applicableTimeZones[] = $timezone;
            }
        }

        return $applicableTimeZones;
    }
}
