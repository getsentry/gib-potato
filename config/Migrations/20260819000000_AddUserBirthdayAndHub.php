<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddUserBirthdayAndHub extends BaseMigration
{
    /**
     * Up Method.
     *
     * @return void
     */
    public function up(): void
    {
        $this->table('users')
            ->addColumn('birthday_day', 'integer', [
                'after' => 'slack_is_bot',
                'default' => null,
                'null' => true,
            ])
            ->addColumn('birthday_month', 'integer', [
                'after' => 'birthday_day',
                'default' => null,
                'null' => true,
            ])
            ->addColumn('hub', 'string', [
                'after' => 'birthday_month',
                'default' => null,
                'length' => 255,
                'null' => true,
            ])
            ->update();
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
    {
        $this->table('users')
            ->removeColumn('birthday_day')
            ->removeColumn('birthday_month')
            ->removeColumn('hub')
            ->update();
    }
}
