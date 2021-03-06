<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Ktpl\Elasticsearch\Model\Indexer\Elasticsearch\Plugin;

use Ktpl\Elasticsearch\Model\Indexer\Elasticsearch;

class Attribute extends AbstractPlugin
{
    /**
     * @var \Magento\Framework\Search\Request\Config
     */
    private $config;

    /**
     * @param \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry
     * @param \Magento\Framework\Search\Request\Config $config
     */
    public function __construct(
        \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry,
        \Magento\Framework\Search\Request\Config $config
    ) {
        parent::__construct($indexerRegistry); // TODO: Change the autogenerated stub
        $this->config = $config;
    }

    /**
     * Invalidate indexer on attribute save (searchable flag change)
     *
     * @param \Magento\Catalog\Model\ResourceModel\Attribute $subject
     * @param \Closure $proceed
     * @param \Magento\Framework\Model\AbstractModel $attribute
     *
     * @return \Magento\Catalog\Model\ResourceModel\Attribute
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundSave(
        \Magento\Catalog\Model\ResourceModel\Attribute $subject,
        \Closure $proceed,
        \Magento\Framework\Model\AbstractModel $attribute
    ) {
        $isNew = $attribute->isObjectNew();
        $needInvalidation = (
                $attribute->dataHasChangedFor('is_searchable')
                || $attribute->dataHasChangedFor('is_filterable')
                || $attribute->dataHasChangedFor('is_visible_in_advanced_search')
            ) && !$isNew;

        $result = $proceed($attribute);
        if ($needInvalidation) {
            $this->indexerRegistry->get(Elasticsearch::INDEXER_ID)->invalidate();
        }
        if ($isNew || $needInvalidation) {
            $this->config->reset();
        }

        return $result;
    }

    /**
     * Invalidate indexer on searchable attribute delete
     *
     * @param \Magento\Catalog\Model\ResourceModel\Attribute $subject
     * @param \Closure $proceed
     * @param \Magento\Framework\Model\AbstractModel $attribute
     *
     * @return \Magento\Catalog\Model\ResourceModel\Attribute
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundDelete(
        \Magento\Catalog\Model\ResourceModel\Attribute $subject,
        \Closure $proceed,
        \Magento\Framework\Model\AbstractModel $attribute
    ) {
        $needInvalidation = !$attribute->isObjectNew() && $attribute->getIsSearchable();
        $result = $proceed($attribute);
        if ($needInvalidation) {
            $this->indexerRegistry->get(Elasticsearch::INDEXER_ID)->invalidate();
        }

        return $result;
    }
}
