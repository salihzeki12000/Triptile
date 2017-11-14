(function ($) {
    Drupal.behaviors.userTableLink = {
        attach: function (context) {
            $(context).find('.views-table').find('.user-table-row').once('tableRowClick').click(function() {
                var href = $(this).find('a').attr('href');
                if (href !== undefined && href.length > 0) {
                    window.location.href = href;
                }
            });
        }
    };
})(jQuery);