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

$options_limit = 1000;

function get_sql_query_for_args_by_q_type($q_type, $arg1){
    global $options_limit;
    switch ($q_type){
        case 1:
            $stmt = prepare_stmt("SELECT season AS opt
                FROM  OlympicGame og
                WHERE og.year = ? AND 
                og.city != '' AND 
                og.game_id NOT IN (SELECT game_id FROM Question_type1);");
            if (!$stmt->bind_param("i", $arg1)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            break;
        case 4:
            $stmt = prepare_stmt("SELECT a.dbp_label AS opt
                FROM Athlete a, (SELECT DISTINCT medal_color FROM AthleteMedals) as colors
                WHERE colors.medal_color = ?
                AND concat(colors.medal_color, a.athlete_id) not in (select concat(medal_color, athlete_id) 
                                                                                FROM Question_type4)  
                ORDER BY a.dbp_label ASC;
                ");
//                ORDER BY RAND()
//                LIMIT ?");
//            if (!$stmt->bind_param("si", $arg1, $options_limit)) {
            if (!$stmt->bind_param("s", $arg1)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            break;
        case 5:
            $stmt = prepare_stmt("SELECT season AS opt
                FROM  OlympicGame og
                WHERE og.year = ? AND og.game_id NOT IN (SELECT game_id FROM Question_type5);");
            if (!$stmt->bind_param("i", $arg1)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            break;
    }
    return $stmt;
}


function get_2nd_arg_options_by_q_type($q_type, $arg1){
    $sql_stmt = get_sql_query_for_args_by_q_type($q_type, $arg1);
    execute_sql_statement($sql_stmt);
    $row = bind_result_array($sql_stmt);

    $res_array = array();
    while ($sql_stmt->fetch()) {
        array_push($res_array, $row['opt']);
    }
    $sql_stmt->close();
    return $res_array;
}

if ($_SERVER["REQUEST_METHOD"] == "GET"){
    if (!empty($_GET["q_type"]) && !empty($_GET['arg1'])) {
        $q_type = $_GET["q_type"];
        $arg1 = $_GET['arg1'];
        $options_arr = get_2nd_arg_options_by_q_type($q_type, $arg1);
        echo json_encode($options_arr);
    }
}

// close database connection
$db->close();


?>