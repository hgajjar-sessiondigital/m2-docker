<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Ktpl\Elasticsearch\Model\Indexer\Elasticsearch\Plugin\Store;

use Ktpl\Elasticsearch\Model\Indexer\Elasticsearch;
use Ktpl\Elasticsearch\Model\Indexer\Elasticsearch\Plugin\AbstractPlugin;

class View extends AbstractPlugin
{
    /**
     * Invalidate indexer on store view save
     *
     * @param \Magento\Store\Model\ResourceModel\Store $subject
     * @param \Closure $proceed
     * @param \Magento\Framework\Model\AbstractModel $store
     *
     * @return \Magento\Store\Model\ResourceModel\Store
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundSave(
        \Magento\Store\Model\ResourceModel\Store $subject,
        \Closure $proceed,
        \Magento\Framework\Model\AbstractModel $store
    ) {
        $needInvalidation = $store->isObjectNew();
        $result = $proceed($store);
        if ($needInvalidation) {
            $this->indexerRegistry->get(Elasticsearch::INDEXER_ID)->invalidate();
        }
        return $result;
    }

    /**
     * Invalidate indexer on store view delete
     *
     * @param \Magento\Store\Model\ResourceModel\Store $subject
     * @param \Magento\Store\Model\ResourceModel\Store $result
     *
     * @return \Magento\Store\Model\ResourceModel\Store
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterDelete(
        \Magento\Store\Model\ResourceModel\Store $subject,
        \Magento\Store\Model\ResourceModel\Store $result
    ) {
        $this->indexerRegistry->get(Elasticsearch::INDEXER_ID)->invalidate();
        return $result;
    }
}
