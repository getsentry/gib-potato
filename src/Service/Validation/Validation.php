<?php

namespace App\Service\Validation;

use App\Model\Entity\Message;
use App\Model\Entity\User;
use App\Service\Event\MessageEvent;
use App\Service\Event\ReactionAddedEvent;
use App\Service\Validation\Exception\PotatoException;
use Cake\I18n\FrozenTime;
use Cake\ORM\Locator\LocatorAwareTrait;

class Validation
{
    use LocatorAwareTrait;

    protected const MAX_AMOUNT = 5;

    protected MessageEvent|ReactionAddedEvent $event;
    protected User $sender;

    public function __construct(MessageEvent|ReactionAddedEvent $event, User $sender) {
        $this->event = $event;
        $this->sender = $sender;
    }

    public function amount(): self
    {
        if ($this->event->amount > self::MAX_AMOUNT) {
            throw new PotatoException('You can only gib out *5* potato a day 😢');
        }

        $recieversCount = count($this->event->receivers);
        if ($this->event->amount * $recieversCount > self::MAX_AMOUNT) {
            throw new PotatoException('Each :potato: is multiplied by the amount of people you @ mention. You can only gib out *5* potato a day 😢');
        }

        return $this;
    }

    public function receivers(): self
    {
        $recieversCount = count($this->event->receivers);

        if ($recieversCount === 0) {
            throw new PotatoException('You need to @ mention someone to gib potato 🧐');
        }

        if ($recieversCount > 5) {
            throw new PotatoException('You can only gib :potato: to *5* people at once 😢');
        }

        if (in_array($this->event->sender, $this->event->receivers)) {
            throw new PotatoException('You can\'t gib potato to yourself 🤔');
        }

        return $this;
    }

    public function sender(): self
    {
        $messagesTable = $this->fetchTable('Messages');

        $query = $messagesTable->find();
        $result = $query
            ->select([
                'given_out' => $query->func()->sum('amount')
            ])
            ->where([
                'sender_user_id' => $this->sender->id,
                'type' => Message::TYPE_POTATO,
                'created >=' => new FrozenTime('24 hours ago'),
            ])
            ->first();

        if ($result->given_out >= self::MAX_AMOUNT) {
            throw new PotatoException('You already gib out all your :potato: today 😢');
        }

        $amountLeftToday = self::MAX_AMOUNT - $result->given_out;
        if ($this->event->amount > $amountLeftToday) {
            throw new PotatoException(sprintf('You only have *%s* :potato: left to gib today 😢', $amountLeftToday));
        }

        return $this;
    }
}