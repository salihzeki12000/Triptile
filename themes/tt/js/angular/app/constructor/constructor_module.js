var app = angular.module('TripConstructor',
  ['ng', 'ngDialog', 'angular.filter', 'slickCarousel', 'leaflet-directive',
    'moment-picker', 'duScroll', 'ngStorage', 'internationalPhoneNumber', 'ngMask', 'ngAnimate']);

app.config(function($interpolateProvider) {
  $interpolateProvider.startSymbol('[[');
  $interpolateProvider.endSymbol(']]');
});