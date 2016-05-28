<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

require_once 'mysql_general.php';

function get_question_format($q_type){
    switch ($q_type) {
        case 1:
            return 'Where did the %s %s olympic games take place?';
        case 2:
            return 'How many Olympic games did %s participated in?';
        case 3:
            return 'Which of the following was part of the %s competitors at the Olympic games?';
        case 4:
            return '';
        case 5:
            return '';
    }
}

function get_questions_args_sql_query($q_type, $num_questions){
    switch ($q_type) {
        case 1:
            $sql_query_format = "SELECT year, season
                FROM Question_type1
                ORDER BY RAND()
                LIMIT %d";
            break;
        case 2:
            $sql_query_format = "SELECT dbp_label, name
                FROM Question_type2
                ORDER BY RAND()
                LIMIT %d";
            break;
        case 3:
            return '';
        case 4:
            return '';
        case 5:
            return '';
    }
    // insert the num_question parameters:
    $sql_query = sprintf($sql_query_format, $num_questions);
    return $sql_query;
}

function get_correct_answer_sql_query_format($q_type){
    switch ($q_type) {
        case 1:
            return "SELECT city
                FROM OlympicGame
                WHERE year = %s AND
                      season = '%s'";
        case 2:
            return "SELECT count(DISTINCT ag.game_id) AS num_games
                FROM AthleteGames ag, Athlete a
                WHERE ag.athlete_id = a.athlete_id 
                AND a.dbp_label = '%s'"; // TODO: add index to name !! add foreign key to dbp_label?
        case 3:
            return '';
        case 4:
            return '';
        case 5:
            return '';
    }
}

function get_wrong_answer_sql_query_format($q_type){
    switch ($q_type) {
        case 1:
            return "SELECT city
                FROM OlympicGame
                WHERE (year != '%s' OR
                    season != '%s') AND 
                    city != '' AND 
                    city != '%s'
                LIMIT 3";
        case 2:
            return "select DISTINCT count(athlete_id) as cnt
                FROM (SELECT DISTINCT game_id, athlete_id
                    FROM AthleteGames) as bla
                group by athlete_id
                HAVING cnt != %d
                LIMIT 3";
        case 3:
            return '';
        case 4:
            return '';
        case 5:
            return '';
    }


}

function build_question_from_args($q_type, $args_row){
    // get the question format for q_type
    $question_format = get_question_format($q_type);

    // build the question
    switch ($q_type) {
        case 1:
            $year = $args_row['year'];
            $season = $args_row['season'];
            $question = sprintf($question_format, $year, $season);
            break;
        case 2:
            $name = $args_row['name'];
            $question = sprintf($question_format, $name);
            break;
        case 3:
            return '';
        case 4:
            return '';
        case 5:
            return '';
    }
    return $question;
}

function get_correct_answer($q_type, $args_row){
    $correct_answer_sql_query_format = get_correct_answer_sql_query_format($q_type);
    switch ($q_type) {
        case 1:
            $year = $args_row['year'];
            $season = $args_row['season'];
            // get the correct answer
            $sql_query = sprintf($correct_answer_sql_query_format, $year, $season);
            $result = run_sql_select_query($sql_query);
            $correct_answer = $result->fetch_assoc()['city'];
            return $correct_answer;
        case 2:
            $label = $args_row['dbp_label'];
            $sql_query = sprintf($correct_answer_sql_query_format, $label);
            $result = run_sql_select_query($sql_query);
            $correct_answer = $result->fetch_assoc()['num_games'];
            return $correct_answer;
        case 3:
            return '';
        case 4:
            return '';
        case 5:
            return '';
    }

}

function get_wrong_answers_arr($q_type, $args_row, $correct_answer){
    $wrong_answer_sql_query_format = get_wrong_answer_sql_query_format($q_type);
    $answer_array = array();
    switch ($q_type) {
        case 1:
            $year = $args_row['year'];
            $season = $args_row['season'];
            // get the correct answer
            $sql_query = sprintf($wrong_answer_sql_query_format, $year, $season, $correct_answer);
            $result = run_sql_select_query($sql_query);
            while ($row = $result->fetch_assoc()) {
                array_push($answer_array, $row['city']);
            }
            break;
        case 2:
            $sql_query = sprintf($wrong_answer_sql_query_format, $correct_answer);
            $result = run_sql_select_query($sql_query);
            while ($row = $result->fetch_assoc()) {
                array_push($answer_array, $row['cnt']);
            }
            break;
        case 3:
            return '';
        case 4:
            return '';
        case 5:
            return '';
    }
    return $answer_array;
}



function add_type_x_questions_with_answers(&$questions_array, $q_type, $num_questions){

    // get sql query for getting the q_type blank filling for num_questions
    $sql_args_query = get_questions_args_sql_query($q_type, $num_questions);

    // run the query for getting the questions args
    $result = run_sql_select_query($sql_args_query);

    if ($result->num_rows < $num_questions){
        die(sprintf('ERROR: Not enough questions for question type %d', $q_type));
    }


    while ($args_row = $result->fetch_assoc()){
        $question_dict = array();

        // build the question
        $question = build_question_from_args($q_type, $args_row);

        // add to question dict
        $question_dict["question"] = $question;

        // get the correct answer
        $correct_answer = get_correct_answer($q_type, $args_row);

        // get 3 wrong answers to answer array
        $answer_array = get_wrong_answers_arr($q_type, $args_row, $correct_answer);

        // put the correct answer in the answer array in a random place
        $place = mt_rand(0, 3);
        array_splice($answer_array, $place, 0, $correct_answer);
        $question_dict["options"] = $answer_array;
        $question_dict["answer"] = $place;

        // put the dict in the question array
        array_push($questions_array, $question_dict); // TODO: what should we do if array is not of size 3 ???
    }
}

$questions_arr = array();

$num_q_for_type = 1;

add_type_x_questions_with_answers($questions_arr, 2, $num_q_for_type);

echo json_encode($questions_arr);

$db->close();

?>


