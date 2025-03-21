<?php
declare(strict_types=1);

namespace App\Command;

use App\Http\SlackClient;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Sentry\MonitorConfig;
use Sentry\MonitorSchedule;
use function Sentry\withMonitor;

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
        withMonitor(
            slug: 'send-tater-tuesday-message',
            callback: fn() => $this->_execute($args, $io),
            monitorConfig: new MonitorConfig(
                schedule: new MonitorSchedule(
                    type: MonitorSchedule::TYPE_CRONTAB,
                    value: '0 18 * * 2',
                ),
                checkinMargin: 5,
                maxRuntime: 10,
                timezone: 'UTC',
            ),
        );
    }

    /**
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null|void The exit code or null for success
     */
    protected function _execute(Arguments $args, ConsoleIo $io)
    {
        $message = $args->getArgument('message');
        $channel = $args->getArgument('channel');

        $io->out(sprintf('Sending message "%s" to channel "%s"', $message, $channel));

        $slackClient = new SlackClient();

        $slackClient->postMessage(
            channel: $channel,
            text: $message,
        );

        $io->success("\n[DONE]");
    }
}
