<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

$db = new mysqli("localhost", 'root', '', 'db_project_test');
//$db = new mysqli('mysqlsrv.cs.tau.ac.il', 'DbMysql08', 'DbMysql08', 'DbMysql08');  # for nova


function get_type1_question_with_answers($num_questions=1){
    global $db;
    $out_json = "";
    // return a question for type 1:
    $question_format = 'Where did the %s %s olympic games take place?';
    $sql_question_format = "SELECT year, season
        FROM `Question_type1`
        ORDER BY RAND()
        LIMIT %d";
    $sql_correct_answer_format = "
        SELECT city
        FROM OlympicGame
        WHERE year = %s AND
        season = '%s'";
    $sql_wrong_answer_format = "
        SELECT city
        FROM OlympicGame
        WHERE (year != '%s' OR
        season != '%s') AND city != '' AND city != '%s'
        LIMIT 3";

    // insert the num_question parameters:
    $sql_query = sprintf($sql_question_format, $num_questions);

    if(!$result = $db->query($sql_query)){
        die('ERROR: There was an error running the query [' . $db->error . ']');
    }
    else if ($result->num_rows < $num_questions){
        die('ERROR: Not enough questions for type 1');
    }

    $type1_questions_array =  array();

    while ($row = $result->fetch_assoc()){
        $question_dict = array();

        $year = $row['year'];
        $season = $row['season'];
        $question = sprintf($question_format, $year, $season);
        $question_dict["question"] = $question;

        // get the correct answer
        $sql_query1 = sprintf($sql_correct_answer_format, $year, $season);
        if(!$result1 = $db->query($sql_query1)){
            die('There was an error running the query [' . $db->error . ']');
        }
        $correct_answer = $result1->fetch_assoc()['city'];

        // get 3 wrong answers
        $sql_query2 = sprintf($sql_wrong_answer_format, $year, $season, $correct_answer);
        if(!$result2 = $db->query($sql_query2)){
            die('There was an error running the query [' . $db->error . ']');
        }
        $answer_array = array();
        while ($row = $result2->fetch_assoc()) {
            array_push($answer_array, $row['city']);
        }

        // put the correct answer in the answer array in a random place
        $place = mt_rand(0, 3);
        array_splice($answer_array, $place, 0, $correct_answer);
        $question_dict["options"] = $answer_array;
        $question_dict["answer"] = $place;

        array_push($type1_questions_array, $question_dict); // TODO: what should we do if array is not of size 3 ???

    }
    return $type1_questions_array;
}



$ten_questions_arr = array();

$ten_questions_arr = $ten_questions_arr + get_type1_question_with_answers(2);

echo json_encode($ten_questions_arr);

$db->close();

?>


