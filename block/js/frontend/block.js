const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
const { getSetting } = window.wc.wcSettings;
const { PAYMENT_STORE_KEY } = window.wc.wcBlocksData;
const { useSelect } = window.wp.data;
const { __ } = window.wp.i18n;
const { decodeEntities } = window.wp.htmlEntities;
const { createElement, useEffect } = window.wp.element;

const settings = getSetting('soleaspay_data', {});
const defaultLabel = __('Soleaspay', 'woo-gutenberg-products-block');
const defaultButtonLabel = __('Pay with Soleaspay', 'woo-gutenberg-products-block');
const label = decodeEntities( settings.title ) || defaultLabel;
const placeOrderButtonLabel = decodeEntities( settings.button_title ) || defaultButtonLabel;

/**
 * Content React component
 */
const content = Object(createElement)(
    (props) => {
        const { checkoutStatus } = props;
        const { isComplete } = checkoutStatus;
        const paymentResult = useSelect((select) => {
            return select(PAYMENT_STORE_KEY).getPaymentResult();
        }, []);

        useEffect(() => {
            if (isComplete) {
                const { result, soleaspay_response_data} = paymentResult.paymentDetails;
                if(result === "success" && soleaspay_response_data !== undefined){
                    form_checkout = document.querySelector("form");
                    form_checkout.insertAdjacentHTML("beforebegin", soleaspay_response_data);
                    document.getElementById('soleaspay_data_form').submit();
                }
            }
        }, [ isComplete, paymentResult ]);
        return decodeEntities( settings.description || '' )
    },
    null);
/**
 * Soleaspay payment method config object.
 */
const soleaspayOptions = {
    name: 'soleaspay',
    label: label,
    content: content,
    edit: content,
    canMakePayment: () => true,
    ariaLabel: label,
    placeOrderButtonLabel: placeOrderButtonLabel,
    supports: {
        features: settings.supports,
    },
};


registerPaymentMethod( soleaspayOptions );