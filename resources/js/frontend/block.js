import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { getSetting } from '@woocommerce/settings';
import { PAYMENT_STORE_KEY } from '@woocommerce/block-data';
import { useSelect }  from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { useEffect, useMemo } from '@wordpress/element';

/** @var {Object} */
const settings = getSetting('soleaspay_data', {});
/** @var {string} */
const defaultLabel = __('Soleaspay', 'woo-gutenberg-products-block');
/** @var {string} */
const defaultButtonLabel = __('Pay with Soleaspay', 'woo-gutenberg-products-block');
/** @var {string} */
const label = decodeEntities( __(settings.title, 'woo-gutenberg-products-block') ) || defaultLabel;
/** @var {string} */
const description = decodeEntities( __(settings.description, 'woo-gutenberg-products-block') || '' );
/** @var {string} */
const placeOrderButtonLabel = decodeEntities( __(settings.button_title, 'woo-gutenberg-products-block') ) || defaultButtonLabel;
/** @var {string} */
const icon = decodeEntities(settings.icon || '');
/** @var {string[]} */
const images = settings.images;

/**
 * @typedef {Object} IconData 
 * @property {string} id - id icon image
 * @property {string} alt - alternative text for icon
 * @property {string} src - source uri of icon
 */

/**
 * generate list to create Icons in view page
 * 
 * @param {string[]} imagesUrl
 * 
 * @returns {IconData[]}
 */
const myIconModel = (imagesUrl) => {
    return imagesUrl.map((image, key) => (
        {
            id : `soleaspay-icon-image-${key}`,
            alt : `Soleaspay Icon Image${key}`,
            src : image
        }
    ))
}

/**
 * generate Image render for list of image
 * 
 * @param {Array} imagesUrl
 * @param {Number} width
 * @param {Number} height
 * @return {JSX.Element}
 * @constructor
 */
const ImagesList = ({imagesUrl, width = 100, height = 90}) => {
    return <div className="soleaspay-data-block-image-list">
        {imagesUrl.map((image, key) => (
            <img 
                className="soleaspay-data-block-image-data"
                key={key}
                src={image}
                width={width}
                height={height}
                alt={`Image-${key+1}`}
                style={{ margin: '2px' }}
            />
        ))}
    </div>;
};

/**
 * Generate icon for Label payment Method
 *
 * @returns {JSX.Element}
 * @constructor
 */
const IconImage = () => {
    return <img
        src={icon}
        alt="Icon-image"
    />
}


/**
 * Label Component
 * 
 * @param {Object} components Woocommerce Component from payment API.
 * @return {JSX.Element}
 * @constructor
 */
const LabelContent = ({components}) => {
    const {PaymentMethodIcons, PaymentMethodLabel} = components;
    return <div
        style={{
            display: 'flex',
            width: '100%',
            flexDirection: 'row',
            alignContent: 'space-between',
            justifyContent: 'space-between',
            paddingRight: '60px'
        }}
    >
    <PaymentMethodLabel icon={<IconImage/>} text={label} />
    <PaymentMethodIcons icons={myIconModel(images)} align="right" />
    </div>;
}

/**
 * Content Component
 * 
 * @param {Object} checkoutStatus Props from payment API.
 * @return {JSX.Element}
 * @constructor
 */
const Content = ({checkoutStatus}) => {
    const { isComplete } = checkoutStatus;
    /** @var {Object} */
    const paymentResult = useSelect((select) => {
        return select(PAYMENT_STORE_KEY).getPaymentResult();
    }, []);

    useEffect(() => {
        if (isComplete) {
            const { result, soleaspay_response_data} = paymentResult.paymentDetails;
            if(result === "success" && soleaspay_response_data !== undefined){
                const form_checkout = document.querySelector("form");
                form_checkout.insertAdjacentHTML("beforebegin", soleaspay_response_data);
                document.getElementById('soleaspay_data_form').submit();
            }
        }
    }, [ isComplete, paymentResult ]);
    
    return <div className="soleaspay-data-block">
        <div className="soleaspay-data-block-description">{description}</div>
        {useMemo(() => { return <ImagesList imagesUrl={images}/> }, [images])}
    </div>;
}

/**
 * Soleaspay payment method config object.
 *
 * @type {{placeOrderButtonLabel: string, edit: JSX.Element, name: string, supports: {features: Object}, label: JSX.Element, canMakePayment: (function(): boolean), content: JSX.Element, ariaLabel: string}}
 */
const soleaspayOptions = {
    name: settings.name,
    label: <LabelContent/>,
    content: <Content/>,
    edit: <Content/>,
    canMakePayment: () => true,
    ariaLabel: label,
    placeOrderButtonLabel: placeOrderButtonLabel,
    supports: {
        features: settings.supports,
    },
};

registerPaymentMethod( soleaspayOptions );