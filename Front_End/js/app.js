var questions_http = "http://cs.tau.ac.il/~nogalavi1/mockAnswerDifferentFormat.php";

var app = angular.module('quizApp', []);

app.directive('quiz', function(quizFactory, questionsFactory) {
    return {
        restrict: 'AE',
        scope: {},
        templateUrl: 'template.html',
        link: function(scope, elem, attrs) {
            scope.start = function() {
                scope.id = 0;
                scope.getQuestion().then(function(q) {
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
                        console.log("no q");
                        console.log(q);
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



app.factory('questionsFactory', function($http) {
    return {
        serverCall: function() {
           return  $http.get(questions_http).then(function(data) {
               console.log("from php:");
               questions = data.data;
               console.log(questions);
               var questions = questions;
               return questions;
           });
        }
    }
});

app.factory('quizFactory', function($http) {


    //var questions = [
    //    {
    //        question: "Which is the largest country in the world by population?",
    //        options: ["India", "USA", "China", "Russia"],
    //        answer: 2
    //    },
    //    {
    //        question: "When did the second world war end?",
    //        options: ["1945", "1939", "1944", "1942"],
    //        answer: 0
    //    },
    //    {
    //        question: "Which was the first country to issue paper currency?",
    //        options: ["USA", "France", "Italy", "China"],
    //        answer: 3
    //    },
    //    {
    //        question: "Which city hosted the 1996 Summer Olympics?",
    //        options: ["Atlanta", "Sydney", "Athens", "Beijing"],
    //        answer: 0
    //    },
    //    {
    //        question: "Who invented telephone?",
    //        options: ["Albert Einstein", "Alexander Graham Bell", "Isaac Newton", "Marie Curie"],
    //        answer: 1
    //    }
    //
    //];

    var fQuestions = $http.get(questions_http);


    return {
        getQuestion: function(id) {
            return fQuestions.then(function(questions) {
                questions = questions.data;
                if(id < questions.length) {
                    console.log(questions);
                    return questions[id];
                } else {
                    console.log("returning false\n");
                    console.log(questions);
                    return false;
                 }

            });
            console.log(fQuestions);
        }
    };
});




