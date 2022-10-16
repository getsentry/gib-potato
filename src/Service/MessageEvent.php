<?php
declare(strict_types=1);

namespace App\Service;

class MessageEvent extends AbstractEvent
{
    protected array $eventData;

    public function __construct(array $eventData)
    {
        parent::__construct();

        $this->eventData = $eventData;
    }

    public function process()
    {
        $messageText = $this->eventData['text'];
        $messageFromUser = $this->eventData['user'];
        $messageTimeStamp = $this->eventData['ts'];
        $messageChannel = $this->eventData['channel'];

        // Check for supported emoji in the message
        if (MessageUtility::validateMessage($messageText) === false) {
            return;
        }

        // RegEx the <@U.....> out of the message
        $messageTextUsers = [];
        preg_match_all('/<@U.*?>/', $messageText, $output);

        $messageTextUsers = $output[0];

        // Remove duplicates
        $messageTextUsers = array_values(array_unique($messageTextUsers));

        // Strip <@ > from the slack user id strings
        $messageTextUsers = array_map(
            function ($userString) {
                $userString = str_replace('<@', '', $userString);
                $userString = str_replace('>', '', $userString);

                return $userString;
            },
            $messageTextUsers
        );

        $type = MessageUtility::parseType($messageText);
        if ($type === false) {
            $this->slackClient->postEphemeral(
                $messageChannel,
                $messageFromUser,
                'Sorry, but you can only use one type to gib 😣'
            );

            return;
        }

        $amount = MessageUtility::parseAmount($messageText);
        if ($amount === false) {
            $this->slackClient->postEphemeral(
                $messageChannel,
                $messageFromUser,
                sprintf('Not enough :%s: left to gib... 😥', $type),
            );

            return;
        }

        // The message does not contain any user mentions
        if (empty($messageTextUsers)) {
            // @FIXME Might be spammy
            $this->slackClient->postEphemeral(
                $messageChannel,
                $messageFromUser,
                sprintf('You have to @mention someone to gib :%s:', $type),
            );

            return;
        }

        // @FIXME add method to validate avail stuff to gib

        // Award the reaction to the users mentioned in the message
        foreach ($messageTextUsers as $messageTextUser) {
            // The message author mentioned themselves in their message, blame them!
            if ($messageFromUser === $messageTextUser) {
                $this->slackClient->postEphemeral(
                    $messageChannel,
                    $messageFromUser,
                    sprintf('You can\'t gib yourself :%s:! 🤨', $type, $type),
                );

                continue;
            }

            $result = (new AwardService())->gib(
                fromSlackUserId: $messageFromUser,
                toSlackUserId: $messageTextUser,
                amount: $amount,
                type: $type,
            );

            if ($result === false) {
                $this->slackClient->postEphemeral(
                    $messageChannel,
                    $messageFromUser,
                    sprintf('Not enoguh :%s: left to gib to <@%s>... 😥', $type, $messageTextUser),
                );
            }
        }
    }
}
