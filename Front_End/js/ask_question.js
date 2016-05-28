formats_http = "http://cs.tau.ac.il/~naftaly1/get_question_types.php";
firstArg_http = "http://cs.tau.ac.il/~naftaly1/get_question_types.php/?argbla=blabla";

var app = angular.module('askQuestion', []);

app.controller('askController', function($scope, $http) {

    $scope.DropDownDiabled = true;

    $scope.createFormatsDropDown = function() {
        $http.get(formats_http).then(function(d) {
            $scope.parseJason(d.data);
            //var qTypeAnswer = "http://cs.tau.ac.il/~naftaly1/get_question_types.php/?q_type=" + $scope.questionTypeIndex;
            //$http.get(qTypeAnswer).then(function(opt) {
                //$scope.parseFirstArgumentJason(opt.data);
        //});
    });


    $scope.recreateArgs  = function () {
        $scope.questionTypeIndex = $scope.formats.indexOf($scope.selectedFormat);
        var numOfBlanks = $scope.numsOfBlanks[$scope.questionTypeIndex];
        $scope.showArgsDropDowns(numOfBlanks);
    }

    $scope.showArgsDropDowns = function(numOfArgs) {
        var i;
        $scope.dropDownArr = new Array(3);
        for (i = 0; i < 3; i++) {
            if (i < numOfArgs) {
                $scope.dropDownArr[i] = $scope.args[$scope.questionTypeIndex][i];
            }
            else {
                $scope.dropDownArr[i] = false;
            }
        }
    }


    $scope.parseJason = function(data) {
        var formats = [];
        var numsOfBlanks = [];
        var args = [];
        var i;
        for (i = 0; i < data.length; i++) {
            formats.push(data[i].question_format);
            numsOfBlanks.push(data[i].num_args);
            args.push(data[i].args);
        }
        $scope.formats = formats;
        $scope.numsOfBlanks = numsOfBlanks;
        $scope.args = args;
    }
        
        
        
        
        
        
        
        $scope.createFormatsDropDown();


    }
});


