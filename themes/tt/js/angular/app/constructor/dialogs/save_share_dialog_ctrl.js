/*
 * Edit activity popup controller.
 */
app.controller('EditSaveShareDialogCtrl', function ($scope, $http, ngDialog) {
  $scope.$parent.getUserIp();

  $scope.data = {
    show:{
      email4: 0,
      email5: 0,
      addEmailButton: 1
    },
    form: {
      email1: '',
      email2: '',
      email3: '',
      email4: '',
      email5: '',
    },
    saveShare: {}
  };

  $scope.showNextEmail = function(){
    if(!$scope.data.show.email4){
      $scope.data.show.email4 = 1;
    }
    else if(!$scope.data.show.email5){
      $scope.data.show.email5 = 1;
      $scope.data.show.addEmailButton = 0;
    }
  };

  $scope.saveAndShareOrder = function(){
    $scope.order.saveAndShare = true;
    if($scope.form.$error){
      $http.post('/save/order', $scope.order)
        .then(function (response) { //Save order success
            withoutCurrency = 0;
            countryData = angular.element("#phone-input").intlTelInput("getSelectedCountryData");
            phone = angular.element("#phone-input").val();

            $scope.data.saveShare = {
              name: $scope.data.form.name,
              ip: $scope.$parent.data.ip,
              email1: $scope.data.form.email1,
              email2: $scope.data.form.email2,
              email3: $scope.data.form.email3,
              email4: $scope.data.form.email4,
              email5: $scope.data.form.email5,
              phone: '+' + countryData.dialCode + phone,
              total: $scope.getOrderTotal(withoutCurrency),
              whoGo: $scope.order.common.whoCount,
              whenGo: $scope.order.common.whenGoDate,
              description: $scope.data.form.description,
              link: window.location.origin + '/en/itinerary/' + response.data,
            };

            $http.post('/export/save-share', $scope.data.saveShare)
              .then(function (response) { //Share success
                  var header = Drupal.t('Success');
                  var description = Drupal.t('Your trip has been shared');
                  ngDialog.open({
                    template: '<h2>'+header+'</h2><div class="description">'+description+'</div>',
                    className: 'messages-popup success-message ngdialog-theme-default',
                    plain: true
                  });
                },
                function (response) { //Share error
                  var header = Drupal.t('Error');
                  var description = Drupal.t('Please, reload page');
                  ngDialog.open({
                    template: '<h2>'+header+'</h2><div class="description">'+description+'</div>',
                    className: 'messages-popup error-message ngdialog-theme-default',
                    plain: true
                  });
                });
          },
          function (response) { //Save order error
            var header = Drupal.t('Error');
            var description = Drupal.t('Please, reload page');
            ngDialog.open({
              template: '<h2>'+header+'</h2><div class="description">'+description+'</div>',
              className: 'messages-popup error-message ngdialog-theme-default',
              plain: true
            });
          });
    }
  };

});
