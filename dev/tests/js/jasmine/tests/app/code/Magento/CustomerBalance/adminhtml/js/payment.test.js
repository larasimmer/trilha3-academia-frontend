/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/* eslint-disable max-nested-callbacks */
define([
    'Magento_CustomerBalance/js/payment'
], function (Payment) {
    'use strict';

    describe('CustomerBalance/js/payment', function () {
        var eventMock = {
                currentTarget: {
                    checked: null
                }
            },
            dataProvider = [
                {
                    'attr': true,
                    'value': 1
                },
                {
                    'attr': false,
                    'value': 0
                }
            ],
            i,
            payment;

        beforeAll(function () {
            payment = new Payment();
            window.order = {
                /**
                 * Fake function
                 */
                loadArea: function () {}
            };
        });

        afterAll(function () {
            window.order = null;
        });

        /**
         * @param {Boolean} targetAttr
         * @param {Int} expected
         */
        function check(targetAttr, expected) {
            it('Checks if enabled "Use Store Credit" option updates order totals section.', function () {
                eventMock.currentTarget.checked = targetAttr;
                spyOn(window.order, 'loadArea');

                payment.updateTotals(eventMock);
                expect(window.order.loadArea).toHaveBeenCalledWith(
                    ['totals', 'billing_method'],
                    true,
                    {
                        'payment[use_customer_balance]': expected
                    }
                );
            });
        }

        for (i = 0; i < dataProvider.length; i++) {
            check(dataProvider[i].attr, dataProvider[i].value);
        }
    });
});
