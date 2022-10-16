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

        // The message does not contain any user mentions
        if (empty($messageTextUsers)) {
            $this->slackClient->postEphemeral(
                $messageChannel,
                $messageFromUser,
                'You have to @mention someone to gib',
            );

            return;
        }

        // The message author reacted to their own message, blame them!
        // $reaction = 'fries';
        // if ($messageTextUsers === [$messageFromUser]) {
        //     $this->slackClient->postEphemeral(
        //         $messageChannel,
        //         $messageFromUser,
        //         sprintf('You cannot gib yourself :%s:! 🤨', $reaction),
        //     );
        //
        //     return;
        // }
    }
}
