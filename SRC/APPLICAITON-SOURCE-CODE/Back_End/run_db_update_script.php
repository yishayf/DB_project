<html>
<body>
<form method="post">
    <p>
        <button name="button">Run DB update</button>
    </p>
</form>
</body>

<?php

if (isset($_POST['button'])){

    $output = array();
    exec('python ../OLD_TO_DELETE/test.py', $output);
    foreach ($output as &$row){
        echo $row.'</br>';
    }
}

echo exec('python ../OLD_TO_DELETE/test.py', $output);

?>

