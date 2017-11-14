(function ($, Drupal) {
  Drupal.behaviors.slideBlock = {
    attach: function (context) {

      var maxWidth = 768;

      $('.block-plugin-id--footer-icons .footer-icon').once('footerIconClick').click(function() {
        var $block = $('.' + $(this).attr('id'));

        if ($(window).width() >= maxWidth) {
          $block.show('slide', {direction:'right'}, 500);
          $block.css('display', 'flex');
        }
        else {
          $block.dialog({
            title: $block.find('.footer-title').text(),
            dialogClass: "footer-popup " + $(this).attr('id'),
            height: $(window).height(),
            width: $(window).width(),
          });
        }
      });

      $('.footer-arrow').once('footerArrowClick').click(function() {
        $(this).parents('.whole-footer').hide('slide', {direction:'right'}, 500);
      });

    }
  };
})(jQuery, Drupal);