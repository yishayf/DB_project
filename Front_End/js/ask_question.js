server_local = "http://localhost/DB_php"
server_nova = "http://cs.tau.ac.il/~naftaly1"
server_sharon = "http://192.168.14.37/phpTest"
formats_http = server_sharon + "/get_question_types.php";
firstArg_http = server_sharon + "/get_1st_arg_options_for_q_type.php/?q_type=";
secondArg_http = server_sharon + "/get_2nd_arg_options_for_q_type.php/?q_type=";

var app = angular.module('askQuestion', []);

app.controller('askController', function($scope, $http) {


    $scope.createFormatsDropDown = function() {
        $http.get(formats_http).then(function (d) {
            $scope.parseJason(d.data);
        });
    }
    
    $scope.firstUpdated = function () {
        $scope.selectedSecondArg = null;
        var questionType = $scope.questionTypeIndex;
        if ($scope.numsOfBlanks[questionType] > 1){
            var qTypeAnswer = secondArg_http + questionType +1 + "&arg1=" + $scope.selectedFirstArg;
            $http.get(qTypeAnswer).then(function (options) {
                $scope.secondArgOptions = options.data;
            });
        }
        $scope.updateAllArgsSelected();
    }

    $scope.secondUpdated = function() {
        $scope.selectedThirdArg = null;
        $scope.updateAllArgsSelected();
    }


    $scope.updateAllArgsSelected = function() {
        var numArgs = $scope.numsOfBlanks[$scope.questionTypeIndex];
        var one = (numArgs == 1 && ($scope.selectedFirstArg!=null));
        var two = (numArgs == 2 && ($scope.selectedFirstArg!=null) && ($scope.selectedSecondArg!=null));
        var three = (numArgs == 3 && ($scope.selectedFirstArg!=null) && ($scope.selectedSecondArg!=null) && ($scope.selectedThirdArg!=null));
        console.log(one || two || three);
        $scope.allArgsSelected =  one || two || three;
    }


    $scope.updateFirstArg = function () {
        //var qTypeAnswer = "http://localhost/DB_php/get_question_types.php/?q_type=" + $scope.questionTypeIndex;
        var questionType = $scope.questionTypeIndex;
        var qTypeAnswer = firstArg_http + (parseInt(questionType) + 1);
        $http.get(qTypeAnswer).then(function (options) {
            $scope.firstArgOptions = options.data;
        });
    }
        
        
        
    $scope.recreateArgs  = function () {
        $scope.selectedFirstArg = null;
        $scope.selectedSecondArg = null;
        $scope.selectedThirdArg = null;
        $scope.allArgsSelected = false;
        $scope.questionTypeIndex = $scope.formats.indexOf($scope.selectedFormat);
        var numOfBlanks = $scope.numsOfBlanks[$scope.questionTypeIndex];
        $scope.showArgsDropDowns(numOfBlanks);
        $scope.updateFirstArg();
        $scope.selectedFormatWithNumbers = $scope.wordsToNumbers($scope.selectedFormat);
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

    $scope.submit = function() {

    }

    $scope.wordsToNumbers = function() {
        var s = $scope.selectedFormat;
        var result = "";
        var i;
        var numOfmatches = 1;
        for (i=0; i<s.length; i++ ) {
            if (s.charAt(i) == '[') {
                result += ('#'+numOfmatches);
                numOfmatches += 1;
                while (s.charAt(i) != ']') {
                    i ++
                }
                i++;
            }
            result += s.charAt(i);
        }
        return result;
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
});

