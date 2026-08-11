<?php
declare(strict_types=1);

namespace App\Event;

use App\Event\Validation\Exception\PotatoException;
use App\Event\Validation\Validation;
use App\Service\AwardService;
use App\Service\NotificationService;
use App\Service\UserService;
use App\Service\VoucherService;

class ReactionAddedEvent extends AbstractEvent
{
    public int $amount;
    public string $sender;
    public array $receivers;
    public string $channel;
    public string $text;
    public string $reaction;
    public string $timestamp;
    public string $permalink;

    public ?string $threadTimestamp;

    /**
     * Constructor
     *
     * @param array $event Event data.
     */
    public function __construct(array $event)
    {
        parent::__construct();

        $this->type = self::TYPE_REACTION_ADDED;
        $this->amount = $event['amount'];
        $this->sender = $event['sender'];
        $this->receivers = $event['receivers'];
        $this->channel = $event['channel'];
        $this->text = $event['text'];
        $this->reaction = $event['reaction'];
        $this->timestamp = $event['timestamp'];
        $this->eventTimestamp = $event['event_timestamp'];
        $this->permalink = $event['permalink'];

        $this->threadTimestamp = $event['thread_timestamp'] ?? null;
    }

    public function isVoucher(): bool
    {
        return $this->reaction === ':admission_tickets:';
    }

    /**
     * @inheritDoc
     */
    public function process(): void
    {
        if ($this->isVoucher()) {
            $this->processVoucher();

            return;
        }

        $this->processPotato();
    }

    protected function processPotato(): void
    {
        $userService = new UserService();
        $awardService = new AwardService();
        $notificationService = new NotificationService();

        $fromUser = $userService->getOrCreateUser($this->sender);
        $validator = new Validation(
            event: $this,
            sender: $fromUser,
        );

        try {
            $validator
                ->amount()
                ->receivers()
                ->sender();
        } catch (PotatoException $e) {
            $this->slackClient->postEphemeral(
                channel: $this->channel,
                user: $this->sender,
                text: $e->getMessage(),
                threadTimestamp: $this->threadTimestamp,
            );

            return;
        }

        $toUsers = [];
        foreach ($this->receivers as $receiver) {
            $toUser = $userService->getOrCreateUser($receiver);
            $toUsers[] = $toUser;
        }

        $awardService->gib(
            fromUser: $fromUser,
            toUsers: $toUsers,
            event: $this,
        );
        $notificationService->notifyUsers(
            fromUser: $fromUser,
            toUsers: $toUsers,
            event: $this,
        );
    }

    protected function processVoucher(): void
    {
        $userService = new UserService();
        $voucherService = new VoucherService();

        $fromUser = $userService->getOrCreateUser($this->sender);
        $validator = new Validation(
            event: $this,
            sender: $fromUser,
        );

        try {
            $validator
                ->voucherAmount()
                ->receivers()
                ->voucherSender();
        } catch (PotatoException $e) {
            $this->slackClient->postEphemeral(
                channel: $this->channel,
                user: $this->sender,
                text: $e->getMessage(),
                threadTimestamp: $this->threadTimestamp,
            );

            return;
        }

        $toUsers = [];
        foreach ($this->receivers as $receiver) {
            $toUser = $userService->getOrCreateUser($receiver);
            $toUsers[] = $toUser;
        }

        $voucherService->gib(
            fromUser: $fromUser,
            toUsers: $toUsers,
            event: $this,
        );

        $toUserNames = [];
        foreach ($toUsers as $toUser) {
            $toUserNames[] = sprintf('<@%s>', $toUser->slack_user_id);
        }

        if ($fromUser->notifications['vouchers'] === true) {
            $vouchersLeft = $fromUser->vouchersLeftToday();

            $gibMessage = sprintf(
                'You did gib *%s* :admission_tickets: to %s. It will be redeemed at midnight.',
                count($toUserNames),
                implode(', ', $toUserNames),
            );
            $gibMessage .= PHP_EOL;
            $gibMessage .= sprintf(
                'You have *%s* :admission_tickets: left today.',
                $vouchersLeft,
            );
            $gibMessage .= PHP_EOL;
            $gibMessage .= sprintf('> %s', $this->permalink);

            $this->slackClient->postMessage(
                channel: $fromUser->slack_user_id,
                text: $gibMessage,
            );
        }
    }
}
