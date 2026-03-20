<?php      
session_start();

// 1. Seguridad: Si el usuario no está logueado, redirigir
if(!isset($_SESSION['session_user_id'])){
    header('Location: signin.html');
    exit();
}

// 2. Importar la conexión
require('../config/database.php');

// 3. Procesar el formulario solo si se recibe una solicitud POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validar campos obligatorios
    if (!isset($_POST['producto']) || !isset($_POST['cantidad']) || !isset($_POST['categoria'])) {
        echo "<script>alert('Faltan campos obligatorios'); window.history.back();</script>";
        exit();
    }

    // Recibir y limpiar datos
    $producto     = trim($_POST['producto']);
    $cantidad     = intval($_POST['cantidad']);
    $descripcion  = trim($_POST['descripcion'] ?? '');
    $fecha_venc   = $_POST['fecha_vencimiento'];
    $categoria    = $_POST['categoria'];
    $usuario_id   = $_SESSION['session_user_id']; 

    // Preparar la consulta SQL
    $sql_insert = "INSERT INTO donaciones (
                        producto, 
                        cantidad, 
                        descripcion, 
                        fecha_vencimiento, 
                        categoria, 
                        estado,
                        fecha_registro
                    ) VALUES ($1, $2, $3, $4, $5, 'disponible', NOW())";

    // Ejecutar con pg_query_params
    $params = array($producto, $cantidad, $descripcion, $fecha_venc, $categoria);
    $res_insert = pg_query_params($conn_supa, $sql_insert, $params);

    if($res_insert){
        echo "<script>alert('Donación registrada exitosamente'); window.location.href='main.php';</script>";
        exit();
    } else {
        $error = pg_last_error($conn_supa);
        echo "<script>alert('Error al registrar: " . addslashes($error) . "'); window.history.back();</script>";
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Donación - Desperdicio Cero</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; display: flex; justify-content: center; padding: 40px; }
        .card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 500px; }
        h2 { color: #2c3e50; text-align: center; margin-bottom: 25px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        textarea { height: 80px; resize: vertical; }
        button { width: 100%; padding: 12px; background-color: #27ae60; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; margin-top: 10px; }
        button:hover { background-color: #219150; }
        .btn-back { display: block; text-align: center; margin-top: 15px; color: #7f8c8d; text-decoration: none; font-size: 14px; }
    </style>
</head>
<body>

<div class="card">
    <h2>Nueva Donación</h2>
    
    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
        
        <div class="form-group">
            <label for="producto">Nombre del Producto:</label>
            <input type="text" id="producto" name="producto" required placeholder="Ej: Arroz Diana 1kg">
        </div>

        <div class="form-group">
            <label for="cantidad">Cantidad (unidades/paquetes):</label>
            <input type="number" id="cantidad" name="cantidad" min="1" required placeholder="1">
        </div>

        <div class="form-group">
            <label for="categoria">Categoría:</label>
            <select id="categoria" name="categoria" required>
                <option value="" disabled selected>Seleccione una categoría...</option>
                <option value="Perecedero">Perecedero</option>
                <option value="No perecedero">No perecedero</option>
                <option value="Enlatado">Enlatado</option>
                <option value="Fruta/Verdura">Fruta/Verdura</option>
                <option value="Lácteos">Lácteos</option>
                <option value="Cárnicos">Cárnicos</option>
                <option value="Panadería">Panadería</option>
            </select>
        </div>

        <div class="form-group">
            <label for="fecha_vencimiento">Fecha de Vencimiento:</label>
            <input type="date" id="fecha_vencimiento" name="fecha_vencimiento" required>
        </div>

        <div class="form-group">
            <label for="descripcion">Descripción adicional (opcional):</label>
            <textarea id="descripcion" name="descripcion" placeholder="Ej: El empaque está sellado pero tiene una pequeña arruga."></textarea>
        </div>

        <button type="submit">Registrar Alimento</button>
        <a href="main.php" class="btn-back">Volver al Panel Principal</a>
    </form>
</div>

<script>
    // Evitar registrar fechas pasadas
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('fecha_vencimiento').setAttribute('min', today);
</script>

</body>
</html>