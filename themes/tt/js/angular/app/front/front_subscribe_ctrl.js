app.controller('FrontSubscribeController', function($scope, $http, ngDialog, $document, $window){

  $scope.data = {
    class: {},
  };

  $scope.getUserIp = function(){
    var json1 = 'http://ipv4.myexternalip.com/json';
    var json2 = 'http://freegeoip.net/json/';
    $http.get(json1).then(function(result) {
      $scope.data.ip = result.data.ip;
    }, function(e) {
      $http.get(json2).then(function(result) {
        $scope.data.ip = result.data.ip;
      }, function(e) {
        // error for 2 services
      });
    });
  };

  $scope.getUserIp();

  $scope.subscribeLead = function(){
    $scope.clickedElementPosition = angular.copy($scope.pixelsScrolled);

    $scope.data.subscribe = {
      ip: $scope.data.ip,
      email: $scope.data.email
    };
    if(!angular.isUndefined($scope.data.email)){
      document.getElementById("loading").className = "show-load";
      $http.post('/export/subscribe', $scope.data.subscribe)
        .then(function(response){ //Share success
            document.getElementById("loading").className = "";
            var header = Drupal.t('You have been subscribed');
            var description = Drupal.t('We call you later');
            ngDialog.open({
              template: '<h2>'+header+'</h2><div class="description">'+description+'</div>',
              className: 'messages-popup success-message ngdialog-theme-default',
              plain: true
            });
          },
          function(response){ //Share error
            document.getElementById("loading").className = "";
            var header = Drupal.t('Error');
            var description = Drupal.t('Please, reload page');
            ngDialog.open({
              template: '<h2>'+header+'</h2><div class="description">'+description+'</div>',
              className: 'messages-popup error-message ngdialog-theme-default',
              plain: true
            });
          });
    }
    else{
      $scope.data.class.email = 'field-error';
    }
  }

  $scope.clearClass = function(){
    $scope.data.class.email = '';
  }

  $document.on('scroll', function() {
    $scope.$apply(function () {
      $scope.pixelsScrolled = $window.scrollY;
    });
  });

  $scope.$on('ngDialog.closed', function (e, $dialog) {
    $window.scrollTo(0, $scope.clickedElementPosition);
  });


});