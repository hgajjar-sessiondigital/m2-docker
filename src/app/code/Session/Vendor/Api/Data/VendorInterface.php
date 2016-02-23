<?php
namespace Session\Vendor\Api\Data;

interface VendorInterface
{
    /**
     * constants for keys of data array
     */
    const VENDOR_ID = 'vendor_id';
    const NAME      = 'name';

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId();

    /**
     * Get Name
     *
     * @return string|null
     */
    public function getName();

    /**
     * Set ID
     *
     * @param int $id
     * @return \Session\Vendor\Api\Data\VendorInterface
     */
    public function setId($id);

    /**
     * Set Name
     *
     * @param string $name
     * @return \Session\Vendor\Api\Data\VendorInterface
     */
    public function setName($name);
}