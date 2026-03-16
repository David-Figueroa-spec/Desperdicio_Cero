
<?php
session_start();
if(isset($_SESSION['session_user_id'])){
    header('Location: main.php');
    exit();
}
// Paso 1. Obtener la conexión a la base de datos
require('../config/database.php');

// Paso 2. Capturar datos del formulario
// Usamos el operador ?? para evitar errores si un campo no existe para ciertos roles
$rol = $_POST['user_role'] ?? 'jugador';
$e_mail = trim($_POST['email']);
$p_wd = trim($_POST['passwd']);

// Campos específicos según el rol
$nombre_entidad = $_POST['empresa'] ?? $_POST['org'] ?? null;
$f_name = $_POST['fname'] ?? null; // Persona de contacto
$m_number = $_POST['mnumber'] ?? null;
$tipo_inst = $_POST['tipo'] ?? null;
$u_name = $_POST['username'] ?? null;

$url_photo = "user_default.png";

// Encriptación segura (sustituye a md5)
$ecn_pass = password_hash($p_wd, PASSWORD_DEFAULT);

// Paso 3. Verificar si el email ya existe
$check_email = "SELECT email FROM usuarios WHERE email = $1 LIMIT 1";
$res_check = pg_query_params($conn_supa, $check_email, array($e_mail));

if(pg_num_rows($res_check) > 0){
    echo "<script>alert('¡El usuario ya existe!'); window.location.href='signup.html';</script>";
} else {
    // Paso 4. Crear la consulta de inserción con los campos de tu tabla 'usuarios'
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

    // Paso 5. Ejecutar la consulta de forma segura
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

    // Paso 6. Validar resultado
    if($res){
        echo "<script>alert('¡Registro exitoso! Ya puedes iniciar sesión.'); window.location.href='signin.html';</script>";
    } else {
        echo "Error en el servidor: " . pg_last_error($conn_supa);
    }
}
?>