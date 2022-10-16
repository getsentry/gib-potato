<?php
declare(strict_types=1);

namespace App\Service;

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

        // RegEx the <@U.....> out of the message
        $reactionMessageTextUsers = [];
        preg_match_all('/<@U.*?>/', $message['text'], $output);

        $reactionMessageTextUsers = $output[0];

        // Remove duplicates
        $reactionMessageTextUsers = array_values(array_unique($reactionMessageTextUsers));

        // Strip <@ > from the slack user id strings
        $reactionMessageTextUsers = array_map(
            function ($userString) {
                $userString = str_replace('<@', '', $userString);
                $userString = str_replace('>', '', $userString);

                return $userString;
            },
            $reactionMessageTextUsers
        );

        // Remove users that reacted to a message there were mentioned in
        $reactionMessageTextUsers = array_values(array_diff($reactionMessageTextUsers, [$reactionFromUser]));

        // The message someone reacted to does not contain any user mentions
        if (empty($reactionMessageTextUsers)) {
            // The message author reacted to their own message, blame them!
            if ($reactionMessageFromUser === $reactionFromUser) {
                $this->slackClient->postEphemeral(
                    $reactionMessageChannel,
                    $reactionFromUser,
                    sprintf('You cannot gib yourself :%s:! 🤨', $reaction),
                );

                return;
            }

            // Award the reaction to the author of the message
            $result = (new AwardService())->gib(
                fromSlackUserId: $reactionFromUser,
                toSlackUserId: $reactionMessageFromUser,
                amount: 1, // A reaction is always amount 1
                type: $reaction,
            );

            if ($result === false) {
                $this->slackClient->postEphemeral(
                    $reactionMessageChannel,
                    $reactionFromUser,
                    sprintf('Not enoguh :%s: left to gib... 😥', $reaction),
                );
            }

            return;
        }

        // @FIXME add method to validate avail stuff to gib

        // Award the reaction to the users mentioned in the message
        foreach ($reactionMessageTextUsers as $reactionMessageTextUser) {
            $result = (new AwardService())->gib(
                fromSlackUserId: $reactionFromUser,
                toSlackUserId: $reactionMessageTextUser,
                amount: 1, // A reaction is always amount 1
                type: $reaction,
            );

            if ($result === false) {
                $this->slackClient->postEphemeral(
                    $reactionMessageChannel,
                    $reactionFromUser,
                    sprintf('Not enoguh :%s: left to gib... 😥', $reaction),
                );
            }
        }
    }
}
