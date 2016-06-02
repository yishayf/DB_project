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
    exec('python ../db_tests/test.py', $output);
    foreach ($output as &$row){
        echo $row.'</br>';
    }
}

echo exec('python ../db_tests/test.py', $output);

?>

