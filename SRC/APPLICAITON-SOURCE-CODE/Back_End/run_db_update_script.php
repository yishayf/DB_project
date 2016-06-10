<?php

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

if (isset($_POST['button'])){

    $output = array();
    $status;
    // check if the script is currently running
    exec("ps -eaf | grep ../admin/olympic_data_retrieval.py", $output, $status);
    if ($status){
        echo "Unable to determine if the script is already running. Please try again later.";
        exit(0);
    }
    foreach ($output as &$row){
        if (strpos($row, '../admin/olympic_data_retrieval.py') !== FALSE && (strpos($row, 'grep') === FALSE)){
            echo "Update is currently running. Please try again later.";
            exit(0);
        }
    }
    // script is not running - execute the script
    $output2 = array();
    $status2;
    exec('python ../admin/olympic_data_retrieval.py 2>&1', $output2, $status2);
    if (!$status2){
        echo "Update was successful </br>";
    }
    else{
        echo "Error while running update script </br>";
    }
    // echo the output of the script
    foreach ($output2 as &$row){
        echo $row.'</br>';
    }
}
?>