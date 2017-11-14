/**
 * @file
 * Attaches behaviors for the Selectize.js module.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches behavior to the page from defined settings for selectize to each specified element.
   */
  Drupal.behaviors.selectize = {
    attach: function (context) {
      if (typeof drupalSettings.selectize != 'undefined') {
        $.each(drupalSettings.selectize, function (index, value) {
          var parameters = JSON.parse(value);
          parameters.render = {
            option: function(item) {
              return '<div>' + item[parameters.labelField] + '</div>';
            }
          };
          parameters.load = function(query, callback) {
            if (!query.length) return callback();
            if (parameters.ajaxUrl) {
              $.ajax({
                url: parameters.ajaxUrl + encodeURIComponent(query),
                type: 'GET',
                error: function() {
                  callback();
                },
                success: function(res) {
                  // @todo in the future will bee need range response, example: res.slice(0, 10);
                  callback(res);
                }
              });
            }
            else {
              return callback();
            }
          };
          if (parameters.isMobile != true  || (parameters.isMobile == true && parameters.displayOnMobile == true)) {
            $('#' + index).selectize(parameters);
          }
        });
      }
    }
  };

})(jQuery, Drupal, drupalSettings);
