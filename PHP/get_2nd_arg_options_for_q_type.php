<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

require_once 'mysql_general.php';

function get_sql_query_for_args_by_q_type($q_type, $arg1){
    switch ($q_type){
        case 1:
            $query_format = "SELECT valid.season
                FROM (SELECT year, season from OlympicGame WHERE
                concat(year, season) not in (select concat(year, season) from Question_type1)) AS valid
                WHERE valid.year = %d";
            $query = sprintf($query_format, $arg1);
        case 2:
            break;
        case 3;
            break;
        case 4;
            break;
        case 5;
    }
    return $query;
}


function get_2nd_arg_options_by_q_type($q_type, $arg1){
    $sql_query = get_sql_query_for_args_by_q_type($q_type, $arg1);
    $result = run_sql_select_query($sql_query);
    $res_array = array();
    switch ($q_type){
        case 1:
            while ($row = $result->fetch_assoc()) {
                array_push($res_array, $row['season']);
            }
    }
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

?>