(function ($, Drupal) {
  Drupal.behaviors.trainBookingThankYouPage = {
    attach: function (context, settings) {

      // Pushing data to DataLayer after loading the passenger form page.
      $('.thank-you-page').once('GA DataLayer').each(function () {
        var trainOrderCookie = readCookie('ga_need_to_push');
        if (trainOrderCookie) {
          window.trainBooking.purchase(getPurchaseData());
          eraseCookie('ga_need_to_push');
        }
      });

      // Return prepared data for GA DataLayer.
      function getPurchaseData() {
        var products = [];
        var currencyCode = 'USD';
        var actionField = {
          'id': $('.transaction-data').attr('data-transaction-id'),
          'affiliation': 'Online Store',
          'revenue': $('.transaction-data').attr('data-transaction-total'),
          'tax': $('.transaction-data').attr('data-tax')
        };
        $('.transaction-data .product-data').each(function () {
          currencyCode = $(this).attr('data-coach-class-currency-code');
          products.push({
            'name': $(this).attr('data-name'),
            'id': $(this).attr('data-coach-class-id'),
            'price': $(this).attr('data-coach-class-ga'),
            'category': $(this).attr('data-coach-class-name'),
            'quantity': $(this).attr('data-quantity')
          })
        });

        return {actionField: actionField, products: products, currencyCode: currencyCode}
      }

      function readCookie(name) {
        var nameEQ = name + "=";
        var ca = document.cookie.split(';');
        for(var i=0;i < ca.length;i++) {
          var c = ca[i];
          while (c.charAt(0)==' ') c = c.substring(1,c.length);
          if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
        }
        return null;
      }

      function createCookie(name,value,days) {
        var expires = "";
        if (days) {
          var date = new Date();
          date.setTime(date.getTime() + (days*24*60*60*1000));
          expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + value + expires + "; path=/";
      }

      function eraseCookie(name) {
        createCookie(name,"",-1);
      }

      $('.order-details-wrapper .travelers .view-all-items').once('viewAllClick').click(function () {
        var $this = $(this);
        var show_class = 'show-all';
        var $travelers_container = $this.parent().find('.travelers-container');
        $travelers_container.toggleClass(show_class);

        var view_all_text = $travelers_container.hasClass(show_class) ? Drupal.t('view less', {}, {'context': 'Thank You Page'}) : Drupal.t('view all', {}, {'context': 'Thank You Page'});
        $this.text(view_all_text);
      });

      var hide_travelers_class = 'hide-travelers';

      $('.order-details-wrapper .view-travelers').once('viewTravelersClick').click(function() {
        var $this = $(this);
        toggleTravelers($this,'.info-row');
        toggleTravelers($this,'.title-row');
        var view_travelers_text = $this.closest('.info-row').nextAll('.title-row').eq(0).hasClass(hide_travelers_class) ? Drupal.t('view travelers', {}, {'context': 'Thank You Page'}) : Drupal.t('hide travelers', {}, {'context': 'Thank You Page'});
        $(this).text(view_travelers_text);
      });

      function toggleTravelers($link, elementClass) {
        $link.closest('.info-row').nextAll(elementClass).eq(0).toggleClass(hide_travelers_class);
      }
    }
  }
})(jQuery, Drupal);