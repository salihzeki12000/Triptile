/*
 * Controller for edit step.
 */
app.controller('EditStepDialogCtrl', function ($scope, ngDialog) {

  $scope.currentStep = {
    delete: 0,
    hub: angular.copy($scope.order.steps[$scope.activeStepNum].hub),
  };

  $scope.closeDialog = function () {
    ngDialog.close();
  };

  /*
   * Return hub information
   */
  $scope.hubName = function(){
    if(!angular.isUndefined($scope.order.steps[$scope.activeStepNum].hub)){
      hubName = angular.copy($scope.order.steps[$scope.activeStepNum].hub.name);
      return hubName;
    }
    else{
      return '';
    }
  };

  $scope.hubCountry = function(){
    if(!angular.isUndefined($scope.order.steps[$scope.activeStepNum].hub)){
      hubCountry = angular.copy($scope.order.steps[$scope.activeStepNum].hub.country_name);
      return hubCountry;
    }
    else{
      return '';
    }
  };

  $scope.hubRegion = function(){
    if(!angular.isUndefined($scope.order.steps[$scope.activeStepNum].hub)){
      hubRegion = angular.copy($scope.order.steps[$scope.activeStepNum].hub.region);
      return hubRegion;
    }
    else{
      return '';
    }
  };

  $scope.hubDuration = function(timeOfDay){
    if(!angular.isUndefined($scope.order.steps[$scope.activeStepNum].hub)){
      count = '';
      if(timeOfDay == 'days'){
        count = angular.copy($scope.order.steps[$scope.activeStepNum].hub.days);
      }
      else{
        count = $scope.order.steps[$scope.activeStepNum].hub.days - 1;
      }
      return count;
    }
    else{
      return '';
    }
  };

  /*
   * Return hotel name
   */
  $scope.hotelName = function(){
    if(!angular.isUndefined($scope.order.steps[$scope.activeStepNum].hotel)){
      hotelName = $scope.order.steps[$scope.activeStepNum].hotel.name;
      return hotelName;
    }
    else{
      return '';
    }
  };

  /*
   * Return hotel description
   */
  $scope.hotelDescription = function(stepNum){
    if(!angular.isUndefined($scope.order.steps[stepNum].hotel)){
      hotel = $scope.order.steps[stepNum].hotel;
      star = hotel.star;
      return star + ' ' + Drupal.t('star hotel');
    }
    else if($scope.getLength($scope.load[stepNum].hotels) == 0){
      return Drupal.t('Hotels not found');
    }
    else{
      return Drupal.t('Not selected');
    }
  };

  $scope.hotelPriceName = function(stepNum){
    if(!angular.isUndefined($scope.order.steps[stepNum].hotel)){
      if(!angular.isUndefined(hotel.price)){
        return hotel.price.name
      }
    }
    return '';
  };

  $scope.stepPlusDays = function(){
    $scope.order.steps[$scope.activeStepNum].hub.days++;
  };

  $scope.stepMinusDays = function(){
    $scope.order.steps[$scope.activeStepNum].hub.days != 1 ? $scope.order.steps[$scope.activeStepNum].hub.days-- : '';
  };


  $scope.getDuration = function(type, withCount){
    count = angular.copy($scope.order.steps[$scope.activeStepNum].hub.days);
    if(type == 'days'){
      if(withCount){
        return Drupal.formatPlural(count, '1 day', '@count days');
      }
      else{
        return Drupal.formatPlural(count, 'day', 'days');
      }
    }
    else if(type == 'nights' && count > 1){
      count--;
      return ' / ' + Drupal.formatPlural(count, '1 night', '@count nights');
    }
  };

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
  }

});