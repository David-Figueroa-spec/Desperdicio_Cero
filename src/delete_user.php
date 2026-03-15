<?php
require('../config/database.php');
//steap 2. Get data or params
$user_id = $_GET['userId'];
//Steap 3. prepare query
$sql_delete_user = "delete from users where id = $user_id ";
//Steap 4. execute query
 $result = pg_query($conn_supa, $sql_delete_user);
    if (!$result){
        die("Error: ". pg_last_error());
    }else{
        echo "<script>alert('User has been deleted')</script>";
        header('refresh:0;url=list_users.php');
    }
?>