<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * QuickWins Model
 *
 * @method \App\Model\Entity\QuickWin newEmptyEntity()
 * @method \App\Model\Entity\QuickWin newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\QuickWin> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\QuickWin get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\QuickWin findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\QuickWin patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\QuickWin> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\QuickWin|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\QuickWin saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\QuickWin>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\QuickWin>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\QuickWin>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\QuickWin> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\QuickWin>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\QuickWin>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\QuickWin>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\QuickWin> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class QuickWinsTable extends Table
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

        $this->setTable('quick_wins');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Users', [
            'foreignKey' => 'sender_user_id',
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
            ->uuid('sender_user_id')
            ->notEmptyString('sender_user_id');

        $validator
            ->scalar('message')
            ->allowEmptyString('message');

        $validator
            ->scalar('permalink')
            ->maxLength('permalink', 255)
            ->requirePresence('permalink', 'create')
            ->notEmptyString('permalink');

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
        $rules->add($rules->existsIn(['sender_user_id'], 'Users'), ['errorField' => 'sender_user_id']);

        return $rules;
    }
}
