<?php     
session_start();

// Seguridad: Si no hay sesión, al login
if(!isset($_SESSION['session_user_id'])){
    header('Location: signin.html');
    exit();
}

require('../config/database.php'); // Conexión $conn_supa
$usuario_id = $_SESSION['session_user_id'];

// --- LÓGICA PARA CONFIRMAR ENTREGA ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_entrega'])) {
    $id_donacion = intval($_POST['id_entrega']);

    // Actualizamos el estado a 'entregado'
    // IMPORTANTE: Solo permitimos el cambio si el estado actual es 'en camino'
    $sql_update = "UPDATE donaciones 
                   SET estado = 'entregado' 
                   WHERE id = $1 AND estado = 'en camino' AND receptor_id = $2";
    
    $res_update = pg_query_params($conn_supa, $sql_update, array($id_donacion, $usuario_id));

    if($res_update) {
        // Recargamos para ver los cambios
        header("Location: mis_pedidos.php?status=recibido");
        exit();
    }
}

// --- CONSULTA PARA MOSTRAR LOS PEDIDOS ---
// Traemos lo que está 'en camino' (pendientes) y lo 'entregado' (historial)
$sql_select = "SELECT id, producto, cantidad, estado, fecha_vencimiento 
               FROM donaciones 
               WHERE receptor_id = $1 
               ORDER BY (estado = 'en camino') DESC, id DESC";

$res_pedidos = pg_query_params($conn_supa, $sql_select, array($usuario_id));
$mis_pedidos = pg_fetch_all($res_pedidos) ?: [];

// Incluimos la vista HTML
include('mis_pedidos_view.html');
?>