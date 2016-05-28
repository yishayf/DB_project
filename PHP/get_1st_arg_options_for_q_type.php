<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

require_once 'mysql_general.php';

function get_sql_query_for_args_by_q_type($q_type){
    switch ($q_type){
        case 1:
            return "SELECT DISTINCT og.year FROM 
                OlympicGame og, (SELECT year, season from OlympicGame WHERE
                concat(year, season) not in (select concat(year, season) from Question_type1)) AS valid 
                WHERE og.year = valid.year AND og.City != '';";
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
    $sql_query = get_sql_query_for_args_by_q_type($q_type);
    $result = run_sql_select_query($sql_query);
    $res_array = array();
    switch ($q_type){
        case 1:
            while ($row = $result->fetch_assoc()) {
                array_push($res_array, $row['year']);
            }
    }
    return $res_array;
}

if ($_SERVER["REQUEST_METHOD"] == "GET"){
    if (!empty($_GET["q_type"])) {
        $q_type = $_GET["q_type"];
        $options_arr = get_1st_arg_options_by_q_type($q_type);
        echo json_encode($options_arr);
    }
}

?>