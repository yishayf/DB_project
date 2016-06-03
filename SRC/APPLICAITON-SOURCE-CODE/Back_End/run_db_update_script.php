<?php

if (isset($_POST['button'])){

    $output = array();
    exec('python ../../../OLD_TO_DELETE/test.py', $output);
    foreach ($output as &$row){
        echo $row.'</br>';
    }
}
?>

