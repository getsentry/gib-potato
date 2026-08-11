<?php
declare(strict_types=1);

namespace App\Event;

use App\Service\UserService;
use App\Service\VoucherService;

class ReactionRemovedEvent extends AbstractEvent
{
    public string $sender;
    public string $channel;
    public string $reaction;
    public string $timestamp;

    /**
     * @param array $event Event data.
     */
    public function __construct(array $event)
    {
        parent::__construct();

        $this->type = self::TYPE_REACTION_REMOVED;
        $this->sender = $event['sender'];
        $this->channel = $event['channel'];
        $this->reaction = $event['reaction'];
        $this->timestamp = $event['timestamp'];
        $this->eventTimestamp = $event['event_timestamp'] ?? '';
    }

    /**
     * @inheritDoc
     */
    public function process(): void
    {
        if ($this->reaction !== ':admission_tickets:') {
            return;
        }

        $userService = new UserService();
        $voucherService = new VoucherService();

        $fromUser = $userService->getOrCreateUser($this->sender);

        $voucherService->cancel(
            fromUser: $fromUser,
            channel: $this->channel,
            timestamp: $this->timestamp,
        );
    }
}
