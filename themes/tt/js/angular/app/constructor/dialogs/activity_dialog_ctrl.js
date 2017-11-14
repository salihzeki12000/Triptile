/*
 * Edit activity popup controller.
 */
app.controller('EditActivityDialogCtrl', function ($scope, ngDialog, $filter) {

  $scope.currentStep = {};
  $scope.currentStep.activitiesArray = [];

  /*
   * Get activities and prices for popup
   */
  $scope.currentStep.prices = [];
  pricesActivities = angular.copy($scope.load[$scope.activeStepNum].prices.activities);
  angular.forEach(pricesActivities, function(price) {
    $scope.currentStep.prices.push(price);
  });

  $scope.currentStep.activities = [];
  activities = angular.copy($scope.load[$scope.activeStepNum].activities);
  angular.forEach(activities, function(element) {
    $scope.currentStep.activities.push(element);
  });

  if(!angular.isUndefined($scope.order.steps[$scope.activeStepNum].activity)){
    $scope.currentStep.activity = angular.copy($scope.order.steps[$scope.activeStepNum].activity);
    $scope.currentStep.activitiesArray = angular.copy($scope.order.steps[$scope.activeStepNum].activity);
  }


  $scope.getActivityName = function(){
    if(!angular.isUndefined($scope.currentStep.activitiesArray)){
      if($scope.currentStep.activitiesArray.length > 0){
        activitiesNames = [];
        angular.forEach($scope.currentStep.activitiesArray, function(activity){
          activitiesNames.push(activity.name);
        });
        return activitiesNames.join(', ');
      }
      else{
        return Drupal.t('Please, select activity');
      }
    }
    else{
      return Drupal.t('Please, select activity');
    }

  };


  $scope.getActivityPrice = function(activityId){
    activity = $filter("filter")($scope.currentStep.activities, {id: activityId});
    priceId = activity[0].price_options[0];
    priceObject = $filter("filter")($scope.currentStep.prices, {id: priceId});
    whoCount = angular.copy($scope.order.common.whoCount);
    if(!angular.isUndefined(priceObject[0])){
      priceNumber = priceObject[0].price__number;
      if(!angular.isUndefined(priceObject)) {
        return $filter('formatPriceObject')( {price__number: priceNumber * whoCount});
      }
    }
  }

  $scope.getActivityTotalPrice = function(){
    priceNumber = 0;
    whoCount = angular.copy($scope.order.common.whoCount);
    angular.forEach($scope.currentStep.activitiesArray, function(activity){
      priceNumber = parseInt(priceNumber) + parseInt(angular.copy(activity.price.price__number));
    });
    if(priceNumber != 0){
      return $filter('formatPriceObject')({price__number: priceNumber * whoCount});
    }
  }

  $scope.isActivityActive = function(activityId){
    activities = $scope.currentStep.activitiesArray;
    index = activities.map(function(e) { return e.id; }).indexOf(activityId);
    if(index != -1){
      return 'active';
    }
  }

  $scope.setActivity = function(activityId){
    activity = $filter("filter")($scope.currentStep.activities, {id: activityId});
    activity = angular.copy(activity[0]);
    priceId = activity.price_options[0];
    priceObject = $filter("filter")($scope.currentStep.prices, {id: priceId});
    activity.price = priceObject[0];

    $scope.currentStep.activity = activity;

    activities = $scope.currentStep.activitiesArray;
    index = activities.map(function(e) { return e.id; }).indexOf(activity.id);
    if(index == -1) {
      $scope.currentStep.activitiesArray.push(activity);
    }
    else{
      $scope.currentStep.activitiesArray.splice(index, 1);
    }
  };

  $scope.deleteActivity = function(){
    delete $scope.order.steps[$scope.activeStepNum].activity;
    $scope.saveMainOrder($scope.order);
    $scope.closeThisDialog();
  }

  $scope.cancel = function () {
    delete $scope.currentStep;
    $scope.closeThisDialog();
  };


  /*
   * @todo Save multiple activities and re-count total
   */
  $scope.saveOrder = function () {
    if($scope.currentStep.activitiesArray.length > 0){
      $scope.order.steps[$scope.activeStepNum].activity = angular.copy($scope.currentStep.activitiesArray);
    }
    else{
      delete($scope.order.steps[$scope.activeStepNum].activity);
    }

    $scope.saveMainOrder($scope.order);
    $scope.closeThisDialog();
    delete $scope.currentStep;
  };
});