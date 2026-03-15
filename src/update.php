<?php
//step 1. Get database connection
require('../config/database.php');
//Steap 2. Get from-data 
$user_id = trim ($_POST['iduser']);
$f_name = trim ( $_POST['fname']);
$l_name =  trim ($_POST['lname']);
//Steap 3. Update query
$sql_update_user="
    update users set
        firstname ='$f_name',
        lastname ='$l_name'
    where
        id=$user_id";
$result = pg_query($conn_supa, $sql_update_user);
    if (!$result){
        echo"<script>alert('Update Success !!!!')</scrip>;";+
        header('refresh:0;url=list_users.php');
    }else{
        echo"something wrong!";
    }

?>