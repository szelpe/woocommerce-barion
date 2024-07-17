const barionSettings = window.wc.wcSettings.getSetting( 'barion_data', {} );
const barionLabel = window.wp.htmlEntities.decodeEntities( barionSettings.title ) || window.wp.i18n.__( 'Barion', 'pay-via-barion-for-woocommerce' );
const barionContent = () => {
    return React.createElement("div", { dangerouslySetInnerHTML: { __html: window.wp.htmlEntities.decodeEntities(barionSettings.description || '') } });
};

const createLabel = () => {
  return React.createElement('div', {}, 
           React.createElement('span', {
      'aria-hidden': "true"
    }, window.wp.htmlEntities.decodeEntities(barionSettings.title || __('Barion', 'pay-via-barion-for-woocommerce')))
  ),
   React.createElement('img', {
      src: barionSettings.logo,
      alt: window.wp.htmlEntities.decodeEntities(barionSettings.title || __('Barion', 'pay-via-barion-for-woocommerce')),
    });
};

const BarionCheckout = {
  name: 'barion',
  label: createLabel(),
  content: Object(window.wp.element.createElement)(barionContent, null),
  edit: Object(window.wp.element.createElement)(barionContent, null),
  canMakePayment: () => true,
  placeOrderButtonLabel: barionSettings.order_button_label,
  ariaLabel: window.wp.htmlEntities.decodeEntities(barionSettings.title || __('Barion', 'pay-via-barion-for-woocommerce')),
  supports: {
    features: barionSettings.supports,
  },
};

window.wc.wcBlocksRegistry.registerPaymentMethod( BarionCheckout );