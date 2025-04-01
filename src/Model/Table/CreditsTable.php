<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Credits Model
 *
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 *
 * @method \App\Model\Entity\Credit newEmptyEntity()
 * @method \App\Model\Entity\Credit newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Credit> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Credit get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Credit findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Credit patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Credit> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Credit|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Credit saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Credit>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Credit>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Credit>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Credit> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Credit>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Credit>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Credit>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Credit> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class CreditsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('credits');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
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
            ->uuid('user_id')
            ->notEmptyString('user_id');

        $validator
            ->integer('amount')
            ->requirePresence('amount', 'create')
            ->notEmptyString('amount');

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
        $rules->add($rules->existsIn(['user_id'], 'Users'), ['errorField' => 'user_id']);

        return $rules;
    }
}
