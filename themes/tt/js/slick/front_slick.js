/**
 * @file
 */

(function ($, Drupal, settings) {

  "use strict";

  Drupal.behaviors.ttSlickConfig = {
    attach: function (context) {
      $("#block-views-block-what-people-say-block-1 .views-row ul")
      	.not('.slick-initialized')
      	.slick({
      		slidesToShow: 2,
          responsive: [
            {
            breakpoint: 780,
            settings: {
              slidesToShow: 1,
              }
            }
          ]
      	}
      );

      $("#block-views-block-our-most-popular-tour-block-1 .views-row-wrapper")
        .not('.slick-initialized')
        .slick({
          slidesToShow: 3,
          responsive: [
            {
            breakpoint: 1080,
            settings: {
              slidesToShow: 2,
              variableWidth: true
              }
            },
            {
            breakpoint: 800,
            settings: {
              slidesToShow: 1,
              variableWidth: true
              }
            }
          ]
        }
      );

    }
  }

})(jQuery, Drupal, drupalSettings);