<?php
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
?>