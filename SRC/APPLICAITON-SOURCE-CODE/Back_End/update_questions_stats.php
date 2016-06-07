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

function get_update_stats_sql_query($q_type, $is_correct, $arg1, $arg2, $id){
    $column = $is_correct ? 'num_correct' : 'num_wrong';

    switch ($q_type){
        case 1:
            $format = sprintf("UPDATE Question_type1 SET %s = %s + 1 WHERE game_id = ?", $column, $column);
            $stmt = prepare_stmt($format);
            if (!$stmt->bind_param("i", $id)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            break;
        case 2:
            $format = sprintf("UPDATE Question_type2 SET %s = %s + 1 WHERE athlete_id = ?", $column, $column);
            $stmt = prepare_stmt($format);
            if (!$stmt->bind_param("i", $id)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            break;
        case 3:
            $format = sprintf("UPDATE Question_type3 SET %s = %s + 1 WHERE field_id = ?", $column, $column);
            $stmt = prepare_stmt($format);
            if (!$stmt->bind_param("i", $id)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            break;
        case 4:
            $format = sprintf("UPDATE Question_type4 SET %s = %s + 1 WHERE medal_color = ? AND athlete_id = ?", $column, $column);
            $stmt = prepare_stmt($format);
            if (!$stmt->bind_param("si", $arg1, $id)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            break;
        case 5:
            $format = sprintf("UPDATE Question_type5 SET %s = %s + 1 WHERE game_id = ?", $column, $column);
            $stmt = prepare_stmt($format);
            if (!$stmt->bind_param("i", $id)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            break;
        case 6:
            $format = sprintf("UPDATE Question_type6 SET %s = %s + 1 WHERE athlete_id = ?", $column, $column);
            $stmt = prepare_stmt($format);
            if (!$stmt->bind_param("i", $id)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            break;
    }
    return $stmt;
}

function update_stats($stats){
    foreach ($stats as &$q_info) {
        $q_type = $q_info["q_type"];
        $arg1 = $q_info["arg1"];
        $arg2 = $q_info["arg2"];
        $id = $q_info["id"];
        $is_correct = $q_info["correct"];
        $stmt = get_update_stats_sql_query($q_type, $is_correct, $arg1, $arg2, $id);
        execute_sql_insert_or_update_statement($stmt);
        $stmt->close();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST"){

    if (!empty($_POST["stats"])) {
        $stats = $_POST["stats"];//json_decode($_POST["stats"], true); //
        update_stats ($stats);
    }
}

// close database connection
$db->close();

?>