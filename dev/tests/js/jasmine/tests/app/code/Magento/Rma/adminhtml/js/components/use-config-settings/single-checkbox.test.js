/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'Magento_Rma/js/components/use-config-settings/single-checkbox'
], function (Checkbox) {
    'use strict';

    var model;

    describe('Magento_Rma/js/components/use-config-settings/single-checkbox', function () {
        beforeEach(function () {
            model = new Checkbox({
                name: 'use_config_settings',
                dataScope: '',
                value: 1,
                visible: true,
                disabled: false,
                checked: false
            });
        });

        it('Verify initial disableIsReturnable', function () {
            expect(model.get('disableIsReturnable')).toBe(false);
        });

        it('Verify disableIsReturnable when element becomes disabled', function () {
            model.set('disabled', true);
            expect(model.get('disableIsReturnable')).toBe(true);
        });

        it('Verify disableIsReturnable when element becomes checked', function () {
            model.set('checked', true);
            expect(model.get('disableIsReturnable')).toBe(true);
        });
    });
});
