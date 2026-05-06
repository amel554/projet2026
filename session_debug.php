<?php
session_start();
header('Content-Type: text/plain; charset=UTF-8');
echo "SESSION DEBUG\n";
echo str_repeat('=', 40) . "\n";
print_r($_SESSION);
echo "\n\nSERVER\n";
print_r($_SERVER);
?>