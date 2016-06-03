/**
 * Created by Yishay on 6/3/2016.
 */

var app = angular.module('updateDb', []);

app.controller('updateDbController', function($scope, $http, $location) {

    $scope.isCurrentlyUpdating = false;
    $scope.update = function() {
        console.log($scope.isCurrentlyUpdating)
        $scope.isCurrentlyUpdating = true;
        console.log($scope.isCurrentlyUpdating)
    }
});

