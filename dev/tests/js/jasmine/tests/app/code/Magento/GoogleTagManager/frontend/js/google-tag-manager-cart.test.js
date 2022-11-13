/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define(['squire'], function (Squire) {
    'use strict';

    var injector = new Squire(),
        mocks = {
            'Magento_Customer/js/customer-data': {
                /**
                 * Wrapper function for mock "get" method
                 *
                 * @returns func - mock function
                 */
                get: function () {
                    var func = jasmine.createSpy().and.returnValue({
                        items: [
                            {
                                'name': 'simple01',
                                'product_sku': 'simple01',
                                'product_id': '1',
                                'price': 100
                            },
                            {
                                'name': 'simple02',
                                'product_sku': 'simple02',
                                'product_id': '2',
                                'price': 200
                            },
                            {
                                'name': 'simple04',
                                'product_sku': 'simple04',
                                'product_id': '4',
                                'price': 400,
                                'qty': 0
                            }
                        ]
                    });

                    func.subscribe = jasmine.createSpy();

                    return func;
                }
            }
        },
        gtm;

    beforeEach(function (done) {
        injector.mock(mocks);
        injector.require(['jquery', 'Magento_GoogleTagManager/js/google-tag-manager-cart'], function ($) {
            var element = $('<div>elem</div>');

            gtm = element.gtmCart({}).data('mage-gtmCart');
            done();
        });
    });

    describe('Magento_GoogleTagManager/js/google-tag-manager-cart', function () {
        describe('"getProduct" method', function () {
            it('Check for gtm definition', function () {
                expect(gtm).toBeDefined();
            });

            it('Check result value for "simple01"', function () {
                var product = gtm.getProduct({
                    'id': '1'
                });

                expect(product.price).toBe(100);
                expect(product.name).toBe('simple01');
            });

            it('Check result value for "simple02"', function () {
                var product = gtm.getProduct({
                    'id': '2'
                });

                expect(product.price).toBe(200);
                expect(product.name).toBe('simple02');
            });

            it('Check result value for sku that do not exists', function () {
                var product = gtm.getProduct({
                    'id': '3'
                });

                expect(typeof product).toBe('object');
                expect(product.price).not.toBeDefined();
                expect(product.name).not.toBeDefined();
            });
        });
        describe('"_executeEvents" method', function () {
            it('Check execute events "ajax:addToCart" for product with quantity 0', function () {
                var productInfo = [{
                    'id': '4'
                }];

                gtm.options.actions['ajax:addToCart'] = jasmine.createSpy();
                gtm.options.temporaryEventStorage = [
                    {
                        'type': 'ajax:addToCart',
                        'productInfo': productInfo
                    }
                ];
                gtm._executeEvents();

                expect(gtm.options.actions['ajax:addToCart']).not.toHaveBeenCalled();
            });
        });
    });
});
