//var questions_http = "http://cs.tau.ac.il/~nogalavi1/mockAnswerDifferentFormat.php";
var questions_http = "http://192.168.14.37/phpTest/generate_trivia_questions.php";
var post_statistics = "http://192.168.14.37/phpTest/update_questions_stats.php";

var app = angular.module('quizApp', []);

app.directive('quiz', function(quizFactory, $http) {

    return {
        restrict: 'AE',
        scope: {},
        templateUrl: 'template.html',
        link: function(scope) {


            scope.start = function() {
                scope.history = [];
                scope.id = 0;
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
                        scope.correctRatio = parseInt(q.num_correct)/denominator;
                        scope.arg1 = q.arg1;
                        scope.arg2 = q.arg2;
                        scope.format = q.q_type;
                    } else {
                        scope.quizOver = true;
                    }
                });
            };

            scope.checkAnswer = function() {
                if(!$('input[name=answer]:checked').length) return;

                var ans = $('input[name=answer]:checked').val();

                if(ans == scope.options[scope.answer]) {
                    scope.score++;
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

                scope.history.push({'q_type':scope.format, 'arg1':scope.arg1, 'arg2':scope.arg2, 'correct':correct}) ;
                scope.answerMode = false;
            };

            scope.nextQuestion = function() {
                scope.id++;
                scope.getQuestion();
            }

            scope.done = function() {
                console.log(scope.history);
                $http.post(post_statistics, {'stats': scope.history}, {
                    headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
                    transformRequest: transform}).then(function(r) {
                    console.log("Statistics sent succesfully");
                    console.log(r);
                });

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
        }
    };
});






