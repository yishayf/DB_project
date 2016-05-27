<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

$db = new mysqli("localhost", 'root', '', 'db_project_test');


// return a question for type 1:
$sql = <<<SQL
    SELECT question_format
    FROM `QuestionFormats`
    WHERE `table_name` = 'Question_type1'
SQL;

if(!$result = $db->query($sql)){
    die('There was an error running the query [' . $db->error . ']');
}

while($row = $result->fetch_assoc()){
    $question = $row['question_format'];
}
echo $question . "kkk";

$sql = <<<SQL
    SELECT year, season
    FROM `Question_type1`
    ORDER BY RAND()
    LIMIT 1
SQL;

if(!$result = $db->query($sql)){
    die('There was an error running the query [' . $db->error . ']');
}

while($row = $result->fetch_assoc()){
    echo $row['year'] . $row['season'];
}



$db->close();

?>

//http://php.net/manual/en/function.sprintf.php