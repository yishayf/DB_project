<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

require_once 'mysql_general.php';


function get_update_stats_sql_query($q_type, $is_correct, $arg1, $arg2, $id){
    $column = $is_correct ? 'num_correct' : 'num_wrong';

    switch ($q_type){
        case 1:
            $format = "UPDATE Question_type%d SET %s = %s + 1 WHERE game_id = %d";
            return sprintf($format, $q_type, $column, $column, $id);
        case 2:
            $format = "UPDATE Question_type%d SET %s = %s + 1 WHERE athlete_id = %d";
            return sprintf($format, $q_type, $column, $column, $id);
        case 3:
            $format = "UPDATE Question_type%d SET %s = %s + 1 WHERE field_id = %d";
            return sprintf($format, $q_type, $column, $column, $id);
        case 4:
            $format = "UPDATE Question_type%d SET %s = %s + 1 WHERE medal_color = '%s' AND athlete_id = %d";
            return sprintf($format, $q_type, $column, $column, $arg1, $id);
        case 5:
            $format = "UPDATE Question_type%d SET %s = %s + 1 WHERE game_id = %d";
            return sprintf($format, $q_type, $column, $column, $id);
        case 6:
            $format = "UPDATE Question_type%d SET %s = %s + 1 WHERE athlete_id = %d";
            return sprintf($format, $q_type, $column, $column, $id);
    }
}

function update_stats($stats){
    foreach ($stats as &$q_info) {
        $q_type = $q_info["q_type"];
        $arg1 = $q_info["arg1"];
        $arg2 = $q_info["arg2"];
        $id = $q_info["id"];
        $is_correct = $q_info["correct"];
        $query = get_update_stats_sql_query($q_type, $is_correct, $arg1, $arg2, $id);
        run_sql_update_query($query);
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST"){

    if (!empty($_POST["stats"])) {
        $stats = $_POST["stats"];//json_decode($_POST["stats"], true); //
        update_stats ($stats);
    }
}

// close database connection
$db->close();

?>