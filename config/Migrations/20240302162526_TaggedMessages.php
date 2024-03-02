<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class TaggedMessages extends AbstractMigration
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
        $this->table('tagged_messages', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('message', 'string', [
                'default' => null,
                'limit' => 4096,
                'null' => false,
            ])
            ->addColumn('permalink', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('sender_user_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addIndex(
                [
                    'sender_user_id',
                ]
            )
            ->addIndex(
                [
                    'receiver_user_id',
                ]
            )
            ->addIndex(
                [
                    'permalink',
                ]
            )
            ->create();

        $this->table('messages')
            ->addColumn('permalink', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addIndex(
                [
                    'permalink',
                ]
            )
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
        $this->table('tagged_messages')->drop()->save();

        $this->table('messages')
            ->removeIndexByName('permalink')
            ->removeColumn('permalink')
            ->update();
    }
}
