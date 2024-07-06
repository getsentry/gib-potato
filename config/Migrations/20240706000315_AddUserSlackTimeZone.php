<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddUserSlackTimeZone extends AbstractMigration
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
        $this->table('users')
            ->addColumn('slack_time_zone', 'string', [
                'after' => 'progression_id',
                'default' => null,
                'length' => 255,
                'null' => true,
            ])
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
        $this->table('users')
            ->removeColumn('slack_time_zone')
            ->update();
    }
}
