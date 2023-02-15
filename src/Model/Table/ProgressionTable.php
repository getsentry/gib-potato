<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Progression Model
 *
 * @method \App\Model\Entity\Progression newEmptyEntity()
 * @method \App\Model\Entity\Progression newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Progression[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Progression get($primaryKey, $options = [])
 * @method \App\Model\Entity\Progression findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Progression patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Progression[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Progression|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Progression saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Progression[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Progression[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Progression[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Progression[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ProgressionTable extends Table
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

        $this->setTable('progression');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->hasMany('Users', [
            'foreignKey' => 'progression_id',
        ]);
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
            ->scalar('name')
            ->maxLength('name', 255)
            ->allowEmptyString('name');

        $validator
            ->integer('sent_threshold')
            ->requirePresence('sent_threshold', 'create')
            ->notEmptyString('sent_threshold');

        $validator
            ->integer('received_threshold')
            ->requirePresence('received_threshold', 'create')
            ->notEmptyString('received_threshold');

        $validator
            ->scalar('operator')
            ->maxLength('operator', 255)
            ->requirePresence('operator', 'create')
            ->notEmptyString('operator');

        return $validator;
    }
}
