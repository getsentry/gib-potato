<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Utility\Hash;

class ReactionAddedEvent extends AbstractEvent
{
    protected array $eventData;

    public function __construct(array $eventData)
    {
        parent::__construct();

        $this->eventData = $eventData;
    }

    public function process()
    {
        $reaction = $this->eventData['reaction'];
        $reactionFromUser = $this->eventData['user'];
        $reactionMessageFromUser = $this->eventData['item_user'];
        $reactionMessageTimeStamp = $this->eventData['item']['ts'];
        $reactionMessageChannel = $this->eventData['item']['channel'];

        // Check for supported reactions
        if (MessageUtility::validateReaction($reaction) === false) {
            return;
        }

        $message = $this->slackClient->getSlackMessage(
            $reactionMessageChannel,
            $reactionMessageTimeStamp
        );

        // The message someone reacted to could not be found
        if (empty($message)) {
            return;
        }

        $reactionMessageTextUsers = [];
        $reactionMessageText = $message['text'];

        preg_match_all('/<@s.*?>/', $reactionMessageText, $output);
        $reactionMessageTextUsers = $output[0];

        //
        if (empty($reactionMessageTextUsers) && $reactionMessageFromUser === $reactionFromUser) {
            $this->slackClient->postEphemeral(
                $reactionMessageChannel,
                $reactionFromUser,
                'You cannot gib yourself potato! 🤨'
            );

            return;
        }

        // dlog($message);
        // dlog($reaction);
        // dlog($reactionFromUser);
        // dlog($reactionMessageFromUser);
        // dlog($reactionMessageTimeStamp);
        // dlog($reactionMessageChannel);
    }
}