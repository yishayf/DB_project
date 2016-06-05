server_local = "http://localhost/OlympiData/Back_End"
server_nova = "http://cs.tau.ac.il/~naftaly1"
server_sharon = "http://10.100.102.3/OlympiData/Back_End"
current_server = server_sharon
formats_http = current_server + "/get_question_types.php";
firstArg_http = current_server + "/get_1st_arg_options_for_q_type.php/?q_type=";
secondArg_http = current_server + "/get_2nd_arg_options_for_q_type.php/?q_type=";
post_question = current_server + "/add_question_from_user.php/" //?q_type=num_args, arg1, arg2

var app = angular.module('askQuestion', []);

app.controller('askController', function($scope, $http, $location) {

    $scope.showErrorMessage = false;
    $scope.createFormatsDropDown = function() {
        $http.get(formats_http).then(function (d) {
            $scope.parseJason(d.data);
        });
    }
    
    $scope.firstUpdated = function () {
        $scope.selectedSecondArg = null;
        var questionType = $scope.questionTypeIndex;
        if ($scope.numsOfBlanks[questionType] > 1){
            var qTypeAnswer = secondArg_http + (questionType +1) + "&arg1=" + $scope.selectedFirstArg;
            $http.get(qTypeAnswer).then(function (options) {
                $scope.secondArgOptions = options.data;
            }, function (data) {
                $scope.errorMessage = data.data;
                $scope.showErrorMessage = true;
            });
        }
        $scope.updateAllArgsSelected();
    }

    $scope.secondUpdated = function() {
        //$scope.selectedThirdArg = null;
        $scope.updateAllArgsSelected();
    }


    $scope.updateAllArgsSelected = function() {
        var numArgs = $scope.numsOfBlanks[$scope.questionTypeIndex];
        var one = (numArgs == 1 && ($scope.selectedFirstArg!=null));
        var two = (numArgs == 2 && ($scope.selectedFirstArg!=null) && ($scope.selectedSecondArg!=null));
        //var three = (numArgs == 3 && ($scope.selectedFirstArg!=null) && ($scope.selectedSecondArg!=null) && ($scope.selectedThirdArg!=null));
        $scope.allArgsSelected =  one || two; // || three;
    }


    $scope.updateFirstArg = function () {
        //var qTypeAnswer = "http://localhost/DB_php/get_question_types.php/?q_type=" + $scope.questionTypeIndex;
        var questionType = $scope.questionTypeIndex;
        var qTypeAnswer = firstArg_http + (parseInt(questionType) + 1);
        $http.get(qTypeAnswer).then(function (options) {
            console.log("Correct");
            $scope.firstArgOptions = options.data;
        }, function (data) {
            $scope.errorMessage = data.data;
            $scope.showErrorMessage = true;
        });
    }
        
        
        
    $scope.recreateArgs  = function () {
        $scope.selectedFirstArg = null;
        $scope.selectedSecondArg = null;
        //$scope.selectedThirdArg = null;
        $scope.allArgsSelected = false;
        $scope.questionTypeIndex = $scope.formats.indexOf($scope.selectedFormat);
        var numOfBlanks = $scope.numsOfBlanks[$scope.questionTypeIndex];
        $scope.showArgsDropDowns(numOfBlanks);
        $scope.updateFirstArg();
        $scope.selectedFormatWithNumbers = $scope.wordsToNumbers($scope.selectedFormat);
    }


    $scope.showArgsDropDowns = function(numOfArgs) {
        var i;
        $scope.dropDownArr = new Array(2); //3
        for (i = 0; i < 2; i++) { // i<3
            if (i < numOfArgs) {
                $scope.dropDownArr[i] = $scope.args[$scope.questionTypeIndex][i];
            }
            else {
                $scope.dropDownArr[i] = false;
            }
        }
    }

    $scope.submit = function() {
        var qtype = parseInt($scope.questionTypeIndex)+1;
        var numArgs = $scope.numsOfBlanks[$scope.questionTypeIndex];
        var arg1 = $scope.selectedFirstArg;
        var arg2 = $scope.selectedSecondArg;
        var requestData = {"q_type":qtype, "num_args":numArgs, "arg1":arg1, "arg2":arg2};
        console.log(requestData);
        $scope.allArgsSelected  = false; //disable submit button
        $http.post(post_question, requestData, {
            headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
            transformRequest: transform}).then(function(r) {
            console.log("Submit succeded");
            $scope.showPopop();
        });
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


    var transform = function(data){
        return $.param(data);
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


    $scope.showPopop = function() {
        $scope.showPopUp = true;
        $scope.firstArgOptions = null;
        $scope.secondArgOptions = null;
        //$scope.thirdArgOptions = null;
        $scope.formats = null;
        $scope.popupRunning = true;

    }

    $scope.go = function(hash) {
        $location.path(hash);
        console.log(hash);
    }

    $scope.createFormatsDropDown();


});


