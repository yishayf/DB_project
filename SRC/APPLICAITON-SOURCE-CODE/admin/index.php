<?php
if (!empty($_SERVER['HTTPS']) && ('on' == $_SERVER['HTTPS'])) {
    $uri = 'https://';
} else {
    $uri = 'http://';
}


// ask for user name ans password using http basic authentication
$username = null;
$password = null;

// mod_php
if (isset($_SERVER['PHP_AUTH_USER'])) {
    $username = $_SERVER['PHP_AUTH_USER'];
    $password = $_SERVER['PHP_AUTH_PW'];

// most other servers
} elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {

    if (strpos(strtolower($_SERVER['HTTP_AUTHORIZATION']),'basic')===0)
        list($username,$password) = explode(':',base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));

}

if (is_null($username) || is_null($password) || $username != 'DbMysql08' || $password != '12345') {
    $count = $count + 1;
    echo $count;

    if ($count > 3) {
        echo 'No more tries. Unauthorized';
        die();
    }
    header('WWW-Authenticate: Basic realm="My Realm"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'You hit cancel, Unauthorized';
    die();

} else {
    echo "Redirecting...";
}

// redirect to admin page
$uri .= $_SERVER['HTTP_HOST'];
$uri .= $_SERVER['REQUEST_URI'];
header('Location: '.$uri.'../Front_End/update_db.html');
exit;
?>
