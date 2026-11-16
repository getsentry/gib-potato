<?php
declare(strict_types=1);

namespace App\Command;

use App\Model\Entity\User;
use App\Service\ProgressionService;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Sentry\MonitorConfig;
use Sentry\MonitorSchedule;
use function Sentry\withMonitor;

/**
 * Progression command.
 */
class ProgressionCommand extends Command
{
    /**
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return void
     */
    public function execute(Arguments $args, ConsoleIo $io): void
    {
        withMonitor(
            slug: 'progression',
            callback: fn() => $this->_execute($args, $io),
            monitorConfig: new MonitorConfig(
                schedule: new MonitorSchedule(
                    type: MonitorSchedule::TYPE_CRONTAB,
                    value: '13 * * * *',
                ),
                checkinMargin: 30,
                maxRuntime: 15,
                timezone: 'UTC',
            ),
        );
    }

    /**
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return void
     */
    protected function _execute(Arguments $args, ConsoleIo $io): void
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
