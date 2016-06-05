<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

require_once 'mysql_general.php';

$options_limit = 1000;

function get_sql_query_for_args_by_q_type($q_type, $arg1){
    global $options_limit;
    switch ($q_type){
        case 1:
            $query_format = "SELECT season AS opt
                FROM  OlympicGame og
                WHERE og.year = %d AND og.game_id NOT IN (SELECT game_id FROM Question_type1);";

            $query = sprintf($query_format, $arg1);
            break;
        case 4:
            $query_format = "SELECT a.dbp_label AS opt
                FROM Athlete a, (SELECT DISTINCT medal_color FROM AthleteMedals) as colors
                WHERE colors.medal_color = '%s'
                AND concat(colors.medal_color, a.athlete_id) not in (select concat(medal_color, athlete_id) 
                                                                                FROM Question_type4)
                ORDER BY RAND()
                LIMIT %d";
            $query = sprintf($query_format, $arg1, $options_limit);
            break;
        case 5:
            $query_format = "SELECT season AS opt
                FROM  OlympicGame og
                WHERE og.year = %d AND og.game_id NOT IN (SELECT game_id FROM Question_type5);";
            $query = sprintf($query_format, $arg1);
            break;
    }
    return $query;
}


function get_2nd_arg_options_by_q_type($q_type, $arg1){
    $sql_query = get_sql_query_for_args_by_q_type($q_type, $arg1);
    $result = run_sql_select_query($sql_query);
    $res_array = array();
    while ($row = $result->fetch_assoc()) {
        array_push($res_array, $row['opt']);
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

// close database connection
$db->close();


?>