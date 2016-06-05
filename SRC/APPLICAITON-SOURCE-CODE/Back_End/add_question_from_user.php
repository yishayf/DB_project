<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

require_once 'mysql_general.php';


function get_insert_query_by_q_type($q_type, $arg1, $arg2){
    global $db;
    switch ($q_type){
        case 1:
            $stmt = $db->prepare("INSERT INTO Question_type1 (game_id)
                      SELECT game_id FROM OlympicGame WHERE year = ? AND season = ?;");
            if (!$stmt->bind_param("is", $arg1, $arg2)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            break;
        case 2:
            $stmt = $db->prepare("INSERT INTO Question_type2 (athlete_id)  
                      SELECT athlete_id FROM Athlete WHERE dbp_label  = ?;");
            if (!$stmt->bind_param("s", $arg1)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            break;
        case 3:
            $stmt = $db->prepare("INSERT INTO Question_type3 (field_id)
                      SELECT field_id FROM OlympicSportField WHERE field_name = ?;");
            if (!$stmt->bind_param("s", $arg1)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            break;
        case 4:
            $stmt = $db->prepare("INSERT INTO Question_type4 (athlete_id, medal_color) 
                      SELECT a.athlete_id, ?
                      FROM Athlete a
                      WHERE a.dbp_label = ?;");
            if (!$stmt->bind_param("ss", $arg1, $arg2)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            break;
        case 5:
            $stmt = $db->prepare("INSERT INTO Question_type5 (game_id)
                      SELECT game_id FROM OlympicGame WHERE year = ? AND season = ?;");
            if (!$stmt->bind_param("is", $arg1, $arg2)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            break;
        case 6:
            $stmt = $db->prepare("INSERT INTO Question_type6 (athlete_id)  
                      SELECT athlete_id FROM Athlete WHERE dbp_label = ?;");
            if (!$stmt->bind_param("s", $arg1)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            break;
    }
    return $stmt;
}


function add_question_by_type($q_type, $arg1, $arg2=null){
    $stmt = get_insert_query_by_q_type($q_type, $arg1, $arg2);
    execute_sql_insert_statement($stmt);
    $stmt->close();
}


if ($_SERVER["REQUEST_METHOD"] == "POST"){

    if (!empty($_POST["q_type"]) && !empty($_POST["num_args"])) {
        $q_type = $_POST["q_type"];
        $num_args = $_POST["num_args"];

        if ($num_args == 1 && !empty($_POST["arg1"])){
            $arg1 = $_POST["arg1"];
            add_question_by_type($q_type, $arg1);
        }
        else if ($num_args == 2 && !empty($_POST["arg1"]) && !empty($_POST["arg2"])){
            $arg1 = $_POST["arg1"];
            $arg2 = $_POST["arg2"];
            echo $arg1;
            add_question_by_type($q_type, $arg1, $arg2);
        }
        else {
            http_response_code(400);
            die("Error: invalid arguments");
        }
    }
    else {
        http_response_code(400);
        die("Error: invalid arguments");
    }
}

// close database connection
$db->close();

?>