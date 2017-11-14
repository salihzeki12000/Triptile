
/*
 * Edit connection popup controller.
 */
app.controller('EditConnectionDialogCtrl', function ($scope, ngDialog, $filter) {

  $scope.currentStep = {};

  /*
   * Get connections and prices for popup
   */
  $scope.currentStep.connections = [];
  angular.forEach($scope.load[$scope.activeStepNum].connections, function(element) {
    $scope.currentStep.connections.push(element);
  });

  if(!angular.isUndefined($scope.order.steps[$scope.activeStepNum].connection)){
    $scope.currentStep.connection = angular.copy($scope.order.steps[$scope.activeStepNum].connection);
  }

  $scope.currentStep.prices = [];
  angular.forEach($scope.load[$scope.activeStepNum].prices.connections, function(element) {
    $scope.currentStep.prices.push(element);
  });

  $scope.connectionName = function(){
    if(!angular.isUndefined($scope.currentStep.connection)) {
      return $scope.currentStep.connection.name;
    }
    else if(!angular.isUndefined($scope.order.steps[$scope.activeStepNum].connection)){
      return $scope.order.steps[$scope.activeStepNum].connection.name;
    }
    else{
      return Drupal.t('Please, choose connection');
    }
  };

  $scope.connectionPrice = function(connectionId){
    if(!angular.isUndefined($scope.currentStep.connection)){
      connection = $filter("filter")($scope.currentStep.connections, {id: connectionId});
      whoCount = angular.copy($scope.order.common.whoCount);
      priceId = angular.copy(connection[0].price_options[0]);
      priceObject = $filter("filter")($scope.currentStep.prices, {id: priceId});
      priceObject = priceObject[0];
      if(!angular.isUndefined(priceObject)) {
        priceNumber = priceObject.price__number;
        return $filter('formatPriceObject')({
          'price__number': priceObject.price__number,
          'price__currency': '$'
        });
      }
    }
  };

  $scope.connectionTotal = function(){
    if(!angular.isUndefined($scope.currentStep.connection.price)){
      priceObject = angular.copy($scope.currentStep.connection.price);
      whoCount = angular.copy($scope.order.common.whoCount);
      priceNumber = parseInt(priceObject.price__number) * parseInt(whoCount);
      if(typeof priceObject != 'undefined') {
        return $filter('formatPriceObject')({
          'price__number': priceNumber,
          'price__currency': '$'
        });
      }
    }
    else{
      return '';
    }
  };

  $scope.getPricesForConnections = function(){
    if(!angular.isUndefined($scope.currentStep.connection)) {
      connection = $filter("filter")($scope.currentStep.connections, {id: $scope.currentStep.connection.id});
      connectionPrices = $filter('filterPricesByEntity')($scope.currentStep.prices, connection[0]);
      return connectionPrices;
    }
  };

  $scope.setPrice = function(priceId){
    $scope.isChooseConnectionPriceOpen = 0;
    $scope.currentStep.priceId = priceId;
    price = $filter("filter")($scope.currentStep.prices, {id: $scope.currentStep.priceId})
    $scope.currentStep.connection.price = price[0];
  };

  $scope.priceName = function(){
    if(!angular.isUndefined($scope.currentStep.connection.price)) {
      return $scope.currentStep.connection.price.name;
    }
    else{
      return Drupal.t('Please, choose price');
    }
  };

  $scope.setConnection = function(connectionId){
    connection = $filter("filter")($scope.currentStep.connections, {id: connectionId});
    priceId = connection[0].price_options[0];
    priceObject = $filter("filter")($scope.currentStep.prices, {id: priceId});
    connection[0].price = priceObject[0];

    $scope.currentStep.connection = connection[0];
    $scope.isChooseConnectionOpen = 0;
  };

  $scope.isConnectionActive = function(connectionId){
    if(connectionId == $scope.currentStep.connection.id){
      return 'active';
    }
  };

  $scope.isPriceActive = function(priceId){
    if(priceId == $scope.currentStep.connection.price.id){
      return 'active';
    }
  };

  $scope.deleteConnection = function(){
    delete $scope.order.steps[$scope.activeStepNum].connection;
    $scope.saveMainOrder($scope.order);
    $scope.closeThisDialog();
  };

  $scope.cancel = function () {
    delete $scope.currentStep;
    $scope.closeThisDialog();
  };

  $scope.saveOrder = function () {
    $scope.order.steps[$scope.activeStepNum].connection = angular.copy($scope.currentStep.connection);

    $scope.saveMainOrder($scope.order);
    $scope.closeThisDialog();
    delete $scope.currentStep;
  };

});