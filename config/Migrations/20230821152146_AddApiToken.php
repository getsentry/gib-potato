<?php
declare(strict_types=1);

use App\Model\Entity\User;
use Cake\ORM\Locator\LocatorAwareTrait;
use Migrations\AbstractMigration;

use function Sentry\captureException;

class AddApiToken extends AbstractMigration
{
    use LocatorAwareTrait;

    /**
     * Up Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-up-method
     * @return void
     */
    public function up(): void
    {
        $this->table('api_tokens')
            ->addColumn('user_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('token', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('last_used', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->create();

        // $usersTable = $this->fetchTable('Users');
        // $apiTokensTable = $this->fetchTable('ApiTokens');

        // $users = $usersTable->find()
        //     ->where([
        //         'Users.slack_is_bot' => false,
        //         'Users.status' => User::STATUS_ACTIVE,
        //         'Users.role !=' => User::ROLE_SERVICE,
        //     ])
        //     ->all();

        // foreach ($users as $user) {
        //     try {
        //         $apiTokensTable->generateApiToken($user);
        //     } catch (Throwable $e) {
        //         captureException($e);
        //     }
        // }
    }

    /**
     * Down Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-down-method
     * @return void
     */
    public function down(): void
    {
        $this->table('api_tokens')->drop()->save();
    }
}
