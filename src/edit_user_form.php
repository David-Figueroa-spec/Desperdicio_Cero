<?php
//step 1. Get database connection
require('../config/database.php');
$user_id=$_GET['userId'];
$sql_get_user = "select * from users where id = $user_id";
$result = pg_query($conn_supa, $sql_get_user);
if (!$result){
        die("Error: ". pg_last_error());
    }
    while ($row = pg_fetch_assoc($result)){
        $ide_number= $row['ide_number'];
        $fname= $row['firstname'];
        $lname= $row['firstname'];


    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <center>
    <from name="edit-user-form" action="update_user.php" method="post">
    
     <input type="hidden" 
    name="iduser" 
    value="<?php echo $user_id?>"
    readonly
    required/><br><br>
    
    <label> Identification number: </label>
    <input type="text" 
    name="idenumber" 
    value="<?php echo $ide_number?>"
    readonly
    required/><br><br>
    
    <label>  firstname: </label>
    <input type="text" 
    name="fname" 
    value="<?php echo $fname?>"
    required/><br><br>

    <label>lastname: </label>
    <input type="text" 
    name="lname" 
    value="<?php echo $lname?>"
    required/><br><br>
    <label>User photo </label><br><imput type="file" name="photo_user">
    <button>Update user</button>
    </form></form>
</body>
</html>