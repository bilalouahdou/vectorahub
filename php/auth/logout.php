<?php
session_start();
session_destroy();
header('Location: /test/index.php');
exit;
?>
