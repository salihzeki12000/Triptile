app.controller('ConstructorSearchController', function($scope, $location, ngDialog, $filter, $window){

  $scope.tempfilter = {
    'filter': {},
  };

  $scope.minDateMoment = moment();

  $scope.showFilter = function(){
    $scope.$parent.isFilterOpen = !$scope.$parent.isFilterOpen;
    if($scope.$parent.isFilterOpen){
      $scope.tempfilter = {};
      $scope.tempfilter.filter = angular.copy($scope.filter);
    }
  }

  $scope.filterCancel = function(){
    $scope.$parent.isFilterOpen = false;
    $scope.tempfilter = {
      'filter': {},
    };
  }

  $scope.filterSave = function(){
    $scope.$parent.filter = angular.copy($scope.tempfilter.filter);
    $scope.$parent.isFilterOpen = false;
    $scope.tempfilter = {
      'filter': {},
    };
    $scope.$parent.returnFromMobile();
  }

  $scope.openFilters = function(){
    angular.element(document.querySelector('body')).addClass('shut-down-black');
    angular.element(document.querySelector('.constructor-search-wrapper')).addClass('displayblock');
    $scope.tempfilter = {};
    $scope.tempfilter.filter = angular.copy($scope.filter);
  };

  $scope.$on('initFilters', function(e) {
    console.log($scope.$parent.isFilterOpen);
    $scope.showFilter();
    console.log($scope.$parent.isFilterOpen);
  });

  /*
   * Who functions
   */
  // Open search container
  $scope.openSearchContainer = function($activeElement){
    $scope.hideDatePicker();
    if ($activeElement == 'who' && angular.isUndefined($scope.whoCount)){
      $scope.whoCount = 1;
    }
    $scope.showSearchContainer = true;
    $scope.searchActiveElement = $activeElement;
  };

  // Show search container
  $scope.showSearchContainerFunc = function($activeElement) {
    if(angular.element(document.querySelector('body')).hasClass('shut-down-black')){
      return true;
    }
    return $activeElement == $scope.searchActiveElement ? true : false;
  }

  $scope.whoPlus = function(){
    $scope.order.common.whoCount++;
    $scope.saveMainOrder($scope.order);
  }
  $scope.whoMinus = function(){
    $scope.order.common.whoCount != 1 ? $scope.order.common.whoCount-- : '';
    $scope.saveMainOrder($scope.order);
  }

  /*
   * Functions for filter
   */
  $scope.setHotelFilter = function(star){
    star = star[0][0];
    index = $scope.tempfilter.filter.star.indexOf(star);
    if(index == -1) {
      $scope.tempfilter.filter.star.push(star);
    }
    else{
      $scope.tempfilter.filter.star.splice(index, 1);
    }
  }

  $scope.isHotelFilterOptionActive = function(starCount){
    if(!angular.isUndefined($scope.tempfilter.filter.star)){
      index = $scope.tempfilter.filter.star.indexOf(starCount);
      if(index != -1) {
        return 'active';
      }
    }
    else{
      return '';
    }
  }

  $scope.showDatePicker = function($event){
    picker = angular.element(document.querySelector(".moment-picker.inline"));
    picker.addClass('displayblock');
    var target = $event.target;
    target.blur();
  };

  $scope.showDatePickerItinerary = function($event){
    if(window.innerWidth < 551){
      body = angular.element(document.querySelector("body"));
      body.addClass('shut-down-itinerary');

      input = angular.element(document.querySelector('.when-go'));
      input.addClass('displayblock');

      picker = angular.element(document.querySelector('.moment-picker.inline'));
      picker.addClass('displayblock');
    }
    else if(window.innerWidth > 550){
      picker = angular.element(document.querySelector('.moment-picker.inline'));
      picker.addClass('displayblock');
    }
    var target = $event.target;
    target.blur();

  };

  $scope.hideDatePicker = function(){
    picker = angular.element(document.querySelector(".moment-picker.inline"));
    picker.removeClass('displayblock');
  }

  $scope.onDatePickerChange = function(newValue, oldValue){
    if(!angular.isUndefined($scope.order.common) && !angular.isUndefined($scope.order.steps)){
      $scope.$parent.order.common.whenGoDate = newValue._d;
      $scope.$parent.saveMainOrder($scope.order);
      if(!angular.isUndefined($scope.temp.load[1])){
        $scope.$parent.reloadAllPricesForAllEntities();
      }
    }
  }

  /*
   * Watchers
   */

  $scope.$watch('order.common.whoCount', function(newval, oldval) {
    if(newval == null){
      $scope.order.common.whoCount = oldval;
    }
    $scope.setWhoCountText();
  });

  /*
   * Close datepicker
   */
  $window.addEventListener('click', function(e){
    if (angular.element('.moment-picker').find(e.target).length > 0 || e.srcElement.localName == 'td' ||
      angular.element(e.target).hasClass('when-go') || angular.element(e.target).hasClass('start-date')){
      // Clicked in calendar
    }
    else{
      // Clicked outside calendar
      $scope.hideDatePicker();
    }
  });

  $scope.changeWhoCount = function() {
    $scope.setWhoCountText();
  }

  $scope.setWhoCountText = function(){
    if (!angular.isUndefined($scope.order.common.whoCount)) {
      if ($scope.order.common.whoCount == 1) {
        text = 'traveler';
      }
      else {
        text = 'travelers';
      }
      $scope.whoCountText = $scope.order.common.whoCount + ' ' + text;
    }
  }

  $scope.returnItineraryTitle = function(){
    hub1Name = $scope.order.steps[1].hub.name
    lastStep = $scope.getLength($scope.order.steps);
    hub2Name = $scope.order.steps[lastStep].hub.name;
    return "From " + hub1Name + ' to ' + hub2Name + ' Tour';
  }

});