<?php
declare(strict_types=1);

namespace App\Service;

use App\Event\ReactionAddedEvent;
use App\Model\Entity\User;
use App\Model\Entity\Voucher;
use Cake\ORM\Locator\LocatorAwareTrait;
use Sentry\SentrySdk;
use function Sentry\logger;
use function Sentry\trace_metrics;

class VoucherService
{
    use LocatorAwareTrait;

    /**
     * @param \App\Model\Entity\User $fromUser User who gave the voucher.
     * @param array<\App\Model\Entity\User> $toUsers Users who will receive the voucher.
     * @param \App\Event\ReactionAddedEvent $event The event.
     * @return void
     */
    public function gib(
        User $fromUser,
        array $toUsers,
        ReactionAddedEvent $event,
    ): void {
        $vouchersTable = $this->fetchTable('Vouchers');

        foreach ($toUsers as $toUser) {
            $voucher = $vouchersTable->newEntity([
                'sender_user_id' => $fromUser->id,
                'receiver_user_id' => $toUser->id,
                'channel' => $event->channel,
                'timestamp' => $event->timestamp,
                'permalink' => $event->permalink,
                'status' => Voucher::STATUS_PENDING,
            ], [
                'accessibleFields' => [
                    'sender_user_id' => true,
                    'receiver_user_id' => true,
                    'channel' => true,
                    'timestamp' => true,
                    'permalink' => true,
                    'status' => true,
                ],
            ]);
            $vouchersTable->saveOrFail($voucher);

            $span = SentrySdk::getCurrentHub()->getSpan();
            if ($span !== null) {
                $span->setData([
                    'gibpotato.vouchers.given_out' => 1,
                    'gibpotato.event_type' => $event->type,
                ]);
            }
            trace_metrics()->count(
                'gibpotato.vouchers.given_out',
                1.0,
                [
                    'gibpotato.event_type' => $event->type,
                ],
            );

            logger()->info(
                message: '"%s" gave "%s" a potato voucher 🎟️',
                values: [
                    $fromUser->slack_name,
                    $toUser->slack_name,
                ],
            );
        }
    }

    /**
     * @param \App\Model\Entity\User $fromUser User who removed the reaction.
     * @param string $channel The channel.
     * @param string $timestamp The message timestamp.
     * @return void
     */
    public function cancel(
        User $fromUser,
        string $channel,
        string $timestamp,
    ): void {
        $vouchersTable = $this->fetchTable('Vouchers');

        $vouchers = $vouchersTable->find()
            ->where([
                'sender_user_id' => $fromUser->id,
                'channel' => $channel,
                'timestamp' => $timestamp,
                'status' => Voucher::STATUS_PENDING,
            ])
            ->all();

        foreach ($vouchers as $voucher) {
            $vouchersTable->deleteOrFail($voucher);

            logger()->info(
                message: '"%s" cancelled a potato voucher 🎟️',
                values: [
                    $fromUser->slack_name,
                ],
            );
        }
    }
}
