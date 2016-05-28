<?php
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

insert_question_type($question_types, 1, "Where did the (YEAR) (SEASON) Olympic games take place?", 2,
    array('arg1' => 'year', 'arg2' => 'season'));
insert_question_type($question_types, 2, "Which athlete won a (COLOR) Olympic medal?", 1);
insert_question_type($question_types, 3, "When was (athlete) born?", 1);

echo json_encode($question_types);
?>