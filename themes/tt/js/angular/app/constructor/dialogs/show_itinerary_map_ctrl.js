/*
 * Edit activity popup controller.
 */
app.controller('ShowItineraryMapDialogCtrl', function ($scope, $timeout) {
  $timeout(function() {
    $scope.$broadcast('initItineraryMap', {});
  }, 300);

});