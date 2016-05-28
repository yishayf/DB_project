<?php
require_once 'mysql_general.php';

function run_sql_select_query($sql_query){
    global $db;
    if(!$result = $db->query($sql_query)){
        die('There was an error running the query [' . $db->error . ']');
    }
    return $result;
}

?>