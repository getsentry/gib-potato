<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Users Model
 *
 * @method \App\Model\Entity\User newEmptyEntity()
 * @method \App\Model\Entity\User newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\User[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\User get($primaryKey, $options = [])
 * @method \App\Model\Entity\User findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\User patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\User[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\User|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\User saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\User[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\User[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\User[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\User[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class UsersTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('users');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->getSchema()->setColumnType('notifications', 'json');

        $this->hasMany('MessagesSent', [
            'className' => 'Messages',
        ])
            ->setForeignKey('sender_user_id');
        $this->hasMany('MessagesReceived', [
            'className' => 'Messages',
        ])
            ->setForeignKey('receiver_user_id');

        $this->hasMany('Polls')
            ->setForeignKey('user_id');
        $this->hasMany('PollResponses')
            ->setForeignKey('user_id');

        $this->belongsTo('Progression', [
            'className' => 'Progression',
        ])
            ->setForeignKey('progression_id');

        $this->hasOne('ApiTokens')
            ->setForeignKey('user_id');
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('progression_id')
            ->allowEmptyString('progression_id');

        $validator
            ->scalar('status')
            ->maxLength('status', 255)
            ->requirePresence('status', 'create')
            ->notEmptyString('status');

        $validator
            ->scalar('role')
            ->maxLength('role', 255)
            ->requirePresence('role', 'create')
            ->notEmptyString('role');

        $validator
            ->scalar('slack_user_id')
            ->maxLength('slack_user_id', 255)
            ->requirePresence('slack_user_id', 'create')
            ->notEmptyString('slack_user_id')
            ->add('slack_user_id', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->scalar('slack_name')
            ->maxLength('slack_name', 255)
            ->requirePresence('slack_name', 'create')
            ->notEmptyString('slack_name');

        $validator
            ->scalar('slack_picture')
            ->maxLength('slack_picture', 255)
            ->requirePresence('slack_picture', 'create')
            ->notEmptyString('slack_picture');

        $validator
            ->boolean('slack_is_bot')
            ->allowEmptyString('slack_is_bot');

        $validator
            ->allowEmptyString('notifications');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['slack_user_id']), ['errorField' => 'slack_user_id']);

        $rules->add($rules->existsIn('progression_id', 'Progression'), ['errorField' => 'progression_id']);

        return $rules;
    }
}
