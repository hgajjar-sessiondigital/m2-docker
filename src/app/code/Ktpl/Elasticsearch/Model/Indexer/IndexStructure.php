<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Ktpl\Elasticsearch\Model\Indexer;

use Magento\Framework\Search\Request\Dimension;
use Magento\Framework\Indexer\ScopeResolver\IndexScopeResolver;

class IndexStructure
{
    /**
     * @var client
     */
    private $client;
    private $indexScopeResolver;

    protected $helper;

    protected $_dateFormat = "yyyy-MM-dd";

    protected $storeManager;

    /**
     * @var array Snowball languages.
     * @link http://www.elasticsearch.org/guide/reference/index-modules/analysis/snowball-tokenfilter.html
     */
    protected $_snowballLanguages = array(
        'Armenian', 'Basque', 'Catalan', 'Danish', 'Dutch', 'English', 'Finnish', 'French',
        'German', 'Hungarian', 'Italian', 'Kp', 'Lovins', 'Norwegian', 'Porter', 'Portuguese',
        'Romanian', 'Russian', 'Spanish', 'Swedish', 'Turkish',
    );

    /**
     * @param IndexScopeResolver $indexScopeResolver
     * @param \Ktpl\Elasticsearch\Model\Resource\Client $client
     */
    public function __construct(
        IndexScopeResolver $indexScopeResolver,
        \Ktpl\Elasticsearch\Model\Resource\Client $client,
        \Ktpl\Elasticsearch\Helper\Elasticsearch $helper,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->indexScopeResolver = $indexScopeResolver;
        $this->client = $client->getConnection();
        $this->helper = $helper;
        $this->storeManager = $storeManager;
    }

    /**
     * @param string $index
     * @param Dimension[] $dimensions
     * @return void
     */
    public function delete($index, array $params)
    {
        $dimensions = $params[1];
        $indexName = $this->indexScopeResolver->resolve($index, [$dimensions]);
        try {
            $this->client->indices()->delete(array('index' => $indexName));
        } catch (\Exception $e) {
            //ignore
        }
    }

    /**
     * @param string $index
     * @param Dimension[] $dimensions
     * @return void
     */
    public function create($index, array $params)
    {
        $searchableAttributes = $params[0];
        $dimensions = $params[1];
        // $this->_prepareIndex($index);
        // $indexName = $this->indexScopeResolver->resolve($index, $dimensions);
        // $this->client->indices()->create(array('index' => $indexName));

        $indexName = $this->indexScopeResolver->resolve($index, [$dimensions]);
        try {
            $indexSettings = $this->_getIndexSettings();
            $indexExists = $this->client->indices()->exists(array('index' => $indexName));

            if (!$indexExists) {
                $mappings = $this->_prepareMappings($searchableAttributes);
                $this->client->indices()->create(
                    array(
                        'index' => $indexName,
                        'body' => array(
                            'settings' => $indexSettings,
                            'mappings' => array(
                                'product' => array(
                                    "_all" => array(
                                        "enabled" => true,
                                        "analyzer" => "edgeNGram_analyzer",
                                        "search_analyzer" => "standard"
                                    ),
                                    'properties' => $mappings
                                )
                            )
                        )
                    )
                );
            } else {
                $this->client->indices()->putSettings(
                    array(
                        'index' => $indexName,
                        'body' => array(
                            'settings' => $indexSettings
                        )
                    )
                );
            }
            // $mapping = new Elastica\Type\Mapping();
            // $mapping->setType($index->getType('product'));
            // $mapping->setProperties($this->_getIndexProperties());
            // $mapping->send();
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            throw $e;
        }
    }

    /**
     * Returns indexation analyzers and filters configuration.
     *
     * @return array
     */
    protected function _getIndexSettings()
    {
        $indexSettings = array();
        $indexSettings['number_of_shards'] = 1;
        $indexSettings['number_of_replicas'] = 0;
        $indexSettings['analysis']['analyzer'] = array(
            'whitespace' => array(
                'tokenizer' => 'standard',
                'filter' => array('lowercase'),
            ),
            'edge_ngram_front' => array(
                'tokenizer' => 'standard',
                'filter' => array('length', 'edge_ngram_front', 'lowercase'),
            ),
            'edge_ngram_back' => array(
                'tokenizer' => 'standard',
                'filter' => array('length', 'edge_ngram_back', 'lowercase'),
            ),
            'shingle' => array(
                'tokenizer' => 'standard',
                'filter' => array('shingle', 'length', 'lowercase'),
            ),
            'shingle_strip_ws' => array(
                'tokenizer' => 'standard',
                'filter' => array('shingle', 'strip_whitespaces', 'length', 'lowercase'),
            ),
            'shingle_strip_apos_and_ws' => array(
                'tokenizer' => 'standard',
                'filter' => array('shingle', 'strip_apostrophes', 'strip_whitespaces', 'length', 'lowercase'),
            ),
            "edgeNGram_analyzer" => array(
               "type" => "custom",
               "tokenizer" => "whitespace",
               "filter" => array(
                  "lowercase",
                  "asciifolding",
                  "edgeNGram_filter"
              )
            )
        );
        $indexSettings['analysis']['filter'] = array(
            'shingle' => array(
                'type' => 'shingle',
                'max_shingle_size' => 20,
                'output_unigrams' => true,
            ),
            'strip_whitespaces' => array(
                'type' => 'pattern_replace',
                'pattern' => '\s',
                'replacement' => '',
            ),
            'strip_apostrophes' => array(
                'type' => 'pattern_replace',
                'pattern' => "'",
                'replacement' => '',
            ),
            'edge_ngram_front' => array(
                'type' => 'edgeNGram',
                'min_gram' => 3,
                'max_gram' => 10,
                'side' => 'front',
            ),
            'edge_ngram_back' => array(
                'type' => 'edgeNGram',
                'min_gram' => 3,
                'max_gram' => 10,
                'side' => 'back',
            ),
            'length' => array(
                'type' => 'length',
                'min' => 2,
            ),
            "edgeNGram_filter" => array(
               "type" => "edgeNGram",
               "min_gram" => 2,
               "max_gram" => 20
            )
        );

        foreach ($this->storeManager->getStores() as $store) {
            $languageCode = $this->helper->getLanguageCodeByStore($store);
            $lang = \Zend_Locale_Data::getContent('en_GB', 'language', $languageCode);
            if (!in_array($lang, $this->_snowballLanguages)) {
                continue; // language not present by default in elasticsearch
            }
            $indexSettings['analysis']['analyzer']['analyzer_' . $languageCode] = array(
                'type' => 'snowball',
                'language' => $lang,
                'filter' => array('length', 'lowercase')
            );
            $indexSettings['analysis']['filter']['snowball_' . $languageCode] = array(
                'type' => 'snowball',
                'language' => $lang,
            );
        }

        // if ($this->isIcuFoldingEnabled()) {
        //     foreach ($indexSettings['analysis']['analyzer'] as &$analyzer) {
        //         array_unshift($analyzer['filter'], 'icu_folding');
        //     }
        //     unset($analyzer);
        // }

        return $indexSettings;
    }

    /**
     * Returns attribute type for indexation.
     *
     * @param Mage_Catalog_Model_Resource_Eav_Attribute $attribute
     * @return string
     */
    protected function _getAttributeType($attribute)
    {
        $type = 'string';
        if ($attribute->getBackendType() == 'decimal') {
            $type = 'double';
        } elseif ($attribute->getBackendType() == 'datetime') {
            $type = 'date';
        } elseif ($attribute->usesSource() || $attribute->getFrontendClass() == 'validate-digits') {
            $type = 'integer';
        }

        return $type;
    }

    protected function _prepareMappings($searchableAttributes)
    {
        $properties = array();

        foreach ($searchableAttributes as $attribute) {
            switch ($attribute->getBackendType()) {
                case 'text':
                    foreach ($this->storeManager->getStores() as $store) {
                        $languageCode = $this->helper->getLanguageCodeByStore($store);
                        $key = $this->helper->getAttributeFieldName($attribute);
                        $weight = $attribute->getSearchWeight();
                        $properties[$key] = array(
                            'type' => 'string',
                            'boost' => $weight > 0 ? $weight : 1,
                            'analyzer' => 'analyzer_' . $languageCode,
                        );
                    }
                case 'static':
                case 'varchar':
                case 'decimal':
                case 'datetime':
                    $key = $this->helper->getAttributeFieldName($attribute);
                    if ($this->helper->_isAttributeIndexable($attribute) && !isset($properties[$key])) {
                        $weight = $attribute->getSearchWeight();
                        $properties[$key] = array(
                            'type' => $this->_getAttributeType($attribute),
                            'boost' => $weight > 0 ? $weight : 1,
                        );
                        if ($attribute->getBackendType() == 'datetime') {
                            $properties[$key]['format'] = $this->_dateFormat;
                        }
                    }
                    break;
            }
        }

        // TODO: Handle sortable attributes

        // Custom attributes indexation
        $properties['visibility'] = array(
            'type' => 'integer',
        );
        $properties['store_id'] = array(
            'type' => 'integer',
        );
        $properties['in_stock'] = array(
            'type' => 'boolean',
        );
        $properties['image'] = array(
            'type' => 'string',
            'boost' => 1,
            'analyzer' => 'analyzer_en',
        );
        $properties['category_names'] = array(
            'type' => 'string',
            // 'index_name' => 'category',
            'analyzer' => 'keyword',
        );
        $properties['url'] = array(
            'type' => 'string',
        );
        $properties['name_suggest'] = array(
            'type' => 'completion'
        );

        return $properties;
    }
}
