<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

$db = new mysqli("localhost", 'root', '', 'db_project_test');
//$db = new mysqli('mysqlsrv.cs.tau.ac.il', 'DbMysql08', 'DbMysql08', 'DbMysql08');  # for nova


function get_type1_question_with_answers($num_questions){
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
        LIMIT 3"; // TODO: city != '' should be city != null   !!!!

    // get the question parameters:
    $sql_query = sprintf($sql_question_format, $num_questions);

    if(!$result = $db->query($sql_query)){
        die('There was an error running the query [' . $db->error . ']');
    }
    else if ($result->num_rows < $num_questions){
        die('Not enough questions in table Question_type1');
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
        $question_dict["answer"] = $correct_answer;

        // get 3 wrong answers
        $sql_query2 = sprintf($sql_wrong_answer_format, $year, $season, $correct_answer);
        if(!$result2 = $db->query($sql_query2)){
            die('There was an error running the query [' . $db->error . ']');
        }
        $wrong_answer_array = array();
        while ($row = $result2->fetch_assoc()) {
            array_push($wrong_answer_array, $row['city']);
        }
        $question_dict['wrong_answers'] = $wrong_answer_array;
        array_push($type1_questions_array, $question_dict);
    }
    return $type1_questions_array;
}

$type1_q_array = get_type1_question_with_answers(2);
echo json_encode($type1_q_array);

$db->close();

?>

