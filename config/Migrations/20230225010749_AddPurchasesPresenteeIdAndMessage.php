<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddPurchasesPresenteeIdAndMessage extends AbstractMigration
{
    /**
     * Up Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-up-method
     * @return void
     */
    public function up(): void
    {

        $this->table('purchases')
            ->addColumn('presentee_id', 'uuid', [
                'after' => 'user_id',
                'default' => null,
                'length' => null,
                'null' => true,
            ])
            ->addColumn('message', 'string', [
                'after' => 'price',
                'default' => null,
                'length' => 1024,
                'null' => true,
            ])
            ->addIndex(
                [
                    'presentee_id',
                ],
                [
                    'name' => 'presentee_id',
                ]
            )
            ->update();

        $this->table('purchases')
            ->addForeignKey(
                'presentee_id',
                'users',
                'id',
                [
                    'update' => 'NO_ACTION',
                    'delete' => 'NO_ACTION',
                ]
            )
            ->update();
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
        $this->table('purchases')
            ->dropForeignKey(
                'presentee_id'
            )->save();

        $this->table('purchases')
            ->removeIndexByName('presentee_id')
            ->update();

        $this->table('purchases')
            ->removeColumn('presentee_id')
            ->removeColumn('message')
            ->update();
    }
}
