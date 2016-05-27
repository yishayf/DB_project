<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

function insert_question_type(&$question_types, $table_name, $q_format, $num_args){
    $q_dict = array(
        "table" => $table_name,
        "question_format" => $q_format,
        "num_args" => $num_args
    );
    array_push($question_types, $q_dict);
}

$question_types = array();

insert_question_type($question_types, "Question_type1", "Where did the (YEAR) (SEASON) Olympic games take place?", 2);
insert_question_type($question_types, "Question_type2", "Which athlete won a (COLOR) Olympic medal?", 1);
insert_question_type($question_types, "Question_type3", "How old is (athlete)?", 1);

echo json_encode($question_types);
?>