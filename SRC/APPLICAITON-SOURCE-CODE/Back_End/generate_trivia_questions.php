<?php

///////////////////////// General code ///////////////////////////////////////////////
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

// open db connection
//$db = new mysqli("localhost", 'root', '', 'db_project_test');
$db = new mysqli('mysqlsrv.cs.tau.ac.il', 'DbMysql08', 'DbMysql08', 'DbMysql08');  # for nova
//$db = new mysqli('localhost', 'DbMysql08', 'DbMysql08', 'DbMysql08', 3305);  # for nova local

// Check connection
if ($db->connect_error) {
    http_response_code(500);
    die("Connection failed: " . $db->connect_error);
}
$db->set_charset('utf8');

function execute_sql_statement(&$stmt){
    global $db;
    if(!$stmt->execute()){
        $stmt->close();
        http_response_code(500);
        die('There was an error running the query [' . $db->error . ']');
    }
    $stmt->store_result();
}

function execute_sql_insert_or_update_statement(&$stmt){
    global $db;
    if(!$stmt->execute()){
        http_response_code(500);
        $stmt->close();
        die('There was an error running the query [' . $db->error . ']');
    }
    $stmt->store_result();
    return TRUE;
}


function prepare_stmt($stmt_text){
    global $db;
    if (!$stmt = $db->prepare($stmt_text)){
        http_response_code(500);
        die("Error:preparing query failed: (" . $stmt->errno . ") " . $db->error);
    }
    return $stmt;
}

/*
 * Utility function to automatically bind columns from selects in prepared statements to an array
 * CREDIT: https://gunjanpatidar.wordpress.com/2010/10/03/bind_result-to-array-with-mysqli-prepared-statements/
 */
function bind_result_array($stmt){
    $meta = $stmt->result_metadata();
    $result = array();
    while ($field = $meta->fetch_field())
    {
        $result[$field->name] = NULL;
        $params[] = &$result[$field->name];
    }
    call_user_func_array(array($stmt, 'bind_result'), $params);
    return $result;
}

/////////////////////////////////////////////////////////////////////////////////////

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
            $stmt = prepare_stmt("SELECT comment AS more_info 
                  FROM OlympicGame WHERE game_id = ?;");
            if (!$stmt->bind_param("i", $id)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            break;
        case 2:
            $id = $args_row['id'];
            $stmt = prepare_stmt("SELECT comment AS more_info 
                  FROM Athlete WHERE athlete_id = ?;");
            if (!$stmt->bind_param("i", $id)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            break;
        case 3:
            $stmt = prepare_stmt("SELECT comment AS more_info 
                  FROM Athlete WHERE dbp_label = ?;");
            if (!$stmt->bind_param("s", $correct_answer)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            break;
        case 4:
            $id = $args_row['id'];
            $stmt = prepare_stmt("SELECT comment AS more_info 
                  FROM Athlete WHERE athlete_id = ?;");
            if (!$stmt->bind_param("i", $id)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            break;
        case 5:
            $stmt = prepare_stmt("SELECT comment AS more_info 
                  FROM Athlete WHERE dbp_label = ?;");
            if (!$stmt->bind_param("s", $correct_answer)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            break;
        case 6:
            $id = $args_row['id'];
            $stmt = prepare_stmt("SELECT comment AS more_info 
                  FROM Athlete WHERE athlete_id = ?;");
            if (!$stmt->bind_param("i", $id)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            break;
    }
    execute_sql_statement($stmt);
    $row = bind_result_array($stmt);
    $stmt->fetch();
    $info = $row['more_info'];
    $stmt->close();
    return $info;
}

function get_info_title_for_q_type($q_type, $args_row, $correct_answer){
    switch ($q_type) {
        case 1:
            $format = "%d %s Olympics";
            $year = $args_row['year'];
            $season = $args_row['season'];
            $title = ucwords(sprintf($format, $year, $season));
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

function get_image_url_q_type($q_type, $args_row, $correct_answer){
    switch ($q_type) {
        case 1:
            return "";
        case 2:
            $id = $args_row['id'];
            $stmt = prepare_stmt("SELECT image_url AS image 
                  FROM Athlete WHERE athlete_id = ?;");
            if (!$stmt->bind_param("i", $id)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            break;
        case 3:
            $stmt = prepare_stmt("SELECT image_url AS image 
                  FROM Athlete WHERE dbp_label = ?;");
            if (!$stmt->bind_param("s", $correct_answer)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            break;
        case 4:
            $id = $args_row['id'];
            $stmt = prepare_stmt("SELECT image_url AS image 
                  FROM Athlete WHERE athlete_id = ?;");
            if (!$stmt->bind_param("i", $id)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            break;
        case 5:
            $stmt = prepare_stmt("SELECT image_url AS image 
                  FROM Athlete WHERE dbp_label = ?;");
            if (!$stmt->bind_param("s", $correct_answer)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            break;
        case 6:
            $id = $args_row['id'];
            $stmt = prepare_stmt("SELECT image_url AS image 
                  FROM Athlete WHERE athlete_id = ?;");
            if (!$stmt->bind_param("i", $id)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            break;
    }
    execute_sql_statement($stmt);
    $row = bind_result_array($stmt);
    $stmt->fetch();
    $image = $row['image'];
    $stmt->close();
    return $image;
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
            $stmt = prepare_stmt("SELECT q1.game_id AS id, og.year, og.season, q1.num_correct, q1.num_wrong
                FROM Question_type1 q1, OlympicGame og
                WHERE q1.game_id = og.game_id
                ORDER BY RAND()
                LIMIT ?");
            break;
        case 2:
            $stmt = prepare_stmt("SELECT q2.athlete_id AS id, a.dbp_label, q2.num_correct, q2.num_wrong
                FROM Question_type2 q2, Athlete a
                WHERE q2.athlete_id = a.athlete_id
                ORDER BY RAND()
                LIMIT ?");
            break;
        case 3:
            $stmt = prepare_stmt("SELECT q3.field_id AS id, f.field_name, q3.num_correct, q3.num_wrong
                FROM Question_type3 q3, OlympicSportField f
                WHERE q3.field_id = f.field_id
                ORDER BY RAND()
                LIMIT ?");
            break;
        case 4:
            $stmt = prepare_stmt("SELECT q4.medal_color, q4.athlete_id AS id, a.dbp_label, q4.num_correct, q4.num_wrong
                FROM Question_type4 q4, Athlete a
                WHERE a.athlete_id = q4.athlete_id
                ORDER BY RAND()
                LIMIT ?");
            break;
        case 5:
            $stmt = prepare_stmt("SELECT q5.game_id AS id, og.year, og.season, q5.num_correct, q5.num_wrong
                FROM Question_type5 q5, OlympicGame og
                WHERE q5.game_id = og.game_id
                ORDER BY RAND()
                LIMIT ?");
            break;
        case 6:
            $stmt = prepare_stmt("SELECT q6.athlete_id AS id, a.dbp_label, q6.num_correct, q6.num_wrong
                FROM Question_type6 q6, Athlete a
                WHERE q6.athlete_id = a.athlete_id
                ORDER BY RAND()
                LIMIT ?");
            break;
    }
    // insert the num_question parameters:
    if (!$stmt->bind_param("i", $num_questions)) {
        http_response_code(500);
        die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
    }
    return $stmt;
}

function get_correct_answer_sql_query_format($q_type){
    switch ($q_type) {
        case 1:
            return "SELECT city
                FROM OlympicGame
                WHERE game_id = ?;";
        case 2:
            return "SELECT count(DISTINCT ag.game_id) AS num_games
                FROM AthleteGames ag, Athlete a
                WHERE ag.athlete_id = ?;";
        case 3:
            return "SELECT a.dbp_label 
                FROM Athlete a, AthleteOlympicSportFields af, OlympicSportField f 
                WHERE  a.athlete_id = af.athlete_id AND
                af.field_id = ?
                ORDER BY RAND()
                LIMIT 1";
        case 4:
            return "SELECT COUNT(*) AS cnt
                FROM  AthleteMedals am
                WHERE am.athlete_id = ?
                AND am.medal_color = ?";
        case 5:
            return "SELECT a.dbp_label
                    FROM Athlete a, (SELECT temp.medal_count AS medal_count, temp.athlete_id AS athlete_id
                            FROM (SELECT COUNT(*) AS medal_count, am.athlete_id
                            FROM AthleteMedals am
                            WHERE am.game_id = ?
                            GROUP BY(am.athlete_id)) AS temp
                            WHERE temp.medal_count = (SELECT MAX(medal_count) 
                                                      FROM (SELECT COUNT(*) AS medal_count, am.athlete_id
                                                            FROM AthleteMedals am
                                                            WHERE am.game_id = ?
                                                            GROUP BY(am.athlete_id)) AS temp)
                            ORDER BY RAND()
                            LIMIT 1) AS maxAthleteForGame
                    Where a.athlete_id =  maxAthleteForGame.athlete_id;";
        case 6:
            return "SELECT DISTINCT c.competition_name
                    FROM CompetitionType c, AthleteMedals am 
                    WHERE am.athlete_id = ? 
                    AND am.competition_id = c.competition_id
                    LIMIT 1;";
    }
}

function get_wrong_answer_sql_query_format($q_type){
    switch ($q_type) {
        case 1:
            return "SELECT city AS wrong_answer
                FROM OlympicGame
                WHERE (game_id != ?) AND 
                    city != '' AND 
                    city != ? /* a city can appear twice */
                LIMIT 3";
        case 2:
            return "select DISTINCT count(athlete_id) AS wrong_answer
                FROM (SELECT game_id, athlete_id
                    FROM AthleteGames) as temp
                group by athlete_id
                HAVING count(athlete_id) != ?
                LIMIT 3";
        case 3:
            return "SELECT a.dbp_label AS wrong_answer 
                FROM Athlete a
                WHERE  a.dbp_label not in (SELECT a1.dbp_label FROM 
                Athlete a1, AthleteOlympicSportFields af, OlympicSportField f 
                WHERE a1.athlete_id = af.athlete_id AND
                af.field_id = ?)
                ORDER BY RAND()
                LIMIT 3";
        case 4:
            return "SELECT DISTINCT COUNT(*) AS wrong_answer
                FROM AthleteMedals am
                WHERE am.medal_color = ?
                GROUP BY(am.athlete_id)
                HAVING COUNT(*) != ?
                ORDER BY RAND()
                LIMIT 3";
        case 5:
            return "SELECT a.dbp_label AS wrong_answer
                FROM Athlete a
                WHERE a.athlete_id not in (SELECT temp.athlete_id AS athlete_id
                                            FROM (SELECT COUNT(*) AS medal_count, am.athlete_id
                                            FROM AthleteMedals am
                                            WHERE am.game_id = ?
                                            GROUP BY(am.athlete_id)) AS temp
                                            WHERE temp.medal_count = (SELECT MAX(medal_count) 
                                                                      FROM (SELECT COUNT(*) AS medal_count, am.athlete_id
                                                                            FROM AthleteMedals am
                                                                            WHERE am.game_id = ?
                                                                            GROUP BY(am.athlete_id)) AS temp)) 
                ORDER BY RAND()
                LIMIT 3";
        case 6:
            return "SELECT DISTINCT c.competition_name AS wrong_answer
                    FROM CompetitionType c
                    WHERE c.competition_id NOT IN (SELECT DISTINCT c.competition_id
                                                    FROM CompetitionType c, AthleteMedals am 
                                                    WHERE am.athlete_id = ?
                                                    AND am.competition_id = c.competition_id)
                    ORDER BY RAND()
                    LIMIT 3;";
    }
}

function get_correct_answer($q_type, $args_row){
    $correct_answer_sql_query_format = get_correct_answer_sql_query_format($q_type);
    $stmt = prepare_stmt($correct_answer_sql_query_format);

    switch ($q_type) {
        case 1:
            $game_id = $args_row['id'];
            if (!$stmt->bind_param("i", $game_id)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            execute_sql_statement($stmt);
            $row = bind_result_array($stmt);
            $stmt->fetch();
            $correct_answer = $row['city'];
            $stmt->close();
            return $correct_answer;
        case 2:
            $athlete_id = $args_row['id'];
            if (!$stmt->bind_param("i", $athlete_id)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            execute_sql_statement($stmt);
            $row = bind_result_array($stmt);
            $stmt->fetch();
            $correct_answer = $row['num_games'];
            $stmt->close();
            return $correct_answer;
        case 3:
            $field_id = $args_row['id'];
            if (!$stmt->bind_param("i", $field_id)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            execute_sql_statement($stmt);
            $row = bind_result_array($stmt);
            $stmt->fetch();
            $correct_answer = $row['dbp_label'];
            $stmt->close();
            return $correct_answer;
        case 4:
            $athlete_id = $args_row['id'];
            $medal_color = $args_row['medal_color'];
            if (!$stmt->bind_param("is", $athlete_id, $medal_color)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            execute_sql_statement($stmt);
            $row = bind_result_array($stmt);
            $stmt->fetch();
            $correct_answer = $row['cnt'];
            $stmt->close();
            return $correct_answer;
        case 5:
            $game_id = $args_row['id'];
            if (!$stmt->bind_param("ii", $game_id, $game_id)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            execute_sql_statement($stmt);
            $row = bind_result_array($stmt);
            $stmt->fetch();
            $correct_answer = $row['dbp_label'];
            $stmt->close();
            return $correct_answer;
        case 6:
            $athlete_id = $args_row['id'];
            if (!$stmt->bind_param("i", $athlete_id)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            execute_sql_statement($stmt);
            $row = bind_result_array($stmt);
            $stmt->fetch();
            $correct_answer = $row['competition_name'];
            $stmt->close();
            return $correct_answer;
    }
}

function get_wrong_answers_arr($q_type, $args_row, $correct_answer){
    $wrong_answer_sql_query_format = get_wrong_answer_sql_query_format($q_type);
    $stmt = prepare_stmt($wrong_answer_sql_query_format);

    $answer_array = array();
    switch ($q_type) {
        case 1:
            $game_id = $args_row['id'];
            if (!$stmt->bind_param("is", $game_id, $correct_answer)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            break;
        case 2:
            if (!$stmt->bind_param("i", $correct_answer)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            break;
        case 3:
            $field_id = $args_row['id'];
            if (!$stmt->bind_param("i", $field_id)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            break;
        case 4:
            $medal_color = $args_row['medal_color'];
            if (!$stmt->bind_param("si", $medal_color, $correct_answer)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            break;
        case 5:
            $game_id = $args_row['id'];
            if (!$stmt->bind_param("ii", $game_id, $game_id)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            break;
        case 6:
            $athlete_id = $args_row['id'];
            if (!$stmt->bind_param("i", $athlete_id)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            break;
    }

    execute_sql_statement($stmt);
    $row = bind_result_array($stmt);
    while ($stmt->fetch()) {
        $wrong_answer_str = explode("(", $row['wrong_answer'], 2)[0];
        array_push($answer_array, $wrong_answer_str);
    }
    $stmt->close();
    return $answer_array;
}

function add_type_x_questions_with_answers(&$questions_array, $q_type, $num_questions){
    // get sql query for getting the q_type blank filling for num_questions
    $sql_args_stmt = get_questions_args_sql_query($q_type, $num_questions);
    // run the query for getting the questions args
    execute_sql_statement($sql_args_stmt);
    if ($sql_args_stmt->num_rows < $num_questions){
        http_response_code(500);
        die(sprintf('ERROR: Not enough questions for question type %d', $q_type));
    }

    $id = null;
    $arg1 = null;
    $arg2 = null;
    $args_row = bind_result_array($sql_args_stmt);
    while ($sql_args_stmt->fetch()){
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
        $image_url = get_image_url_q_type($q_type, $args_row, $correct_answer);
        $question_dict["more_info"] = $info;
        $question_dict["more_info_title"] = $info_title;
        $question_dict["image_url"] = $image_url;
        // put the correct answer in the answer array in a random place
        $place = mt_rand(0, 3);
        $correct_answer_str = explode("(", $correct_answer, 2)[0];
        array_splice($answer_array, $place, 0, $correct_answer_str);
        $question_dict["options"] = $answer_array;
        $question_dict["answer"] = $place;

        // put the dict in the question array
        array_push($questions_array, $question_dict);
    }
    $sql_args_stmt->close();
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




