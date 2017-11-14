(function ($, Drupal) {
    Drupal.behaviors.trainBookingStickyForm = {
        attach: function (context, settings) {
            var maxWidth = 768;

            if ($(window).width() > maxWidth) {
                var $form = $(".train-search-form-block");
                var previousScroll = 0;
                var stickyClass = 'sticky';
                var fixedClass = 'fixed';
                var formPosition = $form.offset().top;
                var totalScrolled = 0;

                $(window).once('documentScroll').scroll(function() {
                    var currentScroll = $(this).scrollTop();
                    // down
                    if (currentScroll > previousScroll){
                        $form.removeClass(stickyClass);
                        totalScrolled = 0;

                        $form.closest('.' + fixedClass + '').replaceWith($form);
                    }
                    // up
                    else {
                        if(currentScroll > formPosition) {
                            if(!$form.parents('.' + fixedClass + '').length) {
                                $form.wrap( "<div class='" + fixedClass + "'></div>" );
                            }
                            totalScrolled += previousScroll - currentScroll;
                            if(limitExceeded(totalScrolled)) {

                                $form.closest('.' + fixedClass + '').addClass(stickyClass);
                            }

                        }
                        else {
                            $form.removeClass(stickyClass);

                            $form.closest('.' + fixedClass + '').replaceWith($form);
                            totalScrolled = 0;
                        }
                    }
                    previousScroll = currentScroll;
                });
            }

            function limitExceeded(totalScrolled) {
                return (totalScrolled > $(window).height()/2) ? 1 : 0;
            }
        }
    };
})(jQuery, Drupal);