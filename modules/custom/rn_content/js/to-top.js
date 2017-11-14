(function ($) {
  Drupal.behaviors.rnToTopBehavior = {
    attach: function (context, settings) {
      $(".to-top").once('scrollToTop').click(function() {
        $('html, body').animate({ scrollTop: 0 }, 'slow');
        if($('.train-search-form-block').css('display') !== 'block') {
          $('.train-search-form-block').delay( 500 ).show('slide', {direction:'up'}, 500);
        }
      });
    }
  };

})(jQuery);