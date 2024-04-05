const settings = window.wc.wcSettings.getSetting( 'barion_data', {} );
const label = window.wp.htmlEntities.decodeEntities( settings.title ) || window.wp.i18n.__( 'Barion', 'pay-via-barion-for-woocommerce' );
const Content = () => {
    return React.createElement("div", { dangerouslySetInnerHTML: { __html: window.wp.htmlEntities.decodeEntities(settings.description || '') } });
};

const createLabel = () => {
  return React.createElement('div', {}, 
    React.createElement('img', {
      src: settings.logo,
      alt: window.wp.htmlEntities.decodeEntities(settings.title || __('Barion', 'pay-via-barion-for-woocommerce')),
    }),
        React.createElement('span', {
      'aria-hidden': "true"
    }, window.wp.htmlEntities.decodeEntities(settings.title || __('Barion', 'pay-via-barion-for-woocommerce')))
  );
};

const BarionCheckout = {
  name: 'barion',
  label: createLabel(),
  content: Object(window.wp.element.createElement)(Content, null),
  edit: Object(window.wp.element.createElement)(Content, null),
  canMakePayment: () => true,
  placeOrderButtonLabel: window.wp.i18n.__('Proceed to Barion', 'pay-via-barion-for-woocommerce'),
  ariaLabel: window.wp.htmlEntities.decodeEntities(settings.title || __('Barion', 'pay-via-barion-for-woocommerce')),
  supports: {
    features: settings.supports,
  },
};

window.wc.wcBlocksRegistry.registerPaymentMethod( BarionCheckout );