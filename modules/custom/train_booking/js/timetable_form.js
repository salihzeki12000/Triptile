(function ($) {
  Drupal.behaviors.trainBookingTimetableForm = {
    attach: function (context, settings) {

      var trainClass = '.train-wrapper';
      var $trainWrapper = $(trainClass);
      var coachClassClass = '.coach-class-wrapper';
      var $coachClassWrapper = $(coachClassClass);

      /************ Onload. **************/

      $('#edit-stars .form-type-checkbox').once('setRatingWidth').each(function (e) {
        var ratingWidth = $(this).find('input').val();
        $(this).find('.active-stars').css('width', ratingWidth + '%');
      });

      /************ Start Basic behavior for select coach classes in the train wrapper. **************/

      // Select on row click
      $coachClassWrapper.once('coachClassRowClick').click(function () {
        $(this).closest('.coach-classes-wrapper').append('<div class="overlay"></div>');
        $(this).closest('.coach-classes-wrapper').find('.overlay').append('<div class="custom-throbber"><div class="loading"></div></div>');
        $coachClassWrapper.removeClass('selected');
        $(coachClassClass + ' .form-radio:checked').prop('checked', false);
        $(this).find('.form-radio').prop('checked', !$(this).find('.form-radio').prop('checked'));
        $(this).find('.form-radio').change();
        $(this).addClass('selected');
      });

      // Show/close coach classes
      $(trainClass + ' .select-seat').once('coachClassClick').click(function () {
        $trainWrapper.removeClass('opened');
        $('.coach-classes-wrapper').hide();
        $coachClassWrapper.removeClass('selected');
        $(coachClassClass + ' .form-radio:checked').prop('checked', false);
        $(this).closest(trainClass).find('.coach-classes-wrapper').show();
        $(trainClass + ' .select-seat').show();
        $(this).parents(trainClass).addClass('opened');
        $(this).hide();

        // Pushing data to DataLayer after submitting form.
        window.trainBooking.selectSeats(getDetailData($(this).closest(trainClass).find('.coach-classes-wrapper')));
      });

      // Select(picked) some coach class.
      $(coachClassClass + ' .form-radio').once('coachClassChange').change(function () {
        $(this).closest(coachClassClass).addClass('selected');
        var $changedRadio = $(this);
        $(coachClassClass + ' .form-radio:checked').each(function(){
          if ($changedRadio.get(0) != $(this).get(0)) {
            $(this).closest(coachClassClass).removeClass('selected');
            $(this).prop('checked', false);
          }
        });
      });

      // Closing coach classes wrapper by clicking on cross.
      $('.coach-classes-wrapper .close').once('coachClassClose').click(function () {
        $(coachClassClass + ' .form-radio:checked').each(function() {
          $(this).closest(coachClassClass).removeClass('selected');
          $(this).prop('checked', false);
        });
        $trainWrapper.removeClass('opened');
        $(this).closest('.coach-classes-wrapper').hide();
        $(trainClass + ' .select-seat').show();
      });

      // Pushing data to DataLayer after submitting form.
      $('.form-submit').once('submit TimetableForm').click(function () {
        window.trainBooking.addToCart(getAddToCartData($(this).closest('.coach-classes-wrapper').find('.coach-class-wrapper.selected')));
      });

      /************ End Basic behavior for select coach classes in the train wrapper. *******/


      /************ Start sorting behavior in the train info part. *************************/

      $(document).once('init').each(function () {
        sortTrains($('.train-table-header .sorting-row div[data-sort="departure-time"]'));
      });

      $('.route-info-wrapper .sorting-label span').once('sortLabel').click(function () {
        $('.timetable-header').toggleClass('active-sorting');
      });

      $('.train-table-header .sorting-row div').once('sortRow').click(function () {
        sortTrains($(this));
      });

      $('.sorting-type').once('toggleSorting').click(function() {
        $(this).toggleClass('opened');
      });

       // Sort train after clicking on sorting labels.
      function sortTrains($sortBy) {
        $('.route-info-wrapper .sorting-label span').text($sortBy.html());
        var sortDirection = ascDescChanger($sortBy);
        $('.train-table-header .sorting-row div').removeClass('active');
        $sortBy.addClass('active');
        var sortBy = 'data-' + $sortBy.attr('data-sort');
        $trainWrapper.each(function () {
          $(this).css('order', $(this).attr(sortBy));
        });
        if (sortDirection == 'asc') {
          $('#edit-main-trains').css('flex-direction', 'column');
        }
        else {
          $('#edit-main-trains').css('flex-direction', 'column-reverse');
        }
      }

       // Change ascending and descending direction for some sorting element.
      function ascDescChanger($element) {
        if ($element.hasClass('active')) {
          if ($element.attr('data-sort-direction') == 'asc') {
            $element.attr('data-sort-direction', 'desc');
            return 'desc';
          }
          else {
            $element.attr('data-sort-direction', 'asc');
            return 'asc';
          }
        }
        else {
          if ($element.attr('data-sort') == 'popularity' || $element.attr('data-sort') == 'rate') {
            $element.attr('data-sort-direction', 'desc');
            return 'desc';
          }
          else {
            $element.attr('data-sort-direction', 'asc');
          }
          return 'asc';
        }
      }

      /****** End sorting behavior in the train info part. ********************/


      /****** The Begin Show/Hide Train behavior BIG part ********************/

      function trainShowBehaviorHandler() {
        $trainWrapper.each(function () {
          var flagShow = true;

          // Sidebar rate checkboxes
          var rate = $(this).attr('data-rate');
          var $starbox = $('#edit-stars input[value="' + rate + '"]');
          if (!$starbox.is(":checked")) {
            flagShow = false;
          }

          // Travel date sliders.
          if (!sidebarTravelDateChanging($(this))) {
            flagShow = false;
          }

          // Switch flagShow to false if no one CoachClass is visible.
          if ($(this).find(coachClassClass + '.visible').length == 0) {
            flagShow = false;
          }

          // The conclusion - to show or to hide the train.
          if (flagShow) {
            $(this).show();
          }
          else {
            $(this).hide();
          }
        });

        changingCountOfResultsHandler();
      }

      // Sidebar rating checkboxes behavior.
      $('#edit-stars input').once('starsChange').change(function () {
        trainShowBehaviorHandler();
      });

      // Sidebar Travel slider behavior.
      $('#edit-departure-slider').slider({
        range: true,
        min: 0,
        max: 24,
        values: [0, 24],
        slide: function(event, ui) {
          travelDataSliding($(this), ui)
        },
        stop: function(event, ui) {
          trainShowBehaviorHandler();
        }
      });

      $('#edit-arrival-slider').slider({
        range: true,
        min: 0,
        max: 24,
        values: [0, 24],
        slide: function(event, ui) {
          travelDataSliding($(this), ui)
        },
        stop: function(event, ui) {
          trainShowBehaviorHandler();
        }
      });

      function travelDataSliding($slider, ui) {
        $slider.siblings('.lowest').text(ui.values[0] + ':00');
        $slider.siblings('.highest').text(ui.values[1] + ':00');
      }

      // Checking range of train's departure and arrival times.
      function sidebarTravelDateChanging($trainWrapper) {
        var departureSliderValues = $('#edit-departure-slider').slider('values');
        var arrivalSliderValues = $('#edit-arrival-slider').slider('values');
        if ($trainWrapper.attr('data-departure-hours') >= departureSliderValues[0] &&
          $trainWrapper.attr('data-departure-hours') <= departureSliderValues[1] &&
          $trainWrapper.attr('data-arrival-hours') >= arrivalSliderValues[0] &&
          $trainWrapper.attr('data-arrival-hours') <= arrivalSliderValues[1]) {
          return true;
        }
        else {
          return false;
        }
      }

      /****** The END Show/Hide Train behavior BIG part *******************/


      /****** The Begin Show/Hide Coach Classes behavior BIG part *********/

      var $priceSlider = $('#edit-price-slider');

      function coachClassShowBehaviorHandler() {
        $trainWrapper.each(function () {

          $(this).find(coachClassClass).each(function () {
            var flagShow = true;
            var $coachClassWrapper = $(this);
            // Sidebar Car type checkboxes.
            if (!sidebarCoachClassChanging($coachClassWrapper)) {
              flagShow = false;
            }

            // Sidebar price slider.
            if (!sidebarPriceChanging($coachClassWrapper)) {
              flagShow = false;
            }

            // The conclusion - to show or to hide the coach class.
            if (flagShow) {
              $coachClassWrapper.addClass('visible');
            }
            else {
              $coachClassWrapper.removeClass('visible');
            }
          });

          // Update Train displaying after updating CoachClasses.
          trainShowBehaviorHandler();
        });
      }

      // Sidebar car type checkboxes behavior.
      $('#edit-coach-class input').once('coachClassCheckbox').change(function () {
        coachClassShowBehaviorHandler();
      });

      // More/Less buttons
      $('#edit-help-buttons .more').once('helpButtonsMore').click(function () {
        $(this).hide();
        $('#edit-help-buttons .less').show();
        $('#edit-coach-class .car-type-item.less-mode').removeClass('hide');
      });
      $('#edit-help-buttons .less').once('helpButtonsLess').click(function () {
        $(this).hide();
        $('#edit-help-buttons .more').show();
        $('#edit-coach-class .car-type-item.less-mode').addClass('hide');
      });

      $('.check-uncheck-wrapper .checker').once('filterCheckerChange').change(function () {
        var $this = $(this);
        $this.closest('.filter-container').find('input[type="checkbox"]:not(.checker)').prop('checked', $(this).prop('checked'));
        var label_text = $this.prop('checked') ? Drupal.t('Uncheck all', {}, {'context': 'Timetable Form'}) : Drupal.t('Check all', {}, {'context': 'Timetable Form'});
        $this.closest('.check-uncheck-wrapper').find('label[for="' + $(this).attr('id') + '"]').text(label_text);
        coachClassShowBehaviorHandler();
      });

      // Reset sliders values to default.
      function resetSlider($slider, options) {
        $slider.slider("values", 0, options.min);
        $slider.slider("values", 1, options.max);
      }

      // Reset filters.
      $('.reset-trigger').once('resetTriggerClick').click(function(){
        $('.filters input[type="checkbox"]').each(function () {
          $(this).prop('checked', true);
        });

        $('.ui-slider').each(function(){
          var options = $(this).slider('option');
          resetSlider($(this), options);
        });
        coachClassShowBehaviorHandler();
      });

      // Returns true if the CoachClass is checked.
      function sidebarCoachClassChanging($coachClassWrapper) {
        $coachClassWrapper.removeClass('selected');
        $coachClassWrapper.find('.form-radio:checked').prop('checked', false);
        var coachClass = $coachClassWrapper.attr('coach-class-sidebar-code');
        var $coachbox = $('#edit-coach-class input[value="' + coachClass + '"]');
        if ($coachbox.is(":checked")) {
          return true;
        }
      }

      // Price slider.
      var priceFrom = parseFloat($priceSlider.attr('price-from'));
      var priceTo = parseFloat($priceSlider.attr('price-to'));
      var options = {
        range: true,
        min: priceFrom,
        max: priceTo,
        values: [priceFrom, priceTo],
        slide: function(event, ui) {
          $('#edit-lowest-price .number').text(ui.values[0]);
          $('#edit-highest-price .number').text(ui.values[1]);
        },
        stop: function(event, ui) {
          coachClassShowBehaviorHandler();
        }
      };
      $priceSlider.slider(options);

      // Returns true if CoachClass'es price meet the range of price slider
      function sidebarPriceChanging($coachClassWrapper) {
        var priceSliderValues = $priceSlider.slider('values');
        var minPrice = priceSliderValues[0];
        var maxPrice = priceSliderValues[1];
        var price = $coachClassWrapper.attr('coach-class-price');
        if (minPrice <= price && price <= maxPrice) {
          return true;
        }
      }

      /********* The END Show/Hide Coach Classes behavior BIG part *********/


      /********************** Common functions ****************************/

      // Update count of results after some action.
      function changingCountOfResultsHandler() {
        var count = $("#edit-main-trains .train-wrapper:visible").length;
        if (count > 0) {
          $('#edit-main-trains-no-result:visible').hide();
          $('.train-table-header .sorting-label:hidden').show();
        }
        else {
          $('#edit-main-trains-no-result:hidden').show();
          $('.train-table-header .sorting-label:visible').hide();
        }
        var output = Drupal.formatPlural(count, '1 result', '@count results', {},
          {'context': 'Timetable form'});
        $('.train-table-header .results .quantity-label').text(output);
        return count;
      }

      // open search form on icon click
      $('.searchform-trigger').once('openSearchForm').click(function(){
        $('.train-search-form-block').toggle();
      });

      // Open filters on icon click.
      $('.filters-trigger').once('openFilters').click(function () {
        $('.train-search-form-block').hide();
        $('.sidebar').dialog({
          dialogClass: "popup-filters",
          draggable: false,
          buttons: [{
              text: Drupal.t('Apply', {}, {'context': 'Timetable Form'}),
              click: function() {
                $( this ).dialog( "close" );
              }
          }],
          title: Drupal.t('Filters', {}, {'context': 'Timetable Form'}),
          width: $(window).width(),
          height: $(window).height()
        });
      });

      // Return prepared data for GA DataLayer.
      function getDetailData($coachClassesWrapper) {
        var products = [];
        var currencyCode = 'USD';
        $coachClassesWrapper.find('.coach-class-wrapper').each(function () {
          currencyCode = $(this).find('.ticket-wrapper .ticket-price').attr('data-currency-code');
          var departure = $('.train-table-header .results-wrapper .stations .departure').text();
          var arrival = $('.train-table-header .results-wrapper .stations .arrival').text();
          products.push({
            'name': departure + ' - ' + arrival,
            'id': $(this).attr('data-coach-class-id'),
            'price': $(this).attr('data-coach-class-ga'),
            'category': $(this).find('.ticket-wrapper .ticket-name span').text()
          })
        });

        return {
          products: products,
          currencyCode: currencyCode
        }
      }

      // Return prepared data for GA DataLayer.
      function getAddToCartData($coachClass) {
        var departure = $('.train-table-header .results-wrapper .stations .departure').text();
        var arrival = $('.train-table-header .results-wrapper .stations .arrival').text();
        return {
          products: [{
            'name': departure + ' - ' + arrival,
            'id': $coachClass.attr('data-coach-class-id'),
            'price': $coachClass.attr('data-coach-class-ga'),
            'category': $coachClass.find('.ticket-wrapper .ticket-name span').text(),
            'quantity': $coachClass.attr('data-passengers-count')
          }],
          currencyCode: $coachClass.find('.ticket-wrapper .ticket-price').attr('data-currency-code')
        };
      }

    }
  };
})(jQuery);