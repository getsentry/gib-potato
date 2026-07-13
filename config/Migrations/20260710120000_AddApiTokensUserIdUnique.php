<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddApiTokensUserIdUnique extends BaseMigration
{
    /**
     * Up Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-up-method
     *
     * @return void
     */
    public function up(): void
    {
        $this->table('api_tokens')
            ->addIndex(['user_id'], ['unique' => true])
            ->update();
    }

    /**
     * Down Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-down-method
     *
     * @return void
     */
    public function down(): void
    {
        $this->table('api_tokens')
            ->removeIndex(['user_id'])
            ->update();
    }
}
