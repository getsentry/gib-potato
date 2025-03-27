<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Stocks Model
 *
 * @property \App\Model\Table\SharePricesTable&\Cake\ORM\Association\HasMany $SharePrices
 * @property \App\Model\Table\SharesTable&\Cake\ORM\Association\HasMany $Shares
 *
 * @method \App\Model\Entity\Stock newEmptyEntity()
 * @method \App\Model\Entity\Stock newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Stock> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Stock get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Stock findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Stock patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Stock> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Stock|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Stock saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Stock>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Stock>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Stock>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Stock> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Stock>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Stock>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Stock>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Stock> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class StocksTable extends Table
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

        $this->setTable('stocks');
        $this->setDisplayField('symbol');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->hasMany('SharePrices', [
            'foreignKey' => 'stock_id',
        ]);
        $this->hasMany('Shares', [
            'foreignKey' => 'stock_id',
        ]);
        $this->hasMany('Trades', [
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
            ->scalar('symbol')
            ->maxLength('symbol', 255)
            ->requirePresence('symbol', 'create')
            ->notEmptyString('symbol');

        $validator
            ->scalar('description')
            ->maxLength('description', 255)
            ->allowEmptyString('description');

        return $validator;
    }
}
