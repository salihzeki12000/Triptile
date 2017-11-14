(function($){

  var ecommerce;
  ecommerce = {
    selectSeats : function(data) {
      if (window.dataLayer !== undefined) {
        dataLayer.push({
          'event': 'detail',
          'ecommerce': {
            'currencyCode': data.currencyCode,
            'detail': {
              'actionField': {'list': 'product'},
              'products': data.products
            }
          }
        });
      }
    },

    addToCart : function (data) {
      if (window.dataLayer !== undefined) {
        dataLayer.push({
          'event': 'addToCart',
          'ecommerce': {
            'currencyCode': data.currencyCode,
            'add': {
              'products': data.products
            }
          }
        });
      }
    },

    checkout : function (data) {
      if (window.dataLayer !== undefined) {
        dataLayer.push({
          'event': 'checkout',
          'ecommerce': {
            'currencyCode': data.currencyCode,
            'checkout': {
              'products': data.products
            }
          }
        });
      }
    },

    purchase : function (data) {
      if (window.dataLayer !== undefined) {
        dataLayer.push({
          'event': 'purchase',
          'ecommerce': {
            'currencyCode': data.currencyCode,
            'purchase': {
              'actionField': data.actionField,
              'products': data.products
            }
          }
        });
      }
    }

  };

  window.trainBooking = window.trainBooking ? $.extend(window.trainBooking, ecommerce) : ecommerce;

})(jQuery);

