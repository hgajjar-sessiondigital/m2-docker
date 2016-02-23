<?php
namespace Ktpl\Elasticsearch\Model\Adminhtml\System\Config\Backend;

/**
 * @author      KTPL
 */
class Engine
{
    /**
     * Extend After save call
     * Invalidate catalog search index if engine was changed
     *
     * @return $this
     */
    public function beforeAfterSave(\Magento\CatalogSearch\Model\Adminhtml\System\Config\Backend\Engine $subject)
    {
        if ($subject->isValueChanged()) {
            if ($subject->getValue() == 'elasticsearch') {
                $this->indexerRegistry->get(\Magento\CatalogSearch\Model\Indexer\Fulltext::INDEXER_ID)->invalidate();        
            } else {
                $subject::afterSave();    
            }
        }
        return $this;
    }
}
