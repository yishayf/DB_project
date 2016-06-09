<?php
///////////////////////// General code ///////////////////////////////////////////////
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

require_once 'mysql_general.php';
/////////////////////////////////////////////////////////////////////////////////////

$options_limit = 100;

function get_sql_query_for_args_by_q_type($q_type){
    global $options_limit;
    switch ($q_type){
        case 1:
            $stmt = prepare_stmt("SELECT DISTINCT og.year AS opt
            FROM OlympicGame og
            WHERE og.game_id NOT IN (SELECT game_id FROM Question_type1) 
            AND og.city != '';");
            break;
        case 2:
            /* Any athlete will surely have a sport field, becaue we selected only atheltes that participated
            in some sport field in the olympic */
            $stmt = prepare_stmt("SELECT a.dbp_label AS opt
            FROM Athlete a
            WHERE a.athlete_id NOT IN (SELECT athlete_id FROM Question_type2)
            ");
//            ORDER BY RAND()
//            LIMIT ?;");
//            if (!$stmt->bind_param("i", $options_limit)) {
//                http_response_code(500);
//                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
//            }
            break;
        case 3;
            $stmt = prepare_stmt("SELECT field_name AS opt
                FROM OlympicSportField
                WHERE field_id NOT IN (SELECT field_id FROM Question_type3);");
            break;
        case 4;
            $stmt = prepare_stmt("SELECT DISTINCT medal_color AS opt
                FROM AthleteMedals;");
            break;
        case 5:
            $stmt = prepare_stmt("SELECT DISTINCT og.year AS opt
            FROM OlympicGame og
            WHERE og.game_id NOT IN (SELECT game_id FROM Question_type5)
            AND og.game_id IN (SELECT DISTINCT game_id from AthleteMedals);");
            break;
        case 6:
            $stmt = prepare_stmt("SELECT DISTINCT a.dbp_label AS opt
            FROM Athlete a, AthleteMedals am
            WHERE a.athlete_id = am.athlete_id
            AND a.athlete_id NOT IN (SELECT athlete_id FROM Question_type6)
            ORDER BY a.dbp_label ASC;
            ");
//            ORDER BY RAND()
//            LIMIT ?;");
//            if (!$stmt->bind_param("i", $options_limit)) {
//                http_response_code(500);
//                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
//            }
            break;
    }
    return $stmt;
}

function get_1st_arg_options_by_q_type($q_type){
    $sql_stmt = get_sql_query_for_args_by_q_type($q_type);
    execute_sql_statement($sql_stmt);
    $row = bind_result_array($sql_stmt);

    $res_array = array();
    while ($sql_stmt->fetch()) {
        array_push($res_array, $row['opt']);
    }
    $sql_stmt->close();
    return $res_array;
}

if ($_SERVER["REQUEST_METHOD"] == "GET"){
    if (!empty($_GET["q_type"])) {
        $q_type = $_GET["q_type"];
        $options_arr = get_1st_arg_options_by_q_type($q_type);
        echo json_encode($options_arr);
    }
}

// close database connection
$db->close();

?>