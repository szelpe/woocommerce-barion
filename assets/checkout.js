const settings = window.wc.wcSettings.getSetting( 'barion_data', {} );
const label = window.wp.htmlEntities.decodeEntities( settings.title ) || window.wp.i18n.__( 'Barion', 'pay-via-barion-for-woocommerce' );
const Content = () => {
    return React.createElement("div", { dangerouslySetInnerHTML: { __html: window.wp.htmlEntities.decodeEntities(settings.description || '') } });
};

const BarionCheckout = {
	name: 'barion',
	label: React.createElement('img', {
        src: `${settings.logo}`,
        alt: window.wp.htmlEntities.decodeEntities(settings.title || __('Barion', 'pay-via-barion-for-woocommerce')),
    }),
	content: Object( window.wp.element.createElement )( Content, null ),
	edit: Object( window.wp.element.createElement )( Content, null ),
	canMakePayment: () => true,
	placeOrderButtonLabel: window.wp.i18n.__( 'Proceed to Barion', 'pay-via-barion-for-woocommerce' ),
	ariaLabel: label,
	supports: {
		features: settings.supports,
	},
};
window.wc.wcBlocksRegistry.registerPaymentMethod( BarionCheckout );