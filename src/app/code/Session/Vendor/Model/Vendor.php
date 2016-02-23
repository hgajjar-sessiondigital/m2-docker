<?php
namespace Session\Vendor\Model;

use Magento\Framework\Model\AbstractModel;
use Session\Vendor\Api\Data\VendorInterface;

class Vendor extends AbstractModel implements VendorInterface
{
    /**
     * Initialize resourse model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Session\Vendor\Model\ResourceModel\Vendor');
    }

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId()
    {
        return $this->getData(self::VENDOR_ID);
    }

    /**
     * Get Name
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->getData(self::NAME);
    }

    /**
     * Set ID
     *
     * @param int $id
     * @return \Session\Vendor\Api\Data\VendorInterface
     */
    public function setId($id)
    {
        $this->setData(self::VENDOR_ID, $id);
    }

    /**
     * Set Name
     *
     * @param string $name
     * @return \Session\Vendor\Api\Data\VendorInterface
     */
    public function setName($name)
    {
        $this->setData(self::NAME, $name);
    }


}