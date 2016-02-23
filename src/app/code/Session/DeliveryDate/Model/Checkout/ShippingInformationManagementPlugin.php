<?php
namespace Session\DeliveryDate\Model\Checkout;

/**
 * Class ShippingInformationManagementPlugin
 * @package Session\DeliveryDate\Model\Checkout
 */
class ShippingInformationManagementPlugin
{
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * ShippingInformationManagementPlugin constructor.
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     */
    public function __construct(\Magento\Quote\Api\CartRepositoryInterface $quoteRepository)
    {
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * @param \Magento\Checkout\Model\ShippingInformationManagement $subject
     * @param $cartId
     * @param \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
     */
    public function beforeSaveAddressInformation(
        \Magento\Checkout\Model\ShippingInformationManagement $subject,
        $cartId,
        \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
    )
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $deliveryDate = $addressInformation->getExtensionAttributes()->getDeliveryDate();
        $quote = $this->quoteRepository->getActive($cartId);
        $quote->setDeliveryDate($deliveryDate);
    }
}