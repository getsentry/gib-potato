<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * PollOptionsTable Model
 *
 * @property \App\Model\Table\PollsTable&\Cake\ORM\Association\BelongsTo $Polls
 * @property \App\Model\Table\PollResponsesTable&\Cake\ORM\Association\HasMany $PollResponses
 * @method \App\Model\Entity\PollOption newEmptyEntity()
 * @method \App\Model\Entity\PollOption newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\PollOption[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\PollOption get($primaryKey, $options = [])
 * @method \App\Model\Entity\PollOption findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\PollOption patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\PollOption[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\PollOption|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\PollOption saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\PollOption[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\PollOption[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\PollOption[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\PollOption[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class PollOptionsTable extends Table
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

        $this->setTable('poll_options');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Polls', [
            'foreignKey' => 'poll_id',
            'joinType' => 'INNER',
        ]);
        $this->hasMany('PollResponses', [
            'foreignKey' => 'poll_option_id',
            'dependent' => true,
            'cascadeCallbacks' => true,
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
            ->integer('poll_id')
            ->notEmptyString('poll_id');

        $validator
            ->scalar('title')
            ->maxLength('title', 1024)
            ->requirePresence('title', 'create')
            ->notEmptyString('title');

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
        $rules->add($rules->existsIn('poll_id', 'Polls'), ['errorField' => 'poll_id']);

        return $rules;
    }
}
