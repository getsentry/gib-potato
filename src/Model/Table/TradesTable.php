<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Trades Model
 *
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \App\Model\Table\SharesTable&\Cake\ORM\Association\BelongsTo $Shares
 *
 * @method \App\Model\Entity\Trade newEmptyEntity()
 * @method \App\Model\Entity\Trade newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Trade> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Trade get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Trade findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Trade patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Trade> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Trade|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Trade saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Trade>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Trade>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Trade>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Trade> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Trade>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Trade>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Trade>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Trade> deleteManyOrFail(iterable $entities, array $options = [])
 */
class TradesTable extends Table
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

        $this->setTable('trades');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Shares', [
            'foreignKey' => 'share_id',
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
            ->integer('user_id')
            ->notEmptyString('user_id');

        $validator
            ->integer('share_id')
            ->notEmptyString('share_id');

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
        $rules->add($rules->existsIn(['share_id'], 'Shares'), ['errorField' => 'share_id']);

        return $rules;
    }
}
