server_nova = "../Back_End";
server_local = "http://localhost/OlympiData/Back_End";
server_sharon = "http://10.100.102.3/OlympiData/Back_End";
current_server = server_local;
var app = angular.module('quizApp', []);
//var questions_http = "http://cs.tau.ac.il/~nogalavi1/mockAnswerDifferentFormat.php";

app.directive('quiz', function(quizFactory, $http) {

    return {
        restrict: 'AE',
        scope: {},
        templateUrl: 'template.html',
        link: function(scope) {


            scope.start = function() {
                scope.showErrorMessage = false;
                scope.errorMessage = null;
                scope.history = [];
                scope.id = 0;
                scope.checkGetQuestions();
                scope.getQuestion().then(function() {
                    scope.quizOver = false;
                    scope.inProgress = true;
                });
            };

            scope.reset = function() {
                scope.inProgress = false;
                scope.score = 0;
                scope.start();


            }

            scope.checkGetQuestions = function(){
                quizFactory.checkGetStatus(scope);
            }

            scope.setError = function (errStr) {
                scope.errorMessage = errStr;
            }

            scope.doShowErrorMessage = function () {
                scope.showErrorMessage = true;
            }

            scope.getQuestion = function() {
                return quizFactory.getQuestion(scope.id).then(function(q) {
                    if (q) {
                        console.log(q);
                        scope.question = q.question;
                        scope.options = q.options;
                        scope.answer = q.answer;
                        scope.answerMode = true;
                        var denominator = parseInt(q.num_correct) + parseInt(q.num_wrong);
                        if (denominator == 0) {
                            denominator = 1;
                        }
                        scope.correctRatio = Math.round(100*parseInt(q.num_correct)/denominator);
                        scope.arg1 = q.arg1;
                        scope.arg2 = q.arg2;
                        scope.idval = q.id;
                        scope.format = q.q_type;
                        scope.info =  q.more_info; //TODO ((q.more_info == "") ? null :
                        scope.infoTitle = q.more_info_title;
                        scope.image = q.image_url;
                        console.log(scope.infoT);
                        console.log(scope.image);
                    } else {
                        scope.quizOver = true;
                        // update game statistics
                        console.log(scope.history);
                        $http.post(post_statistics, {'stats': scope.history}, {
                            headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
                            transformRequest: transform}).then(function(r) {
                            console.log("Statistics sent succesfully");
                            console.log(r.data);
                        });
                    }
                });
            };

            scope.moreInfo = function() {
                scope.hasPicture = true;
                scope.infoPopupRunning = true;
                if (scope.image == ""){
                    scope.hasPicture = false;
                }
            }

            scope.doneInfo = function() {
                scope.infoPopupRunning = false;
            }

            scope.checkAnswer = function() {
                if(!$('input[name=answer]:checked').length) return;

                var ans = $('input[name=answer]:checked').val();

                if(ans == scope.options[scope.answer]) {
                    scope.score+=10;
                    scope.correctAns = true;
                } else {
                    scope.correctAns = false;
                }

                var correct;

                if (scope.correctAns) {
                    correct = 1;
                } else {
                    correct = 0;
                }
                console.log(scope.idval);
                console.log(scope.arg1);
                scope.history.push({'q_type':scope.format, 'arg1':scope.arg1, 'arg2':scope.arg2, 'id':scope.idval, 'correct':correct}) ;
                console.log(scope.history);
                scope.answerMode = false;
            };

            scope.nextQuestion = function() {
                scope.id++;
                scope.getQuestion();
            }

            scope.done = function() {
                // console.log(scope.history);
                // $http.post(post_statistics, {'stats': scope.history}, {
                //     headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
                //     transformRequest: transform}).then(function(r) {
                //     console.log("Statistics sent succesfully");
                //     console.log(r.data);
                // });
            }

            var transform = function(data){
                return $.param(data);
            }

            scope.reset();
        }

    }
});




app.factory('quizFactory', function($http) {

    var fQuestions = $http.get(questions_http);

    return {
        getQuestion: function(id) {
            return fQuestions.then(function(questions) {
                questions = questions.data;
                if(id < questions.length) {
                    return questions[id];
                } else {
                    return false;
                 }

            });
        },
        checkGetStatus: function(scope){
            fQuestions.then(
                function (data) {
                    console.log(data.data);
                },
                function (data) {
                    console.log(data.data);
                    scope.setError(data.data);
                    scope.doShowErrorMessage();
                }
            )
        }
    };
});






