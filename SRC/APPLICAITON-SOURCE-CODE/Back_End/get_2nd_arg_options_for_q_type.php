<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

require_once 'mysql_general.php';

$options_limit = 1000;

function get_sql_query_for_args_by_q_type($q_type, $arg1){
    global $db;
    global $options_limit;
    switch ($q_type){
        case 1:
            $stmt = $db->prepare("SELECT season AS opt
                FROM  OlympicGame og
                WHERE og.year = ? AND og.game_id NOT IN (SELECT game_id FROM Question_type1);");
            if (!$stmt->bind_param("i", $arg1)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            break;
        case 4:
            $stmt = $db->prepare("SELECT a.dbp_label AS opt
                FROM Athlete a, (SELECT DISTINCT medal_color FROM AthleteMedals) as colors
                WHERE colors.medal_color = ?
                AND concat(colors.medal_color, a.athlete_id) not in (select concat(medal_color, athlete_id) 
                                                                                FROM Question_type4)
                ORDER BY RAND()
                LIMIT ?");
            if (!$stmt->bind_param("si", $arg1, $options_limit)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            break;
        case 5:
            $stmt = $db->prepare("SELECT season AS opt
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
    $stmt = get_sql_query_for_args_by_q_type($q_type, $arg1);
    $result = execute_sql_statement($stmt);
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