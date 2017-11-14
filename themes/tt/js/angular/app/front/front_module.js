var app = angular.module('FrontSearch', ['moment-picker', 'duScroll', 'ngStorage', 'ngDialog']);

app.config(function($interpolateProvider) {
  $interpolateProvider.startSymbol('[[');
  $interpolateProvider.endSymbol(']]');
});