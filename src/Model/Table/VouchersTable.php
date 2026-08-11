<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Vouchers Model
 *
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $SenderUsers
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $ReceiverUsers
 * @method \App\Model\Entity\Voucher newEmptyEntity()
 * @method \App\Model\Entity\Voucher newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Voucher get($primaryKey, $options = [])
 * @method \App\Model\Entity\Voucher findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Voucher patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Voucher|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Voucher saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class VouchersTable extends Table
{
    /**
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('vouchers');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('SenderUsers', [
            'className' => 'Users',
            'foreignKey' => 'sender_user_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('ReceiverUsers', [
            'className' => 'Users',
            'foreignKey' => 'receiver_user_id',
            'joinType' => 'INNER',
        ]);
    }

    /**
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->uuid('sender_user_id')
            ->notEmptyString('sender_user_id');

        $validator
            ->uuid('receiver_user_id')
            ->notEmptyString('receiver_user_id');

        $validator
            ->scalar('channel')
            ->maxLength('channel', 255)
            ->requirePresence('channel', 'create')
            ->notEmptyString('channel');

        $validator
            ->scalar('timestamp')
            ->maxLength('timestamp', 255)
            ->requirePresence('timestamp', 'create')
            ->notEmptyString('timestamp');

        $validator
            ->scalar('permalink')
            ->maxLength('permalink', 255)
            ->requirePresence('permalink', 'create')
            ->notEmptyString('permalink');

        $validator
            ->scalar('status')
            ->maxLength('status', 255)
            ->notEmptyString('status');

        return $validator;
    }

    /**
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn('sender_user_id', 'SenderUsers'), ['errorField' => 'sender_user_id']);
        $rules->add($rules->existsIn('receiver_user_id', 'ReceiverUsers'), ['errorField' => 'receiver_user_id']);

        return $rules;
    }
}
