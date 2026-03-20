<?php
session_start();

// Si ya tiene sesión, no tiene sentido que se registre de nuevo
if(isset($_SESSION['session_user_id'])){
    header('Location: main.php');
    exit();
}

require('../config/database.php');

// Capturar datos con limpieza básica
$rol            = $_POST['user_role'] ?? 'jugador';
$e_mail         = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
$p_wd           = $_POST['passwd'] ?? '';

// Campos opcionales según el formulario
$nombre_entidad = $_POST['empresa'] ?? $_POST['org'] ?? null;
$f_name         = $_POST['fname'] ?? null; 
$m_number       = $_POST['mnumber'] ?? null;
$tipo_inst      = $_POST['tipo'] ?? null;
$u_name         = $_POST['username'] ?? null;

// Validar que el email y la contraseña no estén vacíos
if(empty($e_mail) || empty($p_wd)) {
    echo "<script>alert('Email y contraseña son obligatorios'); window.location.href='signup.html';</script>";
    exit();
}

// Encriptación segura
$ecn_pass = password_hash($p_wd, PASSWORD_DEFAULT);

// Paso 3. Verificar si el email ya existe
$check_email = "SELECT email FROM usuarios WHERE email = $1 LIMIT 1";
$res_check = pg_query_params($conn_supa, $check_email, array($e_mail));

if($res_check && pg_num_rows($res_check) > 0){
    echo "<script>alert('¡El correo ya está registrado!'); window.location.href='signup.html';</script>";
} else {
    // Paso 4. Consulta de inserción
    $query = "INSERT INTO usuarios (
        user_role, 
        email, 
        password_hash, 
        nombre_entidad, 
        nombre_contacto, 
        telefono, 
        tipo_institucion, 
        username
    ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8)";

    $params = array(
        $rol, 
        $e_mail, 
        $ecn_pass, 
        $nombre_entidad, 
        $f_name, 
        $m_number, 
        $tipo_inst, 
        $u_name
    );
    
    $res = pg_query_params($conn_supa, $query, $params);

    if($res){
        echo "<script>alert('¡Registro exitoso! Ya puedes iniciar sesión.'); window.location.href='signin.html';</script>";
    } else {
        // Log del error para depuración (puedes quitar el pg_last_error en producción)
        error_log("Error en registro: " . pg_last_error($conn_supa));
        echo "<script>alert('Hubo un error en el servidor. Inténtalo más tarde.'); window.location.href='signup.html';</script>";
    }
}
?>