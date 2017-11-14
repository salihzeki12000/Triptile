(function ($, Drupal) {
  Drupal.behaviors.trainBookingSearchForm = {
    attach: function (context, settings) {

      // @TODO: move maxWith to configs

      // @TODO: When dialog is closing by clicking on the cross need to trigger button "close"

      var maxWidth = 768;
      var dialogActiveClassName="dialog-active";
      var dialogContainerSelector="body";

      // Mobile stations dialog
      if ($(window).width() < maxWidth) {
        $('.stations-wrapper select').each(function () {
          $(this)[0].selectize.on('change', function () {
            var currentValue = $(this)[0].getValue();
            if (currentValue && currentValue != " ") {
              var selectId = '#' + $(this)[0].$control_input.attr('id');
              var stationInDialog = $(selectId).closest('.ui-dialog-content');
              stationInDialog.dialog("destroy");
              if (selectId == '#edit-departure-station-selectized') {
                stationInDialog.prependTo("#train-booking-search-form .form-item-departure-station");
              }
              if (selectId == '#edit-arrival-station-selectized') {
                stationInDialog.prependTo("#train-booking-search-form .form-item-arrival-station");
              }
            }
          })
        });

        // @TODO: create dialog function wrapper

        // Dialog creation
        $('#train-booking-search-form').once('pickStation').on('focus', '.stations-wrapper input', function () {
          var $dialog = $(this).closest('.js-form-type-select');
          $dialog.dialog({
            title: $(this).attr("placeholder"),
            dialogClass: "popup-search-form stations",
            height: $(window).height(),
            width: $(window).width(),
            autoOpen: false,
            resizable: false,
            draggable: false,
            buttons: [{
              text: Drupal.t('OK', {}, {'context': 'Search Form Stations Dialog'}),
              click: function () {
                $(this).dialog("destroy");
                $(dialogContainerSelector).removeClass(dialogActiveClassName);
              }
            }],
            create: function(event, ui) {
              $(dialogContainerSelector).addClass(dialogActiveClassName);
            },
            beforeClose: function(event, ui) {
              $(dialogContainerSelector).removeClass(dialogActiveClassName);
            },
            open: function () {
              toTop();
            }
          });
          $dialog.bind(
            'dialogclose',
            function(event) {
              if ($(this).data("ui-dialog")) {
                $(this).dialog("destroy");
              }
            }
          );
          // Displaying
          $dialog.dialog('open');
        });
      }

      // Update each selectize value. Just delete "leg-1" for updating selectize value of any leg.
      $('.leg-1 .stations-wrapper .station').once('clickStation').on('change', function () {
        var currentValue = " ";
        var $stationSelectize;
        var leg = $(this).attr('leg');
        var station = $(this).attr('station');
        var formMode = $(this).closest('.form-flex-container').attr('form-mode');
        var $currentSelectize = $(this)[0].selectize;
        var currentStationOptions = $currentSelectize.options;
        currentValue = $currentSelectize.getValue();
        if (currentValue && currentValue != " ") {
          $('.' + leg +' .stations-wrapper select.' + station).each(function () {
            $stationSelectize = $(this)[0].selectize;
            $stationSelectize.addOption(currentStationOptions[currentValue]);
            $stationSelectize.addItem(currentValue);
          });
          if (formMode === 'complex-mode' && station == 'arrival-station') {
            $stationSelectize = $('.complex-mode .leg-2 .stations-wrapper select.departure-station')[0].selectize;
            $stationSelectize.addOption(currentStationOptions[currentValue]);
            $stationSelectize.addItem(currentValue);
          }
          if (formMode === 'roundtrip-mode' && leg === 'leg-1') {
            var revert_station = station == 'departure-station' ? 'arrival-station' : 'departure-station';
            $stationSelectize = $('.roundtrip-mode .leg-2 .stations-wrapper select.' + revert_station)[0].selectize;
            $stationSelectize.addOption(currentStationOptions[currentValue]);
            $stationSelectize.addItem(currentValue);
          }
        }
      });

      var $departureSelectize = $('.stations-wrapper select.departure-station')[0].selectize;
      var $arrivalSelectize = $('.stations-wrapper select.arrival-station')[0].selectize;
      var route_stations = drupalSettings.stations;
      if (route_stations !== undefined) {
        $departureSelectize.addOption(route_stations.departure_station);
        $departureSelectize.addItem(route_stations.departure_station.id);
        $arrivalSelectize.addOption(route_stations.arrival_station);
        $arrivalSelectize.addItem(route_stations.arrival_station.id);
      }

      $('#train-booking-search-form').once('swapStation').on('click', '.swap-stations', function (e) {
        var departureStationOptions = $departureSelectize.options;
        var arrivalStationOptions = $arrivalSelectize.options;
        var departureStationValue = $departureSelectize.getValue();
        var arrivalStationValue = $arrivalSelectize.getValue();
        $departureSelectize.addOption(arrivalStationOptions[arrivalStationValue]);
        $departureSelectize.addItem(arrivalStationValue, true);
        $arrivalSelectize.addOption(departureStationOptions[departureStationValue]);
        $arrivalSelectize.addItem(departureStationValue, true);
        e.preventDefault();
      });

      // Passengers field
      var i;
      var passengersNumber;
      var spinner = $('.form-flex-container .ui-spinner-input');
      var adultsSpinner = $('.form-flex-container .passengers-wrapper .adults-number');
      var childrenSpinner = $('.form-flex-container .passengers-wrapper .children-number');

      for (i = 1; i <= childrenSpinner.val(); i++) {
        $('.children-age-wrapper > .form-type-select:nth-child(' + i + ')').show();
      }

      $('.form-flex-container.complex-mode .passengers-wrapper .form-item-complex-mode-passengers-passengers-field-wrapper-adults').once('searchForm adding custom html').before('<div class="adult-number-input"></div>');
      $('.form-flex-container.complex-mode .passengers-wrapper .children-wrapper').once('searchForm adding custom html').before('<div class="children-number-input"></div>');
      $('.form-flex-container.complex-mode .passengers-wrapper .adult-number-input').once('complex click adult').on('click', function () {
        $('.form-flex-container.complex-mode .passengers-wrapper .form-item-complex-mode-passengers-passengers-field-wrapper-adults').toggle();
      });
      $('.form-flex-container.complex-mode .passengers-wrapper .children-number-input').once('complex click children').on('click', function () {
        $('.form-flex-container.complex-mode .passengers-wrapper .children-wrapper').toggle();
      });

      setPassengersNumber(parseInt(adultsSpinner.val()) + parseInt(childrenSpinner.val()));
      setAdultsNumber(parseInt(adultsSpinner.val()));
      setChildrenNumber(parseInt(childrenSpinner.val()));

      // Passengers spinner behavior.
      spinner.once('spin').on('spin', function (event, ui) {
        if ($(this).hasClass('adults-number')) {
          adultsSpinner.spinner("value", ui.value);
          passengersNumber = parseInt(childrenSpinner.val()) + parseInt(ui.value);
          setAdultsNumber(parseInt(ui.value));
        }
        if ($(this).hasClass('children-number')) {
          childrenSpinner.spinner("value", ui.value);
          setChildrenNumber(parseInt(ui.value));
          passengersNumber = parseInt(adultsSpinner.val()) + parseInt(ui.value);
          if (ui.value > $(this).val()) {
            $('.children-age-wrapper > .form-type-select:nth-child(' + ui.value + ')').show();
          }
          else {
            $('.children-age-wrapper > .form-type-select:nth-child(' + $(this).val() + ')').hide();
          }
        }
        setPassengersNumber(passengersNumber);
      });
      spinner.once('spinchange').on('spinchange', function (event, ui) {
        // @todo set all spinner value on this event.
        passengersNumber = parseInt(adultsSpinner.val()) + parseInt(childrenSpinner.val());
        setPassengersNumber(passengersNumber);
        setAdultsNumber(parseInt(adultsSpinner.val()));
        setChildrenNumber(parseInt(childrenSpinner.val()));
        if ($(this).hasClass('children-number')) {
          if ($(this).val() >= 0 && $(this).val() <= 10) {
            var numberForHide = 10 - $(this).val();
            if (numberForHide > 0) {
              for (i = parseInt($(this).val()) + 1; i <= 10; i++) {
                $('.children-age-wrapper > .form-type-select:nth-child(' + i + ')').hide();
              }
            }
            for (i = 1; i <= $(this).val(); i++) {
              $('.children-age-wrapper > .form-type-select:nth-child(' + i + ')').show();
            }
          }
        }
      });

      $('.children-age-wrapper select.children').once('changeChildAge').on('change', function () {
        var childAge = $(this).val();
        var child = $(this).attr('child');
        $('.children-age-wrapper select.children.child-' + child).each(function () {
          $(this).val(childAge).trigger("chosen:updated");
        });
      });

      //  Hide/Show behavior
      $('.passengers-number').once('passengersDialog').click(function() {
        // Mobile travel date dialog
        if ($(window).width() < maxWidth) {
          // Dialog creation
          var $passengersWrapper = $(this).siblings('.passengers-wrapper');
          $passengersWrapper.dialog({
            title: Drupal.t("Passengers", {}, {'context': 'Search Form'}),
            dialogClass: "popup-search-form passengers",
            height: $(window).height(),
            width: $(window).width(),
            autoOpen: false,
            resizable: false,
            draggable: false,
            buttons: [{
              text: Drupal.t('OK', {}, {'context': 'Search Form Passenger Dialog'}),
              click: function() {
                $(this).dialog("destroy");
                $(dialogContainerSelector).removeClass(dialogActiveClassName);
              }
            }],
            create: function(event, ui) {
              $(dialogContainerSelector).addClass(dialogActiveClassName);
            },
            beforeClose: function(event, ui) {
              $(dialogContainerSelector).removeClass(dialogActiveClassName);
            },
            open: function () {
              toTop();
            }
          });
          $passengersWrapper.bind(
            'dialogclose',
            function(event) {
              if ($(this).data("ui-dialog")) {
                $(this).dialog("destroy");
              }
            }
          );
          // Displaying
          $passengersWrapper.dialog('open');
        }
        else {
          // Toggle passengers field
          $('.passengers-field .passengers-wrapper').toggle();
        }
      });

      $('.roundtrip-mode.form-wrapper .leg-1 .travel-date-wrapper').append($('.roundtrip-mode.form-wrapper .leg-2.datepicker-element.departure-date'));
      $('.roundtrip-mode.form-wrapper .leg-1 .travel-date-wrapper').append($('.roundtrip-mode.form-wrapper input.leg-2.departure-date'));
      $('.roundtrip-mode.form-wrapper .leg-2 .travel-date-wrapper').remove();

      // Initialize Datepicker.
      var date = new Date();
      date.setTime( date.getTime() + drupalSettings.min_days_before_departure * 86400000 );
      var departureDate = $('.datepicker-element.leg-1.departure-date');
      var departureDate2 = $('.datepicker-element.leg-2.departure-date');
      var datePicker = $('.datepicker-element');
      var dateFormat = 'dd.mm.yy';
      var minDateValue = $.datepicker.formatDate(dateFormat, date);
      var currentDate;
      var currentDate2;
      var outputDateValue;
      var numberOfMonths = ($(window).width() < maxWidth) ? [2, 1] : 2;

      // Initialize datepicker.
      datePicker.each(function () {
        if ($(this).closest('.form-flex-container').hasClass('roundtrip-mode')) {

          // Global variables to track the date range
          var cur = -1, prv = -1, counter = 0;
          $(this).datepicker({
            dateFormat: dateFormat,
            minDate: minDateValue,
            showOtherMonths: true,
            numberOfMonths: numberOfMonths,
            beforeShowDay: function ( date ) {
              if ($(this).siblings('input.leg-1.departure-date').val()) {
                prv = $.datepicker.parseDate('dd.mm.yy', $(this).siblings('input.leg-1.departure-date').val());
              }
              if ($(this).siblings('input.leg-2.departure-date').val()) {
                cur = $.datepicker.parseDate('dd.mm.yy', $(this).siblings('input.leg-2.departure-date').val());
              }
              var dateClass = '';
              if (counter !== 1 && +date >= Math.min(+prv, +cur) && +date <= Math.max(+prv, +cur)) {
                dateClass = 'date-range-selected';
                if (+date === Math.min(+prv, +cur)) {
                  dateClass = 'date-range-selected picked-date departure';
                }
                if (+date === Math.max(+prv, +cur)) {
                  dateClass = 'date-range-selected picked-date arrival';
                }
                if (+date === Math.min(+prv, +cur) && +date === Math.max(+prv, +cur)) {
                  dateClass = 'date-range-selected picked-date one-day-roundtrip';
                }
              }

              return [true, dateClass];
            },
            onSelect: function ( dateText, inst ) {
              counter++;
              $(this).data('datepicker').inline = true;
              var d1, d2;
              cur = $(this).datepicker('getDate');
              if ( counter === 1 ) {
                prv = cur;
                setDate(departureDate, $.datepicker.formatDate(dateFormat, prv, {}));
                setDate(departureDate2, '');
                departureDate.siblings('input.leg-1.departure-date').val(dateText);
                departureDate2.siblings('input.leg-2.departure-date').val('');
                $(this).siblings('.travel-date-input').text( dateText );
              } else {
                if (+prv > +cur) {
                  d1 = $.datepicker.formatDate(dateFormat, cur, {});
                  d2 = $.datepicker.formatDate(dateFormat, prv, {});
                }
                else {
                  d1 = $.datepicker.formatDate(dateFormat, prv, {});
                  d2 = $.datepicker.formatDate(dateFormat, cur, {});
                }
                setDate(departureDate, d1);
                setDate(departureDate2, d2);
                departureDate.siblings('input.leg-1.departure-date').val(d1);
                departureDate2.siblings('input.leg-2.departure-date').val(d2);
                $(this).siblings('.travel-date-input').text( d1+' - '+d2 );
              }
              if ( counter === 2 ) {
                counter = 0;
                if ( $(window).width() >= maxWidth  ) {
                  datePicker.hide();
                }
              }
            },
            onClose: function () {
              $(this).data('datepicker').inline = false;
            }
          });
        }
        else {
          $(this).datepicker({
            dateFormat: dateFormat,
            minDate: minDateValue,
            showOtherMonths: true,
            onSelect: function ( dateText, inst ) {
              $(this).data('datepicker').inline = true;
              var departureDateValue = '';
              var departureDate2Value = '';
              $(this).siblings('input').trigger('change');
              if ($(this).hasClass('leg-1')) {
                currentDate = $(this).datepicker('getDate');
                departureDateValue = $(this).val();
                setDate(departureDate, departureDateValue);
                departureDate2.datepicker("option", "minDate", departureDateValue);
                if (!$(this).closest('.form-flex-container').find('.datepicker-element.leg-2.departure-date').length) {
                  setDate(departureDate2, currentDate);
                  $('.travel-date-wrapper input.leg-2.departure-date').val(departureDateValue);
                  $('.travel-date-input.leg-2').text(departureDateValue);
                }
                else {
                  currentDate2 = $(this).closest('.form-flex-container').find('.datepicker-element.leg-2.departure-date').datepicker('getDate');
                  if (+currentDate >= +currentDate2) {
                    setDate(departureDate2, currentDate);
                    $('.travel-date-wrapper input.leg-2.departure-date').val(departureDateValue);
                    $('.travel-date-input.leg-2').text(departureDateValue);
                  }
                }
                $('.travel-date-wrapper input.leg-1.departure-date').val(departureDateValue);
                departureDate2Value = departureDate2.val();
                outputDateValue = departureDateValue;
              }
              if ($(this).hasClass('leg-2')) {
                if ($(this).closest('.form-flex-container').hasClass('roundtrip-mode') || $(this).closest('.ui-dialog').length) {
                  if ($(this).closest('.form-flex-container').hasClass('roundtrip-mode')) {
                    departureDateValue = $(this).siblings('.datepicker-element.leg-1.departure-date').val();
                  }
                  else {
                    departureDateValue = $('.form-flex-container.complex-mode .datepicker-element.leg-1.departure-date').val();
                  }
                  if (!departureDateValue) {
                    departureDateValue = minDateValue;
                    departureDate.siblings('input.leg-1.departure-date').val(departureDateValue);
                    setDate(departureDate, departureDateValue);
                  }
                }
                departureDate2Value = $(this).val();
                setDate(departureDate2, departureDate2Value);
                $('.travel-date-wrapper input.leg-2.departure-date').val(departureDate2Value);
                outputDateValue = departureDate2Value;
              }
              $(this).siblings('.travel-date-input').text(outputDateValue);
              if (($(this).hasClass('leg-1') && $(this).closest('.form-flex-container').hasClass('roundtrip-mode'))
                || ($(this).closest('.ui-dialog').length && !$(this).closest('.complex-mode').length)) {
              }
              else {
                datePicker.hide();
              }
            },
            onClose: function () {
              $(this).data('datepicker').inline = false;
            }
          });
        }
      });

      // Clear date for each datepicker - need for clear behaviour.
      clearDate('all');

      function setDate($calendar, value) {
        /*if ($calendar.length = 1) {
          var formMode = $calendar.closest('.form-flex-container.form-wrapper').attr('form-mode');
          var leg = $calendar.attr('leg');
          $('.train-booking-search-form .form-flex-container').each(function () {
            if ($(this).attr('form-mode') != formMode) {
              $(this).find('.datepicker-element.departure-date.' + leg).datepicker('setDate', value);
            }
          });
        }
        else if ($calendar.length > 1) {*/
          $calendar.each(function () {
            $(this).datepicker('setDate', value);
          });
        //}
      }

      // Setting default value (comes from server).
      var departureDateDefaultValue = departureDate.attr('default-value');
      if (departureDateDefaultValue) {
        departureDate.datepicker('setDate', departureDateDefaultValue);
        departureDate2.datepicker('option', 'minDate', departureDateDefaultValue);
        $('.leg-1.travel-date-input').text(departureDate.val());
      }

      var departureDate2DefaultValue = departureDate2.attr('default-value');
      if (departureDate2DefaultValue) {
        departureDate2.datepicker('setDate', departureDate2DefaultValue);
        $('.roundtrip-mode .leg-1.travel-date-input').text(departureDate.val() + '-' + departureDate2.val());
      }

      // Error on submit.
      $('.train-booking-search-form .form-flex-container').each(function() {
        printTravelDate($(this), true);
      });

      $('.travel-date-input').once('pickTravelDate').click(function() {
        var formMode = $(this).closest('.form-flex-container.visible').attr('form-mode');
        var leg = $(this).attr('leg');
        var $pickDepartureDate = $('.' + formMode + ' .datepicker-element.leg-1.departure-date');
        var $pickDepartureDate2 = $('.' + formMode + ' .datepicker-element.leg-2.departure-date');
        //  Hide/Show behavior
        if (leg === 'leg-1') {
          if (!$(this).closest('.ui-dialog').length || $(this).closest('.complex-mode').length) {
            $pickDepartureDate.toggle();
          }
          if ($pickDepartureDate2.is(':visible') && $(this).closest('.form-flex-container').hasClass('complex-mode')) {
            $pickDepartureDate2.hide();
          }
          if ($(this).closest('.form-flex-container').hasClass('roundtrip-mode') && !$(this).closest('.ui-dialog').length) {
            //$pickDepartureDate2.toggle();
          }
        }
        else if (leg === 'leg-2') {
          if ($pickDepartureDate.is(':visible') && $(this).closest('.form-flex-container').hasClass('complex-mode')) {
            $pickDepartureDate.hide();
          }
          if (!$(this).closest('.ui-dialog').length || $(this).closest('.complex-mode').length) {
            $pickDepartureDate2.toggle();
          }
        }

        // Mobile travel date dialog
        var $dialog = $(this).closest('.travel-date-wrapper');
        if ($(window).width() < maxWidth && !$dialog.closest('.ui-dialog').length) {
          // Dialog creation
          $dialog.dialog({
            title: Drupal.t("Travel date", {}, {'context': 'Search Form'}),
            dialogClass: "popup-search-form travel-date",
            resizable: false,
            draggable: false,
            height: $(window).height(),
            width: $(window).width(),
            autoOpen: false,
            buttons: [{
              text: Drupal.t('OK', {}, {'context': 'Search Form Stations Dialog'}),
              click: function() {
                $(this).dialog("destroy");
                datePicker.hide();
                $(dialogContainerSelector).removeClass(dialogActiveClassName);
              }
            }],
            create: function(event, ui) {
              $(dialogContainerSelector).addClass(dialogActiveClassName);
            },
            beforeClose: function(event, ui) {
              $(dialogContainerSelector).removeClass(dialogActiveClassName);
            },
            open: function () {
              toTop();
            }
          });
          $dialog.bind(
            'dialogclose',
            function(event) {
              datePicker.hide();
              if ($(this).data("ui-dialog")) {
                $(this).dialog("destroy");
              }
            }
          );
          // Displaying
          $dialog.dialog('open');
        }
      });

      // Hides all open widgets if click to other area.
      $(document).once('hideDates').click(function(event) {
        if ($(window).width() >= maxWidth) {
          if(!$(event.target).closest('.ui-datepicker-header').length && !$(event.target).closest('.travel-date-wrapper').length) {
            datePicker.hide();
          }
        }
        if(!$(event.target).closest('#train-booking-search-form .passengers-field').length) {
          $('.passengers-field .passengers-wrapper').hide();
        }
        if(!$(event.target).closest('.form-flex-container.complex-mode .form-item-complex-mode-passengers-wrapper-adults').length && !$(event.target).closest('.adult-number-input').length) {
          $('.form-flex-container.complex-mode .form-item-complex-mode-passengers-wrapper-adults').hide();
        }
        if(!$(event.target).closest('.form-flex-container.complex-mode .children-wrapper').length  && !$(event.target).closest('.children-number-input').length) {
          $('.form-flex-container.complex-mode .children-wrapper').hide();
        }
      });

      // Reset date value for all datepickers or for special.
      function clearDate(element) {
        if (element === 'all') {
          datePicker.datepicker('setDate', null);
        }
        else {
          element.datepicker('setDate', null);
        }
      }

      function setPassengersNumber(passengersNumber) {
        if ($.isNumeric(passengersNumber) && passengersNumber > 0) {
          var output = Drupal.formatPlural(passengersNumber, '1 passenger', '@count passengers', {},
            {'context': 'Search Form'});
          $('.train-booking-search-form .passengers-field .passengers-number .value').text(output);
        }
      }

      // Complex form another stucture of html.
      function setAdultsNumber(adultsNumber) {
        if ($.isNumeric(adultsNumber) && adultsNumber > 0) {
          var output = Drupal.formatPlural(adultsNumber, '1 adult', '@count adults', {},
            {'context': 'Search Form'});
          $('.form-flex-container.complex-mode .passengers-field .adult-number-input').text(output);
        }
      }

      // Complex form another stucture of html.
      function setChildrenNumber(passengersNumber) {
        if ($.isNumeric(passengersNumber) && passengersNumber >= 0) {
          var output = Drupal.formatPlural(passengersNumber, '1 child', '@count children', {},
            {'context': 'Search Form'});
          $('.form-flex-container.complex-mode .passengers-field .children-number-input').text(output);
        }
      }

      function toTop() {
        setTimeout(function(){
          // Hide the address bar!
          window.scrollTo(0, 1);
        }, 0);
      }

      // Switching forms
      $('#edit-form-switcher > span.basic').once('Switching forms').click(function () {
        $(this).addClass('active');
        $('.search-form-switcher .roundtrip').removeClass('active');
        $('.search-form-switcher .complex').removeClass('active');
        printTravelDate($('.train-booking-search-form .basic-mode'), false);
        $('.train-booking-search-form .basic-mode').addClass('visible');
        $('.train-booking-search-form .roundtrip-mode').removeClass('visible');
        $('.train-booking-search-form .complex-mode').removeClass('visible');
      });
      $('#edit-form-switcher > span.roundtrip').once('Switching forms').click(function () {
        datePicker.datepicker( "refresh" );
        $(this).addClass('active');
        $('.search-form-switcher .basic').removeClass('active');
        $('.search-form-switcher .complex').removeClass('active');

        // Settings stations based on first leg.
        var station;
        var $currentSelectize;
        var currentStationOptions;
        var currentValue;
        var $stationSelectize;
        var revert_station;
        $('.train-booking-search-form .roundtrip-mode .leg-1 .stations-wrapper select').each(function () {
          station = $(this).attr('station');
          $currentSelectize = $(this)[0].selectize;
          currentStationOptions = $currentSelectize.options;
          currentValue = $currentSelectize.getValue();
          if (!$.isEmptyObject(currentStationOptions) && currentValue) {
            revert_station = station == 'departure-station' ? 'arrival-station' : 'departure-station';
            $stationSelectize = $('.train-booking-search-form .roundtrip-mode .leg-2 .stations-wrapper select.' + revert_station)[0].selectize;
            $stationSelectize.addOption(currentStationOptions[currentValue]);
            $stationSelectize.addItem(currentValue);
          }
        });
        printTravelDate($('.train-booking-search-form .roundtrip-mode'), false);
        $('.train-booking-search-form .basic-mode').removeClass('visible');
        $('.train-booking-search-form .roundtrip-mode').addClass('visible');
        $('.train-booking-search-form .complex-mode').removeClass('visible');
      });
      $('#edit-form-switcher > span.complex').once('Switching forms').click(function () {
        $(this).addClass('active');
        $('.search-form-switcher .roundtrip').removeClass('active');
        $('.search-form-switcher .basic').removeClass('active');
        printTravelDate($('.train-booking-search-form .complex-mode'), false);
        $('.train-booking-search-form .complex-mode').addClass('visible');
        var dialogWidth = $('.train-search-form-block .block__inner').width();
        // Dialog creation
        $('.train-booking-search-form .form-flex-container.complex-mode').dialog({
          title: '',
          dialogClass: "popup-search-form complex-mode",
          width: dialogWidth,
          autoOpen: false,
          resizable: false,
          draggable: false,
          modal: true,
          create: function(event, ui) {
            $(dialogContainerSelector).addClass(dialogActiveClassName);
          },
          beforeClose: function(event, ui) {
            $(dialogContainerSelector).removeClass(dialogActiveClassName);
          },
          open: function () {
            toTop();
          }
        });
        $('.form-flex-container.complex-mode').bind(
          'dialogclose',
          function(event) {
            datePicker.datepicker( "refresh" );
            if ($(this).data("ui-dialog")) {
              $(this).dialog("destroy");
            }
            $(this).removeClass('visible');
            //$('.train-booking-search-form .form-flex-container.roundtrip-mode').after($(this));
            $('.search-form-switcher .complex').removeClass('active');
            var formMode = $('.form-flex-container.visible').attr('form-mode');
            if (formMode === 'basic-mode') {
              $('.search-form-switcher .basic').addClass('active');
              printTravelDate($('.train-booking-search-form .basic-mode'), false);
            }
            else if (formMode === 'roundtrip-mode') {
              $('.search-form-switcher .roundtrip').addClass('active');
              printTravelDate($('.train-booking-search-form .roundtrip-mode'), false);
            }
          }
        );
        // Displaying
        $('.form-flex-container.complex-mode').dialog('open');
      });

      $('body').once('dialogOverlayClick').on("click", ".ui-widget-overlay", function() {
        $('.form-flex-container.complex-mode').dialog('close');
      });


      function printTravelDate($form, initial) {
        var departureDateValue;
        var outputDateValue;
        var leg;
        $form.find('input.departure-date').each(function () {
          leg = $(this).attr('leg');
          if (initial && $(this).val()) {
            setDate($form.find('.datepicker-element.departure-date.' + leg), $(this).val());
            if (leg = 'leg-1') {
              $form.find('.datepicker-element.departure-date.leg-2').datepicker("option", "minDate", $(this).val());
            }
          }
          if ($form.hasClass('roundtrip-mode') && $(this).hasClass('leg-1')) {
            departureDateValue = $(this).val();
          }
          else if ($form.hasClass('roundtrip-mode') && $(this).hasClass('leg-2')) {
            if (departureDateValue && departureDateValue != " ") {
              outputDateValue = departureDateValue;
              if ($(this).val() && $(this).val() != " ") {
                outputDateValue = departureDateValue + ' - ' + $(this).val();
              }
            }
            if (outputDateValue) {
              $(this).siblings('.travel-date-input').text(outputDateValue);
            }
          }
          else {
            if ($(this).val() && $(this).val() != " ") {
              $(this).siblings('.travel-date-input').text($(this).val());
            }
          }
        });
      }

      var swapStations = '<div class="swap-stations"></div>';
      $('.basic-mode.form-wrapper .stations-wrapper > div:first-child').once('searchForm adding custom html').append(swapStations);
      $('.roundtrip-mode.form-wrapper .stations-wrapper > div:first-child').once('searchForm adding custom html').append(swapStations);

      $('.form-flex-container.complex-mode .form-actions input').click(function () {
        $('.form-flex-container.complex-mode').dialog('destroy');
        $('.form-flex-container.complex-mode').removeClass('visible');
        $('.search-form-switcher .complex').removeClass('active');
        //$('.train-booking-search-form .form-flex-container.roundtrip-mode').after($('.form-flex-container.complex-mode'));
        var formMode = $('.form-flex-container.visible').attr('form-mode');
        if (formMode === 'basic-mode') {
          $('.search-form-switcher .basic').addClass('active');
          printTravelDate($('.train-booking-search-form .basic-mode'), false);
        }
        else if (formMode === 'roundtrip-mode') {
          $('.search-form-switcher .roundtrip').addClass('active');
          printTravelDate($('.train-booking-search-form .roundtrip-mode'), false);
        }
      });

      function printArrayElements(element, index, array) {
        $(this).delay(1000).queue(function() {
          $('.train-booking-search-form  .custom-throbber .fact').remove();
          $('.train-booking-search-form .custom-throbber').append('<div class="fact">' + element + '</div>');
          $(this).dequeue();
        });
      }

      var story = ['Funny story', 'Interesting fact', 'Another interesting fact', 'Useful advice'];
      $('.form-flex-container .form-actions input').once('SubmitForm').click(function () {
        $('.train-booking-search-form .form-flex-container.roundtrip-mode').append('<div class="overlay"></div>');
        $('.train-booking-search-form .form-flex-container.roundtrip-mode .overlay').append('<div class="custom-throbber"><div class="loading"></div></div>');
        $('.train-booking-search-form .form-flex-container.basic-mode').append('<div class="overlay"></div>');
        $('.train-booking-search-form .form-flex-container.basic-mode .overlay').append('<div class="custom-throbber"><div class="loading"></div></div>');
        //story.forEach(printArrayElements);
      });
    }
  };
})(jQuery, Drupal);