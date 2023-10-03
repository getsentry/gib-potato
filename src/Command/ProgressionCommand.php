<?php
declare(strict_types=1);

namespace App\Command;

use App\Model\Entity\User;
use App\Service\ProgressionService;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

/**
 * Progression command.
 */
class ProgressionCommand extends Command
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

        return $parser;
    }

    /**
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null|void The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        $io->out('Updating progression for all users');

        $usersTable = $this->fetchTable('Users');
        $users = $usersTable->find()
            ->where([
                'Users.slack_is_bot' => false,
                'Users.status' => User::STATUS_ACTIVE,
                'Users.role !=' => User::ROLE_SERVICE,
            ])
            ->all();

        $io->comment($users->count() . ' users will be updated');

        /** @var \Cake\Command\Helper\ProgressHelper $progress */
        $progress = $io->helper('Progress');
        $progress->init([
            'total' => $users->count(),
        ]);

        $progressionService = new ProgressionService();

        foreach ($users as $user) {
            $progressionService->progress($user);

            $progress->increment(1);
            $progress->draw();

            // Avoid getting rate limited by Slack
            usleep(200 * 1000); // 200ms
        }

        $io->success("\n[DONE]");
    }
}
