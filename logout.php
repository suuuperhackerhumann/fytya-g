<?php
require_once 'includes/config.php';
initUserSession();
session_unset();
session_destroy();
header('Location: index.php');
exit;
