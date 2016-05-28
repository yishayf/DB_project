formats_http = "http://cs.tau.ac.il/~naftaly1/get_question_types.php";

var app = angular.module('askQuestion', []);

app.controller('askController', function($scope, $http) {


    $scope.createFormats = function() {
        $scope.DropDownDisabled = true;
        $http.get(formats_http).then(function(d) {
            $scope.parseJason(d.data);
            $scope.DropDownDisabled = false;
        });
    }
    
    $scope.recreateArgs  = function () {
    }

    $scope.updateTemplateSelection = function(){
    }


    $scope.parseJason = function(data) {
        var formats = [];
        var numsOfBlanks = [];
        var i;
        for (i = 0; i < data.length; i++) {
            formats.push(data[i].question_format);
            numsOfBlanks.push(data[i].num_args);
        }
        $scope.formats = formats;
        $scope.numsOfBlanks = numsOfBlanks;
    }

    $scope.createFormats();
    $scope.recreateArgs();


});

