<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * SharePrices Model
 *
 * @property \App\Model\Table\StocksTable&\Cake\ORM\Association\BelongsTo $Stocks
 *
 * @method \App\Model\Entity\SharePrice newEmptyEntity()
 * @method \App\Model\Entity\SharePrice newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\SharePrice> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\SharePrice get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\SharePrice findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\SharePrice patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\SharePrice> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\SharePrice|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\SharePrice saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\SharePrice>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\SharePrice>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\SharePrice>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\SharePrice> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\SharePrice>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\SharePrice>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\SharePrice>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\SharePrice> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class SharePricesTable extends Table
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

        $this->setTable('share_prices');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Stocks', [
            'foreignKey' => 'stock_id',
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
            ->allowEmptyString('stock_id');

        $validator
            ->integer('price')
            ->allowEmptyString('price');

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

        return $rules;
    }
}
