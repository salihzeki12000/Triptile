app.filter('capitalize', function() {
  return function(input) {
    return (!!input) ? input.charAt(0).toUpperCase() + input.substr(1).toLowerCase() : '';
  }
});

app.filter('filterHubsBySearchText', function () {
  return function (items, searchText) {
    var results = [];
    toSearch = searchText.toLowerCase();
    angular.forEach(items, function(item, itemKey){
      angular.forEach(item, function(prop, propKey){
        if((items[itemKey][propKey]).toLowerCase().indexOf(toSearch) != -1){
          if(!itemExists(results, items[itemKey])) results.push(items[itemKey]);
        }
      });
    });

    if(results.length == 0){
     return items;
    }
    else{
      return results;
    }


  };
});

/*
 * Filter hubs for next step from connections point_1 of current step
 */
app.filter('filterHubsForNextStep', function () {
  return function (items, steps, load) {
    var filtered = [];
    if(!angular.isUndefined(steps)){
      stepNum = Object.keys(steps).length;
      connections = load[stepNum].connections;
      hubs = [];
      angular.forEach(connections, function(connection) {
        if (!itemExists(hubs, connection.point_2)) {
          hubs.push(connection.point_2);
          filtered.push(load.hubs[connection.point_2]);
        }
      });
    }
    return filtered;
  };
});

/*
 * Filter hubs for this step from connections point_1 of current step
 */
app.filter('filterHubsForThisStep', function () {
  return function (items, steps, load, stepNum) {
    var filtered = [];
    if(stepNum){
      if(!angular.isUndefined(steps)){
        connections = load[stepNum].connections;
        hubs = [];
        angular.forEach(connections, function(connection) {
          if (!itemExists(hubs, connection.point_2)) {
            hubs.push(connection.point_2);
            filtered.push(load.hubs[connection.point_2]);
          }
        });
      }
    }
    else{
      filtered = items;
    }
    return filtered;
  };
});

app.filter('filterHubsWithoutRegions', function () {
  return function (items) {
    var filtered = [];
    angular.forEach(items, function(item) {
      if(!angular.isUndefined(item.region)){
        filtered.push(item);
      }
    });
    return filtered;
  };
});

app.filter('filterConnectionWithPriceOptions', function () {
  return function (items) {
    var filtered = [];
    angular.forEach(items, function(item) {
      if(!angular.isUndefined(item.price_options)){
        filtered.push(item);
      }
    });
    return filtered;
  };
});

app.filter('filterConnectionWithRating', function () {
  return function (items) {
    var filtered = [];
    angular.forEach(items, function(item) {
      if(item.rating != null){
        filtered.push(item);
      }
    });
    return filtered;
  };
});

app.filter('filterHubsUsingConnections', function () {
  return function (connections, hubs) {
    var filtered = [];
    var filteredHubs = [];
    var filteredConnections = [];
    
    // check rating for hubs and connection->hub is not null
    // and get all connections with rating = hub.rating + connection.rating.
    angular.forEach(connections, function(connection) {
      angular.forEach(hubs, function(hub) {
        if(hub.id == connection.point_2){
          if (hub.rating !== null && connection.rating !== null) {
            connection.rating = parseInt(hub.rating, 10) + parseInt(connection.rating, 10);
            filteredConnections.push(connection);
          }
        }
      });
    });
  
    // sort result connection with new rating
    filteredConnections.sort(function (a, b) {
      return (a['rating'] < b['rating'] ? 1 : -1);
    });
    
    // get all hub with result connections
    angular.forEach(filteredConnections, function (connection) {
      angular.forEach(hubs, function(hub) {
          if(hub.id == connection.point_2){
            filteredHubs.push(hub);
          }
        });
      }
    );
 
    // remove duplicates from array hubs
    angular.forEach(filteredHubs, function(value, key) {
      var exists = false;
      angular.forEach(filtered, function(val2, key) {
        if(angular.equals(value.id, val2.id)){
          exists = true
        };
      });
      if(exists == false && value.id != "") {
        filtered.push(value);
      }
    });
    
    return filtered;
  };
});

app.filter('filterHubsInOrder', function () {
  return function (hubs, steps) {
    var stepsHubs = [];
    angular.forEach(steps, function(step) {
      stepsHubs.push(step.hub.id);
    });
    angular.forEach(hubs, function(hub, indexHub) {
      angular.forEach(stepsHubs, function(stepsHubId){
        if(hub.id == stepsHubId){
          delete hubs[indexHub];
        }
      });
    });
    return hubs;
  };
});

app.filter('deleteUndefined', function () {
  return function (items) {
    var filtered = [];
    angular.forEach(items, function(item) {
      if(typeof item != 'undefined'){
        filtered.push(item);
      }
    });
    return filtered;
  };
});

/*
 * Sort objects by alphabet
 */
app.filter('orderByObject', function() {
  return function(items, field, reverse) {
    var filtered = [];
    angular.forEach(items, function(item) {
      filtered.push(item);
    });
    if (field == 'rating'){
    filtered.sort(function (a, b) {
      return parseInt(a[field]) < parseInt(b[field]) ? -1 : (parseInt(a[field]) > parseInt(b[field]) ? 1 : (parseInt(a['id']) > parseInt(b['id']) ? - 1 : 1 ) );
    });
    } else {
      filtered.sort(function (a, b) {
        return (a[field] > b[field] ? 1 : -1);
      });
    }
    if(reverse) filtered.reverse();
    return filtered;
  };
});

/*
 * Filter prices for entity
 */
app.filter('filterPricesByEntity', function () {
  return function (prices, entity) {
    var filtered = [];

    for (var i = 0; i < prices.length; i++) {
      price = prices[i];
      for(var j = 0; j < entity.price_options.length; j++){
        price_option = entity.price_options[j];
        if(price.id == price_option){
          filtered.push(price);
        }
      }
    }
    return filtered;
  };
});

/*
 * Filter array by another array
 */
app.filter('inArray', function($filter){
  return function(list, arrayFilter, element){
    if(arrayFilter){
      return $filter("filter")(list, function(listItem){
        return arrayFilter.indexOf(listItem[element]) != -1;
      });
    }
  };
});

/*
 * Filter connections by next hub
 */
app.filter('filterConnectionByNextHub', function($filter){
  return function(connections, order, stepNum){
    filtered = [];
    stepNum = parseInt(stepNum) + 1;
    nextHubId = order.steps[stepNum].hub.id;
    angular.forEach(connections, function(connection) {
      if(connection.point_2 == nextHubId){
        filtered.push(connection);
      }
    });
    return filtered;
  };
});

/*
 * Filter connections by hubId
 */
app.filter('filterConnectionByHubId', function($filter){
  return function(connections, hubId){
    filtered = [];
    angular.forEach(connections, function(connection) {
      if(connection.point_2 == hubId){
        filtered.push(connection);
      }
    });
    return filtered;
  };
});

/*
 * Return formatted price from price object
 */
app.filter('formatPriceObject', function($filter){
  return function(object){
    priceNum = $filter('number')(object.price__number, 0);
    priceCurrencyCode = object.price__currency_code;
    return '$' + priceNum;
  };
});

/*
 * Highlights phrases
 */
app.filter('highlight', function($sce) {
  return function(text, phrase) {
    if (phrase) text = text.replace(new RegExp('('+phrase+')', 'gi'),
      '<span class="highlighted">$1</span>')

    return $sce.trustAsHtml(text)
  }
});

app.filter('to_trusted', function($sce) {
  return function(text) {
    return $sce.trustAsHtml(text)
  }
});

/*
 * Convert object to array
 */
app.filter('toArray', function () {
  return function (obj, addKey) {
    if (!(obj instanceof Object)) {
      return obj;
    }

    if ( addKey === false ) {
      return Object.values(obj);
    } else {
      return Object.keys(obj).map(function (key) {
        return Object.defineProperty(obj[key], '$key', { enumerable: false, value: key});
      });
    }
  };
});

/*
 * Clean outdated prices and entities
 */
app.filter('cleanOutdatedEntitiesAndPrices', function() {
  return function(data, entityType, whenGoDate) {
    filtered = angular.copy(data);
    whenGoDate = new Date(whenGoDate);

    // Delete outdated prices
    angular.forEach(data.prices, function(price, priceIndex){
      fromDate = new Date(price.available_from);
      fromDate.setHours(fromDate.getHours() - 12);
      untilDate = new Date(price.available_until);
      untilDate.setHours(untilDate.getHours() + 12);
      if(whenGoDate >= fromDate && whenGoDate <= untilDate){}
      else{
        delete filtered.prices[priceIndex];
      }
    });
    // Delete outdated price options from entity
    angular.forEach(angular.copy(filtered[entityType]), function(entity, entityIndex){
      angular.forEach(entity.price_options, function(priceOption, priceOptionIndex){
        isDelete = 1;
        angular.forEach(angular.copy(filtered.prices), function(price){
          if(priceOption == price.id){
            isDelete = 0;
          }
        });

        if(isDelete){
          newIndex = filtered[entityType][entityIndex].price_options.indexOf(priceOption);
          filtered[entityType][entityIndex].price_options.splice(newIndex, 1);
        }
      });

      // Delete entity, if it don't have price options
      if(!angular.isUndefined(entity.price_options)){
        if(entity.price_options.length == 0){
          if(entityIndex == filtered.preferred){
            filtered.preferred = "";
          }
          delete filtered[entityType][entityIndex];
        }
      }
    });
    return filtered;
  }
})

function compareObjects(o1, o2) {
  var k = '';
  for(k in o1) if(o1[k] != o2[k]) return false;
  for(k in o2) if(o1[k] != o2[k]) return false;
  return true;
}

/*
 * JavaScript isset() equivalent
 */
function itemExists(haystack, needle) {
  for(var i = 0; i < haystack.length; i++) if(compareObjects(haystack[i], needle)) return true;
  return false;
}
