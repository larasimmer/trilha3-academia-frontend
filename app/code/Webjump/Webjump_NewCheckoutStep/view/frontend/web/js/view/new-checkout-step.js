define([
    'ko',
    'uiComponent',
    'underscore',
    'Magento_Checkout/js/model/step-navigator'
], function (ko, Component, _, stepNavigator) {
    'use strict';

    /**
     * mystep - is the name of the component's .html template,
     * <Vendor>_<Module>  - is the name of your module directory.
     */
    return Component.extend({
        defaults: {
            template: 'Webjump_NewCheckoutStep/newcheckoutstep',
            messageLogin: ko.observable(''),
            customerDataFullName: '<p>' + window.customerData.firstname + ' ' + window.customerData.lastname + '</p>',
            customerDataEmail: '<p>' + window.customerData.email + '</p>'
        },

        // add here your logic to display step,
        isVisible: ko.observable(true),

        /**
         * @returns {*}
         */
        initialize: function () {
            this._super();
            this.loginVerification();

            // register your step
            stepNavigator.registerStep(
                // step code will be used as step content id in the component template
                'first_step',
                // step alias
                null,
                // step title value
                'Login Verification',
                // observable property with logic when display step or hide step
                this.isVisible,

                _.bind(this.navigate, this),

                /**
                 * sort order value
                 * 'sort order value' < 10: step displays before shipping step;
                 * 10 < 'sort order value' < 20 : step displays between shipping and payment step
                 * 'sort order value' > 20 : step displays after payment step
                 */
                5
            );

            return this;
        },

        loginVerification: function() {

            var isLoggedIn = window.isCustomerLoggedIn;

            if (isLoggedIn) {
                this.messageLogin(this.customerDataFullName + ' ' + this.customerDataEmail);
            } else {
                console.log("Please login first");
            }
        },

        /**
         * The navigate() method is responsible for navigation between checkout steps
         * during checkout. You can add custom logic, for example some conditions
         * for switching to your custom step
         * When the user navigates to the custom step via url anchor or back button we_must show step manually here
         */
        navigate: function () {
            this.isVisible(true);
        },

        /**
         * @returns void
         */
        navigateToNextStep: function () {
            stepNavigator.next();
        }
    });
});