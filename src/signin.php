<?php     
session_start();

if(isset($_SESSION['session_user_id'])){
    header('Location: main.php');
    exit();
}

require('../config/database.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    if (!isset($_POST['email']) || !isset($_POST['passwd'])) {
        header('Location: signin.html');
        exit();
    }

    $e_mail = trim($_POST['email']);
    $p_wd = trim($_POST['passwd']);

    $sql_check_user = "SELECT 
                        id, 
                        username, 
                        email, 
                        password_hash, 
                        user_role,
                        nombre_entidad,
                        nombre_contacto
                       FROM 
                        usuarios 
                       WHERE 
                        email = $1 
                       LIMIT 1";

    $res_check = pg_query_params($conn_supa, $sql_check_user, array($e_mail));

    if($res_check && pg_num_rows($res_check) > 0){
        $row = pg_fetch_assoc($res_check);
        
        if (password_verify($p_wd, $row['password_hash'])) {
            
            $_SESSION['session_user_id']       = $row['id'];
            $_SESSION['session_user_role']     = $row['user_role'];
            $_SESSION['session_user_fullname'] = $row['nombre_entidad'];
            $_SESSION['session_user_contact']  = $row['nombre_contacto'];

            header('Location: main.php');
            exit();
        } else {
            echo "<script>alert('Contraseña incorrecta'); window.location.href='../src/signin.html';</script>";
        }
    } else { 
        echo "<script>alert('Usuario no encontrado'); window.location.href='../src/signin.html';</script>";
    }
} else {
    header('Location: signin.html');
    exit();
}
?>