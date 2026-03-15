<?php
//Start or continue with current session
session_start();

//Destroy current session
session_destroy();

//Redirect to login from
header('refresh:0;url=signin.html');
?>