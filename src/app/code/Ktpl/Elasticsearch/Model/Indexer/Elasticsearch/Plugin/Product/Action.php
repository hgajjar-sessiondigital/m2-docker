<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Ktpl\Elasticsearch\Model\Indexer\Elasticsearch\Plugin\Product;

use Ktpl\Elasticsearch\Model\Indexer\Elasticsearch\Plugin\AbstractPlugin;

class Action extends AbstractPlugin
{
    /**
     * Reindex on product attribute mass change
     *
     * @param \Magento\Catalog\Model\Product\Action $subject
     * @param \Closure $closure
     * @param array $productIds
     * @param array $attrData
     * @param int $storeId
     * @return \Magento\Catalog\Model\Product\Action
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundUpdateAttributes(
        \Magento\Catalog\Model\Product\Action $subject,
        \Closure $closure,
        array $productIds,
        array $attrData,
        $storeId
    ) {
        $result = $closure($productIds, $attrData, $storeId);
        $this->reindexList(array_unique($productIds));
        return $result;
    }

    /**
     * Reindex on product websites mass change
     *
     * @param \Magento\Catalog\Model\Product\Action $subject
     * @param \Closure $closure
     * @param array $productIds
     * @param array $websiteIds
     * @param string $type
     * @return \Magento\Catalog\Model\Product\Action
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundUpdateWebsites(
        \Magento\Catalog\Model\Product\Action $subject,
        \Closure $closure,
        array $productIds,
        array $websiteIds,
        $type
    ) {
        $result = $closure($productIds, $websiteIds, $type);
        $this->reindexList(array_unique($productIds));
        return $result;
    }
}
