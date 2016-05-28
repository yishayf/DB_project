var questions_http = "http://cs.tau.ac.il/~nogalavi1/mockAnswerDifferentFormat.php";
var questions_http = "http://cs.tau.ac.il/~naftaly1/generate_trivia_questions.php";

var app = angular.module('quizApp', []);

app.directive('quiz', function(quizFactory) {
    return {
        restrict: 'AE',
        scope: {},
        templateUrl: 'template.html',
        link: function(scope) {
            scope.start = function() {
                scope.id = 0;
                scope.getQuestion().then(function() {
                    scope.quizOver = false;
                    scope.inProgress = true;
                });
            };

            scope.reset = function() {
                scope.inProgress = false;
                scope.score = 0;
            }

            scope.getQuestion = function() {
                return quizFactory.getQuestion(scope.id).then(function(q) {
                    if (q) {
                        console.log(q);
                        scope.question = q.question;
                        scope.options = q.options;
                        scope.answer = q.answer;
                        scope.answerMode = true;
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

                scope.answerMode = false;
            };

            scope.nextQuestion = function() {
                scope.id++;
                scope.getQuestion();
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





