/*
 * Edit transfer popup controller.
 */
app.controller('EditTransferDialogCtrl', function ($scope, ngDialog, $filter) {

  $scope.currentStep = {};

  /*
   * Get hotels and prices for popup
   */
  $scope.currentStep.transfers = [];
  angular.forEach($scope.load[$scope.activeStepNum].transfers, function(transfer) {
    $scope.currentStep.transfers.push(angular.copy(transfer));
  });

  $scope.currentStep.prices = [];
  angular.forEach($scope.load[$scope.activeStepNum].prices.transfers, function(price) {
    $scope.currentStep.prices.push(angular.copy(price));
  });

  if(!angular.isUndefined($scope.order.steps[$scope.activeStepNum].transfer)){
    $scope.currentStep.transfer = angular.copy($scope.order.steps[$scope.activeStepNum].transfer);
    if(!angular.isUndefined($scope.order.steps[$scope.activeStepNum].transfer.price)){
      $scope.currentStep.price = $scope.order.steps[$scope.activeStepNum].transfer.price;
    }
  }

  $scope.getTransferName = function(){
    if(!angular.isUndefined($scope.currentStep.transfer)){
      return $scope.currentStep.transfer.name;
    }
    else if(!angular.isUndefined($scope.order.steps[$scope.activeStepNum].transfer)){
      return $scope.order.steps[$scope.activeStepNum].transfer.name;
    }
    else{
      return Drupal.t('Please, select transfer');
    }
  }

  $scope.transferPrice = function(transferId){
    if(!angular.isUndefined($scope.currentStep.transfer)){
      transfer = $filter("filter")($scope.currentStep.transfers, {id: transferId});
      priceId = transfer[0].price_options[0];
      priceObject = $filter("filter")($scope.currentStep.prices, {id: priceId});

      if(!angular.isUndefined(priceObject)) {
        price = priceObject[0];
        whoCount = angular.copy($scope.order.common.whoCount);
        maxQuantity = $scope.data.transferDefaultCount;
        if(!angular.isUndefined(price)){
          if(price.max_quantity != '' && price.max_quantity != null){
            maxQuantity = price.max_quantity;
          }
          count = window.Math.ceil(whoCount / maxQuantity);
          transferPrice = $filter('number')(price.price__number, 0);

          return $filter('formatPriceObject')({
            price__number: transferPrice * count,
            price__currency_code: '$',
          });
        }
      }
    }
  }

  $scope.setTransfer = function(transferId){
    transfer = $filter("filter")($scope.currentStep.transfers, {id: transferId});
    priceId = transfer[0].price_options[0];
    priceObject = $filter("filter")($scope.currentStep.prices, {id: priceId});
    transfer[0].price = priceObject[0];
    $scope.currentStep.transfer = transfer[0];
    $scope.isChooseTransferOpen = 0;
  };

  $scope.isTransferActive = function(transferId){
    if(transferId == $scope.currentStep.transfer.id){
      return 'active';
    }
  }

  $scope.deleteTransfer = function(){
    delete $scope.order.steps[$scope.activeStepNum].transfer;
    $scope.saveMainOrder($scope.order);
    $scope.closeThisDialog();
  }

  $scope.cancel = function () {
    delete $scope.currentStep;
    $scope.closeThisDialog();
  };

  $scope.saveOrder = function () {
    if(!angular.isUndefined($scope.currentStep.transfer)){
      $scope.order.steps[$scope.activeStepNum].transfer = angular.copy($scope.currentStep.transfer);
      $scope.saveMainOrder($scope.order);
    }

    $scope.closeThisDialog();
    delete $scope.currentStep;
  };

});