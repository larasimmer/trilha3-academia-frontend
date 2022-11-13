/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_GoogleTagManager/js/google-analytics-universal',
    'jquery'
], function (GaUniversal, $) {
    'use strict';

    describe('GoogleTagManager/js/google-analytics-universal', function () {
        var ga,
            config = {
                blockNames: [
                    'category.products.list',
                    'product.info.upsell',
                    'catalog.product.related',
                    'checkout.cart.crosssell',
                    'search_result_list'
                ]
            };

        beforeEach(function () {
            ga = new GaUniversal(config);
            $('<div class="products wrapper grid products-grid">' +
                ' <ol class="products list items product-items">' +
                '   <li class="item product product-item">' +
                '       <a href="#" class="product photo product-item-photo" tabindex="-1">' +
                '       </a>' +
                '       <div class="product-item-inner">' +
                '           <button type="submit" title="Add to Cart" class="action tocart primary">' +
                '               <span>Add to Cart</span>' +
                '           </button>' +
                '       </div>' +
                '   </li>' +
                ' </ol>' +
                '</div>'
            ).appendTo(document.body);
        });

        it('Check for onClick event to be observed for both .tocart and anchor elements for configurable products',
            function () {
                spyOn($.fn, 'on', 'click');
                ga.bindImpressionClick(
                    'product_configurable_1',
                    'configurable',
                    'Configurable Product',
                    'test-category',
                    'Catalog Page',
                    '1',
                    'category.products.list',
                    '0'
                );
                expect($.fn.on).toHaveBeenCalledTimes(2);
            });

        it('Check for onClick event to be observed for both .tocart and anchor elements for bundle products',
            function () {
                spyOn($.fn, 'on', 'click');
                ga.bindImpressionClick(
                    'product_bundle_1',
                    'bundle',
                    'Bundle Product',
                    'test-category',
                    'Catalog Page',
                    '1',
                    'category.products.list',
                    '0'
                );
                expect($.fn.on).toHaveBeenCalledTimes(2);
            });

        it('Check for onClick event to be observed for both .tocart and anchor elements for grouped products',
            function () {
                spyOn($.fn, 'on', 'click');
                ga.bindImpressionClick(
                    'product_grouped_1',
                    'grouped',
                    'Grouped Product',
                    'test-category',
                    'Catalog Page',
                    '1',
                    'category.products.list',
                    '0'
                );
                expect($.fn.on).toHaveBeenCalledTimes(2);
            });

        it('Check for onClick event to be observed for an anchor element only for simple products',
            function () {
                spyOn($.fn, 'on', 'click');
                ga.bindImpressionClick(
                    'product_simple_1',
                    'simple',
                    'Simple Product',
                    'test-category',
                    'Catalog Page',
                    '1',
                    'category.products.list',
                    '0'
                );
                expect($.fn.on).toHaveBeenCalledTimes(1);
            });
    });
});
