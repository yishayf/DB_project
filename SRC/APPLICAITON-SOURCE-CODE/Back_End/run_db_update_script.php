<?php

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

if (isset($_POST['button'])){

    $output = array();
    exec('python ../admin/olympic_data_retrieval.php', $output);
    foreach ($output as &$row){
        echo $row.'</br>';
    }
}
?>

