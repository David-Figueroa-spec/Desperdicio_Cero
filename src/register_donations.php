<?php     
session_start();

// 1. Seguridad: Solo usuarios logueados
if(!isset($_SESSION['session_user_id'])){
    header('Location: signin.html');
    exit();
}

require('../config/database.php'); // Conexión $conn_supa

// 2. Lógica para SOLICITAR una donación (Cambiar de 'disponible' a 'en camino')
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_donacion'])) {
    $id_donacion = intval($_POST['id_donacion']);
    $usuario_receptor = $_SESSION['session_user_id'];

    // Actualizamos el estado. 
    // Opcional: podrías tener una columna 'receptor_id' para saber quién la pidió.
    $sql_update = "UPDATE donaciones SET estado = 'en camino' 
                   WHERE id = $1 AND estado = 'disponible'";
    
    $res_update = pg_query_params($conn_supa, $sql_update, array($id_donacion));

    if($res_update) {
        echo "<script>alert('Donación apartada. ¡Está en camino!'); window.location.href='donations.php';</script>";
    } else {
        echo "<script>alert('Error al procesar la solicitud');</script>";
    }
}

// 3. Consultar donaciones disponibles
$sql_select = "SELECT id, producto, cantidad, descripcion, fecha_vencimiento, categoria 
               FROM donaciones 
               WHERE estado = 'disponible' 
               ORDER BY fecha_vencimiento ASC";

$res_donaciones = pg_query($conn_supa, $sql_select);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Donaciones Disponibles</title>
    <style>
        .grid { display: flex; flex-wrap: wrap; gap: 20px; }
        .card { border: 1px solid #ccc; padding: 15px; border-radius: 8px; width: 250px; }
        .btn-solicitar { background-color: #28a745; color: white; border: none; padding: 10px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Alimentos Disponibles para Recibir</h1>
    <div class="grid">
        <?php 
        while ($row = pg_fetch_assoc($res_donaciones)) { 
        ?>
            <div class="card">
                <h3><?php echo htmlspecialchars($row['producto']); ?></h3>
                <p><strong>Cantidad:</strong> <?php echo $row['cantidad']; ?></p>
                <p><strong>Categoría:</strong> <?php echo htmlspecialchars($row['categoria']); ?></p>
                <p><strong>Vence:</strong> <?php echo $row['fecha_vencimiento']; ?></p>
                <p><em><?php echo htmlspecialchars($row['descripcion']); ?></em></p>
                
                <form method="POST" action="donations.php">
                    <input type="hidden" name="id_donacion" value="<?php echo $row['id']; ?>">
                    <button type="submit" class="btn-solicitar">Solicitar Alimento</button>
                </form>
            </div>
        <?php } ?>
    </div>
</body>
</html>