<?php     
// 1. Iniciar sesión SIEMPRE al principio y una sola vez
session_start();

// 2. Si ya tiene sesión, mandarlo directo al main inmediatamente
if(isset($_SESSION['session_user_id'])){
    header('Location: main.php');
    exit();
}

// 3. Obtener conexión (Asegúrate de que la ruta sea correcta)
require('../config/database.php');

// 4. Obtener datos del formulario (Solo si se envió el POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validar que los campos no estén vacíos
    if (!isset($_POST['email']) || !isset($_POST['passwd'])) {
        header('Location: signin.html');
        exit();
    }

    $e_mail = trim($_POST['email']);
    $p_wd = trim($_POST['passwd']);

    // 5. Consulta para validar el usuario
    // Nota: Asegúrate de que los nombres de columnas coincidan con tu tabla en Supabase
    $sql_check_user = "SELECT 
                        id, 
                        username, 
                        email, 
                        password_hash, 
                        user_role 
                       FROM 
                        usuarios 
                       WHERE 
                        email = $1 
                       LIMIT 1";

    // 6. Ejecutar consulta segura usando la conexión $conn_supa definida en database.php
    $res_check = pg_query_params($conn_supa, $sql_check_user, array($e_mail));

    if($res_check && pg_num_rows($res_check) > 0){
        $row = pg_fetch_assoc($res_check);
        
        // 7. Verificar contraseña (BCRYPT)
        if (password_verify($p_wd, $row['password_hash'])) {
            
            // Credenciales correctas: Creamos la sesión
            $_SESSION['session_user_id'] = $row['id'];
            $_SESSION['session_user_fullname'] = $row['username'];
            $_SESSION['session_user_role'] = $row['user_role'];

            header('Location: main.php');
            exit();
        } else {
            echo "<script>alert('Contraseña incorrecta'); window.location.href='../src/signin.html';</script>";
        }
    } else { 
        echo "<script>alert('Usuario no encontrado'); window.location.href='../src/signin.html';</script>";
    }
} else {
    // Si intentan entrar al PHP sin enviar el formulario por POST
    header('Location: signin.html');
    exit();
}
?>