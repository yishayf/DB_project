<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

$db = new mysqli("localhost", 'root', '', 'db_project_test');
//$db = new mysqli('mysqlsrv.cs.tau.ac.il', 'DbMysql08', 'DbMysql08', 'DbMysql08');  # for nova

$sql_query = "
        SELECT DISTINCT year
        FROM OlympicGame o
        WHERE  o.City != ''" ; // TODO: should be != null

if(!$result = $db->query($sql_query)){
    die('There was an error running the query [' . $db->error . ']');
}

$years_arr = array();
while ($row = $result->fetch_assoc()) {
    array_push($years_arr, $row['year']);
}

echo json_encode($years_arr);

$db->close();

?>