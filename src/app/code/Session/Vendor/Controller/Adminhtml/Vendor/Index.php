<?php
namespace Session\Vendor\Controller\Adminhtml\Vendor;

use Magento\Backend\App\Action;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class Index
 * @package Session\Vendor\Controller\Adminhtml\Vendor
 */
class Index extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * Index constructor.
     * @param Action\Context $context
     */
    public function __construct(
        Action\Context $context,
        PageFactory $resultPageFactory
    )
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Index Action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /**
         * @var \Magento\Backend\Model\View\Result\Page $resultPage
         */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Session_Vendor::vendor_product');
        $resultPage->addBreadcrumb(__('Vendors'), __('Vendors'));
        $resultPage->addBreadcrumb(__('Manage Vendors'), __('Manage Vendors'));
        $resultPage->getConfig()->getTitle()->prepend(__('Vendors'));

        return $resultPage;
    }

    /**
     * Check if the user is allowed to access vendors grid
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Session_Vendor::vendor_product');
    }
}