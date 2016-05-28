<?php

//$db = new mysqli("192.168.14.37", 'DbMysql08', 'DbMysql08', 'db_project_test');
$db = new mysqli('localhost', 'DbMysql08', 'DbMysql08', 'DbMysql08', 3305);  # for nova
// TODO: handle problem connecting to mysql!!!!

function run_sql_select_query($sql_query){
    global $db;
    if(!$result = $db->query($sql_query)){
        die('There was an error running the query [' . $db->error . ']');
    }
    return $result;
}
?>