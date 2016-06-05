<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

require_once 'mysql_general.php';


function get_update_stats_sql_query($q_type, $is_correct, $arg1, $arg2, $id){
    global $db;
    $column = $is_correct ? 'num_correct' : 'num_wrong';

    switch ($q_type){
        case 1:
            $format = sprintf("UPDATE Question_type1 SET %s = %s + 1 WHERE game_id = ?", $column, $column);
            $stmt = $db->prepare($format);
            if (!$stmt->bind_param("i", $id)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            break;
        case 2:
            $format = sprintf("UPDATE Question_type2 SET %s = %s + 1 WHERE athlete_id = ?", $column, $column);
            $stmt = $db->prepare($format);
            if (!$stmt->bind_param("i", $id)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            break;
        case 3:
            $format = sprintf("UPDATE Question_type3 SET %s = %s + 1 WHERE field_id = ?", $column, $column);
            $stmt = $db->prepare($format);
            if (!$stmt->bind_param("i", $id)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            break;
        case 4:
            $format = sprintf("UPDATE Question_type4 SET %s = %s + 1 WHERE medal_color = ? AND athlete_id = ?", $column, $column);
            $stmt = $db->prepare($format);
            if (!$stmt->bind_param("si", $arg1, $id)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            break;
        case 5:
            $format = sprintf("UPDATE Question_type5 SET %s = %s + 1 WHERE game_id = ?", $column, $column);
            $stmt = $db->prepare($format);
            if (!$stmt->bind_param("i", $id)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            break;
        case 6:
            $format = sprintf("UPDATE Question_type6 SET %s = %s + 1 WHERE athlete_id = ?", $column, $column);
            $stmt = $db->prepare($format);
            if (!$stmt->bind_param("i", $id)) {
                http_response_code(500);
                die("Error: Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
            }
            break;
    }
    return $stmt;
}

function update_stats($stats){
    foreach ($stats as &$q_info) {
        $q_type = $q_info["q_type"];
        $arg1 = $q_info["arg1"];
        $arg2 = $q_info["arg2"];
        $id = $q_info["id"];
        $is_correct = $q_info["correct"];
        $stmt = get_update_stats_sql_query($q_type, $is_correct, $arg1, $arg2, $id);
        execute_sql_update_statement($stmt);
    }
}

// TODO: change error codes
if ($_SERVER["REQUEST_METHOD"] == "POST"){

    if (!empty($_POST["stats"])) {
        $stats = $_POST["stats"];//json_decode($_POST["stats"], true); //
        update_stats ($stats);
    }
}

// close database connection
$db->close();

?>