<?php

$db = new mysqli("localhost", 'root', '', 'db_project_test');
//$db = new mysqli('mysqlsrv.cs.tau.ac.il', 'DbMysql08', 'DbMysql08', 'DbMysql08');  # for nova
// TODO: handle problem connecting to mysql!!!!

function run_sql_select_query($sql_query){
    global $db;
    if(!$result = $db->query($sql_query)){
        die('There was an error running the query [' . $db->error . ']');
    }
    return $result;
}

?>