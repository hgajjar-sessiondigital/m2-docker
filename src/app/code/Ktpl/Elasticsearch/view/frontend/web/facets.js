define([
    'uiComponent',
    'jquery',
    'ko',
    'Ktpl_Elasticsearch/facets',
    'mage/collapsible',
    'mage/loader'
], function (Component,$,ko,collapsible,loader) {
    'use strict';

    ko.bindingHandlers.foreachprop = {
        transformObject: function (obj) {
            var properties = [];
            for (var key in obj) {
                if (obj.hasOwnProperty(key)) {
                    properties.push({ key: key, value: obj[key] });
                }
            }
            return properties;
        },
        init: function(element, valueAccessor, allBindingsAccessor, viewModel, bindingContext) {
            var value = ko.utils.unwrapObservable(valueAccessor()),
                properties = ko.bindingHandlers.foreachprop.transformObject(value);
            ko.applyBindingsToNode(element, { foreach: properties }, bindingContext);
            return { controlsDescendantBindings: true };
        }
    };

    function initSidebar() {
        require([
            'jquery'
        ], function ($) {
            $('[data-role=collapsible]').collapsible();

            // $('[data-role=facets-sidebar] .apply-filter').on('click', function(){
            //     service.applyFilter($(this).data('filter-name'), $(this).text());
            // });
            $('[data-role=facets-sidebar] .filter-current .action.remove').unbind('click');
            $('[data-role=facets-sidebar] .filter-current .action.remove').on('click', function(){
                service.clearFilter($(this).data('item-index'));
            });
        });
    }

    var service = {
        url: null,
        query: null,
        availableFilters: null,
        availableSorters: null,
        appliedFilters: null,
        appliedSorter: null,
        data: {},
        pageSizeOptions: null,
        itemsPerPage: null,
        from: null,
        currentPage: null,
        maxPages: null,
        totalRecords: null,
        initialize: function () {
            this.bind();
        },
        getClient: function (url) {
            return new $.es.Client({
                hosts: url
            });
        },
        bind: function () {
            this.pageSizeOptions = ko.observableArray([9,15,30]);
            this.itemsPerPage = ko.observable(12);
            this.from = ko.observable(0);
            this.currentPage = ko.observable(1);
            this.maxPages = ko.observable(0);
            this.data = ko.observable({});
            this.totalRecords = ko.observable(0);
            this.appliedFilters = ko.observableArray([]);

            // sorters
            var Sorter = function(name, value){this.name = name; this.value = value;}
            this.availableSorters = {
                allOptions : ko.observableArray([
                    new Sorter('Relevance', 'relevance'),
                    new Sorter('Price: Low to High', 'price:lth'),
                    new Sorter('Price: High to Low', 'price:htl')
                ]),
                selectedOption : ko.observable()
            };
            this.appliedSorter = ko.observableArray([]);
        },
        getData: function () {
            this.loadResults();
            return this.data;
        },
        setParams: function (params) {
            $.each(params, function(key,val) {
                this[key] = val;
            }.bind(this));
        },
        loadResults: function() {
            $('#maincontent').loader('show');
            var aggregations = {
                category_names : {
                    terms : {
                        field : 'category_names',
                        // exclude: ['Default Category']
                    }
                }
            };
            $.each(this.availableFilters, function(index, item){
                aggregations[item.attribute_code] = {
                    terms : { field : item.attribute_code }
                }
            });
            if (this.request) this.request.abort();
            this.request = this.getClient(this.url).search({
                index: 'catalogsearch_elasticsearch_scope1',
                body: {
                    query: {
                        filtered: {
                            query : {
                                match: {
                                    _all: {
                                        query: this.query,
                                        operator: 'and'
                                    }
                                }
                            },
                            filter : {bool:{must:this.appliedFilters()}}
                        }
                    },
                    size: this.itemsPerPage(),
                    from: this.from(),
                    aggs: aggregations,
                    sort: this.appliedSorter()
                }
            });
            this.request.then(function (body) {
                var _this = this;
                // remove applied aggregations from result
                $.each(body.aggregations, function(key, item){
                    $.each(_this.appliedFilters(), function(i, filter){
                        if (filter.term) {
                            if (key in filter.term) delete body.aggregations[key];
                        } else if (filter.range) {
                            if (key in filter.range) delete body.aggregations[key];
                        }
                    });
                });

                this.data({});
                this.data(body);

                this.totalRecords(body.hits.total);

                //set pagination variables
                this.setPaginationVariables();

                initSidebar();
                $('#maincontent').loader('hide');
            }.bind(this));
        },
        setPaginationVariables: function() {
            var _this = this;
            this.maxPages(Math.ceil(_this.totalRecords() / _this.itemsPerPage()));
        },
        applyFilter: function(filter, value) {
            if (filter == 'price') {
                value = value.split('-');

                if (value[1]) value = { 'gte': value[0],'lte': value[1]};
                else value = { 'gte': value[0]};

                this.appliedFilters.push({'range': {[filter]: value}});
            } else {
                this.appliedFilters.push({'term': {[filter]: value}});
            }
            this.loadResults();
        },
        clearFilter: function(filterIndex) {
            this.appliedFilters.splice(filterIndex);
            this.loadResults();
        },
        getAppliedFilters: function() {
            return this.appliedFilters;
        },
        moveNext: function() {
            service.changePage(service.currentPage() + 1)
        },
        movePrev: function() {
            service.changePage(service.currentPage() - 1)
        },
        changePage: function(newIndex) {
            if (newIndex < 0
    			|| newIndex == service.currentPage())
    			// || newIndex > this.maxPageIndex())
    		{
    			return;
    		}
            service.currentPage(newIndex);
            service.from((service.currentPage() - 1) * service.itemsPerPage());
            service.loadResults();
        },
        onPageSizeChange: function() {
            service.currentPage(1);
            service.setPaginationVariables();
            service.loadResults();
        },
        applySort: function() {
            var sorter = service.availableSorters.selectedOption().value;
            var order = 'asc';
            service.appliedSorter.removeAll();
            switch (sorter) {
                case 'price:lth':
                    sorter = 'price';
                    order = 'asc';
                    break;
                case 'price:htl':
                    sorter = 'price';
                    order = 'desc';
                    break;
                default:
                    sorter = null;
            }
            if (sorter) service.appliedSorter.push({ [sorter] : { order: order }});
            service.loadResults();
        },
        renderPrice: function(price, currency='') {
            return currency + (Math.round(price * 100) / 100)
        },
        renderFilter: function(attr_code) {
            var _this = this;
            if (_this.attributeNames == null) {
                _this.attributeNames = [];
                $.each(this.availableFilters, function(index, item){
                    _this.attributeNames[item.attribute_code] = item.frontend_label;
                });
                _this.attributeNames['category_names'] = 'Category';
            }
            return _this.attributeNames[attr_code];
        },
        makePriceRange: function(terms) {
            var max = 0, min = 0;
            var range = {};
            $.each(terms, function(k,v) {
                if (v.key > max) max = v.key;

                if (min == 0) { min = v.key; }
                else {
                    if (v.key < min) min = v.key;
                }
            });

            var range = service.getPriceRange(max, min, terms);

            var dbRanges = service.getCounts(range, min, max, terms);
            var data = [];

            if (dbRanges) {
                var lastIndex = Object.keys(dbRanges);
                lastIndex = lastIndex[lastIndex.length - 1];

                $.each(dbRanges, function(index,count){

                    var fromPrice = (index == 1) ? '' : ((index - 1) * range);
                    var toPrice = (index == lastIndex) ? '' : (index * range);

                    data.push({
                        'label' : service.renderRangeLabel(fromPrice, toPrice),
                        'value' : fromPrice + '-' + toPrice,
                        'count' : count
                    });
                });
            }

            return data;
        },
        renderRangeLabel: function(fromPrice, toPrice) {
            var formattedFromPrice = service.renderPrice(fromPrice);
            if (toPrice == '' || !toPrice) {
                return formattedFromPrice + ' and above';
            } else if (fromPrice == toPrice) {
                return formattedFromPrice;
            } else {
                if (fromPrice != toPrice) {
                    toPrice -= .01;
                }
                return formattedFromPrice + ' - ' + service.renderPrice(toPrice);
            }
        },
        getPriceRange: function(maxPrice, minPrice, terms) {
            var index = 1, MIN_RANGE_POWER = 10;
            var range;
            do {
                range = Math.pow(10, (Math.floor(maxPrice).toString().length - index));
                var items = service.getRangeItemCounts(range, minPrice, maxPrice, terms);
                index++;
            }
            while(range > MIN_RANGE_POWER && Object.keys(items).length < 2);

            return range;
        },
        getRangeItemCounts: function(range, minPrice, maxPrice, terms) {
            var items = service.getCounts(range, minPrice, maxPrice, terms);
            return items;
        },
        getCounts: function(range, minPrice, maxPrice, terms) {
            var items = {};

            var i = 1;

            var ranges = [], temp = [];
            $.each(terms, function(k,v){
                var priceExpression = Math.round(v.key * 1, 2);
                var rangeExpression = Math.floor(priceExpression / range) + 1;

                var lower = (rangeExpression * range) - range;
                var higher = (rangeExpression * range);

                if (temp.indexOf(rangeExpression) == -1) {
                    ranges.push({
                        lower: lower,
                        higher: higher - .01,
                        rangeExpression: rangeExpression
                    });
                }
                temp.push(rangeExpression);
            });

            $.each(ranges, function(i,range){
                $.each(terms, function(k,v){

                    if (v.key >= range.lower &&
                        v.key <= range.higher ) {

                        if (items[range.rangeExpression]) {
                            items[range.rangeExpression] = items[range.rangeExpression] + v.doc_count;
                        } else {
                            items[range.rangeExpression] = v.doc_count;
                        }
                    }

                    i++;
                });
            });


            return items;
        }
    }

    return Component.extend({
        defaults: {
            url: null,
            query: null,
            availableFilters: null
        },
        initialize: function () {
            this._super();
            $('#maincontent').loader();
            service.initialize();
            service.setParams({url: this.url, query: this.query, availableFilters: this.availableFilters});
            this.elasticSearchResults = service.getData();
            this.appliedFilters = service.getAppliedFilters();
            this.service = service;
            initSidebar();
        }
    });
});
