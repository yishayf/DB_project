<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

// open db connection
//$db = new mysqli('localhost', 'DbMysql08', 'DbMysql08', 'DbMysql08', 3305);  # for nova
$db = new mysqli("localhost", 'root', '', 'db_project_test');
if ($db->connect_errno) {
    http_response_code(500);
    echo "Error: Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
}
$db->set_charset('utf8');



function execute_sql_statement($stmt){
    global $db;
    if(!$stmt->execute()){
        $stmt->close();
        http_response_code(500);
        die('There was an error running the query [' . $db->error . ']');
    }
    $result = $stmt->get_result();
    $stmt->close();
    return $result;
}


function execute_sql_update_statement($stmt){
    echo "in update";
    global $db;
    if(!$stmt->execute()){
        echo "problem";
        $stmt->close();
        http_response_code(500);
        die('There was an error running the query [' . $db->error . ']');
    }
    echo "ran statement";
    $stmt->close();
    return TRUE;
}

function execute_sql_insert_statement($stmt){
    global $db;
    if(!$stmt->execute()){
        $stmt->close();
        echo nl2br($db->errno."\r\n");
        echo nl2br('There was an error running the query [' . $db->error . ']')."\r\n";
        http_response_code(500);
        die('There was an error running the query [' . $db->error . ']');
    }
    $stmt->close();
    return TRUE;
}



//function run_sql_select_query($sql_query){
//    global $db;
//    if(!$result = $db->query($sql_query)){
//        http_response_code(500);
//        die('There was an error running the query [' . $db->error . ']');
//    }
//    return $result;
//}
//
//// TODO: code duplication
//function run_sql_update_query($sql_query){
//    global $db;
//    if($db->query($sql_query) === TRUE){
//        return true;
//    }
//    else {
//        echo nl2br($db->errno."\r\n");
//        echo nl2br('There was an error running the query [' . $db->error . ']')."\r\n";
//        return false;
//    }
//}

?>

