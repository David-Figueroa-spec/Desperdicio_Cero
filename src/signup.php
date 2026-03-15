<?php
//step 1. Get database connection
require('../config/database.php');
 
$f_name = trim ( $_POST['fname']);
$l_name =  trim ($_POST['lname']);
$m_number =  trim ($_POST['mnumber']);
$id_number = trim ( $_POST['idnumber']);
$e_mail = trim ( $_POST['email']);
$p_wd = trim ( $_POST['passwd']);
$url_photo = "user_default.png";


//$ecn_pass = password_hash($p_wd, PASSWORD_DEFAULT );
$ecn_pass = md5($p_wd);
$check_email=" 
SELECT 
    u.email
FROM
    users u
WHERE
    email = '$e_mail' or ide_number = '$id_number'
LIMIT 1    
";
$res_check = pg_query($conn_supa, $check_email);
if(pg_num_rows($res_check) > 0){
    echo"<script>alert('User already exits !!')</script>";+
    header('refresh:0;url=signup.html');

} else{
//step 3. create query to insert into
$query="
insert into users(
firstname, lastname, mobile_number, 
ide_number, email, password,url_photo
) 
values(
'$f_name', '$l_name', '$m_number', '$id_number', 
'$e_mail', '$ecn_pass','$url_photo'
)
";
//step 4. Execute query
$res = pg_query($conn_supa, $query);

//step 5. Validate result

if($res){
    //echo "User has been created successfully !!!";
    echo"<script>alert('Success !!! Go to login')</script>";+
    header('refresh:0;url=signin.html');
}else{
    echo "something wrong!";
}
}

?>