define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'skroutz',
                component: 'ID_Skroutz/js/view/payment/method-renderer/skroutz-method'
            }
        );
        return Component.extend({});
    }
);