<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

require_once 'mysql_general.php';

function get_sql_query_for_args_by_q_type($q_type){
    switch ($q_type){
        case 1:
            return "SELECT DISTINCT a.year 
                FROM OlympicGame
                WHERE city != ''; "; //TODO: change to != null ?
        case 2:
            break;
        case 3;
            break;
        case 4;
            break;
        case 5;
    }
}


function get_1st_arg_options_by_q_type($q_type){
    switch ($q_type){
        case 1:
            break;
        case 2:
            break;
        case 3;
            break;
        case 4;
            break;
        case 5;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "GET"){
    $q_type = $_GET["q_type"];
    $options = get_1st_arg_options_by_q_type($q_type);
    echo json_encode($options);
}
?>