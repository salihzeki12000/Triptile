app.controller('FrontSearchController', function($scope, $http, $filter, $window, $document, $timeout, $localStorage){

  $scope.searchText = '';
  $scope.class = {};
  $scope.class.whereGoOpen = [];
  $scope.minDateMoment = moment();
  $scope.scrollTo = {
    top: 100,
    speed: 1000,
  };

  // Load order when page open
  $scope.order = $localStorage.step1order;

  $scope.currentStep = { };
  if(angular.isUndefined($scope.order)){
    $scope.order = {};
  }
  if(!angular.isUndefined($scope.order.steps)){
    $scope.currentStep.whenGo = $scope.order.whenGo;
    $scope.whenGo = $filter('date')($scope.currentStep.whenGo,'MM/dd/yyyy');
    $scope.currentStep.whoGo = $scope.order.whoGo;
    $scope.currentStep.whereGo = $scope.order.whereGo;
  }

  // Get region data
  $scope.regions = [];
  $scope.hubs = [];
  $http.get('/region-country/get.json')
    .then(function(res){
      $scope.regions = res.data;
      delete $scope.regions._core;

      // Get hubs data
      $http.get('/load/hubs/get.json')
        .then(function(res){
          hubs = angular.copy(res.data);

          // Set region to hub object
          angular.forEach(hubs, function(hub, h_index){
           $scope.hubs[h_index] = hub;
            angular.forEach(hub, function(prop, prop_index){
              $scope.hubs[h_index][prop_index] = prop;
            });
          });

          if(!angular.isUndefined($scope.currentStep.whereGo)){
            //hub = $filter("filter")($scope.hubs, {id: $scope.currentStep.whereGo});
            //$scope.searchText = hub[0].name;
          }

        });

    });

  // Redirect user to next page and save variables to session
  $scope.go = function(path) {

    if(!angular.isUndefined($scope.momentDate)){
      $scope.currentStep.whenGo = angular.copy($scope.momentDate._d);
    }

    if(angular.isUndefined($scope.currentStep.whereGo) || $scope.currentStep.whereGo == ''){
      $scope.class.whereGo = 'error';
    }
    if(angular.isUndefined($scope.currentStep.whenGo) || $scope.currentStep.whenGo == ''){
      $scope.class.whenGo = 'error';
    }
    if(angular.isUndefined($scope.currentStep.whoGo) || $scope.currentStep.whoGo == ''){
      $scope.class.whoGo = 'error';
    }

    if($scope.class.whereGo != 'error' && $scope.class.whenGo != 'error' && $scope.class.whoGo != 'error'){
      $scope.order = {
        'whereGo': $scope.currentStep.whereGo,
        'whenGo': $scope.currentStep.whenGo,
        'whoGo': $scope.currentStep.whoGo
      };
      $localStorage.step1order = $scope.order;
      window.location = path;
    }
    else{
      $scope.showErrorMessage = true;
    }
  };

  // Plus & minus for who field
  $scope.whoPlus = function(){
    $scope.currentStep.whoGo++;
  };

  $scope.whoMinus = function(){
    $scope.currentStep.whoGo != 1 ? $scope.currentStep.whoGo-- : '';
  };

  $scope.changeWhoGo = function() {
    $scope.setWhoGoText();
  }

  $scope.setWhoGoText = function(){
    if (!angular.isUndefined($scope.currentStep.whoGo)) {
      var translatedText = Drupal.formatPlural($scope.currentStep.whoGo, '1 traveler', '@count travelers');
      $scope.currentStep.whoGoText = translatedText;
    }
  }

  // Open search container
  $scope.openSearchContainer = function(activeElement){
    $scope.hideCalendar();

    var scrollTo = angular.element(document.getElementById('scrollto'));
    $document.scrollTo(scrollTo, $scope.scrollTo.top, $scope.scrollTo.speed);

    $scope.showErrorMessage = 0;

    if (activeElement == 'who' && angular.isUndefined($scope.currentStep.whoGo)){
      $scope.currentStep.whoGo = 1;
    }

    if(window.innerWidth < 801){
      body = angular.element(document.querySelector("body"));
      body.addClass('shut-down');

      input = angular.element(document.querySelector('.' + activeElement + '-go'));
      input.addClass('displayblock');

      if(activeElement == 'when'){
        picker = angular.element(document.querySelector('.moment-picker.inline'));
        picker.addClass('displayblock');
      }

      if(activeElement == 'who'){
        picker = angular.element(document.querySelector('.who-go-open'));
        picker.addClass('displayblock');
      }

    }
    else if(window.innerWidth > 801 && activeElement == 'when'){
      picker = angular.element(document.querySelector('.moment-picker.inline'));
      picker.addClass('displayblock');
    }

    $scope.showSearchContainer = true;
    $scope.searchActiveElement = activeElement;
  };

  $scope.openCalendar = function($event){
    if(window.innerWidth < 801){
      body = angular.element(document.querySelector("body"));
      body.addClass('shut-down');

      input = angular.element(document.querySelector('.when-go'));
      input.addClass('displayblock');

      picker = angular.element(document.querySelector('.moment-picker.inline'));
      picker.addClass('displayblock');
    }
    else if(window.innerWidth > 801){
      picker = angular.element(document.querySelector('.moment-picker.inline'));
      picker.addClass('displayblock');
    }
    if(!angular.isUndefined($event)){
      var target = $event.target;
      target.blur();
    }

    var scrollTo = angular.element(document.getElementById('scrollto'));
    $document.scrollTo(scrollTo, $scope.scrollTo.top, $scope.scrollTo.speed);
  };

  $scope.hideCalendar = function(){
    body = angular.element(document.querySelector("body"));
    if(!body.hasClass('shut-down')){
      hideElements = angular.element(document.querySelectorAll('.displayblock'));
      hideElements.removeClass('displayblock');
    }
  };

  // Hide search container
  $scope.hideSearchContainer = function(){
    $scope.showSearchContainer = false;
  };

  // Show search container
  $scope.showSearchContainerFunc = function(activeElement) {
    return activeElement == $scope.searchActiveElement;
  };

  $window.addEventListener('click', function(e){
    if (angular.element('.moment-picker').find(e.target).length > 0 || e.srcElement.localName == 'td'
        || angular.element(e.target).hasClass('when-go')){
      // Clicked in calendar
    }
    else{
      // Clicked outside calendar
      $scope.hideCalendar();
    }
  });

  /*
   * Functions for regions
   */

  // Set current region on click. currentStep.selectRegion - variable, where save current selected region
  $scope.currentStep.selectRegion = '';
  $scope.setRegion = function(region_name) {
    $scope.searchText = '';
    if($scope.currentStep.selectRegion == region_name){
      $scope.currentStep.selectRegion = '';
      if(window.innerWidth < 801){
        body = angular.element(document.querySelector("body"));
        body.removeClass('region-selected');
      }
    }
    else{
      $scope.currentStep.selectRegion = region_name;
      if(window.innerWidth < 801){
        body = angular.element(document.querySelector("body"));
        body.addClass('region-selected');
      }
    }

    if(!region_name){
      $scope.searchText = '';
    }

    $scope.hubs2 = $scope.hubs;
    if(($filter('filter')($scope.hubs2, $scope.searchText)).length == 0){
      $scope.searchText = '';
    }
  };

  $scope.getHubs = function(){
    hubs = $filter('filter')($scope.hubs, {region: $scope.currentStep.selectRegion});
    hubs2 = $filter('filter')(hubs, $scope.searchText);
    if(hubs2.length > 0){
      return hubs2;
    }
    else{
      return $scope.hubs;
    }
  };

  $scope.getRegionName = function(regionName){
    regionName = $filter('capitalize')(regionName);
    return Drupal.t(regionName + 'ern Europe');
  };

  // Show-hide regions function
  $scope.showRegions = function(){
    filtered = $filter('filter')($scope.hubs, {region: $scope.currentStep.selectRegion});
    allHubsCount = ($filter('filter')(filtered, $scope.searchText)).length;

    if((allHubsCount > 0 && $scope.searchText.length == 0) || (allHubsCount == 0 && $scope.searchText.length != 0)){
      $scope.class.whereGoOpen = [];
      return true;
    }
    else{
      $scope.class.whereGoOpen = ['region-close'];
      return false;
    }
  };
  $scope.hideShowMobileRegion = function(){
    if(window.innerWidth < 801){
      if($scope.currentStep.selectRegion == ''){
        return true;
      }
      else{
        return false;
      }
    }
    else{
      return true;
    }
  };

  // Show-hide regions function
  $scope.getRegionCitiesCount = function(region_name){
    allHubs = $filter('filter')($scope.hubs, {'region':region_name});
    allHubsCount = ($filter('filter')(allHubs, $scope.searchText)).length;
    if((allHubsCount > 0 && $scope.searchText.length == 0) || allHubsCount == 0 || allHubsCount == ''){
      citiesLength = ($filter('filter')($scope.hubs, {'region':region_name})).length
      return Drupal.formatPlural(citiesLength, '1 city', '@count cities');
    }
    else{
      return '0';
    }
  };

  $scope.showNotFound = function(){
    filtered = $filter('filter')($scope.hubs, {region: $scope.currentStep.selectRegion});
    allHubsCount = ($filter('filter')(filtered, $scope.searchText)).length;
    if(!allHubsCount){
      if(window.innerWidth < 801){
        $scope.currentStep.selectRegion = '';
      }
      return true;
    }
    else{
      return false;
    }
  };

  $scope.showCities = function(){
    filtered = $filter('filter')($scope.hubs, {region: $scope.currentStep.selectRegion});
    allHubsCount = ($filter('filter')(filtered, $scope.searchText)).length;
    if((!$scope.currentStep.selectRegion && !$scope.searchText) || allHubsCount == 0){
      return false;
    }
    else{
      return true;
    }
  };

  // When clicked on city - set search text to input
  $scope.setHub = function(hubName, hubId){
    $scope.searchText = hubName;
    $scope.currentStep.whereGo = hubId;
    $scope.searchActiveElement = '';
    $scope.returnFromMobileSearch();
    $scope.openCalendar();
  };

  $scope.onDatePickerChange = function(nv, ov){
    $timeout(function(){
      if(angular.isUndefined($scope.currentStep.whoGo)){
        $scope.returnFromMobileSearch();
        $scope.openSearchContainer('who');
      }
      else if(angular.isUndefined($scope.currentStep.whereGo)){
        $scope.returnFromMobileSearch();
        $scope.openSearchContainer('where');
      }

    }, 10);
  }

  $scope.returnFromMobileSearch = function(){
    body = angular.element(document.querySelector("body"));
    body.removeClass('shut-down');
    hideElements = angular.element(document.querySelectorAll('.displayblock'));
    hideElements.removeClass('displayblock');
  };

  $scope.getClassesForWrapper = function(){
    classes = [];
    if(!angular.isUndefined($scope.class)){
      error = false;
      angular.forEach($scope.class, function(className, classIndex){
        if(className == 'error'){
          classes.push('error-' + classIndex);
          error = true;
        }
      });
    }
    return classes;
  };

  $scope.showError = function(field){
    if($scope.class[field] == 'error' && $scope.showErrorMessage){
      return true;
    }
    else{
      return false;
    }
  };

  $scope.$watch(
    function () {
      if(typeof $window.whenGoFull != 'undefined'){
        $scope.currentStep.whenGo = $window.whenGoFull;
      }
    }, function(n,o){
    }
  );

  /*
   * Watchers
   */
  $scope.$watch('currentStep.whereGo', function(newval, oldval) {
    $scope.class.whereGo = '';
  });

  $scope.$watch('currentStep.whoGo', function(newval, oldval) {
    $scope.class.whoGo = '';
    $scope.setWhoGoText();
  });

  $scope.$watch('momentDate', function(newval, oldval) {
    $scope.class.whenGo = '';
  });

  $scope.$watch('currentStep.whenGo', function(newval, oldval) {
    $scope.class.whenGo = '';
  });

  $scope.$watch('whenGo', function(newval, oldval) {
    $scope.whenGo = $filter('date')($window.whenGoFull,'MM/dd/yyyy');
  });

});