<?php
session_start();

if (!isset($_SESSION['session_user_id'])) {
    header('Location: signin.html');
    exit();
}

require('../config/database.php');
$usuario_receptor = $_SESSION['session_user_id'];

// --- SOLICITAR DONACIÓN ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_donacion'])) {
    $id_donacion = intval($_POST['id_donacion']);

    $sql_update = "UPDATE donaciones 
                   SET estado = 'en camino', receptor_id = $2 
                   WHERE id = $1 AND LOWER(estado) = 'disponible'";

    $res_update = pg_query_params($conn_supa, $sql_update, array($id_donacion, $usuario_receptor));

    if ($res_update) {
        $solicitud_exitosa = true;
    } else {
        $error_msg = "Error al procesar la solicitud.";
    }
}

// --- CONSULTAR DONACIONES DISPONIBLES ---
$sql_select = "SELECT id, producto, cantidad, descripcion, fecha_vencimiento, categoria 
               FROM donaciones 
               WHERE LOWER(estado) = 'disponible' 
               ORDER BY fecha_vencimiento ASC";

$res_donaciones = pg_query($conn_supa, $sql_select);
$donaciones = pg_fetch_all($res_donaciones) ?: [];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donaciones | ReAprovecha</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root { --verde: #2ecc71; --oscuro: #2c3e50; --gris: #f4f7f6; }
        body { font-family: 'Poppins', sans-serif; background: var(--gris); margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: auto; }
        h1 { text-align: center; color: var(--oscuro); }

        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
        .card { background: white; border-radius: 12px; padding: 20px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-top: 5px solid var(--verde); }

        .badge { background: #e8f5e9; color: #27ae60; padding: 4px 8px;
                 border-radius: 5px; font-size: 0.8rem; font-weight: 600; }
        .btn { background: var(--verde); color: white; border: none; padding: 12px;
               width: 100%; border-radius: 6px; cursor: pointer; font-weight: 600;
               margin-top: 15px; font-family: 'Poppins', sans-serif; font-size: 1rem; }
        .btn:hover { background: #27ae60; }

        .msg-error { padding: 15px; background: #f8d7da; color: #721c24;
                     border-radius: 8px; margin-bottom: 20px; text-align: center; }

        /* Modal */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0;
                         width: 100%; height: 100%; background: rgba(0,0,0,0.5);
                         justify-content: center; align-items: center; z-index: 999; }
        .modal-overlay.active { display: flex; }
        .modal { background: white; border-radius: 16px; padding: 40px 30px;
                 text-align: center; max-width: 380px; width: 90%;
                 box-shadow: 0 10px 30px rgba(0,0,0,0.2); animation: popIn 0.3s ease; }
        @keyframes popIn {
            from { transform: scale(0.8); opacity: 0; }
            to   { transform: scale(1);   opacity: 1; }
        }
        .modal .icono { font-size: 3rem; margin-bottom: 10px; }
        .modal h2 { color: var(--oscuro); margin: 0 0 10px; }
        .modal p  { color: #666; margin: 0; }
    </style>
</head>
<body>

    <!-- Modal de éxito -->
    <div class="modal-overlay <?= isset($solicitud_exitosa) ? 'active' : '' ?>" id="modalExito">
        <div class="modal">
            <div class="icono">✅</div>
            <h2>¡Solicitud enviada!</h2>
            <p>El alimento está en camino. Serás redirigido en un momento...</p>
        </div>
    </div>

    <div class="container">
        <h1>🍎 Alimentos Disponibles</h1>

        <?php if (isset($error_msg)): ?>
            <div class="msg-error">❌ <?= htmlspecialchars($error_msg) ?></div>
        <?php endif; ?>

        <div class="grid">
            <?php if (count($donaciones) > 0): ?>
                <?php foreach ($donaciones as $row): ?>
                    <div class="card">
                        <span class="badge"><?= htmlspecialchars($row['categoria']) ?></span>
                        <h3><?= htmlspecialchars($row['producto']) ?></h3>
                        <p><strong>Cantidad:</strong> <?= htmlspecialchars($row['cantidad']) ?></p>
                        <p><strong>Vence:</strong> <?= htmlspecialchars($row['fecha_vencimiento']) ?></p>
                        <p><em><?= htmlspecialchars($row['descripcion']) ?></em></p>

                        <form method="POST">
                            <input type="hidden" name="id_donacion" value="<?= $row['id'] ?>">
                            <button type="submit" class="btn">Solicitar ahora</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align:center; color:#666;">No hay alimentos disponibles por el momento.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const modal = document.getElementById('modalExito');
        if (modal.classList.contains('active')) {
            setTimeout(() => {
                window.location.href = '../index.php'; // Ajusta si tu main tiene otra ruta
            }, 2500);
        }
    </script>

</body>
</html>