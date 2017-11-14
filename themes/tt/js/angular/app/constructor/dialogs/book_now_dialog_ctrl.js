/*
 * Edit activity popup controller.
 */
app.controller('EditBookNowDialogCtrl', function ($scope, $http, ngDialog) {
  $scope.$parent.getUserIp();

  $scope.data = {
    form: {
      name: '',
      phone: ''
    },
    bookNow: {}
  };

  $scope.bookNowOrder = function(){
    if($scope.form.$error){
      $http.post('/save/order', $scope.order)
        .then(function(response){ //Save order success
            withoutCurrency = 0;
            countryData = angular.element("#phone-input").intlTelInput("getSelectedCountryData");
            phone = angular.element("#phone-input").val();

            $scope.data.bookNow = {
              name: $scope.data.form.name,
              ip: $scope.$parent.data.ip,
              email: $scope.data.form.email1,
              phone: '+' + countryData.dialCode + phone,
              total: $scope.getOrderTotal(withoutCurrency),
              whoGo: $scope.order.common.whoCount,
              whenGo: $scope.order.common.whenGoDate,
              description: $scope.data.form.description,
              link: window.location.origin + '/en/itinerary/' + response.data,
            };

            $http.post('/export/book-now', $scope.data.bookNow)
              .then(function(response){ //Book now success
                  var header = Drupal.t('Success');
                  var description = Drupal.t('Your trip has been booked');
                  ngDialog.open({
                    template: '<h2>'+header+'</h2><div class="description">'+description+'</div>',
                    className: 'messages-popup success-message ngdialog-theme-default',
                    plain: true
                  });
                },
                function(response){ //Book now error
                  var header = Drupal.t('Error');
                  var description = Drupal.t('Please, reload page');
                  ngDialog.open({
                    template: '<h2>'+header+'</h2><div class="description">'+description+'</div>',
                    className: 'messages-popup error-message ngdialog-theme-default',
                    plain: true
                  });
                });
          },
          function(response) { //Save order error
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