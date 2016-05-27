<?php

$question_types = array();

$q1_dict = array(
    "table" => "Question_type1",
    "question_format" => "Where did the (YEAR) (SEASON) Olympic games take place?",
    "num_args" => 2
);

$q2_dict = array(
    "table" => "Question_type2",
    "question_format" => "Which athlete won a (COLOR) Olympic medal?",
    "num_args" => 1
);

$q3_dict = array(
    "table" => "Question_type3",
    "question_format" => "How old is (athlete)?", // or when was he born
    "num_args" => 1
);

array_push($question_types, $q1_dict, $q2_dict, $q3_dict);

echo json_encode($question_types);
?>