<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Ktpl\Elasticsearch\Model\Indexer\Mview;


use Ktpl\Elasticsearch\Model\Indexer\Elasticsearch;
use Magento\Framework\Mview\ActionInterface;
use Magento\Framework\Indexer\IndexerInterfaceFactory;

class Action implements ActionInterface
{
    /**
     * @var IndexerInterfaceFactory
     */
    private $indexerFactory;

    /**
     * @param IndexerInterfaceFactory $indexerFactory
     */
    public function __construct(IndexerInterfaceFactory $indexerFactory)
    {
        $this->indexerFactory = $indexerFactory;
    }

    /**
     * Execute materialization on ids entities
     *
     * @param int[] $ids
     * @return void
     * @api
     */
    public function execute($ids)
    {
        /** @var \Magento\IndexerInterfaceFactory\Indexer\IndexerInterface $indexer */
        $indexer = $this->indexerFactory->create()->load(Elasticsearch::INDEXER_ID);
        $indexer->reindexList($ids);
    }
}
