<?php

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

if (isset($_POST['button'])){

    $output = array();
    exec('python ../../../OLD_TO_DELETE/test.py', $output);
    foreach ($output as &$row){
        echo $row.'</br>';
    }
}
?>

