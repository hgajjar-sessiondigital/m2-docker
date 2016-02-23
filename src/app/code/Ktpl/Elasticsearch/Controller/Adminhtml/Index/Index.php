<?php
namespace Ktpl\Elasticsearch\Controller\Adminhtml\Index;

class Index extends \Magento\Backend\App\Action
{
    /**
     * Catalog categories index action
     *
     * @return \Magento\Backend\Model\View\Result\Forward
     */
    public function execute()
    {
        die('1');
    }
    
    protected function _isAllowed() {
        return true;
    }
}