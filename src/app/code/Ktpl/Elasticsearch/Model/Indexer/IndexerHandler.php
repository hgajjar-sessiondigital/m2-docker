<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Ktpl\Elasticsearch\Model\Indexer;

use Magento\Eav\Model\Config;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Indexer\SaveHandler\IndexerInterface;
use Magento\Framework\Search\Request\Dimension;
use Magento\Framework\Search\Request\IndexScopeResolverInterface;
use Magento\Framework\Indexer\SaveHandler\Batch;
use Magento\Framework\Indexer\ScopeResolver\IndexScopeResolver;

class IndexerHandler implements IndexerInterface
{
    /**
     * @var IndexStructure
     */
    private $indexStructure;

    /**
     * @var array
     */
    private $data;

    /**
     * @var array
     */
    private $fields;

    /**
     * @var \Ktpl\Elasticsearch\Model\Resource\Client
     */
    private $client;

    /**
     * @var Batch
     */
    private $batch;

    /**
     * @var Config
     */
    private $eavConfig;

    /**
     * @var int
     */
    private $batchSize;

    /**
     * @var IndexScopeResolverInterface
     */
    private $indexScopeResolver;

    /**
     * @param IndexStructure $indexStructure
     * @param \Ktpl\Elasticsearch\Model\Resource\Client $client
     * @param Config $eavConfig
     * @param Batch $batch
     * @param \Magento\Framework\Indexer\ScopeResolver\IndexScopeResolver $indexScopeResolver
     * @param array $data
     * @param int $batchSize
     */
    public function __construct(
        IndexStructure $indexStructure,
        \Ktpl\Elasticsearch\Model\Resource\Client $client,
        Config $eavConfig,
        Batch $batch,
        IndexScopeResolver $indexScopeResolver,
        array $data,
        $batchSize = 100
    ) {
        $this->indexScopeResolver = $indexScopeResolver;
        $this->indexStructure = $indexStructure;
        $this->client = $client->getConnection();
        $this->batch = $batch;
        $this->eavConfig = $eavConfig;
        $this->data = $data;
        $this->fields = [];

        $this->prepareFields();
        $this->batchSize = $batchSize;
    }

    /**
     * {@inheritdoc}
     */
    public function saveIndex($dimensions, \Traversable $documents)
    {
        foreach ($this->batch->getItems($documents, $this->batchSize) as $batchDocuments) {
            $this->insertDocuments($batchDocuments, $dimensions);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteIndex($dimensions, \Traversable $documents)
    {
        foreach ($this->batch->getItems($documents, $this->batchSize) as $batchDocuments) {
            $data['index'] = $this->indexScopeResolver->resolve('catalogsearch_elasticsearch', $dimensions);
            $data['type'] = 'product';
            $data['id'] = implode($batchDocuments,',');

            try {
                $this->client->delete($data);
            } catch (\Elasticsearch\Common\Exceptions\Missing404Exception $e) {
                //silently exit
            }

        }
    }

    /**
     * {@inheritdoc}
     */
    public function cleanIndex($dimensions)
    {
        $this->indexStructure->delete($this->getIndexName(), $dimensions);
        $this->indexStructure->create($this->getIndexName(), $dimensions);
    }

    /**
     * {@inheritdoc}
     */
    public function isAvailable()
    {
        return true;
    }

    /**
     * @param Dimension[] $dimensions
     * @return string
     */
    private function getTableName($dimensions)
    {
        return $this->indexScopeResolver->resolve($this->getIndexName(), $dimensions);
    }

    /**
     * @return string
     */
    private function getIndexName()
    {
        return $this->data['indexer_id'];
    }

    /**
     * @param array $documents
     * @param Dimension[] $dimensions
     * @return void
     */
    private function insertDocuments(array $documents, array $dimensions)
    {
        // $documents = $this->prepareSearchableFields($documents);
        if (empty($documents)) {
            return;
        }

        $data['index'] = $this->indexScopeResolver->resolve('catalogsearch_elasticsearch', $dimensions);
        $data['type'] = 'product';
        foreach($documents as $document) {
            $data['body'] = $document;
            $data['id'] = $document['entity_id'];
            $this->client->index($data);
        }
    }

    /**
     * @param array $documents
     * @return array
     */
    private function prepareSearchableFields(array $documents)
    {
        $insertDocuments = [];
        foreach ($documents as $entityId => $document) {
            foreach ($document as $attributeId => $fieldValue) {
                $insertDocuments[$entityId . '_' . $attributeId] = [
                    'entity_id' => $entityId,
                    'attribute_id' => $attributeId,
                    'data_index' => $fieldValue,
                ];
            }
        }

        return $insertDocuments;
    }

    /**
     * @return void
     */
    private function prepareFields()
    {
        foreach ($this->data['fieldsets'] as $fieldset) {
            $this->fields = array_merge($this->fields, $fieldset['fields']);
        }
    }
}
