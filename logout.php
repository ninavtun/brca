<?php
require 'config/db.php';
session_destroy();
header('Location: login.php');
exit;
