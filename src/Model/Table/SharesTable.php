<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Shares Model
 *
 * @property \App\Model\Table\StocksTable&\Cake\ORM\Association\BelongsTo $Stocks
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 *
 * @method \App\Model\Entity\Share newEmptyEntity()
 * @method \App\Model\Entity\Share newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Share> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Share get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Share findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Share patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Share> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Share|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Share saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Share>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Share>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Share>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Share> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Share>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Share>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Share>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Share> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class SharesTable extends Table
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

        $this->setTable('shares');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Stocks', [
            'foreignKey' => 'stock_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
        ]);
        $this->hasMany('Trades', [
            'foreignKey' => 'share_id',
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
            ->integer('stock_id')
            ->notEmptyString('stock_id');

        $validator
            ->uuid('user_id')
            ->allowEmptyString('user_id');

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
        $rules->add($rules->existsIn(['stock_id'], 'Stocks'), ['errorField' => 'stock_id']);
        $rules->add($rules->existsIn(['user_id'], 'Users'), ['errorField' => 'user_id']);

        return $rules;
    }
}
