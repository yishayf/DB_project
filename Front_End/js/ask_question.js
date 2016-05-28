server_local = "http://localhost/DB_php"
server_nova = "http://cs.tau.ac.il/~naftaly1"
formats_http = server_local + "/get_question_types.php";
firstArg_http = "http://cs.tau.ac.il/~naftaly1/get_question_types.php/?argbla=blabla";
noga_array = "http://cs.tau.ac.il/~nogalavi1/mockYearsNew.php";

var app = angular.module('askQuestion', []);

app.controller('askController', function($scope, $http) {

    //$scope.DropDownDiabled = true;

    $scope.createFormatsDropDown = function() {
        $http.get(formats_http).then(function (d) {
            $scope.parseJason(d.data);
        });
    }
    
    $scope.firstUpdated = function () {
        console.log($scope.numsOfBlanks);
        if ($scope.numsOfBlanks[$scope.questionTypeIndex] > 1){
            var qTypeAnswer = "http://cs.tau.ac.il/~nogalavi1/mockYearsNew.php";
            $http.get(qTypeAnswer).then(function (options) {
                $scope.secondArgOptions = options.data;
            });
        }
    }
    
    $scope.updateFirstArg = function () {
        //var qTypeAnswer = "http://localhost/DB_php/get_question_types.php/?q_type=" + $scope.questionTypeIndex;
        var qTypeAnswer = "http://cs.tau.ac.il/~nogalavi1/mockYearsNew.php";
        $http.get(qTypeAnswer).then(function (options) {
            $scope.firstArgOptions = options.data;
        });
    }
        
        
        
    $scope.recreateArgs  = function () {
        $scope.selectedFirstArg = null;
        $scope.selectedSecondArg = null;
        $scope.selectedThirdArg = null;
        $scope.questionTypeIndex = $scope.formats.indexOf($scope.selectedFormat);
        var numOfBlanks = $scope.numsOfBlanks[$scope.questionTypeIndex];
        $scope.showArgsDropDowns(numOfBlanks);
        $scope.updateFirstArg();
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
        
    $scope.parseFirstArgumentJason = function (data) {
        
    }
    
    $scope.createFormatsDropDown();
});

