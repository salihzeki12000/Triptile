/*
 * Controller for edit hub.
 */
app.controller('EditHubDialogCtrl', function ($scope, ngDialog, $filter, $timeout) {

  $scope.currentStep = {
    days: angular.copy($scope.order.steps[$scope.activeStepNum].hub.days),
    hubId: angular.copy($scope.order.steps[$scope.activeStepNum].hub.id),
  };

  $scope.plusDays = function(){
    $scope.currentStep.days++;
  }

  $scope.minusDays = function(){
    $scope.currentStep.days != 1 ? $scope.currentStep.days-- : '';
  }

  $scope.cancel = function () {
    $scope.closeThisDialog();
  };

  $scope.setHub = function(hubIdNum){
    $scope.currentStep.hubId = hubIdNum[0][0];
    $scope.isChooseHubOpen = 0;
  };

  $scope.returnCurrentStepHubProp = function(prop_name){
    var hubId = '';
    if($scope.currentStep.hubId){
      hubId = $scope.currentStep.hubId;
    }

    return $scope.load.hubs[hubId][prop_name];
  }

  $scope.deleteHub = function(){
    // If it's first step, then delete order and go to front page
   if($scope.activeStepNum == 1){
      delete $scope.order;
      $scope.saveMainOrder(null);
      window.location = '/';
    }
    // Else delete steps from the end of steps and save order
    else{
      $scope.deleteStepsStartsFromStep($scope.activeStepNum);
      $scope.saveMainOrder($scope.order);
      $scope.refreshRecommendedHubs();
      ngDialog.closeAll();
    }
  }

  $scope.saveOrder = function () {
    hubId = $scope.currentStep.hubId;
    if($scope.order.steps[$scope.activeStepNum].hub.id != hubId){
      $scope.deleteEntitiesFromStep($scope.activeStepNum);
      $scope.deleteStepsStartsFromStep(parseInt($scope.activeStepNum) + 1);
      $scope.loadEntities($scope.activeStepNum, hubId, 'load');
    }
    $scope.order.steps[$scope.activeStepNum].hub = angular.copy($scope.load.hubs[hubId]);
    $scope.order.steps[$scope.activeStepNum].hub.days = angular.copy($scope.currentStep.days);

    if($scope.activeStepNum > 1){
      recommendedConnectionId = $scope.getRecommendedConnectionId(hubId, $scope.activeStepNum - 1);
      $scope.setRecommendedConnection($scope.activeStepNum, recommendedConnectionId);
    }

    $scope.setPreferredEntities($scope.activeStepNum);

    $timeout(function(){
      $scope.saveMainOrder($scope.order);
      $scope.refreshRecommendedHubs();
      $scope.closeThisDialog();
      delete $scope.currentStep;
    }, 1200);
  };

  /*
   * Delete steps from end to stepNum
   */
  $scope.deleteStepsStartsFromStep = function(stepNum){
    stepsNum = $scope.getLength($scope.order.steps);
    for (var i = stepsNum; i >= stepNum; i--){
      delete $scope.order.steps[i];
      delete $scope.order.steps[i - 1].connection;
      delete $scope.load[i];
    }
  };

  $scope.getDuration = function(type){
    count = angular.copy($scope.currentStep.days);
    if(type == 'days'){
      return Drupal.formatPlural(count, '1 day', '@count days');
    }
    else if(type == 'nights' && count > 1){
      count--;
      return ' / ' + Drupal.formatPlural(count, '1 night', '@count nights');
    }
  };

  /*
   * Delete all entities from step if hub changes
   */
  $scope.deleteEntitiesFromStep = function(stepNumber){
    delete $scope.order.steps[stepNumber].hotel;
    delete $scope.order.steps[stepNumber].transfer;
    delete $scope.order.steps[stepNumber].activity;

    steps = Object.keys($scope.order.steps);
    for (var i = stepNumber + 1; i < steps.length; i++){
      delete $scope.order.steps[i];
    }
  }

});