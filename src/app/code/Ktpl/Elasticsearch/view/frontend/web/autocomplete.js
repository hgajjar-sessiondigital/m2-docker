/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*jshint browser:true jquery:true*/
define([
    'jquery',
    'underscore',
    'mage/template',
    'jquery/ui',
    'mage/translate'
], function ($, _, mageTemplate) {
    'use strict';

    /**
     * Check wether the incoming string is not empty or if doesn't consist of spaces.
     *
     * @param {String} value - Value to check.
     * @returns {Boolean}
     */
    function isEmpty(value) {
        return (value.length === 0) || (value == null) || /^\s+$/.test(value);
    }

    $.widget('ktpl.autocomplete', {
        options: {
            autocomplete: 'off',
            minSearchLength: 2,
            responseFieldElements: 'ul li',
            selectClass: 'selected',
            suggestionTemplate:
                '<li class="<%- data.row_class %>" role="option">' +
                    '<span class="qs-option-name">' +
                       ' <% print((data.highlighted)? data.highlighted : data.text) %>' +
                    '</span>' +
                '</li>',
            productTemplate:
                '<li class="qs-option-product">' +
                    '<span class="qs-option-image">' +
                        '<img src="<%- data._source.image %>" width="60"/>' +
                    '</span>' +
                    '<div class="qs-option-details">' +
                        '<span class="qs-name"><%  print(data._source.name) %></span><br/>' +
                        '<span class="qs-desc"><%  print(data._source.description.substr(0,35)) %>...</span><br/>' +
                        '<% if(data._source.variants) { %><span class="qs-price">Starting at </span><% } %><b><%- data._source.currency %><%- Math.round(data._source.price) %></b><br/>' +
                    '</div>' +
                '</li>',
            submitBtn: 'button[type="submit"]',
            searchLabel: '[data-role=minisearch-label]'
        },

        _create: function () {
            this.responseList = {
                indexList: null,
                selected: null
            };
            this.autoComplete = $(this.options.destinationSelector);
            this.searchForm = $(this.options.formSelector);
            this.submitBtn = this.searchForm.find(this.options.submitBtn)[0];
            this.searchLabel = $(this.options.searchLabel);

            _.bindAll(this, '_onKeyDown', '_onPropertyChange', '_onSubmit');

            this.submitBtn.disabled = true;

            this.element.attr('autocomplete', this.options.autocomplete);

            this.element.on('blur', $.proxy(function () {

                setTimeout($.proxy(function () {
                    if (this.autoComplete.is(':hidden')) {
                        this.searchLabel.removeClass('active');
                    }
                    this.autoComplete.hide();
                    this._updateAriaHasPopup(false);
                }, this), 250);
            }, this));

            this.element.trigger('blur');

            this.element.on('focus', $.proxy(function () {
                this.searchLabel.addClass('active');
            }, this));
            this.element.on('keydown', this._onKeyDown);
            this.element.on('input propertychange', this._onPropertyChange);

            this.searchForm.on('submit', $.proxy(function() {
                this._onSubmit();
                this._updateAriaHasPopup(false);
            }, this));

            this.client = new $.es.Client({
                hosts: this.options.url
            });
        },
        /**
         * @private
         * @return {Element} The first element in the suggestion list.
         */
        _getFirstVisibleElement: function () {
            return this.responseList.indexList ? this.responseList.indexList.first() : false;
        },

        /**
         * @private
         * @return {Element} The last element in the suggestion list.
         */
        _getLastElement: function () {
            return this.responseList.indexList ? this.responseList.indexList.last() : false;
        },

        /**
         * @private
         * @param {Boolean} show Set attribute aria-haspopup to "true/false" for element.
         */
        _updateAriaHasPopup: function(show) {
            if (show) {
                this.element.attr('aria-haspopup', 'true');
            } else {
                this.element.attr('aria-haspopup', 'false');
            }
        },

        /**
         * Clears the item selected from the suggestion list and resets the suggestion list.
         * @private
         * @param {Boolean} all - Controls whether to clear the suggestion list.
         */
        _resetResponseList: function (all) {
            this.responseList.selected = null;

            if (all === true) {
                this.responseList.indexList = null;
            }
        },

        /**
         * Executes when the search box is submitted. Sets the search input field to the
         * value of the selected item.
         * @private
         * @param {Event} e - The submit event
         */
        _onSubmit: function (e) {
            var value = this.element.val();

            if (isEmpty(value)) {
                e.preventDefault();
            }

            if (this.responseList.selected) {
                this.element.val(this.responseList.selected.find('.qs-option-name').text());
            }
        },

        /**
         * Executes when keys are pressed in the search input field. Performs specific actions
         * depending on which keys are pressed.
         * @private
         * @param {Event} e - The key down event
         * @return {Boolean} Default return type for any unhandled keys
         */
        _onKeyDown: function (e) {
            var keyCode = e.keyCode || e.which;

            switch (keyCode) {
                case $.ui.keyCode.HOME:
                    this._getFirstVisibleElement().addClass(this.options.selectClass);
                    this.responseList.selected = this._getFirstVisibleElement();
                    break;
                case $.ui.keyCode.END:
                    this._getLastElement().addClass(this.options.selectClass);
                    this.responseList.selected = this._getLastElement();
                    break;
                case $.ui.keyCode.ESCAPE:
                    this._resetResponseList(true);
                    this.autoComplete.hide();
                    break;
                case $.ui.keyCode.ENTER:
                    this.searchForm.trigger('submit');
                    break;
                case $.ui.keyCode.DOWN:
                    if (this.responseList.indexList) {
                        if (!this.responseList.selected) {
                            this._getFirstVisibleElement().addClass(this.options.selectClass);
                            this.responseList.selected = this._getFirstVisibleElement();
                        }
                        else if (!this._getLastElement().hasClass(this.options.selectClass)) {
                            this.responseList.selected = this.responseList.selected.removeClass(this.options.selectClass).next().addClass(this.options.selectClass);
                        } else {
                            this.responseList.selected.removeClass(this.options.selectClass);
                            this._getFirstVisibleElement().addClass(this.options.selectClass);
                            this.responseList.selected = this._getFirstVisibleElement();
                        }
                        this.element.val(this.responseList.selected.find('.qs-option-name').text());
                        this.element.attr('aria-activedescendant', this.responseList.selected.attr('id'));
                    }
                    break;
                case $.ui.keyCode.UP:
                    if (this.responseList.indexList !== null) {
                        if (!this._getFirstVisibleElement().hasClass(this.options.selectClass)) {
                            this.responseList.selected = this.responseList.selected.removeClass(this.options.selectClass).prev().addClass(this.options.selectClass);

                        } else {
                            this.responseList.selected.removeClass(this.options.selectClass);
                            this._getLastElement().addClass(this.options.selectClass);
                            this.responseList.selected = this._getLastElement();
                        }
                        this.element.val(this.responseList.selected.find('.qs-option-name').text());
                        this.element.attr('aria-activedescendant', this.responseList.selected.attr('id'));
                    }
                    break;
                default:
                    return true;
            }
        },

        /**
         * Executes when the value of the search input field changes. Executes a GET request
         * to populate a suggestion list based on entered text. Handles click (select), hover,
         * and mouseout events on the populated suggestion list dropdown.
         * @private
         */
        _onPropertyChange: function () {
            var searchField = this.element,
                clonePosition = {
                    position: 'absolute',
                    // Removed to fix display issues
                    // left: searchField.offset().left,
                    // top: searchField.offset().top + searchField.outerHeight(),
                    // width: searchField.outerWidth(),
                    width: 350,
                    right: 0
                },
                suggestionTemplate = mageTemplate(this.options.suggestionTemplate),
                productTemplate = mageTemplate(this.options.productTemplate),
                dropdown = $('<div></div>'),
                suggestionsDropdown = $('<ul role="listbox" class="suggestions"></ul>'),
                productsDropdown = $('<ul role="listbox"></ul>'),
                value = this.element.val();

            this.submitBtn.disabled = isEmpty(value);

            if (this.request) this.request.abort();

            if (value.length >= parseInt(this.options.minSearchLength, 10)) {
                var suggest = {
                    phraseSuggester: {
                        text: value,
                        phrase : {
                            field: 'name',
                            highlight: {
                              pre_tag: "<b>",
                              post_tag: "</b>"
                            }
                        }
                    },
                    completionSuggester: {
                        text: value,
                        completion: {
                            field: 'name_suggest'
                        }
                    }
                }

                this.request = this.client.search({
                    index: 'catalogsearch_elasticsearch_scope1',
                    body: {
                        query: { match: { _all: { query: value, operator: 'and' } } },
                        suggest: suggest,
                        size: 3,
                        from: 0
                    }
                }), function (error) {
                    console.trace(error.message);
                };
                this.request.then(function (body) {
                    var suggesters = body.suggest;
                    var hits = body.hits.hits;

                    // no results
                    if (!((suggesters.phraseSuggester.length && suggesters.phraseSuggester[0].options.length) ||
                        (suggesters.completionSuggester.length && suggesters.completionSuggester[0].options.length)) &&
                        !hits.length) {
                        dropdown.append($('<div class="qs-no-results">Please try another search term...</div>'));
                    } else {
                    // results found
                        if ((suggesters.phraseSuggester.length && suggesters.phraseSuggester[0].options.length) ||
                            (suggesters.completionSuggester.length && suggesters.completionSuggester[0].options.length)) {
                                dropdown.append($('<div class="qs-heading">Suggestions</div>'));
                        }
                        $.each(suggesters.phraseSuggester, function(j, item) {
                            $.each(item.options, function(j, suggestion) {
                                var html = suggestionTemplate({
                                    data: suggestion
                                });
                                suggestionsDropdown.append(html);
                            });
                        });
                        $.each(suggesters.completionSuggester, function(j, item) {
                            $.each(item.options, function(j, suggestion) {
                                var index = suggestion.text.toLowerCase().indexOf(value);
                                if ( index >= 0 )
                                {
                                    suggestion.text = suggestion.text.substring(0,index) + "<b>" + suggestion.text.substring(index,index+value.length) + "</b>" + suggestion.text.substring(index + value.length);
                                }
                                var html = suggestionTemplate({
                                    data: suggestion
                                });
                                suggestionsDropdown.append(html);
                            });
                        });
                        dropdown.append(suggestionsDropdown);

                        $.each(hits, function(index, item) {
                            var html = productTemplate({
                                data: item
                            });
                            productsDropdown.append(html);
                        });
                        if (hits.length) dropdown.append($('<div class="qs-heading">Products</div>'));
                        dropdown.append(productsDropdown);
                    }

                    this.responseList.indexList = this.autoComplete.html(dropdown)
                        .css(clonePosition)
                        .show()
                        .find(this.options.responseFieldElements + ':visible');

                    this._resetResponseList(false);
                    this.element.removeAttr('aria-activedescendant');

                    if (this.responseList.indexList.length) {
                        this._updateAriaHasPopup(true);
                    } else {
                        this._updateAriaHasPopup(false);
                    }

                    this.responseList.indexList
                        .on('click', function (e) {
                            this.responseList.selected = $(e.target);
                            this.searchForm.trigger('submit');
                        }.bind(this))
                        // .on('mouseenter mouseleave', function (e) {
                        //     this.responseList.indexList.removeClass(this.options.selectClass);
                        //     $(e.target).addClass(this.options.selectClass);
                        //     this.responseList.selected = $(e.target);
                        //     this.element.attr('aria-activedescendant', $(e.target).attr('id'));
                        // }.bind(this))
                        .on('mouseout', function (e) {
                            if (!this._getLastElement() && this._getLastElement().hasClass(this.options.selectClass)) {
                                $(e.target).removeClass(this.options.selectClass);
                                this._resetResponseList(false);
                            }
                        }.bind(this));
                }.bind(this));

            } else {
                this._resetResponseList(true);
                this.autoComplete.hide();
                this._updateAriaHasPopup(false);
                this.element.removeAttr('aria-activedescendant');
            }
        }
    });

    return $.ktpl.autocomplete;
});
