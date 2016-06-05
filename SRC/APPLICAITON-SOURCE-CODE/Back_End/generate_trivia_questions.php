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
            return 'How many olympic games did %s participate in?';
        case 3:
            return 'Which of the following was part of the %s competitors at the olympic games?';
        case 4:
            return 'How many %s medals did %s win at the olympic games?';
        case 5:
            return 'Who won most medals at the %s %s olympic games?';
        case 6:
            return 'In which of the following competition type did %s win a medal?';
    }
}

function get_info_for_q_type($q_type, $args_row, $correct_answer){
    switch ($q_type) {
        case 1:
            $id = $args_row['id'];
            $query_format = "SELECT comment AS more_info 
                  FROM OlympicGame WHERE game_id = %d;";
            $sql_query = sprintf($query_format, $id);
            break;
        case 2:
            $id = $args_row['id'];
            $query_format = "SELECT comment AS more_info 
                  FROM Athlete WHERE athlete_id = %d";
            $sql_query = sprintf($query_format, $id);
            break;
        case 3:
            $query_format = "SELECT comment AS more_info 
                  FROM Athlete WHERE dbp_label = '%s'";
            $sql_query = sprintf($query_format, $correct_answer);
            break;
        case 4:
            $id = $args_row['id'];
            $query_format = "SELECT comment AS more_info 
                  FROM Athlete WHERE athlete_id = %d";
            $sql_query = sprintf($query_format, $id);
            break;
        case 5:
            $query_format = "SELECT comment AS more_info 
                  FROM Athlete WHERE dbp_label = '%s'";
            $sql_query = sprintf($query_format, $correct_answer);
            break;
        case 6:
            $id = $args_row['id'];
            $query_format = "SELECT comment AS more_info 
                  FROM Athlete WHERE athlete_id = %d";
            $sql_query = sprintf($query_format, $id);
            break;
    }
    $result = run_sql_select_query($sql_query);
    $info = $result->fetch_assoc()['more_info'];
    return $info;
}

function get_info_title_for_q_type($q_type, $args_row, $correct_answer){
    switch ($q_type) {
        case 1:
            $format = "%d %s Olympics";
            $year = $args_row['year'];
            $season = $args_row['season'];
            $title = sprintf($format, $year, $season);
            return $title;
        case 2:
            return $args_row['dbp_label'];
            break;
        case 3:
            return $correct_answer;
        case 4:
            return $args_row['dbp_label'];
        case 5:
            return $correct_answer;
        case 6:
            return $args_row['dbp_label'];
    }
}

function build_question_from_args_and_update_args($q_type, $args_row, &$arg1, &$arg2, &$id){
    // get the question format for q_type
    $question_format = get_question_format($q_type);
    // build the question
    switch ($q_type) {
        case 1:
            $id = $args_row['id'];
            $arg1 = $args_row['year'];
            $arg2 = $args_row['season'];
            $question = sprintf($question_format, $arg1, $arg2);
            break;
        case 2:
            $id = $args_row['id'];
            $arg1 = $args_row['dbp_label'];
            $arg2 = null;
            $question = sprintf($question_format, $arg1);
            break;
        case 3:
            $id = $args_row['id'];
            $arg1 = $args_row['field_name'];
            $arg2 = null;
            $question = sprintf($question_format, $arg1);
            break;
        case 4:
            $id = $args_row['id'];
            $arg1 = $args_row['medal_color'];
            $arg2 = $args_row['dbp_label'];
            $question = sprintf($question_format, $arg1, $arg2);
            break;
        case 5:
            $id = $args_row['id'];
            $arg1 = $args_row['year'];
            $arg2 = $args_row['season'];
            $question = sprintf($question_format, $arg1, $arg2);
            break;
        case 6:
            $id = $args_row['id'];
            $arg1 = $args_row['dbp_label'];
            $arg2 = null;
            $arg1_text = explode("(", $arg1, 2)[0];
            $question = sprintf($question_format, $arg1_text);
            break;

    }
    return $question;
}

function get_questions_args_sql_query($q_type, $num_questions){
    switch ($q_type) {
        case 1:
            $sql_query_format = "SELECT q1.game_id AS id, og.year, og.season, q1.num_correct, q1.num_wrong
                FROM Question_type1 q1, OlympicGame og
                WHERE q1.game_id = og.game_id
                ORDER BY RAND()
                LIMIT %d";
            break;
        case 2:
            $sql_query_format = "SELECT q2.athlete_id AS id, a.dbp_label, q2.num_correct, q2.num_wrong
                FROM Question_type2 q2, Athlete a
                WHERE q2.athlete_id = a.athlete_id
                ORDER BY RAND()
                LIMIT %d";
            break;
        case 3:
            $sql_query_format = "SELECT q3.field_id AS id, f.field_name, q3.num_correct, q3.num_wrong
                FROM Question_type3 q3, OlympicSportField f
                WHERE q3.field_id = f.field_id
                ORDER BY RAND()
                LIMIT %d";
            break;
        case 4:
            $sql_query_format = "SELECT q4.medal_color, q4.athlete_id AS id, a.dbp_label, q4.num_correct, q4.num_wrong
                FROM Question_type4 q4, Athlete a
                WHERE a.athlete_id = q4.athlete_id
                ORDER BY RAND()
                LIMIT %d";
            break;
        case 5:
            $sql_query_format = "SELECT q5.game_id AS id, og.year, og.season, q5.num_correct, q5.num_wrong
                FROM Question_type5 q5, OlympicGame og
                WHERE q5.game_id = og.game_id
                ORDER BY RAND()
                LIMIT %d";
            break;
        case 6:
            $sql_query_format = "SELECT q6.athlete_id AS id, a.dbp_label, q6.num_correct, q6.num_wrong
                FROM Question_type6 q6, Athlete a
                WHERE q6.athlete_id = a.athlete_id
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
                WHERE game_id = %d;";
        case 2:
            return "SELECT count(DISTINCT ag.game_id) AS num_games
                FROM AthleteGames ag, Athlete a
                WHERE ag.athlete_id = %d;";
        case 3:
            return "SELECT a.dbp_label 
                FROM Athlete a, AthleteOlympicSportFields af, OlympicSportField f 
                WHERE  a.athlete_id = af.athlete_id AND
                af.field_id = %d
                ORDER BY RAND()
                LIMIT 1";
        case 4:
            return "SELECT COUNT(*) AS cnt
                FROM  AthleteMedals am
                WHERE am.athlete_id = %d
                AND am.medal_color = '%s'";
        case 5:
            return "SELECT a.dbp_label
                    FROM Athlete a, (SELECT temp.medal_count AS medal_count, temp.athlete_id AS athlete_id
                            FROM (SELECT COUNT(*) AS medal_count, am.athlete_id
                            FROM AthleteMedals am
                            WHERE am.game_id = %d
                            GROUP BY(am.athlete_id)) AS temp
                            WHERE temp.medal_count = (SELECT MAX(medal_count) 
                                                      FROM (SELECT COUNT(*) AS medal_count, am.athlete_id
                                                            FROM AthleteMedals am
                                                            WHERE am.game_id = %d
                                                            GROUP BY(am.athlete_id)) AS temp)
                            ORDER BY RAND()
                            LIMIT 1) AS maxAthleteForGame
                    Where a.athlete_id =  maxAthleteForGame.athlete_id;";
        case 6:
            return "SELECT DISTINCT c.competition_name
                    FROM CompetitionType c, AthleteMedals am 
                    WHERE am.athlete_id = %d 
                    AND am.competition_id = c.competition_id
                    LIMIT 1;";
    }
}

function get_wrong_answer_sql_query_format($q_type){
    switch ($q_type) {
        case 1:
            return "SELECT city AS wrong_answer
                FROM OlympicGame
                WHERE (game_id != %d) AND 
                    city != '' AND 
                    city != '%s' /* a city can appear twice */
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
                af.field_id = %d)
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
                                            FROM AthleteMedals am
                                            WHERE am.game_id = %d
                                            GROUP BY(am.athlete_id)) AS temp
                                            WHERE temp.medal_count = (SELECT MAX(medal_count) 
                                                                      FROM (SELECT COUNT(*) AS medal_count, am.athlete_id
                                                                            FROM AthleteMedals am
                                                                            WHERE am.game_id = %d
                                                                            GROUP BY(am.athlete_id)) AS temp)) 
                ORDER BY RAND()
                LIMIT 3";
        case 6:
            return "SELECT DISTINCT c.competition_name AS wrong_answer
                    FROM CompetitionType c
                    WHERE c.competition_id NOT IN (SELECT DISTINCT c.competition_id
                                                    FROM CompetitionType c, AthleteMedals am 
                                                    WHERE am.athlete_id = %d
                                                    AND am.competition_id = c.competition_id)
                    ORDER BY RAND()
                    LIMIT 3;";
    }
}

function get_correct_answer($q_type, $args_row){
    $correct_answer_sql_query_format = get_correct_answer_sql_query_format($q_type);
    switch ($q_type) {
        case 1:
            $game_id = $args_row['id'];
            $sql_query = sprintf($correct_answer_sql_query_format, $game_id);
            $result = run_sql_select_query($sql_query);
            $correct_answer = $result->fetch_assoc()['city'];
            return $correct_answer;
        case 2:
            $athlete_id = $args_row['id'];
            $sql_query = sprintf($correct_answer_sql_query_format, $athlete_id);
            $result = run_sql_select_query($sql_query);
            $correct_answer = $result->fetch_assoc()['num_games'];
            return $correct_answer;
        case 3:
            $field_id = $args_row['id'];
            $sql_query = sprintf($correct_answer_sql_query_format, $field_id);
            $result = run_sql_select_query($sql_query);
            $correct_answer = $result->fetch_assoc()['dbp_label'];
            return $correct_answer;
        case 4:
            $athlete_id = $args_row['id'];
            $medal_color = $args_row['medal_color'];
            $sql_query = sprintf($correct_answer_sql_query_format, $athlete_id, $medal_color);
            $result = run_sql_select_query($sql_query);
            $correct_answer = $result->fetch_assoc()['cnt'];
            return $correct_answer;
        case 5:
            $game_id = $args_row['id'];
            $sql_query = sprintf($correct_answer_sql_query_format, $game_id, $game_id);
            $result = run_sql_select_query($sql_query);
            $correct_answer = $result->fetch_assoc()['dbp_label'];
            return $correct_answer;
        case 6:
            $athlete_id = $args_row['id'];
            $sql_query = sprintf($correct_answer_sql_query_format, $athlete_id);
            $result = run_sql_select_query($sql_query);
            $correct_answer = $result->fetch_assoc()['competition_name'];
            return $correct_answer;
    }
}

function get_wrong_answers_arr($q_type, $args_row, $correct_answer){
    $wrong_answer_sql_query_format = get_wrong_answer_sql_query_format($q_type);
    $answer_array = array();
    switch ($q_type) {
        case 1:
            $game_id = $args_row['id'];
            $sql_query = sprintf($wrong_answer_sql_query_format, $game_id, $correct_answer);
            break;
        case 2:
            $sql_query = sprintf($wrong_answer_sql_query_format, $correct_answer);
            break;
        case 3:
            $field_id = $args_row['id'];
            $sql_query = sprintf($wrong_answer_sql_query_format, $field_id);
            break;
        case 4:
            $medal_color = $args_row['medal_color'];
            $sql_query = sprintf($wrong_answer_sql_query_format, $medal_color, $correct_answer);
            break;
        case 5:
            $game_id = $args_row['id'];
            $sql_query = sprintf($wrong_answer_sql_query_format, $game_id, $game_id);
            break;
        case 6:
            $athlete_id = $args_row['id'];
            $sql_query = sprintf($wrong_answer_sql_query_format, $athlete_id);
            break;
    }
    $result = run_sql_select_query($sql_query);
    while ($row = $result->fetch_assoc()) {
        $wrong_answer_str = explode("(", $row['wrong_answer'], 2)[0];
        array_push($answer_array, $wrong_answer_str);
    }
    return $answer_array;
}

function add_type_x_questions_with_answers(&$questions_array, $q_type, $num_questions){

    // get sql query for getting the q_type blank filling for num_questions
    $sql_args_query = get_questions_args_sql_query($q_type, $num_questions);

    // run the query for getting the questions args
    $result = run_sql_select_query($sql_args_query);

    if ($result->num_rows < $num_questions){
        http_response_code(510);
        die(sprintf('ERROR: Not enough questions for question type %d', $q_type));
    }

    $id = null;
    $arg1 = null;
    $arg2 = null;
    while ($args_row = $result->fetch_assoc()){
        $question_dict = array();

        // build the question
        $question = build_question_from_args_and_update_args($q_type, $args_row, $arg1, $arg2, $id);

        // add to question dict
        $question_dict["question"] = $question;

        // put the arguments the question format gets (info inside blanks)
        $question_dict["q_type"] = $q_type;
        $question_dict["arg1"] = $arg1;
        $question_dict["arg2"] = $arg2;
        $question_dict["id"] = $id;

        // put num correct and num wrong in dict
        $question_dict["num_correct"] = $args_row['num_correct'];
        $question_dict["num_wrong"] = $args_row['num_wrong'];

        // get the correct answer
        $correct_answer = get_correct_answer($q_type, $args_row);

        // get 3 wrong answers to answer array
        $answer_array = get_wrong_answers_arr($q_type, $args_row, $correct_answer);

        // get info:
        $info = get_info_for_q_type($q_type, $args_row, $correct_answer);
        $info_title = get_info_title_for_q_type($q_type, $args_row, $correct_answer);
        $question_dict["more_info"] = $info;
        $question_dict["more_info_title"] = $info_title;
        // put the correct answer in the answer array in a random place
        $place = mt_rand(0, 3);
        $correct_answer_str = explode("(", $correct_answer, 2)[0];
        array_splice($answer_array, $place, 0, $correct_answer_str);
        $question_dict["options"] = $answer_array;
        $question_dict["answer"] = $place;

        // put the dict in the question array
        array_push($questions_array, $question_dict);
    }
}

$questions_arr = array();

// get 2 Q's for 5 of the q_types
$num_q_for_type = 2;
$selected_qtypes = array(1,2,3,4,5,6);
shuffle($selected_qtypes);
array_pop($selected_qtypes);
foreach ($selected_qtypes as $q_type){
    add_type_x_questions_with_answers($questions_arr, $q_type, $num_q_for_type);
}
shuffle($questions_arr);

echo json_encode($questions_arr);

// close database connection
$db->close();

?>




