formats_http = "http://cs.tau.ac.il/~naftaly1/get_question_types.php";
sharons_script = "http://cs.tau.ac.il/~naftaly1/get_question_types.php/?format-selected=1"

var app = angular.module('askQuestion', []);

app.controller('askController', function($scope, $http) {


    $scope.createFormatsDropDown = function() {
        $http.get(formats_http).then(function(d) {
            $scope.parseJason(d.data);
        });
    }


    $scope.recreateArgs  = function () {
        var i = $scope.formats.indexOf($scope.selectedFormat);
        var numOfBlanks = $scope.numsOfBlanks[i];
        $scope.showArgsDropDowns(numOfBlanks);
    }

    $scope.showArgsDropDowns = function(numOfArgs) {
        var i;
        $scope.dropDownArr = new Array(3);
        for (i = 0; i < 3; i++){
            if (i < numOfArgs){
                $scope.dropDownArr[i] = true;
            }
            else {
                $scope.dropDownArr[i] = false;
            }
        }
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

    $scope.createFormatsDropDown();


});

