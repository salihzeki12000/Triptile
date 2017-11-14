/*
 * Controller for edit hotel dialog popup.
 */
app.controller('EditHotelDialogCtrl', function ($scope, ngDialog, $filter) {

  $scope.currentStep = {};

  /*
   * Get hotels and prices for popup
   */
  $scope.currentStep.hotels = [];
  angular.forEach($scope.load[$scope.activeStepNum].hotels, function(element) {
    if (element.price_options){
      $scope.currentStep.hotels.push(element);
    }
  });

  $scope.currentStep.prices = [];
  angular.forEach($scope.load[$scope.activeStepNum].prices.hotels, function(element) {
    $scope.currentStep.prices.push(element);
    //$scope.currentStep.price = $scope.currentStep.prices[0];
  });

  if(!angular.isUndefined($scope.order.steps[$scope.activeStepNum].hotel)){
    $scope.currentStep.hotel = angular.copy($scope.order.steps[$scope.activeStepNum].hotel);
    hotelId = $scope.currentStep.hotel.id;
    $scope.currentStep.hotel.price_options = $scope.load[$scope.activeStepNum].hotels[hotelId].price_options;
  }

  /*
   * Filter for hotels.
   */
  $scope.allFilters = function(hotels){
    allFilters = $scope.filter.allStars;
    if(!angular.isUndefined($scope.filter.star)){
      if($scope.filter.star.length > 0){
        allFilters = $scope.filter.star;
      }
    }
    if(($filter('inArray')(hotels, allFilters, 'star')).length == 0){
      allFilters = $scope.filter.allStars;
    }
    return allFilters;
  }

  $scope.getHotelName = function(){
    if(!angular.isUndefined($scope.currentStep.hotel)) {
      return $scope.currentStep.hotel.name;
    }
    else if(!angular.isUndefined($scope.order.steps[$scope.activeStepNum].hotel)){
      return $scope.order.steps[$scope.activeStepNum].hotel.name;
    }
    else{
      return Drupal.t('Please, choose hotel');
    }
  }

  $scope.priceName = function(){
    output = '';
    if(!angular.isUndefined($scope.currentStep.hotel)) {
      if(!angular.isUndefined($scope.currentStep.hotel.price)) {
        output = $scope.currentStep.hotel.price.name;
      }
      else{
        output = Drupal.t('Please, choose hotel room');
      }
    }

    return output;
  }

  $scope.setHotel = function(hotelIdNum){
    $scope.isChooseHotelPriceOpen = 0;
    $scope.isChooseHotelOpen = 0;
    $scope.currentStep.hotelId = hotelIdNum;
    hotel = $filter('filter')($scope.currentStep.hotels, {id: $scope.currentStep.hotelId});
    $scope.currentStep.hotel = hotel[0];

    pricesCount = $scope.currentStep.hotel.price_options.length;
    if(pricesCount == 1){
      $scope.setPrice($scope.currentStep.hotel.price_options[0]);
    }
    else if(pricesCount > 1){
      prices = $filter('filterPricesByEntity')($scope.currentStep.prices, $scope.currentStep.hotel);
      angular.forEach(prices, function(price, priceIndex){
        if(typeof firstIndex == 'undefined'){
          firstIndex = priceIndex;
        }
        if(angular.isUndefined($scope.currentStep.hotel.price)){
          if(price.preferred == 1){
            $scope.setPrice(price.id);
          }
        }
      });
    }
  };

  $scope.isHotelActive = function(hotelId){
    if(hotelId == $scope.currentStep.hotel.id){
      return 'active';
    }
  }

  $scope.getPricesForHotel = function(){
    if(!angular.isUndefined($scope.currentStep.hotel)){
      hotelPrices = $filter('filterPricesByEntity')($scope.currentStep.prices, $scope.currentStep.hotel);
      return hotelPrices;
    }
  }

  $scope.setPrice = function(priceIdNum){
    if(typeof priceIdNum == 'object'){
      priceId = priceIdNum[0][0];
    }
    else{
      priceId = priceIdNum;
    }
    $scope.isChooseHotelPriceOpen = 0;
    $scope.currentStep.priceId = priceId;
    price = $filter("filter")($scope.currentStep.prices, {id: $scope.currentStep.priceId});
    $scope.currentStep.hotel.price = angular.copy(price[0]);
  }

  $scope.getHotelFilterCount = function(hotel){
    count = $scope.getLength($filter('filter')($scope.currentStep.hotels, {star:hotel.star}));
    var translatedText = '';
    if(count > 0){
      translatedText = Drupal.formatPlural(count, '1 option', '@count options');
    }
    return translatedText;
  }

  $scope.isPriceActive = function(priceId){
    if(priceId == $scope.currentStep.hotel.price.id){
      return 'active';
    }
  };

  $scope.getHotelTotal = function(){
    output = '';
    if(!angular.isUndefined($scope.currentStep.hotel)){
      if(!angular.isUndefined($scope.currentStep.hotel.price)){
        hub = $scope.order.steps[$scope.activeStepNum].hub;
        price = $scope.currentStep.hotel.price;

        price_number = $filter('number')(price.price__number, 0);
        price_number = price_number.replace(',', '');
        $scope.currentStep.priceName = angular.copy(price.name);
        $scope.currentStep.totalPrice = price_number * $scope.order.common.whoCount * hub.days;
        $scope.currentStep.currencyCode = price.price__currency_code;
        output = $filter('formatPriceObject')({
          price__number: $scope.currentStep.totalPrice,
          price__currency_code: $scope.currentStep.currencyCode
        });
      }
    }

    return output;
  }

  $scope.cancel = function () {
    delete $scope.currentStep;
    $scope.closeThisDialog();
  };

  $scope.saveOrder = function () {
    $scope.order.steps[$scope.activeStepNum].hotel = angular.copy($scope.currentStep.hotel);
    delete $scope.order.steps[$scope.activeStepNum].hotel.price_options;

    $scope.saveMainOrder($scope.order);
    $scope.closeThisDialog();
    delete $scope.currentStep;
  };

});
