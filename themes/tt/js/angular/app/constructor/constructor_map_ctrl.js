/*
 * Controller for map.
 */
app.controller('ConstructorMapCtrl', function($scope, $timeout, $http, leafletData){
  $scope.map = {
    'settings': {
      'token': 'pk.eyJ1IjoidHJhdmVsYWxscnVzc2lhIiwiYSI6IjA1Q2F1S3cifQ.BQwNPEEMC764gkvFZvRU7Q',
      'apiUrl': 'https://api.mapbox.com/v4/mapbox.outdoors/',
      'apiGeoJsonUrl': 'https://api.mapbox.com/geocoding/v5/mapbox.places/'
    }
  };
  $scope.map.data = {
    tiles: {
      url: $scope.map.settings.apiUrl + "{z}/{x}/{y}.png?access_token=" + $scope.map.settings.token,
      options: {
        attribution: '© <a href="https://www.mapbox.com/map-feedback/">Mapbox</a> © <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        minZoom: 3,
        maxZoom: 8
      }
    },
    center: {},
    markers:{},
    paths: {},
    connections: {
      'color': {
        'air': '#50B299',
        'ferry': '#7AB7CC',
        'rail': '#F09144',
        'car': '#FFE14C',
        'bus': '#6690CC',
        'walk': '#81F499',
        'subway': '#42494c'
      },
      'weight': 3
    },
    show: true,
    defaultZoom: 5
  };

  if(window.innerWidth < 671){
    $scope.map.data.show = false;
  }

  $scope.$on('showMap', function () {
    $scope.map.data.show = true;
    leafletData.getMap().then(function(map) {
      $timeout(function() {
        map.invalidateSize();
      }, 300);
    });
  });

  // Set markers
  $scope.$watch('order.steps', function(newVal, oldVal){
    setCoordinates = false;
    if(!angular.isUndefined(oldVal)){
      if($scope.getLength(newVal) < $scope.getLength(oldVal)){
        setCoordinates = true;
      }
    }

    angular.forEach(newVal, function(step, stepIndex){
      if(angular.isUndefined(oldVal)){
        setCoordinates = true;
      }
      else{
        if(angular.isUndefined(oldVal[stepIndex])){
          setCoordinates = true;
        }
        else{
          if(step.hub.id != oldVal[stepIndex].hub.id){
            setCoordinates = true;
          }
        }
      }
    });

    if(setCoordinates){
      $scope.map.data.markers = {};
      angular.forEach(newVal, function(step, stepIndex){
        $scope.setHubCoordinates(step, stepIndex)
      });
    }

  }, true);

  // Set connections

  $scope.$watch('order.steps', function(newVal, oldVal){
    setConnectionCoordinates = false;
    if(!angular.isUndefined(oldVal)){
      if(($scope.getLength(newVal) < $scope.getLength(oldVal)) && $scope.getLength(newVal) > 1){
        setConnectionCoordinates = true;
      }
    }

    angular.forEach(newVal, function(step, stepIndex){
      if($scope.getLength(newVal) > 1){
        if(angular.isUndefined(oldVal)){
          setConnectionCoordinates = true;
        }
        else if(stepIndex < $scope.getLength(newVal)){
          if(angular.isUndefined(oldVal[stepIndex])){
            setConnectionCoordinates = true;
          }
          else if(angular.isUndefined(oldVal[stepIndex].connection)){
            setConnectionCoordinates = true;
          }
          else if(step.connection.id != oldVal[stepIndex].connection.id){
            setConnectionCoordinates = true;
          }
        }
      }
    });

    if(setConnectionCoordinates){
      $timeout(function(){
        $scope.setConnectionCoordinates();
      }, 1000);
    }


  }, true);

  $scope.setHubCoordinates = function(step, stepIndex){
    $http.get($scope.map.settings.apiGeoJsonUrl + step.hub.name + '+' + step.hub.country_name + '.json?access_token=' + $scope.map.settings.token)
      .then(function(resultEntities){
        coordinates = resultEntities.data;
        $scope.load[stepIndex].coordinates = coordinates.features[0];

        $scope.map.data.markers[stepIndex] = {
          lat: coordinates.features[0].center[1],
          lng: coordinates.features[0].center[0],
          message: "<strong>" + step.hub.name + "</strong> " + step.hub.country_name,
          icon: {
            type: 'div',
            iconSize: [45, 66],
            iconAnchor: [23, 66],
            popupAnchor:  [0, -50],
            html: '<img src="/themes/tt/images/city-' + step.hub.region + '-selected.png" />' +
            '<span class="icon-number">' + stepIndex + '</span>',
          },
        };

        if(stepIndex == $scope.getLength($scope.order.steps)){
          $scope.map.data.center = {
            lat: coordinates.features[0].center[1],
            lng: coordinates.features[0].center[0],
            zoom: $scope.map.data.defaultZoom,
          }
        }

      });
  }

  $scope.setConnectionCoordinates = function(recommended = 1){
    steps = $scope.order.steps;
    $scope.map.data.paths = {};
    connectionType = '';
    angular.forEach(steps, function(step, stepIndex){
      if(stepIndex < $scope.getLength(steps)){
        nextStepIndex = parseInt(stepIndex) + 1;
        if(!angular.isUndefined(step.connection)){
          connectionType = step.connection.type;
          fromHub = step.hub.name;
          toHub = $scope.order.steps[nextStepIndex].hub.name;
          $scope.map.data.paths[stepIndex] = {
            color: $scope.map.data.connections.color[connectionType],
            weight: $scope.map.data.connections.weight,
            latlngs: [
              { lat: $scope.load[stepIndex].coordinates.center[1], lng: $scope.load[stepIndex].coordinates.center[0] },
              { lat: $scope.load[nextStepIndex].coordinates.center[1], lng: $scope.load[nextStepIndex].coordinates.center[0] },
            ],
          };
        }
      }
    });
    if(recommended){
      $scope.setRecommendedCoordinates();
    }
  }

  $scope.setRecommendedCoordinates = function(){
    angular.forEach($scope.map.data.markers, function(marker, markerNum){
      if($scope.getLength($scope.order.steps) < markerNum){
        delete $scope.map.data.markers[markerNum];
      }
    });
    angular.forEach($scope.map.data.paths, function(path, pathNum){
      if($scope.getLength($scope.order.steps) <= pathNum){
        delete $scope.map.data.paths[pathNum];
      }
    });
    recommendedStepNum = $scope.getLength($scope.map.data.markers) + 1;
    lastStep = $scope.getLength($scope.order.steps);
    angular.forEach($scope.recommended.hubs, function(hub){
      $http.get($scope.map.settings.apiGeoJsonUrl + hub.name + '+' + hub.country_name + '.json?access_token=' + $scope.map.settings.token)
        .then(function(resultEntities){
          recommendedStepNum++;
          coordinates = resultEntities.data;

          $scope.map.data.markers[recommendedStepNum] = {
            lat: coordinates.features[0].center[1],
            lng: coordinates.features[0].center[0],
            message: "<strong>" + hub.name + "</strong> " + hub.country_name,
            icon: {
              type: 'div',
              iconSize: [36, 54],
              iconAnchor: [18, 54],
              popupAnchor:  [0, -50],
              html: '<img src="/themes/tt/images/city-' + hub.region + '-not-selected.png" />'
            },
          };

          recommendedConnectionId = $scope.getRecommendedConnectionId(hub.id);
          recommendedConnection = $scope.load[lastStep].connections[recommendedConnectionId];
          connectionType = recommendedConnection.type;
          $scope.map.data.paths[recommendedStepNum-1] = {
            color: $scope.map.data.connections.color[connectionType],
            weight: $scope.map.data.connections.weight,
            opacity: 0.3,
            latlngs: [
              { lat: $scope.load[lastStep].coordinates.center[1], lng: $scope.load[lastStep].coordinates.center[0] },
              { lat: coordinates.features[0].center[1], lng: coordinates.features[0].center[0] },
            ],
          };

        });
    });
  }

  $scope.$on('setRecommendedCoordinates', function(e) {
    $scope.setRecommendedCoordinates();
  });

  $scope.classForMap = function(){
    if(!angular.isUndefined($scope.order.steps)){
      hubsCount = $scope.getLength($scope.order.steps)
      return 'count-' + hubsCount + '-hubs';
    }
  }

  $scope.$on('initItineraryMap', function(e) {
    console.log('123');
    $scope.setHubCoordinates($scope.order.steps[1], 1);
    angular.forEach($scope.order.steps, function(step, stepIndex){
      $scope.setHubCoordinates(step, stepIndex);
    });

    withoutRecommendedConnections = 0;
    $timeout(function() {
      $scope.setConnectionCoordinates(withoutRecommendedConnections);
    }, 300);
  });

});