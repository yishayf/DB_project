<?php

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

if (isset($_POST['button'])){

    $output = array();
    $status;
    exec('python ../admin/olympic_data_retrieval.py', $output, $status);
    if (!$status){
        echo "Update was successful </br>";
    }
    else{
        echo "Error while running update script </br>";
    }
    foreach ($output as &$row){
        echo $row.'</br>';
    }
}
?>

