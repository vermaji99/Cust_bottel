<?php
require __DIR__ . '/../includes/bootstrap.php';
logout_user();
header("Location: login.php?logged_out=1");
exit;
