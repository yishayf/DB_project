<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

require_once 'mysql_general.php';


function get_insert_query_by_q_type($q_type){
    switch ($q_type){
        case 1:
//            return "INSERT INTO Question_type%d (year, season) VALUES (%d, '%s')";
            return "INSERT INTO Question_type%d (game_id)
                      SELECT game_id FROM OlympicGame WHERE year = %d AND season = '%s';";
        case 2:
            return "INSERT INTO Question_type%d (athlete_id)  
                      SELECT athlete_id FROM Athlete WHERE dbp_label  = '%s';";
        case 3:
//            return "INSERT INTO Question_type%d (field_name) VALUES ('%s')";
            return "INSERT INTO Question_type%d (field_id)
                      SELECT field_id FROM OlympicSportField WHERE field_name = '%s';";
        case 4:
//            return "INSERT INTO Question_type%d (medal_color, dbp_label) VALUES ('%s', '%s')";
            return "INSERT INTO Question_type%d (athlete_id, medal_color) 
                      SELECT a.athlete_id, '%s'
                      FROM Athlete a
                      WHERE a.dbp_label = '%s';";
        case 5:
//            return "INSERT INTO Question_type%d (year, season) VALUES (%d, '%s')";
            return "INSERT INTO Question_type%d (game_id)
                      SELECT game_id FROM OlympicGame WHERE year = %d AND season = '%s';";
        case 6:
            return "INSERT INTO Question_type%d (athlete_id)  
                      SELECT athlete_id FROM Athlete WHERE dbp_label  = '%s';";
    }
}


function add_question_by_type($q_type, $arg1, $arg2=null){
    $insert_query_format = get_insert_query_by_q_type($q_type);
    $insert_query = sprintf($insert_query_format, $q_type, $arg1, $arg2);
    return run_sql_insert_query($insert_query);
}


// TODO: change error codes
if ($_SERVER["REQUEST_METHOD"] == "POST"){

    if (!empty($_POST["q_type"]) && !empty($_POST["num_args"])) {
        $q_type = $_POST["q_type"];
        $num_args = $_POST["num_args"];

        if ($num_args == 1 && !empty($_POST["arg1"])){
            $arg1 = $_POST["arg1"];
            $result = add_question_by_type($q_type, $arg1);
        }
        else if ($num_args == 2 && !empty($_POST["arg1"]) && !empty($_POST["arg2"])){
            $arg1 = $_POST["arg1"];
            $arg2 = $_POST["arg2"];
            echo $arg1;
            $result = add_question_by_type($q_type, $arg1, $arg2);
        }
        else {
            http_response_code(400);
            die("Error: invalid arguments");
        }

        if ($result){
            http_response_code(200);
        }
        else{
            // problem running query
            http_response_code(500);
            die("Error: error running query");
        }
    }
    else {
        http_response_code(400);
        die("Error: invalid arguments");
    }
}

// close database connection
$db->close();

?>