<?php
session_start();
session_destroy();
header('Location: /minimarket/login.php');
exit();
