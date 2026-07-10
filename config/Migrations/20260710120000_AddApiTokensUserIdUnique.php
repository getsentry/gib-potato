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
        // Remove duplicate tokens per user, keeping the most recently used
        // (falling back to the most recently created) one.
        $this->execute(
            'DELETE FROM api_tokens WHERE id NOT IN (
                SELECT DISTINCT ON (user_id) id FROM api_tokens
                ORDER BY user_id, last_used DESC NULLS LAST, created DESC
            )',
        );

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
