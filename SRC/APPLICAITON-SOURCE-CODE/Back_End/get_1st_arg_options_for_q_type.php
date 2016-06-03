<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

require_once 'mysql_general.php';

$options_limit = 100;

function get_sql_query_for_args_by_q_type($q_type){
    global $options_limit;
    switch ($q_type){
        case 1:
            $format = "SELECT DISTINCT og.year AS opt
                FROM OlympicGame og, (SELECT year, season from OlympicGame WHERE
                concat(year, season) not in (select concat(year, season) from Question_type1)) AS valid 
                WHERE og.year = valid.year AND og.City != ''";
            break;
        case 2:
            $format = "SELECT dbp_label AS opt 
                FROM Athlete
                WHERE dbp_label not in (SELECT dbp_label FROM Question_type2)
                ORDER BY RAND()
                LIMIT %d;";
            break;
        case 3;
            $format = "SELECT field_name AS opt
                FROM OlympicSportField
                WHERE field_name NOT IN (SELECT field_name FROM Question_type3);";
            break;
        case 4;
            $format = "SELECT DISTINCT medal_color AS opt
                FROM AthleteMedals;";
            break;
        case 5:
            $format = "SELECT DISTINCT og.year AS opt
                FROM OlympicGame og, (SELECT year, season from OlympicGame WHERE
                concat(year, season) not in (select concat(year, season) from Question_type5)) AS valid 
                WHERE og.year = valid.year;";
            break;
    }
    return sprintf($format, $options_limit);
}

function get_1st_arg_options_by_q_type($q_type){
    $sql_query = get_sql_query_for_args_by_q_type($q_type);
    $result = run_sql_select_query($sql_query);

    $res_array = array();
    while ($row = $result->fetch_assoc()) {
        array_push($res_array, $row['opt']);
    }

//    switch ($q_type){
//        case 1:
//            while ($row = $result->fetch_assoc()) {
//                array_push($res_array, $row['year']);
//            }
//            break;
//        case 2:
//            while ($row = $result->fetch_assoc()) {
//                array_push($res_array, $row['dbp_label']);
//            }
//            break;
//        case 3:
//            while ($row = $result->fetch_assoc()) {
//                array_push($res_array, $row['field_name']);
//            }
//            break;
//        case 4:
//            while ($row = $result->fetch_assoc()) {
//                array_push($res_array, $row['medal_color']);
//            }
//            break;
//        case 5:
//            while ($row = $result->fetch_assoc()) {
//                array_push($res_array, $row['year']);
//            }
//            break;
//    }
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