<?php

//TODO: add foreign keys to question types and make all tables unique
// tODO: from table 2 remove 
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

function insert_question_type(&$question_types, $q_type, $q_format, $num_args, $args){
    $q_dict = array(
        'q_type' => $q_type,
        'question_format' => $q_format,
        'num_args' => $num_args,
        'args' => $args
    );
    array_push($question_types, $q_dict);
}

$question_types = array();

insert_question_type($question_types, 1, "Where did the [YEAR] [SEASON] Olympic games take place?", 2, array('year', 'season'));
insert_question_type($question_types, 2, "How many Olympic games did [ATHLETE] participate in?", 1, array('name'));
insert_question_type($question_types, 3, "Which of the following was part of the [SPORT FIELD] competitors at the Olympic games?", 1, array('field_name'));

echo json_encode($question_types);
?>