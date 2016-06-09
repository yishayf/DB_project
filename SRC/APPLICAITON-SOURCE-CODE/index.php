<?php
if (!empty($_SERVER['HTTPS']) && ('on' == $_SERVER['HTTPS'])) {
    $uri = 'https://';
} else {
    $uri = 'http://';
}
$uri .= $_SERVER['HTTP_HOST'];
$uri .= $_SERVER['REQUEST_URI'];
header('Location: '.$uri.'Front_End/welcome_page.html');
exit;
?>