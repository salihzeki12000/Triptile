(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.map = {
    attach: function (context) {
      if (typeof drupalSettings.routes != 'undefined') {
        var routes = drupalSettings.routes;
        if (routes[0]['departure_station'].lat)
        L.mapbox.accessToken = 'pk.eyJ1IjoidHJhdmVsYWxscnVzc2lhIiwiYSI6IjA1Q2F1S3cifQ.BQwNPEEMC764gkvFZvRU7Q';

        var map = L.mapbox.map('map','travelallrussia.a732a1d3',{
          minZoom: 3,
          maxZoom: 10,
          scrollWheelZoom: false
        }).setView(getMiddle(routes), 6);

        for (var route in routes) {
          for (var station in routes[route]) {
            drawMarker(routes[route][station]);
          }
        }

        for (var route in routes) {
          drawPolyLine(routes[route]);
        }
      }

      function drawMarker(station) {
        L.marker([station.lat, station.lng], {
          icon: L.divIcon({
            // Specify a class name we can refer to in CSS.
            className: 'station-icon',
            // Set marker width and height
            iconSize: [15, 15]
          })
        }).addTo(map);

        L.marker([station.lat,station.lng], {
          icon: L.divIcon({
            // Specify a class name we can refer to in CSS.
            className: 'station-label',
            html: '<span>' + station.name + '</span>',
            iconSize: [100, 20]
          })
        }).addTo(map);
      }

      function getMiddle(routes) {
        var lat = 0;
        var lng = 0;
        var count = 0;

        for (var route in routes) {
          for (var station in routes[route]) {
            lat += parseFloat(routes[route][station].lat);
            lng += parseFloat(routes[route][station].lng);
            count++;
          }
        }

        return [lat/count, lng/count];
      }

      function drawPolyLine(stations) {
        L.polyline(getPolyLines(stations), {
          color: '#C10017',
          weight: 3,
          opacity: 1,
          smoothFactor: 1
        }).addTo(map);
      }

      function getPolyLines(stations) {
        var polyLines = [];
        for (var station in stations) {
          var op = L.latLng(stations[station].lat, stations[station].lng);
          polyLines.push(op);
        }

        return polyLines;
      }
    }
  };

})(jQuery, Drupal, drupalSettings);
