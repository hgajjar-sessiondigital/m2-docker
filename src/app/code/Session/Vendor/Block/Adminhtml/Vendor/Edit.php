<?php
namespace Session\Vendor\Block\Adminhtml\Vendor;

class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Initialize vendor edit block
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = 'vendor_id';
        $this->_blockGroup = 'Session_Vendor';
        $this->_controller = 'adminhtml_vendor';

        parent::_construct();

        if ($this->_isAllowedAction('Session_Vendor::save')) {
            $this->buttonList->update('save', 'label', __('Save Vendor'));
            $this->buttonList->add(
                'saveandcontinue',
                [
                    'label' => __('Save and Continue Edit'),
                    'class' => 'save',
                    'data_attribute' => [
                        'mage-init' => [
                            'button' => ['event' => 'saveAndContinueEdit', 'target' => '#edit_form'],
                        ],
                    ]
                ],
                -100
            );
        } else {
            $this->buttonList->remove('save');
        }

        if ($this->_isAllowedAction('Session_Vendor::vendor_delete')) {
            $this->buttonList->update('delete', 'label', __('Delete Vendor'));
        } else {
            $this->buttonList->remove('delete');
        }
    }

    /**
     * Retrieve text for header element depending on loaded post
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        if ($this->_coreRegistry->registry('vendor')->getId()) {
            return __("Edit Vendor '%1'", $this->escapeHtml($this->_coreRegistry->registry('vendor')->getName()));
        } else {
            return __('New Vendor');
        }
    }

    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }

    /**
     * Getter of url for "Save and Continue" button
     * tab_id will be replaced by desired by JS later
     *
     * @return string
     */
    protected function _getSaveAndContinueUrl()
    {
        return $this->getUrl('vendor/*/save', ['_current' => true, 'back' => 'edit', 'active_tab' => '']);
    }
}