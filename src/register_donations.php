<?php     
session_start();

// 1. Seguridad: Si el usuario no está logueado, no puede registrar donaciones
if(!isset($_SESSION['session_user_id'])){
    header('Location: signin.html');
    exit();
}

// 2. Importar la conexión (usando tu variable $conn_supa)
require('../config/database.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validar que los campos necesarios existan
    if (!isset($_POST['producto']) || !isset($_POST['cantidad']) || !isset($_POST['categoria'])) {
        echo "<script>alert('Faltan campos obligatorios'); window.history.back();</script>";
        exit();
    }

    // 3. Recibir y limpiar datos
    $producto     = trim($_POST['producto']);
    $cantidad     = intval($_POST['cantidad']);
    $descripcion  = trim($_POST['descripcion'] ?? '');
    $fecha_venc   = $_POST['fecha_vencimiento'];
    $categoria    = $_POST['categoria'];
    $usuario_id   = $_SESSION['session_user_id']; // Tomamos el ID del donante de la sesión

    // 4. Preparar la consulta SQL con los nuevos campos y el ENUM
    // Nota: Asegúrate de que la columna 'usuario_id' exista en tu tabla de Supabase si quieres rastrear quién donó
    $sql_insert = "INSERT INTO donaciones (
                        producto, 
                        cantidad, 
                        descripcion, 
                        fecha_vencimiento, 
                        categoria, 
                        estado,
                        fecha_registro
                    ) VALUES ($1, $2, $3, $4, $5, 'disponible', NOW())";

    // 5. Ejecutar con pg_query_params para evitar inyecciones SQL
    $params = array($producto, $cantidad, $descripcion, $fecha_venc, $categoria);
    $res_insert = pg_query_params($conn_supa, $sql_insert, $params);

    if($res_insert){
        echo "<script>alert('Donación registrada exitosamente'); window.location.href='main.php';</script>";
    } else {
        $error = pg_last_error($conn_supa);
        echo "<script>alert('Error al registrar: " . addslashes($error) . "'); window.history.back();</script>";
    }

} else {
    // Si intentan entrar directo al PHP sin el formulario, los mandamos al HTML
    header('Location: add_donation.html');
    exit();
}
?>