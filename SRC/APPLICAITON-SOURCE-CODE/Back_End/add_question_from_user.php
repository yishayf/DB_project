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


function get_insert_query_by_q_type($q_type, $arg1, $arg2){
    switch ($q_type){
        case 1:
            $stmt = prepare_stmt("INSERT INTO Question_type1 (game_id)
                      SELECT game_id FROM OlympicGame WHERE year = ? AND season = ?;");
            if (!$stmt->bind_param("is", $arg1, $arg2)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            break;
        case 2:
            $stmt = prepare_stmt("INSERT INTO Question_type2 (athlete_id)  
                      SELECT athlete_id FROM Athlete WHERE dbp_label  = ?;");
            if (!$stmt->bind_param("s", $arg1)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            break;
        case 3:
            $stmt = prepare_stmt("INSERT INTO Question_type3 (field_id)
                      SELECT field_id FROM OlympicSportField WHERE field_name = ?;");
            if (!$stmt->bind_param("s", $arg1)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            break;
        case 4:
            $stmt = prepare_stmt("INSERT INTO Question_type4 (athlete_id, medal_color) 
                      SELECT a.athlete_id, ?
                      FROM Athlete a
                      WHERE a.dbp_label = ?;");
            if (!$stmt->bind_param("ss", $arg1, $arg2)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            break;
        case 5:
            $stmt = prepare_stmt("INSERT INTO Question_type5 (game_id)
                      SELECT game_id FROM OlympicGame WHERE year = ? AND season = ?;");
            if (!$stmt->bind_param("is", $arg1, $arg2)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            break;
        case 6:
            $stmt = prepare_stmt("INSERT INTO Question_type6 (athlete_id)  
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
    execute_sql_insert_or_update_statement($stmt);
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