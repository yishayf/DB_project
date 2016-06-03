<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

require_once 'mysql_general.php';


function get_update_stats_sql_query($q_type, $is_correct, $arg1, $arg2){
    switch ($q_type){
        case 1:
            $format = "UPDATE Question_type%d SET %s = %s + 1 WHERE year = %s AND season = '%s'";
            break;
        case 2:
            $format = "UPDATE Question_type%d SET %s = %s + 1 WHERE dbp_label = '%s'";
            break;
        case 3:
            $format = "UPDATE Question_type%d SET %s = %s + 1 WHERE field_name = '%s'";
            break;
        case 4:
            $format = "UPDATE Question_type%d SET %s = %s + 1 WHERE medal_color = '%s' AND dbp_label = '%s'";
            break;
        case 5:
            $format = "UPDATE Question_type%d SET %s = %s + 1 WHERE year = %s AND season = '%s'";
    }

    $column = $is_correct ? 'num_correct' : 'num_wrong';
    return sprintf($format, $q_type, $column, $column, $arg1, $arg2);

}

function update_stats($stats){
    foreach ($stats as &$q_info) {
        $q_type = $q_info["q_type"];
        $arg1 = $q_info["arg1"];
        $arg2 = $q_info["arg2"];
        $is_correct = $q_info["correct"];
        $query = get_update_stats_sql_query($q_type, $is_correct, $arg1, $arg2);
        run_sql_update_query($query);
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