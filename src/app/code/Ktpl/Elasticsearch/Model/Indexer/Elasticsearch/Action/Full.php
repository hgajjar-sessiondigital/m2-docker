<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Ktpl\Elasticsearch\Model\Indexer\Elasticsearch\Action;

use Ktpl\Elasticsearch\Model\Indexer\Elasticsearch;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Full
{
    /**
     * Scope identifier
     */
    const SCOPE_FIELD_NAME = 'scope';

    /**
     * Searchable attributes cache
     *
     * @var \Magento\Eav\Model\Entity\Attribute[]
     */
    protected $searchableAttributes;

    /**
     * Index values separator
     *
     * @var string
     */
    protected $separator = ' | ';

    /**
     * Array of \DateTime objects per store
     *
     * @var \DateTime[]
     */
    protected $dates = [];

    /**
     * Product Type Instances cache
     *
     * @var array
     */
    protected $productTypes = [];

    /**
     * Product Emulators cache
     *
     * @var array
     */
    protected $productEmulators = [];

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory
     */
    protected $productAttributeCollectionFactory;

    /**
     * Catalog product status
     *
     * @var \Magento\Catalog\Model\Product\Attribute\Source\Status
     */
    protected $catalogProductStatus;

    /**
     * Eav config
     *
     * @var \Magento\Eav\Model\Config
     */
    protected $eavConfig;

    /**
     * Catalog product type
     *
     * @var \Magento\Catalog\Model\Product\Type
     */
    protected $catalogProductType;

    /**
     * Catalog product repository
     *
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * Catalog product Factory
     *
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;

    /**
     * Core event manager proxy
     *
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\CatalogSearch\Model\ResourceModel\Engine
     */
    protected $engine;

    /**
     * @var \Magento\Framework\IndexerInterface
     */
    protected $indexHandler;

    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    protected $dateTime;

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $localeResolver;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $localeDate;

    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var \Magento\CatalogSearch\Model\ResourceModel\Fulltext
     */
    protected $fulltextResource;

    /**
     * @var \Magento\Framework\Search\Request\Config
     */
    protected $searchRequestConfig;

    /**
     * @var \Magento\Framework\Search\Request\DimensionFactory
     */
    private $dimensionFactory;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $connection;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    protected $catalogHelper;

    /**
     * @param ResourceConnection $resource
     * @param \Magento\Catalog\Model\Product\Type $catalogProductType
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
     * @param \Magento\Catalog\Model\ProductFactory $productFactory,
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\Framework\Search\Request\Config $searchRequestConfig
     * @param \Magento\Catalog\Model\Product\Attribute\Source\Status $catalogProductStatus
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $productAttributeCollectionFactory
     * @param \Magento\CatalogSearch\Model\ResourceModel\EngineProvider $engineProvider
     * @param \Ktpl\Elasticsearch\Model\Indexer\IndexerHandlerFactory $indexHandlerFactory
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param \Magento\Framework\Locale\ResolverInterface $localeResolver
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\CatalogSearch\Model\ResourceModel\Fulltext $fulltextResource
     * @param \Magento\Framework\Search\Request\DimensionFactory $dimensionFactory
     * @param \Magento\Framework\Indexer\ConfigInterface $indexerConfig
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ResourceConnection $resource,
        \Magento\Catalog\Model\Product\Type $catalogProductType,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Framework\Search\Request\Config $searchRequestConfig,
        \Magento\Catalog\Model\Product\Attribute\Source\Status $catalogProductStatus,
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $productAttributeCollectionFactory,
        \Magento\CatalogSearch\Model\ResourceModel\EngineProvider $engineProvider,
        \Ktpl\Elasticsearch\Model\Indexer\IndexerHandlerFactory $indexHandlerFactory,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\CatalogSearch\Model\ResourceModel\Fulltext $fulltextResource,
        \Magento\Framework\Search\Request\DimensionFactory $dimensionFactory,
        \Magento\Framework\Indexer\ConfigInterface $indexerConfig,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Catalog\Helper\Product $catalogHelper
    ) {
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
        $this->catalogProductType = $catalogProductType;
        $this->productRepository = $productRepository;
        $this->productFactory = $productFactory;
        $this->eavConfig = $eavConfig;
        $this->searchRequestConfig = $searchRequestConfig;
        $this->catalogProductStatus = $catalogProductStatus;
        $this->productAttributeCollectionFactory = $productAttributeCollectionFactory;
        $this->eventManager = $eventManager;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->engine = $engineProvider->get();
        $configData = $indexerConfig->getIndexer(Elasticsearch::INDEXER_ID);
        $this->indexHandler = $indexHandlerFactory->create(['data' => $configData]);
        $this->dateTime = $dateTime;
        $this->localeResolver = $localeResolver;
        $this->localeDate = $localeDate;
        $this->fulltextResource = $fulltextResource;
        $this->dimensionFactory = $dimensionFactory;
        $this->priceCurrency = $priceCurrency;
        $this->catalogHelper = $catalogHelper;
    }

    /**
     * Rebuild whole fulltext index for all stores
     *
     * @return void
     */
    public function reindexAll()
    {
        $storeIds = array_keys($this->storeManager->getStores());
        foreach ($storeIds as $storeId) {
            $this->cleanIndex($storeId);
            $this->rebuildStoreIndex($storeId);
        }
        $this->searchRequestConfig->reset();
    }

    /**
     * Return validated table name
     *
     * @param string|string[] $table
     * @return string
     */
    protected function getTable($table)
    {
        return $this->resource->getTableName($table);
    }

    /**
     * Regenerate search index for all stores
     *
     * @param int|array|null $productIds
     * @return void
     */
    protected function rebuildIndex($productIds = null)
    {
        $storeIds = array_keys($this->storeManager->getStores());
        foreach ($storeIds as $storeId) {
            $dimension = $this->dimensionFactory->create(['name' => self::SCOPE_FIELD_NAME, 'value' => $storeId]);
            $this->indexHandler->deleteIndex([$dimension], $this->getIterator($productIds));
            $this->indexHandler->saveIndex(
                [$dimension],
                $this->rebuildStoreIndex($storeId, $productIds)
            );
        }
        $this->fulltextResource->resetSearchResults();
        $this->searchRequestConfig->reset();
    }

    /**
     * Get parents IDs of product IDs to be re-indexed
     *
     * @param int[] $entityIds
     * @return int[]
     */
    protected function getProductIdsFromParents(array $entityIds)
    {
        return $this->connection
            ->select()
            ->from($this->getTable('catalog_product_relation'), 'parent_id')
            ->distinct(true)
            ->where('child_id IN (?)', $entityIds)
            ->where('parent_id NOT IN (?)', $entityIds)
            ->query()
            ->fetchAll(\Zend_Db::FETCH_COLUMN);
    }

    /**
     * Regenerate search index for specific store
     *
     * @param int $storeId Store View Id
     * @param int|array $productIds Product Entity Id
     * @return \Generator
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function rebuildStoreIndex($storeId, $productIds = null)
    {
        if ($productIds !== null) {
            $productIds = array_unique(array_merge($productIds, $this->getProductIdsFromParents($productIds)));
        }
        // prepare searchable attributes
        $staticFields = [];
        foreach ($this->getSearchableAttributes('static') as $attribute) {
            $staticFields[] = $attribute->getAttributeCode();
        }
        $dynamicFields = [
            'int' => array_keys($this->getSearchableAttributes('int')),
            'varchar' => array_keys($this->getSearchableAttributes('varchar')),
            'text' => array_keys($this->getSearchableAttributes('text')),
            'decimal' => array_keys($this->getSearchableAttributes('decimal')),
            'datetime' => array_keys($this->getSearchableAttributes('datetime')),
        ];

        // status and visibility filter
        $visibility = $this->getSearchableAttribute('visibility');
        $status = $this->getSearchableAttribute('status');
        $statusIds = $this->catalogProductStatus->getVisibleStatusIds();
        $allowedVisibility = $this->engine->getAllowedVisibility();

        $lastProductId = 0;
        $count = 0;
        while (true) {
            $products = $this->getSearchableProducts($storeId, $staticFields, $productIds, $lastProductId);
            if (!$products) {
                break;
            }

            $productAttributes = [];
            $productRelations = [];
            foreach ($products as $productData) {
                $lastProductId = $productData['entity_id'];
                $productAttributes[$productData['entity_id']] = $productData['entity_id'];
                $productChildren = $this->getProductChildIds($productData['entity_id'], $productData['type_id']);
                $productRelations[$productData['entity_id']] = $productChildren;
                if ($productChildren) {
                    foreach ($productChildren as $productChildId) {
                        $productAttributes[$productChildId] = $productChildId;
                    }
                }
            }

            $productAttributes = $this->getProductAttributes($storeId, $productAttributes, $dynamicFields);

            foreach ($products as $i=>$productData) {
                if (!isset($productAttributes[$productData['entity_id']])) {
                    continue;
                }

                $productAttr = $productAttributes[$productData['entity_id']];
                if (!isset($productAttr[$visibility->getAttributeCode()])
                    || !in_array($productAttr[$visibility->getAttributeCode()], $allowedVisibility)
                ) {
                    continue;
                }
                if (!isset($productAttr[$status->getAttributeCode()])
                    || !in_array($productAttr[$status->getAttributeCode()], $statusIds)
                ) {
                    continue;
                }

                if (count($productRelations[$productData['entity_id']]) > 0)
                $productAttr['variants'] = count($productRelations[$productData['entity_id']]);

                $productIndex = [$productData['entity_id'] => $productAttr];

                // $hasChildren = false;
                // $productChildren = $productRelations[$productData['entity_id']];
                // if ($productChildren) {
                //     foreach ($productChildren as $productChildId) {
                //         if (isset($productAttributes[$productChildId])) {
                //             $productChildAttr = $productAttributes[$productChildId];
                //             if (!isset($productChildAttr[$status->getAttributeCode()])
                //                 || !in_array($productChildAttr[$status->getAttributeCode()], $statusIds)
                //             ) {
                //                 continue;
                //             }
                //
                //             $hasChildren = true;
                //             $productIndex[$productChildId] = $productChildAttr;
                //         }
                //     }
                // }
                // if ($productChildren !== null && !$hasChildren) {
                //     continue;
                // }

                $index = $this->prepareProductIndex($productIndex, $productData, $storeId);
                $index['entity_id'] = $productData['entity_id'];

                yield $count => $index;
                $count++;
            }
        }
    }

    /**
     * Retrieve searchable products per store
     *
     * @param int $storeId
     * @param array $staticFields
     * @param array|int $productIds
     * @param int $lastProductId
     * @param int $limit
     * @return array
     */
    protected function getSearchableProducts(
        $storeId,
        array $staticFields,
        $productIds = null,
        $lastProductId = 0,
        $limit = 100
    ) {
        $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();
        $select = $this->connection->select()
            ->useStraightJoin(true)
            ->from(
                ['e' => $this->getTable('catalog_product_entity')],
                array_merge(['entity_id', 'type_id'], $staticFields)
            )
            ->join(
                ['website' => $this->getTable('catalog_product_website')],
                $this->connection->quoteInto('website.product_id = e.entity_id AND website.website_id = ?', $websiteId),
                []
            );

        if ($productIds !== null) {
            $select->where('e.entity_id IN (?)', $productIds);
        }

        $select->where('e.entity_id > ?', $lastProductId)->limit($limit)->order('e.entity_id');

        $result = $this->connection->fetchAll($select);

        return $result;
    }

    /**
     * Clean search index data for store
     *
     * @param int $storeId
     * @return void
     */
    protected function cleanIndex($storeId)
    {
        $dimension = $this->dimensionFactory->create(['name' => self::SCOPE_FIELD_NAME, 'value' => $storeId]);
        $this->indexHandler->cleanIndex([$dimension]);
    }

    /**
     * Delete search index data for store
     *
     * @param int $storeId Store View Id
     * @param array $productIds Product Entity Id
     * @return void
     */
    protected function deleteIndex($storeId = null, $productIds = null)
    {
        $dimension = $this->dimensionFactory->create(['name' => self::SCOPE_FIELD_NAME, 'value' => $storeId]);
        $this->indexHandler->deleteIndex([$dimension], $this->getIterator($productIds));
    }

    /**
     * Retrieve EAV Config Singleton
     *
     * @return \Magento\Eav\Model\Config
     */
    protected function getEavConfig()
    {
        return $this->eavConfig;
    }

    /**
     * Retrieve searchable attributes
     *
     * @param string $backendType
     * @return \Magento\Eav\Model\Entity\Attribute[]
     */
    public function getSearchableAttributes($backendType = null)
    {
        if (null === $this->searchableAttributes) {
            $this->searchableAttributes = [];

            $productAttributes = $this->productAttributeCollectionFactory->create();
            $productAttributes->addToIndexFilter(true);

            /** @var \Magento\Eav\Model\Entity\Attribute[] $attributes */
            $attributes = $productAttributes->getItems();

            $this->eventManager->dispatch(
                'catelogsearch_searchable_attributes_load_after',
                ['engine' => $this->engine, 'attributes' => $attributes]
            );

            $entity = $this->getEavConfig()->getEntityType(\Magento\Catalog\Model\Product::ENTITY)->getEntity();

            foreach ($attributes as $attribute) {
                $attribute->setEntity($entity);
            }

            $this->searchableAttributes = $attributes;
        }

        if ($backendType !== null) {
            $attributes = [];
            foreach ($this->searchableAttributes as $attributeId => $attribute) {
                if ($attribute->getBackendType() == $backendType) {
                    $attributes[$attributeId] = $attribute;
                }
            }

            return $attributes;
        }

        return $this->searchableAttributes;
    }

    /**
     * Retrieve searchable attribute by Id or code
     *
     * @param int|string $attribute
     * @return \Magento\Eav\Model\Entity\Attribute
     */
    protected function getSearchableAttribute($attribute)
    {
        $attributes = $this->getSearchableAttributes();
        if (is_numeric($attribute)) {
            if (isset($attributes[$attribute])) {
                return $attributes[$attribute];
            }
        } elseif (is_string($attribute)) {
            foreach ($attributes as $attributeModel) {
                if ($attributeModel->getAttributeCode() == $attribute) {
                    return $attributeModel;
                }
            }
        }

        return $this->getEavConfig()->getAttribute(\Magento\Catalog\Model\Product::ENTITY, $attribute);
    }

    /**
     * Returns expression for field unification
     *
     * @param string $field
     * @param string $backendType
     * @return \Zend_Db_Expr
     */
    protected function unifyField($field, $backendType = 'varchar')
    {
        if ($backendType == 'datetime') {
            $expr = $this->connection->getDateFormatSql($field, '%Y-%m-%d %H:%i:%s');
        } else {
            $expr = $field;
        }
        return $expr;
    }

    /**
     * Load product(s) attributes
     *
     * @param int $storeId
     * @param array $productIds
     * @param array $attributeTypes
     * @return array
     */
    protected function getProductAttributes($storeId, array $productIds, array $attributeTypes)
    {
        $result = [];
        $selects = [];
        $ifStoreValue = $this->connection->getCheckSql('t_store.value_id > 0', 't_store.value', 't_default.value');
        foreach ($attributeTypes as $backendType => $attributeIds) {
            if ($attributeIds) {
                $tableName = $this->getTable('catalog_product_entity_' . $backendType);
                $eavTableName = $this->getTable('eav_attribute');
                $eavOptionTableName = $this->getTable('eav_attribute_option');
                $eavOptionValueTableName = $this->getTable('eav_attribute_option_value');
                $selects[] = $this->connection->select()->from(
                    ['t_default' => $tableName],
                    ['entity_id', 'attribute_id']
                )->joinLeft(
                    ['t_store' => $tableName],
                    $this->connection->quoteInto(
                        't_default.entity_id=t_store.entity_id' .
                        ' AND t_default.attribute_id=t_store.attribute_id' .
                        ' AND t_store.store_id = ?',
                        $storeId
                    ),
                    ['value' => $this->unifyField($ifStoreValue, $backendType)]
                )->joinLeft(
                    ['eav' => $eavTableName],
                    't_default.attribute_id=eav.attribute_id',
                    ['attribute_code']
                )->joinLeft(
                    ['eav_option' => $eavOptionTableName],
                    't_default.attribute_id=eav_option.attribute_id',
                    []
                )->joinLeft(
                    ['eav_option_value' => $eavOptionValueTableName],
                    'eav_option_value.option_id=eav_option.option_id',
                    ['real_value' => 'eav_option_value.value']
                )->where(
                    't_default.store_id = ?',
                    0
                )->where(
                    't_default.attribute_id IN (?)',
                    $attributeIds
                )->where(
                    't_default.entity_id IN (?)',
                    $productIds
                );
            }
        }

        if ($selects) {
            $select = $this->connection->select()->union($selects, \Magento\Framework\DB\Select::SQL_UNION_ALL);
            $query = $this->connection->query($select);
            while ($row = $query->fetch()) {
                if (!empty($row['real_value']))
                    $result[$row['entity_id']][$row['attribute_code']] = $row['real_value'];
                else
                    $result[$row['entity_id']][$row['attribute_code']] = $row['value'];
            }
        }

        return $result;
    }

    /**
     * Retrieve Product Type Instance
     *
     * @param string $typeId
     * @return \Magento\Catalog\Model\Product\Type\AbstractType
     */
    protected function getProductTypeInstance($typeId)
    {
        if (!isset($this->productTypes[$typeId])) {
            $productEmulator = $this->getProductEmulator($typeId);

            $this->productTypes[$typeId] = $this->catalogProductType->factory($productEmulator);
        }
        return $this->productTypes[$typeId];
    }

    /**
     * Return all product children ids
     *
     * @param int $productId Product Entity Id
     * @param string $typeId Super Product Link Type
     * @return array|null
     */
    protected function getProductChildIds($productId, $typeId)
    {
        $typeInstance = $this->getProductTypeInstance($typeId);
        $relation = $typeInstance->isComposite(
            $this->getProductEmulator($typeId)
        ) ? $typeInstance->getRelationInfo() : false;

        if ($relation && $relation->getTable() && $relation->getParentFieldName() && $relation->getChildFieldName()) {
            $select = $this->connection->select()->from(
                ['main' => $this->getTable($relation->getTable())],
                [$relation->getChildFieldName()]
            )->where(
                $relation->getParentFieldName() . ' = ?',
                $productId
            );
            if ($relation->getWhere() !== null) {
                $select->where($relation->getWhere());
            }
            return $this->connection->fetchCol($select);
        }

        return null;
    }

    /**
     * Retrieve Product Emulator (Magento Object)
     *
     * @param string $typeId
     * @return \Magento\Framework\DataObject
     */
    protected function getProductEmulator($typeId)
    {
        if (!isset($this->productEmulators[$typeId])) {
            $productEmulator = new \Magento\Framework\DataObject();
            $productEmulator->setTypeId($typeId);
            $this->productEmulators[$typeId] = $productEmulator;
        }
        return $this->productEmulators[$typeId];
    }

    /**
     * Prepare Fulltext index value for product
     *
     * @param array $indexData
     * @param array $productData
     * @param int $storeId
     * @return string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function prepareProductIndex($indexData, $productData, $storeId)
    {
        $index = [];

        foreach ($this->getSearchableAttributes('static') as $attribute) {
            $attributeCode = $attribute->getAttributeCode();

            if (isset($productData[$attributeCode])) {

                if ('store_id' === $attributeCode) {
                    continue;
                }

                $value = $this->getAttributeValue($attribute->getId(), $productData[$attributeCode], $storeId);
                if ($value) {
                    if (isset($index[$attribute->getAttributeCode()])) {
                        if (!is_array($index[$attribute->getAttributeCode()])) {
                            $index[$attribute->getAttributeCode()] = [$index[$attribute->getAttributeCode()]];
                        }
                        $index[$attribute->getAttributeCode()][] = $value;
                    } else {
                        $index[$attribute->getAttributeCode()] = $value;
                    }
                }
            }
        }

        foreach ($indexData as $entityId => $attributeData) {
            foreach ($attributeData as $attributeCode => $attributeValue) {
                switch ($attributeCode) {
                    case 'price':
                        $value = $attributeValue;
                        break;

                    default:
                        $value = $this->getAttributeValue($attributeCode, $attributeValue, $storeId);
                        break;
                }

                if (!empty($value)) {
                    $index[$attributeCode] = $value;
                }
            }
        }

        // add special attributes
        $product = $this->productRepository->getById($productData['entity_id']);
        $url = $product->getUrlModel()->getUrl($product, array('_escape' => true));
        // url
        $index['url'] = $url;
        // images
        $image = $this->catalogHelper->getImageUrl($product);
        if ($image) {
            $index['image'] = $image;
        }

        // suggestion
        $index['name_suggest'] = array(
            'input' => explode(' ', $index['name']),
            'output' => $index['name']
        );

        // category names
        $catNames = [];
        foreach ($product->getCategoryCollection()->addAttributeToSelect('name')->getItems() as $cat) {
            $catNames[] = $cat['name'];
        }
        // $index['category_names'] = implode(' ',$catNames);
        $index['category_names'] = $catNames;

        // currency
        if (!isset($this->currency[$storeId]))
            $this->currency[$storeId] = $this->priceCurrency->getCurrencySymbol($storeId);
        $index['currency'] = $this->currency[$storeId];

        // variants
        if (isset($indexData[$productData['entity_id']]['variants']))
        $index['variants'] = $indexData[$productData['entity_id']]['variants'];

        unset($product);

        $product = $this->getProductEmulator(
            $productData['type_id']
        )->setId(
            $productData['entity_id']
        )->setStoreId(
            $storeId
        );
        $typeInstance = $this->getProductTypeInstance($productData['type_id']);
        $data = $typeInstance->getSearchableData($product);
        if ($data) {
            $index['options'] = $data;
        }

        return $index;
    }

    /**
     * Retrieve attribute source value for search
     *
     * @param int $attributeId
     * @param mixed $valueId
     * @param int $storeId
     * @return mixed
     */
    protected function getAttributeValue($attributeId, $valueId, $storeId)
    {
        $attribute = $this->getSearchableAttribute($attributeId);
        $value = $this->engine->processAttributeValue($attribute, $valueId);

        if ($attribute->getIsSearchable()
            && $attribute->usesSource()
            && $this->engine->allowAdvancedIndex()
        ) {
            $attribute->setStoreId($storeId);
            $valueText = $attribute->getSource()->getIndexOptionText($valueId);

            if (is_array($valueText)) {
                $value .=  $this->separator . implode($this->separator, $valueText);
            } else {
                $value .= $this->separator . $valueText;
            }
        }

        $value = preg_replace('/\\s+/siu', ' ', trim(strip_tags($value)));

        return $value;
    }

    /**
     * Retrieve Date value for store
     *
     * @param int $storeId
     * @param string $date
     * @return string|null
     */
    protected function getStoreDate($storeId, $date = null)
    {
        if (!isset($this->dates[$storeId])) {
            $timezone = $this->scopeConfig->getValue(
                $this->localeDate->getDefaultTimezonePath(),
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            );

            $this->localeResolver->emulate($storeId);

            $dateObj = new \DateTime();
            $dateObj->setTimezone(new \DateTimeZone($timezone));
            $this->dates[$storeId] = $dateObj;

            $this->localeResolver->revert();
        }

        if (!$this->dateTime->isEmptyDate($date)) {
            /** @var \DateTime $dateObj */
            $dateObj = $this->dates[$storeId];
            return $this->localeDate->formatDateTime($dateObj, \IntlDateFormatter::MEDIUM, \IntlDateFormatter::NONE);
        }

        return null;
    }

    /**
     * Get iterator
     *
     * @param array $data
     * @return \Generator
     */
    protected function getIterator(array $data)
    {
        foreach ($data as $key => $value) {
            yield $key => $value;
        }
    }
}
