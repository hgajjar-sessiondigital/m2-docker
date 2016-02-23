<?php
namespace Session\Vendor\Controller\Adminhtml\Vendor;

use Magento\Backend\App\Action;
use Magento\TestFramework\ErrorLog\Logger;

class Save extends \Magento\Backend\App\Action
{

    /**
     * @param Action\Context $context
     */
    public function __construct(Action\Context $context)
    {
        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Session_Vendor::save');
    }

    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($data) {
            /** @var \Session\Vendor\Model\Vendor $model */
            $model = $this->_objectManager->create('Session\Vendor\Model\Vendor');

            $id = $this->getRequest()->getParam('vendor_id');
            if ($id) {
                $model->load($id);
            }

            $model->setData($data);

            $this->_eventManager->dispatch(
                'vendor_prepare_save',
                ['vendor' => $model, 'request' => $this->getRequest()]
            );

            try {
                $model->save();
                $this->messageManager->addSuccess(__('You saved this Vendor.'));
                $this->_objectManager->get('Magento\Backend\Model\Session')->setFormData(false);
                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['vendor_id' => $model->getId(), '_current' => true]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\RuntimeException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('Something went wrong while saving the vendor.'));
            }

            $this->_getSession()->setFormData($data);
            return $resultRedirect->setPath('*/*/edit', ['vendor_id' => $this->getRequest()->getParam('vendor_id')]);
        }
        return $resultRedirect->setPath('*/*/');
    }
}