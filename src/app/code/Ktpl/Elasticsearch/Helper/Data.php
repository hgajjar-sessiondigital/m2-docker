<?php

namespace Ktpl\Elasticsearch\Helper;

use Magento\Search\Model\QueryFactory;

class Data extends \Magento\Search\Helper\Data
{
    /**
     * Retrieve result page url and set "secure" param to avoid confirm
     * message when we submit form from secure page to unsecure
     *
     * @param   string $query
     * @return  string
     */
    public function getResultUrl($query = null)
    {
        return $this->_getUrl(
            'elasticsearch',
            ['_query' => [QueryFactory::QUERY_VAR_NAME => $query], '_secure' => $this->_request->isSecure()]
        );
    }
}
