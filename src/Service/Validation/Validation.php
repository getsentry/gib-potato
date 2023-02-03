<?php

namespace App\Service\Validation;

use App\Model\Entity\Message;
use App\Model\Entity\User;
use App\Service\Event\MessageEvent;
use App\Service\Event\ReactionAddedEvent;
use App\Service\Validation\Exception\PotatoException;

class Validation
{
    protected MessageEvent|ReactionAddedEvent $event;
    protected User $sender;

    public function __construct(MessageEvent|ReactionAddedEvent $event, User $sender) {
        $this->event = $event;
        $this->sender = $sender;
    }

    public function amount(): self
    {
        if ($this->event->amount > Message::MAX_AMOUNT) {
            throw new PotatoException('You can only gib out *5* potato a day 😢');
        }

        $recieversCount = count($this->event->receivers);
        if ($this->event->amount * $recieversCount > Message::MAX_AMOUNT) {
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
        $sent = $this->sender->potatoSentToday();
        if ($sent >= Message::MAX_AMOUNT) {
            throw new PotatoException('You already gib out all your :potato: today 😢');
        }

        $left = $this->sender->potatoLeftToday();
        if ($this->event->amount > $left) {
            throw new PotatoException(sprintf('You only have *%s* :potato: left to gib today 😢', $left));
        }

        return $this;
    }
}