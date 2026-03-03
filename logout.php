<?php
/**
 * Logout
 */
require_once 'config/config.php';
require_once 'classes/Database.php';
require_once 'classes/Auth.php';

$auth = Auth::getInstance();
$auth->logout();

header('Location: login.php');
exit;
