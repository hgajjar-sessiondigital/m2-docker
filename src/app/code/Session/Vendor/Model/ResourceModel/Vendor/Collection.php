<?php
namespace Session\Vendor\Model\ResourceModel\Vendor;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 * @package Session\Vendor\Model\ResourceModel\Vendor
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'vendor_id';


    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Session\Vendor\Model\Vendor', 'Session\Vendor\Model\ResourceModel\Vendor');
    }
}