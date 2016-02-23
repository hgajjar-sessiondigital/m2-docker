/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

var config = {
    paths: {
        autocomplete: 'Ktpl_Elasticsearch/autocomplete',
        elasticsearch: 'Ktpl_Elasticsearch/bower_components/elasticsearch/elasticsearch.jquery',
        loadie: '//code.jquery.com/jquery-latest.min'
        // elasticsearch: 'Ktpl_Elasticsearch/bower_components/elasticsearch/elasticsearch'
    },
    shim: {
        autocomplete: ['elasticsearch']
    }
};
