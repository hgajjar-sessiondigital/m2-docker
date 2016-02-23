<?php
namespace Session\DeliveryDate\Model\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class SaveDeliveryDateToOrderObserver
 * @package Session\DeliveryDate\Model\Observer
 */
class SaveDeliveryDateToOrderObserver implements ObserverInterface
{
    /**
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quote = $observer->getQuote();
        $order = $observer->getOrder();
        $order->setDeliveryDate($quote->getDeliveryDate());
        return $this;
    }

}