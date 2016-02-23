<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Ktpl\Elasticsearch\Model\Resource;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
/**
 * Elastic Search Client
 *
 * @author      Hardik Gajjar
 */
class Client
{
    const HOST_CONFIG_PATH = 'catalog/elasticsearch/host';

    protected $connection;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Construct
     * @param \Magento\Framework\Filesystem $filesystem
     * @param ScopeConfigInterface $scopeConfig
     * @param string $configPath
     */
    public function __construct(
        \Magento\Framework\Filesystem $filesystem,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;

        $logger = \Elasticsearch\ClientBuilder::defaultLogger($filesystem->getDirectoryWrite(DirectoryList::VAR_DIR)->getAbsolutePath('log/').'elasticsearch.log');
        $hosts = array (
            $this->scopeConfig->getValue(self::HOST_CONFIG_PATH, ScopeInterface::SCOPE_STORE)
        );

        $this->connection = \Elasticsearch\ClientBuilder::create()
                        ->setHosts($hosts)
                        ->setLogger($logger)
                        ->build();
    }

    public function getConnection() {
        return $this->connection;
    }
}
