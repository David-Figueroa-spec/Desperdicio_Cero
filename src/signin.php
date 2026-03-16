
<?php     
session_start();
if(isset($_SESSION['session_user_id'])){
    header('Location: main.php');
    exit();
}

// 1. Obtener conexión
require('../config/database.php');

// Iniciar sesión
session_start();

// Si ya tiene sesión, mandarlo directo al main
if(isset($_SESSION['session_user_id'])){
     header('Location: main.php');
     exit();
}

// 2. Obtener datos del formulario (Solo si se envió el POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $e_mail = trim($_POST['email']);
    $p_wd = trim($_POST['passwd']);

    // 3. Consulta para validar el usuario (usando nombres de tu tabla usuarios)
    // Usamos el campo nombre_contacto o username según disponibilidad
    $sql_check_user = "SELECT 
                        id,
                        COALESCE(nombre_contacto, username) as fullname,
                        email,
                        password_hash,
                        user_role
                       FROM
                        usuarios
                       WHERE
                        email = $1
                       LIMIT 1";

    // 4. Ejecutar consulta segura
    $res_check = pg_query_params($conn_supa, $sql_check_user, array($e_mail));

    if(pg_num_rows($res_check) > 0){
        $row = pg_fetch_assoc($res_check);
        
        // 5. Verificar contraseña encriptada (sustituye al MD5)
        if (password_verify($p_wd, $row['password_hash'])) {
            
            // Credenciales correctas: Creamos la sesión
            $_SESSION['session_user_id'] = $row['id'];
            $_SESSION['session_user_fullname'] = $row['fullname'];
            $_SESSION['session_user_role'] = $row['user_role'];

            header('Location: main.php');
            exit();
        } else {
            echo "<script>alert('Contraseña incorrecta'); window.location.href='signin.html';</script>";
        }
    } else { 
        echo "<script>alert('Usuario no encontrado'); window.location.href='signin.html';</script>";
    }
} else {
    // Si intentan entrar al PHP sin enviar el formulario
    header('Location: signin.html');
}
?>