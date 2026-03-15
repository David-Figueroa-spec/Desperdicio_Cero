<?php
//step 1. Get database connection
require('../config/database.php');
session_start();

if(!isset($_SESSION['session_user_id'])){
     header('refresh:0;url=error_403.html');

}
echo $_SESSION['session_user_id'] ;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marketapp - list_users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</head>

<body>
    <div class="container mt-3">
    <table  class="table table-striped" class=table;>
     <tr>
        <th>Full name</th>
        <th>E-mail</th>
        <th>Ide. number</th>
        <th>Phone number</th>
        <th>Status</th>
        <th>Photo</th>
        <th>Options</th>
        
</tr>
<?php
    $sql_users = "
    select 
    u.id as user_id,
    u.firstname ||' '|| u.lastname as full_name, 
    u.email, 
    u.ide_number, 
    u.mobile_number, 
    case when u.status = true then 'Active' else 'Inactive' end as status,
    u.url_photo 
    from  users as u ";
    $result = pg_query($conn_supa, $sql_users);
    if (!$result){
        die("Error: ". pg_last_error());
    }
    while ($row = pg_fetch_assoc($result)){
        echo "
            <tr>
                <td>".$row['full_name'] ."</td>
                <td>".$row['email'] ."</td>
                <td>".$row['ide_number'] ."</td>
                <td>".$row['mobile_number'] ."</td>
                <td>".$row['status'] ."</td>







                
               <td> <img src=".$row['url_photo']." width = 
                '30' alt='User photo'>
                </td>

        <td>
            <a href='#'><img src='icons/lupa.png' width='20'></a>
            <a href='edit_user_form.php?userId=".$row['user_id']."'><img src='icons/lapiz.png' width='20'></a>
            <a href='delete_user.php?userId=".$row['user_id']."'>
            <img src='icons/papelera.png' width='20'></a>
        </td>
        
        </tr>
        ";
    }
?>

    </table> 
    </div>   
</body>
</html>