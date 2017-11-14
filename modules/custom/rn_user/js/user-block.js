(function ($) {
  Drupal.behaviors.rnUserBlockBehavior = {
    attach: function (context, settings) {
      var mobile_max_width = drupalSettings.settings_mobile_width;
      if (mobile_max_width !== undefined) {

        if($(window).width() >= mobile_max_width) {
          var indicator_class = 'opened';
          var $trigger = $('.user-settings');
          var $block = $('.user-blocks');

          $trigger.once('menuTriggerClick').click(function () {
            $(this).parent().toggleClass(indicator_class);
          });

          // Hide block on click outside the popup
          $('html').once('menuHtmlClick').click(function (e) {
            var target = $(e.target);
            if (!target.closest(".user-block").length) {
              $block.closest('.block__content').removeClass(indicator_class);
            }
          });

          // Add perfect scrollbar
          var scrollbarLength = 40;
          var options = {
            minScrollbarLength: scrollbarLength,
            maxScrollbarLength: scrollbarLength,
            theme: 'user-block-theme'
          };

          // Initialize scroll bar
          $block.find('.language-switcher').find('.block__content').perfectScrollbar(options);
          $block.find('.block-plugin-id--currency-list').find('.block__content').perfectScrollbar(options);

          $('.user-block').once('updatePerfectScrollbar').click(function () {
            // Update scroll bar size
            $block.find('.language-switcher').find('.block__content').perfectScrollbar('update');
            $block.find('.block-plugin-id--currency-list').find('.block__content').perfectScrollbar('update');
          });
        }
        else {
          var open_class = 'opened-settings';
          var block_title = '.user-blocks .block__title';
          var block_content = '.user-blocks .block__content';

          $(context).find(block_title).once('blockTitleClick').click(function(){
            $(this).closest('.block__inner').toggleClass(open_class);
          });

          $(context).find(block_content).once('blockContentClick').click(function(){
            $content_inside = $(this);
            if (!$content_inside.find('.block__content').length) {
              if(!$content_inside.closest('.block__inner').hasClass(open_class)) {
                $content_inside.closest('.has-title').find('.block__title').trigger('click');
              }
            }
          });
        }
      }
    }
  };

})(jQuery);