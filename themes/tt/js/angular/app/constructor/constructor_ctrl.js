/*
 * Main controller for constructor.
 */
app.controller('ConstructorCtrl', function($scope, $location, ngDialog, $http, $filter, $timeout, $window, $document, $localStorage){

  /*
   * Initialize all variables.
   */
  $scope.data = {
    hotelDefaultCount: 1,
    transferDefaultCount: 3,
    recommendedHubsCount: 15,
    wheelSpeed: 2,
    closeByDocument: true,
    floatMargin: 20,
  };

  $scope.filter = {
    allStars: ['2', '3', '4', '4+', '5', '5+'],
    star: []
  };

  $scope.isFilterOpen = false;

  $scope.allEntities = {
    hotels: {},
    transfers: {},
    activities: [],
    connections: {}
  };

  $scope.plural = {
    'hotels': 'hotel',
    'transfers': 'transfer',
    'activities': 'activity',
    'connections': 'connection'
  };

  $scope.temp = {
    load: {},
    preferred: {}
  };

  $scope.order = {
    common: {
      defaultHubDays: 3
    },
    saveAndShare: {}
  };

  $scope.currentStep = {};
  $scope.mobileVisibleElement = '';

  $scope.load = {};
  $scope.preferred = {};

  $scope.clickedElement = {};

  // Load itinerary object
  if(typeof drupalSettings.orderObject != 'undefined'){
    $scope.step2order = angular.fromJson(drupalSettings.orderObject);
  }

  if(!angular.isUndefined($localStorage.order)){
    $scope.step2order = $localStorage.order;
  }

  /*
   * Load first step
   */
  $scope.step1order = $localStorage.step1order;

  /*
   * Load second step
   */
  if(!angular.isUndefined($scope.step2order) || $scope.step2order == ''){
    $window.whenGoFull = $scope.step2order.common.whenGoDate;
  }
  else if(!angular.isUndefined($scope.step1order)){
    $window.whenGoFull = $scope.step1order.whenGo;
  }
  else{
    window.location = '/';
  }

  /*
   * Load entity for hub and price options for entity.
   */
  $scope.loadEntity = function(stepNum, hubId, entityType, type){
    $http.get('/load/'+ entityType +'/hub/' + hubId + '/get.json')
      .then(function(resultEntities){
        var entities = resultEntities.data;

        // Save data to temp data
        if(angular.isUndefined($scope.temp[type][stepNum])){
          $scope.temp[type][stepNum] = {};
        }
        $scope.temp[type][stepNum][entityType] = resultEntities.data;

        // Clean outdated entities and prices
        $scope.cleanAndReloadPricesForEntityForStartDate(stepNum, entityType, type);
      },
      //If error
      function() {
        console.log('please, reload page');
        console.log('stepNum');
        console.log(stepNum);
        console.log('hubId');
        console.log(hubId);
        console.log('entityType');
        console.log(entityType);
        console.log('type');
        console.log(type);
      });
  };

  /*
   * Load all entities(hotels, activities, transfers) for step
   */
  $scope.loadEntities = function(stepNum, hubId, type) {

    $scope[type][stepNum] = angular.copy($scope.allEntities);
    $scope[type][stepNum].prices = angular.copy($scope.allEntities);
    $scope[type][stepNum].preferred = angular.copy($scope.allEntities);

    $scope.loadEntity(stepNum, hubId, 'hotels', type);
    $scope.loadEntity(stepNum, hubId, 'transfers', type);
    $scope.loadEntity(stepNum, hubId, 'activities', type);
    $scope.loadEntity(stepNum, hubId, 'connections', type);

  };


  /*
   * Load all hubs
   */
  $scope.loadHubs = function(){
    $scope.regions = [];
    $http.get('/load/hubs/get.json')
      .then(function(result){
        $scope.load.hubs = result.data;
        $scope.loadOrder();
      }, function(){
        $scope.loadHubs();
      });
  };
  $scope.loadHubs();

  /*
   * Load order
   */
  $scope.loadOrder = function(){

    if(!angular.isUndefined($scope.step1order)){
      delete $scope.step2order;
    }

    /*
     * Load all entities for first step
     */
    if(angular.isUndefined($scope.step2order) || $scope.step2order == ''){
      var stepNum = 1;
      var hubId = $scope.step1order.whereGo;
      $scope.loadEntities(stepNum, hubId, 'load');

      $scope.order = {
        common: {
          whoCount: $scope.step1order.whoGo,
          whenGoDate: moment($scope.step1order.whenGo),
          defaultHubDays: 3
        }
      };
      $scope.momentDate = moment($scope.order.common.whenGoDate, moment.ISO_8601);

      $scope.order.steps = {};
      $scope.order.steps[stepNum] = {};
      $scope.order.steps[stepNum].hub = angular.copy($scope.load.hubs[hubId]);
      $scope.order.steps[stepNum].hub.days = angular.copy($scope.load.hubs[hubId].days);

      // Set prefered entities
      $scope.setPreferredEntities(stepNum);

      delete $localStorage.step1order;
    }
    else{
      $scope.order = $scope.step2order;
      $scope.momentDate = moment($scope.order.common.whenGoDate, moment.ISO_8601);

      angular.forEach($scope.order.steps, function(stepEntities, stepNum){
        hubId = stepEntities.hub.id;
        $scope.loadEntities(stepNum, hubId, 'load');
      });
    }

    $timeout(function(){
      $scope.refreshRecommendedHubs();
    }, 1000);
  };

  /*
   * Set prices for steps with whenGoDate from temp data
   */
  $scope.cleanAndReloadPricesForEntityForStartDate = function(stepNum, entityType, type){
    entities = $scope.temp[type][stepNum][entityType];
    data = '';
    data = $filter('cleanOutdatedEntitiesAndPrices')(entities, entityType, $scope.order.common.whenGoDate);
    $scope[type][stepNum][entityType] = data[entityType];
    $scope[type][stepNum].prices[entityType] = data['prices'];
    if(!angular.isUndefined(data['preferred'])){
      $scope[type][stepNum].preferred[entityType] = data['preferred'];
    }
  };

  /*
   * Reload all prices for entities.
   */
  $scope.reloadAllPricesForAllEntities = function(){
    angular.forEach($scope.order.steps, function(step, stepNum){
      angular.forEach($scope.allEntities, function(entity, entityType){
        $scope.cleanAndReloadPricesForEntityForStartDate(stepNum, entityType, 'load');
      });
    });

  }

  /*
   * Get preferred price id for entity type
   */
  $scope.getPreferredPriceIdForEntity = function(stepNum, preferredEntityType, preferredEntityId){
    // Choose first price
    entity = $scope.load[stepNum][preferredEntityType][preferredEntityId];
    if(!angular.isUndefined(entity.price_options)){
      priceId = entity.price_options[0];
    }
    else{
      priceId = '';
    }
    return priceId;
  };

  /*
   * Set preferred price object to entity id
   */
  $scope.setPreferredPriceForEntity = function(stepNum, pluralEntityType, preferredEntityType, priceId){
    if(pluralEntityType == 'activity'){
      $scope.order.steps[stepNum][pluralEntityType][0].price = angular.copy($scope.load[stepNum].prices[preferredEntityType][priceId]);
    }
    else{
      $scope.order.steps[stepNum][pluralEntityType].price = angular.copy($scope.load[stepNum].prices[preferredEntityType][priceId]);
    }
  };

  $scope.setPreferredEntities = function(stepNum){
    $timeout(function(){
      angular.forEach($scope.load[stepNum].preferred, function(preferredEntityId, preferredEntityType) {
        pluralEntityType = $scope.plural[preferredEntityType];
        entityStepNum = stepNum;
        if(pluralEntityType == 'connection'){
          entityStepNum = parseInt(stepNum) - 1;
          hubId = $scope.order.steps[stepNum].hub.id;
          preferredEntityId = $scope.getRecommendedConnectionId(hubId, entityStepNum);
        }

        if(!angular.isUndefined($scope.load[entityStepNum])){
          if(!angular.isUndefined($scope.load[entityStepNum][preferredEntityType][preferredEntityId])){
            if(pluralEntityType == 'activity'){
              $scope.order.steps[entityStepNum][pluralEntityType] = [];
              $scope.order.steps[entityStepNum][pluralEntityType][0] = angular.copy($scope.load[entityStepNum][preferredEntityType][preferredEntityId]);
            }
            else{
              $scope.order.steps[entityStepNum][pluralEntityType] = angular.copy($scope.load[entityStepNum][preferredEntityType][preferredEntityId]);
            }
            priceId = $scope.getPreferredPriceIdForEntity(entityStepNum, preferredEntityType, preferredEntityId);
            $scope.setPreferredPriceForEntity(entityStepNum, pluralEntityType, preferredEntityType, priceId);

          }
        }
      });
      $scope.saveMainOrder($scope.order);
    }, 1000);
  };

  $scope.addNewStep = function(stepNum, hubId, days, connectionId){
    $scope.loadEntities(stepNum, hubId, 'load');
    $scope.order.steps[stepNum] = {};
    $scope.order.steps[stepNum].hub = angular.copy($scope.load.hubs[hubId]);
    $scope.order.steps[stepNum].hub.days = days;

    $timeout(function(){
      $scope.refreshRecommendedHubs();
      $scope.setPreferredEntities(stepNum);
    }, 1000);
  };

  /*
   * Create new step and add hub to this step
   */
  $scope.addNewHub = function(hubId, days, connectionId){
    var lastStep = $scope.getLength($scope.order.steps);
    $scope.hubsLoaded = false;
    $scope.addNewStep(lastStep + 1, hubId, days, connectionId);
    $scope.searchText = '';
  };

  $scope.connectionTotal = function(stepNum){
    if(!angular.isUndefined($scope.order.steps[stepNum].connection)){
      if(!angular.isUndefined($scope.order.steps[stepNum].connection.price)){
        priceNumber = angular.copy($scope.order.steps[stepNum].connection.price.price__number);
        whoCount = angular.copy($scope.order.common.whoCount);
        return $filter('formatPriceObject')({price__number: priceNumber * whoCount});
      }
    }
  };

  $scope.connectionDescription = function(stepNum){
    if($scope.getLength($scope.load[stepNum].connections) == 0){
      return Drupal.t('Connections not found');
    }

    if(angular.isUndefined($scope.order.steps[stepNum].connection)){
      return Drupal.t('Not selected');
    }
  };

  $scope.connectionShow = function(stepNum){
    if(!angular.isUndefined($scope.order.steps[parseInt(stepNum)+1])){
      return true;
    }
  };

  /*
   * Return hotel description
   */
  $scope.hotelDescription = function(stepNum){
    if(!angular.isUndefined($scope.order.steps[stepNum].hotel)){
      hotel = $scope.order.steps[stepNum].hotel;
      star = hotel.star;
      price = '';
      if(!angular.isUndefined(hotel.price)){
        price = hotel.price.name;
      }
      return star + ' star hotel / ' + price;
    }
    else if($scope.getLength($scope.load[stepNum].hotels) == 0){
      return Drupal.t('Hotels not found');
    }
    else{
      return Drupal.t('Not selected');
    }
  }

  /*
   * Return transfer information
   */
  $scope.transferDescription = function(stepNum){
    if(!angular.isUndefined($scope.order.steps[stepNum].transfer)){
      transferName = $scope.order.steps[stepNum].transfer.name;
      return transferName;
    }
    else if($scope.getLength($scope.load[stepNum].transfers) == 0){
      return Drupal.t('Transfers not found');
    }
    else{
      return Drupal.t('Not selected');
    }
  }

  /*
   * Return activities information
   */
  $scope.activityDescription = function(stepNum){
    if(!angular.isUndefined($scope.order.steps[stepNum].activity)){
      activityNameArray = [];
      activities = $scope.order.steps[stepNum].activity;
      angular.forEach(activities, function(activity, activityIndex){
        activityNameArray.push(activity.name);
      });
      return activityNameArray;
    }
    else if($scope.getLength($scope.load[stepNum].activities) == 0){
      return [Drupal.t('Activities not found')];
    }
    else{
      return [Drupal.t('Not selected')];
    }
  }

  $scope.openMap = function(){
    angular.element(document.querySelector('body')).addClass('shut-down-map');
    $scope.$broadcast('showMap');
  };

  $scope.returnFromMobile = function(){
    angular.element(document.querySelector('body')).removeClass('shut-down-black');
    angular.element(document.querySelector('body')).removeClass('shut-down-white');
    angular.element(document.querySelector('body')).removeClass('shut-down-map');
    angular.element(document.querySelector('.constructor-search-wrapper')).removeClass('displayblock');
    angular.element(document.querySelector('.constructor-gmap')).removeClass('displayblock');
  }

  $scope.isMobileReturnVisible = function(){
    if($scope.mobileVisibleElement != ''){
      return true;
    }
    else{
      return false;
    }
  };

  $scope.returnFromMobileSearch = function(){
    body = angular.element(document.querySelector("body"));
    body.removeClass('shut-down-itinerary');
    hideElements = angular.element(document.querySelectorAll('.displayblock'));
    hideElements.removeClass('displayblock');
  };

  $scope.getLength = function(obj) {
    return Object.keys(obj).length;
  };

  $scope.showNewStep = function(){
    if(!angular.isUndefined($scope.order.steps)){
      if(!angular.isUndefined($scope.load[$scope.getLength($scope.order.steps)].connections)){
        return $scope.load[$scope.getLength($scope.order.steps)].connections;
      }
    }
    else{
      return false;
    }
  };

  /*
   * Save order to cookies
   */
  $scope.saveMainOrder = function(order){
    if(order == null){
      delete $localStorage.order
    }
    else{
      $localStorage.order = order;
    }
  };

  $scope.clickkk = function(){
    console.log(drupalSettings);
    console.log('s1order');
    console.log($scope.step1order);
    console.log('s2order');
    console.log($scope.step2order);

    console.log('load');
    console.log($scope.load);
    console.log('order');
    console.log($scope.order);
    $scope.refreshRecommendedHubs();

    console.log('temp');
    console.log($scope.temp);

    /*
    $timeout(function(){
      console.log('preferred');
      console.log($scope.preferred);
    }, 500);*/
  };

  /*
   * Hide search container
   */
  $scope.showChooseHub = function(){
    if($window.innerWidth < 670) {
      $scope.addClassesToBody('shut-down-white');
      angular.element(document.querySelector('.tour-wrapper')).addClass('displayblock');
      angular.element(document.querySelector('.search-recommended')).addClass('displayblock');
      angular.element(document.querySelector('.search-hub')).addClass('displayblock');
    }
    $scope.isChooseHubVisible = true;
  };
  /*
   * Hide search container
   */
  $scope.hideChooseHub = function(){
    $scope.removeClassesFromBody('shut-down-white');
    $scope.isChooseHubVisible = false;
  };

  /*
   * Show not found text
   */
  $scope.showNotFound = function(){
    if(($filter('filter')($scope.objectToArray($scope.hubs), $scope.searchText)).length == 0){
      return true;
    }
    else{
      return false;
    }
  };

  /*
   * Convert object to array
   */
  $scope.objectToArray = function(){
    var array = [];
    angular.forEach($scope.load.hubs, function(element) {
      array.push(element);
    });
    return array;
  };

  /*
   * Enable slick carousel with pause
   */
  $scope.enableSlick = function(){
    $scope.numberLoaded = false;
    $timeout(function(){
      $scope.numberLoaded = true;
    }, 500);
  };

  /*
   * Return step total price count
   */
  $scope.stepTotalPrice = function(stepNum, entities, withConnection){
    total = 0;
    whoCount = angular.copy($scope.order.common.whoCount);
    days = parseInt(angular.copy($scope.order.steps[stepNum].hub.days), 10);
    if (days !== 1){
      days = days - 1;
    }
    currencyCode = '$';

    angular.forEach(entities, function(entity, entityIndex){
      if(entityIndex != 'activity'){ // If entity == hotel, transfer, connection
        if(!angular.isUndefined(entity.price)){
          price = 0;
          price = angular.copy(parseInt(entity.price.price__number));
          if(entityIndex == 'hotel'){
            maxQuantity = $scope.data.hotelDefaultCount;
            if(!angular.isUndefined(entity.price.max_quantity)){
              if(entity.price.max_quantity != '' && entity.price.max_quantity != null){
                maxQuantity = entity.price.max_quantity;
              }
            }
            count = window.Math.ceil(whoCount / maxQuantity);
            total += price * count * days;
          }
          else if(entityIndex == 'connection' && withConnection){
            total += price * whoCount;
          }
          else if(entityIndex == 'transfer'){
            maxQuantity = 3;
            if(!angular.isUndefined(entity.price.max_quantity)){
              if(entity.price.max_quantity != '' && entity.price.max_quantity != null){
                maxQuantity = entity.price.max_quantity;
              }
            }
            count = window.Math.ceil(whoCount / maxQuantity);
            total += price * count;
          }
        }
      }
      else{ // If entity == activity
        angular.forEach(entity, function(activity, activityIndex){
          price = activity.price.price__number;
          total += price * whoCount;
        });
      }
    });

    return total;
  }

  /*
   * Step total formatted
   */
  $scope.stepTotal = function(stepNum){
    if(!angular.isUndefined($scope.order.steps)){
      entities = $scope.order.steps[stepNum];
      withoutConnection = 0;
      stepTotal = $scope.stepTotalPrice(stepNum, entities, withoutConnection);

      if(stepTotal){
        return $filter('formatPriceObject')({'price__number': stepTotal, 'price__currency_code': currencyCode});
      }
      else{
        return '';
      }

    }
    else{
      return '';
    }

  };

  /*
   * Order total price
   */
  $scope.getOrderTotal = function(returnWithCurrency = 1){
    if(!angular.isUndefined($scope.order.steps)){
      orderTotal = 0;
      angular.forEach($scope.order.steps, function(entities, stepIndex) {
        withConnection = 1;
        orderTotal += $scope.stepTotalPrice(stepIndex, entities, withConnection);
      });

      if(orderTotal){
        if(returnWithCurrency){
          return $filter('formatPriceObject')({'price__number': orderTotal, 'price__currency_code': '$'});
        }
        else{
          return orderTotal;
        }
      }

    }
    else{
      return '';
    }
  };

  $scope.getHubsCount = function(){
    if(!angular.isUndefined($scope.order.steps)){
      length = $scope.getLength($scope.order.steps);
      return Drupal.formatPlural(length, '1 city', '@count cities');
    }
    else{
      return 0;
    }
  };

  /*
   * Get duration days, nights
   */
  $scope.getDuration = function(stepNum, type){
    count = angular.copy($scope.order.steps[stepNum].hub.days);
    if(type == 'days'){
      return Drupal.formatPlural(count, '1 day', '@count days');
    }
    else if(type == 'nights' && count > 1){
      count--;
      return ' / ' + Drupal.formatPlural(count, '1 night', '@count nights');
    }
  };

  /*
   * Functions for recommended block.
   */
  $scope.getSearchPlaceholder = function(){
    if($window.innerWidth < 1000){
      return Drupal.t('Search for the next city');
    }
    else{
      return Drupal.t('Search for the next city or check our recommendation');
    }
  };

  $scope.recommendedPlus = function(hubId){
    $scope.load.hubs[hubId].days++;
  };

  $scope.recommendedMinus = function(hubId){
    if($scope.load.hubs[hubId].days > 1){
      $scope.load.hubs[hubId].days--;
    }
  };

  $scope.getRecommendedDays = function(hub){
    count = angular.copy(hub.days);
    return Drupal.formatPlural(count, '1 day', '@count days');
  };

  $scope.getTripDurationHours = function(recommendedConnectionId){
    stepsCount = $scope.getLength($scope.order.steps);
    duration = $scope.load[stepsCount].connections[recommendedConnectionId].duration;
    if(duration != null){
      return Drupal.formatPlural(duration, 'Trip duration: 1 hour', 'Trip duration: @count hours');
    }
  };

  $scope.refreshRecommendedHubs = function(){
    $scope.hubsLoaded = false;
    currentStepNum = $scope.getLength($scope.order.steps);
    
    connectionsWithPriceOptions = $filter('filterConnectionWithRating')($scope.load[currentStepNum]['connections']);
    connectionsWithPriceOptions = $filter('filterConnectionWithPriceOptions')(connectionsWithPriceOptions);
    
    hubs = $filter('filterHubsUsingConnections')(connectionsWithPriceOptions, $scope.load.hubs);
    hubs = $filter('filterHubsWithoutRegions')(hubs);
    hubs = $filter('filterHubsInOrder')(hubs, $scope.order.steps);
    hubs = $filter('deleteUndefined')(hubs);
    hubs = $filter('limitTo')(hubs, $scope.data.recommendedHubsCount);
    angular.forEach(hubs, function(hub){
      $scope.loadEntities(hub.id, hub.id, 'preferred');
    });
    $scope.recommended = {};
    $scope.recommended.hubs = hubs;
    $scope.$broadcast('setRecommendedCoordinates');
    
    $timeout(function(){
      $scope.hubsLoaded = true;
    }, 50);
  };

  $scope.slickConfig = {
    'infinite': false,
    'variableWidth': true,
    'slides-to-show': 2,
  };

  $scope.setRecommendedConnection = function(stepNum, connectionId){
    $scope.order.steps[stepNum - 1].connection = angular.copy($scope.load[stepNum - 1].connections[connectionId]);
    priceId = $scope.order.steps[stepNum - 1].connection.price_options[0];
    $scope.order.steps[stepNum - 1].connection.price = angular.copy($scope.load[stepNum - 1].prices.connections[priceId]);
  };

  /*
   * Return recommended connection id for next step hub.
   * Get connection with highest rating.
   */
  $scope.getRecommendedConnectionId = function(hubId, stepNum){
    if(!stepNum){
      stepNum = $scope.getLength($scope.order.steps);
    }
    if(!angular.isUndefined($scope.load[stepNum].connections)){
      connections = angular.copy($scope.load[stepNum].connections);
      connections = $filter('toArray')(connections);
      connections = $filter('filterConnectionByHubId')(connections, hubId);
      connections = $filter('orderBy')(connections, '-rating');
      if(!angular.isUndefined(connections[0])){
        return connections[0].id;
      }
    }
  };

  $scope.getRecommendedConnectionType = function(hubId){
    connectionId = $scope.getRecommendedConnectionId(hubId);
    lastStepNum = $scope.getLength($scope.order.steps);
    if(!angular.isUndefined($scope.load[lastStepNum].connections[connectionId])){
      return $scope.load[lastStepNum].connections[connectionId].type;
    }
  }

  $scope.getTotalPriceForRecommendHub = function(hubId){
    if(!angular.isUndefined($scope.preferred)){
      total = 0;
      whoCount = angular.copy($scope.order.common.whoCount);
      days =  parseInt($scope.load.hubs[hubId].days, 10);
      
      if (days !== 1){
        days = days - 1;
      }

      // Add hotel, transfer, activity
      angular.forEach($scope.preferred[hubId].preferred, function(preferredId, preferredType){
        if(angular.isString(preferredId)){
          entity = $scope.preferred[hubId][preferredType][preferredId];

          if(!angular.isUndefined(entity)){
            if(preferredType == 'hotels'){
              if (!angular.isUndefined(entity.price_options)){
                priceOptionId = entity.price_options[0];
                priceObject = $scope.preferred[hubId].prices[preferredType][priceOptionId];
  
                maxQuantity = $scope.data.hotelDefaultCount;
                count = 1;
                if(!angular.isUndefined(priceObject.max_quantity)){
                  if(priceObject.max_quantity != '' && priceObject.max_quantity != null){
                    maxQuantity = priceObject.max_quantity;
                  }
                }
  
                if(maxQuantity != null){
                  count = window.Math.ceil(whoCount / maxQuantity);
                }
  
                total += parseInt(priceObject.price__number) * days * count;
              }
            }
            else if(preferredType == 'transfers'){
              priceOptionId = entity.price_options[0];
              priceObject = $scope.preferred[hubId].prices[preferredType][priceOptionId];
              if(!angular.isUndefined(priceObject)){
                maxQuantity = $scope.data.transferDefaultCount;
                count = 1;
                if(priceObject.max_quantity != '' && priceObject.max_quantity != null){
                  maxQuantity = priceObject.max_quantity;
                }
                if(maxQuantity != null){
                  count = window.Math.ceil(whoCount / maxQuantity);
                }
                total += parseInt(priceObject.price__number) * count;
              }
            }

            else if(preferredType == 'activities'){
              priceOptionId = entity.price_options[0];
              priceObject = $scope.preferred[hubId].prices[preferredType][priceOptionId];
              if(!angular.isUndefined(priceObject)){
                total += parseInt(priceObject.price__number) * whoCount;
              }
            }
          }
        }
      });

      // Add connection
      currentStepNum = $scope.getLength($scope.order.steps);
      connectionId = $scope.getRecommendedConnectionId(hubId, currentStepNum);
      connection = angular.copy($scope.load[currentStepNum].connections[connectionId]);
      connectionPriceId = connection.price_options[0];
      if(!angular.isUndefined($scope.load[currentStepNum].prices.connections[connectionPriceId])){
        connectionPriceNumber = $scope.load[currentStepNum].prices.connections[connectionPriceId].price__number;
        total += parseInt(connectionPriceNumber) * whoCount;
      }

      if(total == 0){
        return '';
      }
      else{
        return $filter('formatPriceObject')({'price__number': total, 'price__currency_code': '$'});
      }
    }
    else{
      return '';
    }
  };

  /*
   * Functions for filter
   */
  $scope.setHotelFilter = function(star){
    if(angular.isUndefined($scope.filter.star)){
      $scope.filter = {
        star: []
      };
    }
    star = star[0][0];
    index = $scope.filter.star.indexOf(star);
    if(index == -1) {
      $scope.filter.star.push(star);
    }
    else{
      $scope.filter.star.splice(index, 1);
    }
  };

  $scope.isHotelFilterOptionActive = function(starCount){
    if(!angular.isUndefined($scope.filter.star)){
      index = $scope.filter.star.indexOf(starCount);
      if(index != -1) {
        return 'active';
      }
    }
    else{
      return '';
    }
  };

  $scope.getOrderTotalDays = function(){
    totalDays = 0;
    if(!angular.isUndefined($scope.order.steps)){
      angular.forEach($scope.order.steps, function(step, stepIndex){
        totalDays += parseInt($scope.order.steps[stepIndex].hub.days);
      });
      return Drupal.formatPlural(totalDays, '1 day', '@count days');
    }
    return '';
  };

  $scope.addClassesToBody = function(className){
    body = angular.element(document.querySelector("body"));
    body.addClass(className);
  };

  $scope.removeClassesFromBody = function(className){
    body = angular.element(document.querySelector("body"));
    body.removeClass(className);
  };

  $scope.goToItinerary = function(){
    $http.post('/save/order', $scope.order)
      .then(function(response){
          window.location = '/en/itinerary/' + response.data;
        },
        //If error
        function(response) {
          console.log(response);
        });
  }

  $scope.clearAllCookies = function(){
    $localStorage.$reset();
  }

  $scope.getStepIndexName = function(step){
    if(step == 1){
      return Drupal.t('1st');
    }
    else if(step == 2){
      return Drupal.t('2nd');
    }
    else if(step == 3){
      return Drupal.t('3rd');
    }
    else{
      return Drupal.t('@step'+'th', {'@step': step});
    }
  }

  /*
   * Functions for open edit popup for trip order.
   */

  $scope.addDayToHub = function(stepNum){
    days = angular.copy($scope.order.steps[stepNum].hub.days);
    days++;
    $scope.order.steps[stepNum].hub.days = days;
  }

  $scope.subtractDayFromHub = function(stepNum){
    days = angular.copy($scope.order.steps[stepNum].hub.days);
    if(days != 1){
      days--;
      $scope.order.steps[stepNum].hub.days = days;
    }
  }

  $scope.hotelTotal = function(stepNum){
    if(typeof $scope.order.steps[stepNum].hotel != 'undefined'){
      hotel = $scope.order.steps[stepNum].hotel;
      hub = $scope.order.steps[stepNum].hub;
      days = hub.days;
      if (days !== 1){
        days = days - 1;
      }

      hotelPrice = '';
      hotelCurrencyCode = '';
      if(!angular.isUndefined(hotel.price)){
        hotelPrice = hotel.price.price__number;
        hotelCurrencyCode = hotel.price.price__currency_code;
      }

      whoCount = $scope.order.common.whoCount;
      if(hotelPrice != 0){
        return $filter('formatPriceObject')({
          price__number: hotelPrice * whoCount * days,
          price__currency_code: '$',
        });
      }
    }
    return '';
  }

  $scope.transferTotal = function(stepNum){
    if(typeof $scope.order.steps[stepNum].transfer != 'undefined'){
      transfer = $scope.order.steps[stepNum].transfer;

      transferPrice = $filter('number')(transfer.price.price__number, 0);
      transferCurrencyCode = transfer.price.price__currency_code;
      whoCount = angular.copy($scope.order.common.whoCount);
      maxQuantity = $scope.data.transferDefaultCount;
      if(!angular.isUndefined(transfer.price.max_quantity)){
        if(transfer.price.max_quantity != '' && transfer.price.max_quantity != null){
          maxQuantity = transfer.price.max_quantity;
        }
      }
      count = window.Math.ceil(whoCount / maxQuantity);

      return $filter('formatPriceObject')({
        price__number: transferPrice * count,
        price__currency_code: '$',
      });
    }
    else{
      return '';
    }
  }

  $scope.activityTotal = function(stepNum){
    if(!angular.isUndefined($scope.order.steps[stepNum].activity)){
      total = 0;
      whoCount = angular.copy($scope.order.common.whoCount);
      activities = $scope.order.steps[stepNum].activity;
      angular.forEach(activities, function(activity, activityIndex){
        total += parseInt(activity.price.price__number);
      });

      total = total * whoCount;

      if(total > 0){
        return $filter('formatPriceObject')({
          price__number: total,
          price__currency_code: '$',
        });
      }
      else{
        return '';
      }
    }
    else{
      return '';
    }
  };

  $scope.hideStepDialog = function(){
    angular.element(document.querySelector(".edit-step-popup")).addClass('ngdialog-closing');
    angular.element(document.querySelector(".edit-step-popup")).addClass('displaynone');
  };

  $scope.showStepDialog = function(){
    angular.element(document.querySelector(".edit-step-popup")).removeClass('ngdialog-closing');
    angular.element(document.querySelector(".edit-step-popup")).removeClass('displaynone');
  };

  $scope.saveClickedElementPosition = function(position, popupType){
    $scope.clickedElement = {
      position: position,
      popupType: popupType
    }
  };

  $scope.$on('ngDialog.opened', function (e, $dialog) {
    if(angular.element($dialog[0]).hasClass('edit-step-popup')){
      location.hash = '#top';
    }
  });


  /*
   * Open dialog to edit step
   */
  $scope.editStep = function (activeStepNum, isSavePosition) {
    if(isSavePosition){
      $scope.saveClickedElementPosition($scope.pixelsScrolled, 'step');
    }

    $scope.activeStepNum = activeStepNum;
    $scope.activeStep = $scope.order.steps[activeStepNum];

    ngDialog.open({
      template: '/edit/step',
      scope: $scope,
      className: 'edit-step-popup edit-entity-popup ngdialog-theme-default',
      closeByDocument: $scope.data.closeByDocument,
      controller: 'EditStepDialogCtrl'
    });
  };

  /*
   * Open dialog to edit connection
   */
  $scope.editConnection = function (activeStepNum, isSavePosition) {
    if(isSavePosition){
      $scope.saveClickedElementPosition($scope.pixelsScrolled, 'connection');
    }

    $scope.activeStepNum = activeStepNum;
    $scope.activeStep = $scope.order.steps[activeStepNum];

    ngDialog.open({
      template: '/edit/connection',
      scope: $scope,
      className: 'edit-connection-popup edit-entity-popup ngdialog-theme-default',
      closeByDocument: $scope.data.closeByDocument,
      preCloseCallback: function(){
        $scope.activeStepNum = '';
        $scope.activeStep = '';
      },
      controller: 'EditConnectionDialogCtrl'
    });
  };

  /*
   * Open dialog to edit hub
   */
  $scope.editHub = function(activeStepNum, isSavePosition){
    if(isSavePosition){
      $scope.saveClickedElementPosition($scope.pixelsScrolled, 'hub');
    }

    $scope.activeStepNum = activeStepNum;
    $scope.activeStep = $scope.order.steps[activeStepNum];

    $scope.hideStepDialog();

    ngDialog.open({
      template: '/edit/hub',
      scope: $scope,
      className: 'edit-hub-popup edit-entity-popup ngdialog-theme-default',
      closeByDocument: $scope.data.closeByDocument,
      preCloseCallback: function(){
        $scope.showStepDialog();
        delete $scope.currentStep
      },
      controller: 'EditHubDialogCtrl',
    });
  }

  /*
   * Open dialog to edit hotel
   */
  $scope.editHotel = function(activeStepNum, isSavePosition){
    if(isSavePosition){
      $scope.saveClickedElementPosition($scope.pixelsScrolled, 'hotel');
    }

    $scope.activeStepNum = activeStepNum;
    $scope.activeStep = $scope.order.steps[activeStepNum];

    $scope.hideStepDialog();

    ngDialog.open({
      template: '/edit/hotel',
      scope: $scope,
      className: 'edit-hotel-popup edit-entity-popup ngdialog-theme-default',
      closeByDocument: $scope.data.closeByDocument,
      preCloseCallback: function(){
        $scope.showStepDialog();
        delete $scope.currentStep
      },
      controller: 'EditHotelDialogCtrl',
    });
  }

  /*
   * Open dialog to edit transfer
   */
  $scope.editTransfer = function(activeStepNum, isSavePosition){
    if(isSavePosition){
      $scope.saveClickedElementPosition($scope.pixelsScrolled, 'transfer');
    }

    $scope.activeStepNum = activeStepNum;
    $scope.activeStep = $scope.order.steps[activeStepNum];

    $scope.hideStepDialog();

    ngDialog.open({
      template: '/edit/transfer',
      scope: $scope,
      className: 'edit-transfer-popup edit-entity-popup ngdialog-theme-default',
      closeByDocument: $scope.data.closeByDocument,
      preCloseCallback: function(){
        $scope.showStepDialog();
        delete $scope.currentStep
      },
      controller: 'EditTransferDialogCtrl',
    });

  }

  /*
   * Open dialog to edit activity
   */
  $scope.editActivity = function(activeStepNum, isSavePosition){
    if(isSavePosition){
      $scope.saveClickedElementPosition($scope.pixelsScrolled, 'activity');
    }

    $scope.activeStepNum = activeStepNum;
    $scope.activeStep = $scope.order.steps[activeStepNum];

    $scope.hideStepDialog();

    ngDialog.open({
      template: '/edit/activity',
      scope: $scope,
      className: 'edit-activity-popup edit-entity-popup ngdialog-theme-default',
      closeByDocument: $scope.data.closeByDocument,
      preCloseCallback: function(){
        $scope.showStepDialog();
        delete $scope.currentStep
      },
      controller: 'EditActivityDialogCtrl',
    });
  };

  /*
   * Open dialog to save & share
   */
  $scope.editSaveShare = function(isSavePosition){
    if(isSavePosition){
      $scope.saveClickedElementPosition($scope.pixelsScrolled, 'save-share');
    }

    ngDialog.open({
      template: '/edit/save-share',
      scope: $scope,
      className: 'edit-save-share-popup edit-itinerary-popup ngdialog-theme-default',
      closeByDocument: $scope.data.closeByDocument,
      controller: 'EditSaveShareDialogCtrl',
    });
  };

  /*
   * Open dialog to save & share
   */
  $scope.editBookNow = function(isSavePosition){
    if(isSavePosition){
      $scope.saveClickedElementPosition($scope.pixelsScrolled, 'book-now');
    }

    ngDialog.open({
      template: '/edit/book-now',
      scope: $scope,
      className: 'edit-book-now-popup edit-itinerary-popup ngdialog-theme-default',
      closeByDocument: $scope.data.closeByDocument,
      controller: 'EditBookNowDialogCtrl',
    });
  };


  /*
   * Open dialog to save & share
   */
  $scope.showItineraryMap = function(isSavePosition){
    if(isSavePosition){
      $scope.saveClickedElementPosition($scope.pixelsScrolled, 'book-now');
    }

    ngDialog.open({
      template: '/show/itinerary-map',
      scope: $scope,
      className: 'show-itinerary-map-popup edit-itinerary-popup ngdialog-theme-default',
      closeByDocument: $scope.data.closeByDocument,
      controller: 'ShowItineraryMapDialogCtrl',
    });
  };

  /*
   * 3rd step
   */
  $scope.getHotelStars = function(stepNum, hotelId){
    stars = [];
    if(!angular.isUndefined($scope.load[stepNum].hotels[hotelId])){
      starsString = $scope.load[stepNum].hotels[hotelId].star;
      starsString = starsString.replace('+', '');
      for(var i = 0; i < parseInt(starsString); i++){
        stars.push(i);
      }
    }
    return stars;
  };
  
  /*
   * Return hotel price_options
   */
  $scope.hotelPriceOptions = function(stepNum){
    hotels = $scope.load[stepNum].hotels;
    price_options = '';
    angular.forEach(hotels, function(hotel, hotelIndex) {
      if(hotel.price_options){
        price_options = hotel.price_options;
      }
    });
    return price_options;
  };

  /*
   * Pass scroll position to the scope
   */
  $document.on('scroll', function() {
    $scope.$apply(function() {
      $scope.pixelsScrolled = $window.scrollY;
    });

    wrapperWidth = document.querySelector('#block-tt-content .wrapper').clientWidth;
    wrapperMargin = parseInt(angular.element(document.querySelector('#block-tt-content .wrapper')).css('margin-left').replace('px', ''));
    floatingLeft = wrapperWidth + wrapperMargin + $scope.data.floatMargin;

    $scope.styleFloating = {"left": floatingLeft+"px"};


    // Disable floating price
    topTotal = angular.element(document.querySelector('#header-order-total'));
    bottomTotal = angular.element(document.querySelector('#footer-order-total'));
    if(!angular.isUndefined(topTotal.offset()) && !angular.isUndefined(bottomTotal.offset())){
      topTotal = topTotal.offset().top + 20;
      bottomTotal = bottomTotal.offset().top;
      if($window.scrollY > topTotal && ($window.scrollY + $window.innerHeight) < bottomTotal){
        $scope.isShowFloating = 1;
      }
      else{
        $scope.isShowFloating = 0;
      }
    }
  });

  $scope.isShowFloating = 0;
  wrapperWidth = document.querySelector('#block-tt-content .wrapper').clientWidth;
  wrapperMargin = parseInt(angular.element(document.querySelector('#block-tt-content .wrapper')).css('margin-left').replace('px', ''));
  floatingLeft = wrapperWidth + wrapperMargin + $scope.data.floatMargin;

  $scope.styleFloating = {"left": floatingLeft+"px"};

  $scope.getHotelDescriptionValue = function(stepNum, hotelId){
    if(!angular.isUndefined($scope.load[stepNum].hotels[hotelId])){
      text = $filter('to_trusted')($scope.load[stepNum].hotels[hotelId].description__value);
      return text;
    }
  };

  $scope.getUserIp = function(){
    var json1 = 'http://ipv4.myexternalip.com/json';
    var json2 = 'http://freegeoip.net/json/';
    $http.get(json1).then(function(result) {
      $scope.data.ip = result.data.ip;
    }, function(e) {
      console.log('error 1 ip');
      $http.get(json2).then(function(result) {
        $scope.data.ip = result.data.ip;
      }, function(e) {
        console.log('error 2 ip');
      });
    });
  }

});
