<?php
declare(strict_types=1);

namespace App\Command;

use App\Http\SlackClient;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

/**
 * SendMessage command.
 */
class SendMessageCommand extends Command
{
    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/4/en/console-commands/commands.html#defining-arguments-and-options
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);

        $parser->addArguments([
            'channel' => ['help' => 'The channel to post the message to', 'required' => true],
            'message' => ['help' => 'The message to be send', 'required' => true],
        ]);

        return $parser;
    }

    /**
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null|void The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        $slackClient = new SlackClient();

        $slackClient->postMessage(
            channel: $args->getArgument('channel'),
            text: $args->getArgument('message')
        );
    }
}
