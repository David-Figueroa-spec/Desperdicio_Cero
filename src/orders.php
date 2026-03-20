<?php
session_start();

// Control de sesión
if (!isset($_SESSION['session_user_id'])) {
    header('Location: signin.html');
    exit();
}

require('../config/database.php');
$usuario_id = $_SESSION['session_user_id'];

// --- CONFIRMAR ENTREGA ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_entrega'])) {
    $id_donacion = $_POST['id_entrega'];
    
    $sql_update = "UPDATE donaciones 
                   SET estado = 'entregado' 
                   WHERE id = $1 AND LOWER(estado) = 'en camino' AND receptor_id = $2";
    $res_update = pg_query_params($conn_supa, $sql_update, array($id_donacion, $usuario_id));
    
    if ($res_update) {
        // REDIRECCIÓN CRÍTICA: Debe coincidir con el nombre del archivo físico
        header("Location: orders.php?status=recibido");
        exit();
    }
}

// --- TRAER PEDIDOS ---
$sql_select = "SELECT id, producto, cantidad, estado, fecha_vencimiento 
               FROM donaciones 
               WHERE receptor_id = $1 
               ORDER BY (LOWER(estado) = 'en camino') DESC, id DESC";
$res_pedidos = pg_query_params($conn_supa, $sql_select, array($usuario_id));
$mis_pedidos = pg_fetch_all($res_pedidos) ?: [];

$en_camino   = array_filter($mis_pedidos, fn($p) => strtolower($p['estado']) === 'en camino');
$entregados  = array_filter($mis_pedidos, fn($p) => strtolower($p['estado']) === 'entregado');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos | ReAprovecha</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root { 
            --verde: #2ecc71; 
            --oscuro: #2c3e50; 
            --gris: #f4f7f6; 
            --amarillo: #f39c12; 
        }

        body { font-family: 'Poppins', sans-serif; background: var(--gris); margin: 0; padding: 20px; }
        .container { max-width: 900px; margin: auto; }

        /* Encabezado con Flexbox para alinear título y botón */
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        h1 { color: var(--oscuro); margin: 0; }

        .btn-main {
            text-decoration: none;
            background-color: var(--oscuro);
            color: white;
            padding: 10px 18px;
            border-radius: 8px;
            font-weight: 600;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
        }

        .btn-main:hover { background-color: #34495e; transform: scale(1.02); }

        h2 { color: var(--oscuro); border-left: 4px solid var(--verde); padding-left: 10px; margin-top: 40px; }

        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 20px; }

        .card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.08); }
        .card.en-camino  { border-top: 5px solid var(--amarillo); }
        .card.entregado  { border-top: 5px solid var(--verde); opacity: 0.8; }

        .badge-camino    { background: #fff3cd; color: #856404; padding: 4px 10px; border-radius: 5px; font-size: 0.8rem; font-weight: 600; }
        .badge-entregado { background: #e8f5e9; color: #27ae60; padding: 4px 10px; border-radius: 5px; font-size: 0.8rem; font-weight: 600; }

        .btn-confirmar { 
            background: var(--verde); color: white; border: none; padding: 12px;
            width: 100%; border-radius: 6px; cursor: pointer; font-weight: 600;
            margin-top: 12px; font-family: 'Poppins', sans-serif;
        }
        .btn-confirmar:hover { background: #27ae60; }

        .msg-ok { padding: 15px; background: #d4edda; color: #155724; border-radius: 8px; margin-bottom: 20px; text-align: center; }
        .empty-msg { color: #999; font-style: italic; }
    </style>
</head>
<body>

<div class="container">
    <div class="header-container">
        <h1>📦 Mis Pedidos</h1>
        <a href="main.php" class="btn-main">← Menú Principal</a>
    </div>

    <?php if (isset($_GET['status']) && $_GET['status'] === 'recibido'): ?>
        <div class="msg-ok">✅ ¡Entrega confirmada con éxito!</div>
    <?php endif; ?>

    <h2>🚚 En camino</h2>
    <?php if (count($en_camino) > 0): ?>
        <div class="grid">
            <?php foreach ($en_camino as $pedido): ?>
                <div class="card en-camino">
                    <span class="badge-camino">En camino</span>
                    <h3><?= htmlspecialchars($pedido['producto']) ?></h3>
                    <p><strong>Cantidad:</strong> <?= htmlspecialchars($pedido['cantidad']) ?></p>
                    <p><strong>Vence:</strong> <?= htmlspecialchars($pedido['fecha_vencimiento']) ?></p>
                    <form method="POST">
                        <input type="hidden" name="id_entrega" value="<?= $pedido['id'] ?>">
                        <button type="submit" class="btn-confirmar">✔ Confirmar recepción</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="empty-msg">No hay pedidos pendientes en camino.</p>
    <?php endif; ?>

    <h2>✅ Historial</h2>
    <?php if (count($entregados) > 0): ?>
        <div class="grid">
            <?php foreach ($entregados as $pedido): ?>
                <div class="card entregado">
                    <span class="badge-entregado">Entregado</span>
                    <h3><?= htmlspecialchars($pedido['producto']) ?></h3>
                    <p><strong>Cantidad:</strong> <?= htmlspecialchars($pedido['cantidad']) ?></p>
                    <p><strong>Vence:</strong> <?= htmlspecialchars($pedido['fecha_vencimiento']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="empty-msg">No tienes entregas registradas aún.</p>
    <?php endif; ?>

</div>

</body>
</html>