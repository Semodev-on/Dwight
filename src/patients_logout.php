<?php
session_start();
session_destroy();
header("Location: patients_index.php");
exit();
?>