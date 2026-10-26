<?php
require 'sessao_db.php';
$_SESSION = [];
session_destroy();
header('Location: login.php');
exit;
