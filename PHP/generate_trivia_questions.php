<?php

// TOdO change sprintf to something more secure
// TODO: handle errors on client side
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

require_once 'mysql_general.php';

function get_question_format($q_type){
    switch ($q_type) {
        case 1:
            return 'Where did the %s %s olympic games take place?';
        case 2:
            return 'How many olympic games did %s participated in?';
        case 3:
            return 'Which of the following was part of the %s competitors at the olympic games?';
        case 4:
            return 'How many %s medals did %s win at the olympic games?';
        case 5:
            return 'Who won most medals at the %s %s olympic games?';
        case 6:
            return 'In which of the following competition type did %s participated?';
    }
}

function build_question_from_args_and_update_args($q_type, $args_row, &$arg1, &$arg2){
    // get the question format for q_type
    $question_format = get_question_format($q_type);

    // build the question
    switch ($q_type) {
        case 1:
            $arg1 = $args_row['year'];
            $arg2 = $args_row['season'];
            $question = sprintf($question_format, $arg1, $arg2);
            break;
        case 2:
            $arg1 = $args_row['dbp_label'];
            $arg2 = null;
            $question = sprintf($question_format, $arg1);
            break;
        case 3:
            $arg1 = $args_row['field_name'];
            $arg2 = null;
            $question = sprintf($question_format, $arg1);
            break;
        case 4:
            $arg1 = $args_row['medal_color'];
            $arg2 = $args_row['dbp_label'];
            $question = sprintf($question_format, $arg1, $arg2);
            break;
        case 5:
            $arg1 = $args_row['year'];
            $arg2 = $args_row['season'];
            $question = sprintf($question_format, $arg1, $arg2);
            break;
    }
    return $question;
}


function get_questions_args_sql_query($q_type, $num_questions){
    switch ($q_type) {
        case 1:
            $sql_query_format = "SELECT year, season, num_correct, num_wrong
                FROM Question_type1
                ORDER BY RAND()
                LIMIT %d";
            break;
        case 2:
            $sql_query_format = "SELECT dbp_label, num_correct, num_wrong
                FROM Question_type2
                ORDER BY RAND()
                LIMIT %d";
            break;
        case 3:
            $sql_query_format = "SELECT field_name, num_correct, num_wrong
                FROM Question_type3
                ORDER BY RAND()
                LIMIT %d";
            break;
        case 4:
            $sql_query_format = "SELECT medal_color, dbp_label, num_correct, num_wrong
                FROM Question_type4
                ORDER BY RAND()
                LIMIT %d";
            break;
        case 5:
            $sql_query_format = "SELECT year, season, num_correct, num_wrong
                FROM Question_type5
                ORDER BY RAND()
                LIMIT %d";
            break;
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
            return "SELECT a.dbp_label 
                FROM Athlete a, AthleteOlympicSportFields af, OlympicSportField f 
                WHERE  a.athlete_id = af.athlete_id AND
                af.field_id = f.field_id AND
                f.field_name = '%s'
                ORDER BY RAND()
                LIMIT 1";
        case 4:
            return "SELECT COUNT(*) AS cnt
                FROM  Athlete a, AthleteMedals am
                WHERE a.dbp_label = '%s'
                AND a.athlete_id = am.athlete_id
                AND am.medal_color = '%s'";
        case 5:
            return "SELECT a.dbp_label
                    FROM Athlete a, (SELECT temp.medal_count AS medal_count, temp.athlete_id AS athlete_id
                            FROM (SELECT COUNT(*) AS medal_count, am.athlete_id
                            FROM AthleteMedals am, OlympicGame og
                            WHERE og.year = %s
                            AND og.season = '%s'
                            AND og.game_id = am.game_id
                            GROUP BY(am.athlete_id)) AS temp
                            WHERE temp.medal_count = (SELECT MAX(medal_count) 
                                                      FROM (SELECT COUNT(*) AS medal_count, am.athlete_id
                                                            FROM AthleteMedals am, OlympicGame og
                                                            WHERE og.year = %s
                                                            AND og.season = '%s'
                                                            AND og.game_id = am.game_id
                                                            GROUP BY(am.athlete_id)) AS temp)
                            ORDER BY RAND()
                            LIMIT 1) AS maxAthleteForGame
                    Where a.athlete_id =  maxAthleteForGame.athlete_id";
    }
}

function get_wrong_answer_sql_query_format($q_type){
    switch ($q_type) {
        case 1:
            return "SELECT city AS wrong_answer
                FROM OlympicGame
                WHERE (year != '%s' OR
                    season != '%s') AND 
                    city != '' AND 
                    city != '%s'
                LIMIT 3";
        case 2:
            return "select DISTINCT count(athlete_id) AS wrong_answer
                FROM (SELECT DISTINCT game_id, athlete_id
                    FROM AthleteGames) as bla
                group by athlete_id
                HAVING count(athlete_id) != %d
                LIMIT 3";
        case 3:
            return "SELECT a.dbp_label AS wrong_answer 
                FROM Athlete a
                WHERE  a.dbp_label not in (SELECT a1.dbp_label FROM 
                Athlete a1, AthleteOlympicSportFields af, OlympicSportField f 
                WHERE a1.athlete_id = af.athlete_id AND
                af.field_id = f.field_id AND
                f.field_name = '%s')
                ORDER BY RAND()
                LIMIT 3";
        case 4:
            return "SELECT DISTINCT COUNT(*) AS wrong_answer
                FROM AthleteMedals am
                WHERE am.medal_color = '%s'
                GROUP BY(am.athlete_id)
                HAVING COUNT(*) != %d
                ORDER BY RAND()
                LIMIT 3";
        case 5:
            return "SELECT a.dbp_label AS wrong_answer
                FROM Athlete a
                WHERE a.athlete_id not in (SELECT temp.athlete_id AS athlete_id
                                            FROM (SELECT COUNT(*) AS medal_count, am.athlete_id
                                            FROM AthleteMedals am, OlympicGame og
                                            WHERE og.year = %s
                                            AND og.season = '%s'
                                            AND og.game_id = am.game_id
                                            GROUP BY(am.athlete_id)) AS temp
                                            WHERE temp.medal_count = (SELECT MAX(medal_count) 
                                                                      FROM (SELECT COUNT(*) AS medal_count, am.athlete_id
                                                                            FROM AthleteMedals am, OlympicGame og
                                                                            WHERE og.year = %s
                                                                            AND og.season = '%s'
                                                                            AND og.game_id = am.game_id
                                                                            GROUP BY(am.athlete_id)) AS temp)) 
                ORDER BY RAND()
                LIMIT 3";
    }
}

function get_correct_answer($q_type, $args_row){
    $correct_answer_sql_query_format = get_correct_answer_sql_query_format($q_type);
    switch ($q_type) {
        case 1:
            $year = $args_row['year'];
            $season = $args_row['season'];
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
            $field_name = $args_row['field_name'];
            $sql_query = sprintf($correct_answer_sql_query_format, $field_name);
            $result = run_sql_select_query($sql_query);
            $correct_answer = $result->fetch_assoc()['dbp_label'];
            return $correct_answer;
        case 4:
            $medal_color = $args_row['medal_color'];
            $name = $args_row['dbp_label'];
            $sql_query = sprintf($correct_answer_sql_query_format, $name, $medal_color);
            $result = run_sql_select_query($sql_query);
            $correct_answer = $result->fetch_assoc()['cnt'];
            return $correct_answer;
        case 5:
            $year = $args_row['year'];
            $season = $args_row['season'];
            $sql_query = sprintf($correct_answer_sql_query_format, $year, $season, $year, $season);
            $result = run_sql_select_query($sql_query);
            $correct_answer = $result->fetch_assoc()['dbp_label'];
            return $correct_answer;
    }
}

function get_wrong_answers_arr($q_type, $args_row, $correct_answer){
    $wrong_answer_sql_query_format = get_wrong_answer_sql_query_format($q_type);
    $answer_array = array();
    switch ($q_type) {
        case 1:
            $year = $args_row['year'];
            $season = $args_row['season'];
            $sql_query = sprintf($wrong_answer_sql_query_format, $year, $season, $correct_answer);
            break;
        case 2:
            $sql_query = sprintf($wrong_answer_sql_query_format, $correct_answer);
            break;
        case 3:
            $field = $args_row['field_name'];
            $sql_query = sprintf($wrong_answer_sql_query_format, $field);
            break;
        case 4:
            $medal_color = $args_row['medal_color'];
            $sql_query = sprintf($wrong_answer_sql_query_format, $medal_color, $correct_answer);
            break;
        case 5:
            $year = $args_row['year'];
            $season = $args_row['season'];
            $sql_query = sprintf($wrong_answer_sql_query_format, $year, $season, $year, $season);
            break;
    }
    $result = run_sql_select_query($sql_query);
    while ($row = $result->fetch_assoc()) {
        array_push($answer_array, $row['wrong_answer']);
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

    $arg1 = null;
    $arg2 = null;
    while ($args_row = $result->fetch_assoc()){
        $question_dict = array();

        // build the question
        $question = build_question_from_args_and_update_args($q_type, $args_row, $arg1, $arg2);

        // add to question dict
        $question_dict["question"] = $question;

        // put the arguments the question format gets (info inside blanks)
        $question_dict["q_type"] = $q_type;
        $question_dict["arg1"] = $arg1;
        $question_dict["arg2"] = $arg2;

        // put num correct and num wrong in dict
        $question_dict["num_correct"] = $args_row['num_correct'];
        $question_dict["num_wrong"] = $args_row['num_wrong'];

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
// TODO : handle not enough questions in client side
add_type_x_questions_with_answers($questions_arr, 1, $num_q_for_type);
//add_type_x_questions_with_answers($questions_arr, 2, $num_q_for_type);
//add_type_x_questions_with_answers($questions_arr, 3, $num_q_for_type);
//add_type_x_questions_with_answers($questions_arr, 4, $num_q_for_type);
//add_type_x_questions_with_answers($questions_arr, 5, $num_q_for_type);

shuffle($questions_arr);

echo json_encode($questions_arr);

// TODO: remove:
//1)	Where did the (YEAR) (SEASON) Olympic games take place? V
//    2)	How many Olympic games did (athlete) participated in?
//    3)	Which of the following was part of the (sport field) competitors at the Olympic games?
//    4)	How many (color) medals did (athlete) win at the Olympic games?
//    5)	Who won most medals at the (year) (season) Olympic games?
//
//
//    6)	In which of the following competition type did (athlete) participated?
//    7)	Which Athlete won a (COLOR) medal in the competition of (COMPETITION TYPE) at the (YEAR) (SEASON) Olympics?
//    8)	Who won most (color) medals at the Olympic games?
//    9)	How old is (athlete)?
//    10)	Which athlete won a (COLOR) Olympic medal?
?>




