<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

// open db connection
$db = new mysqli("localhost", 'root', '', 'db_project_test');
//$db = new mysqli('mysqlsrv.cs.tau.ac.il', 'DbMysql08', 'DbMysql08', 'DbMysql08');  # for nova
//$db = new mysqli('localhost', 'DbMysql08', 'DbMysql08', 'DbMysql08', 3305);  # for nova local

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    die("Connection failed: " . $conn->connect_error);
}
$db->set_charset('utf8');


function run_sql_select_query($sql_query){
    global $db;
    if(!$result = $db->query($sql_query)){
        http_response_code(500);
        die('There was an error running the query [' . $db->error . ']');
    }
    return $result;
}

function run_sql_insert_query($sql_query){
    global $db;
    if($db->query($sql_query) === TRUE){
        return true;
    }
    else {
        http_response_code(500);
        die('There was an error running the query [' . $db->error . ']');
    }
}

function run_sql_update_query($sql_query){
    global $db;
    if($db->query($sql_query) === TRUE){
        return true;
    }
    else {
        http_response_code(500);
        die('There was an error running the query [' . $db->error . ']');
    }
}

?>

