formats_http = "http://cs.tau.ac.il/~naftaly1/get_question_types.php";

var app = angular.module('askQuestion', []);

app.controller('askController', function($scope, $http) {

    $scope.DropDownDiabled = true;

    $http.get(formats_http).then(function(d) {
        $scope.formats = getText(d.data);
        $scope.formatsJson = d.data;
        $scope.DropDownDiabled = false;
    });

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
    $scope.updateTemplateSelection = function(){
        console.log("YESSSS");
    }

});
//
// function getText(data) {
//     var result = [];
//     var i;
//     for (i=0; i<data.length; i++) {
//         var string = data[i].question_format;
//         result.push(string);
//     }
//     return result;
// }


