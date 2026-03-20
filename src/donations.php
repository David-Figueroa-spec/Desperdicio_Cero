<?php
// 1. Iniciar sesión para capturar el ID del usuario logueado
session_start();

// 2. Conexión a la base de datos (ajusté la ruta según tu captura de VS Code)
include("../config/database.php");

if (!isset($pdo) || $pdo === null) {
    die("Error: No se pudo establecer la conexión PDO a la base de datos.");
}

$solicitud_exitosa = false;
$error_msg = null;

// 3. Procesar la solicitud cuando el usuario hace clic en el botón
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_donacion'])) {
    
    // Validar que el usuario tenga sesión (basado en tu proyecto ReAprovecha)
    if (!isset($_SESSION['session_user_id'])) {
        $error_msg = "Debes iniciar sesión para solicitar alimentos.";
    } else {
        $id = (int)$_POST['id_donacion'];
        $receptor_id = $_SESSION['session_user_id'];

        try {
            // Actualizar estado a 'En camino' y asignar el receptor
            $query = "UPDATE donaciones SET estado = 'En camino', receptor_id = :receptor_id 
                      WHERE id = :id AND LOWER(estado) = 'disponible'";
            $stmt = $pdo->prepare($query);
            $stmt->execute(['id' => $id, 'receptor_id' => $receptor_id]);

            if ($stmt->rowCount() > 0) {
                $solicitud_exitosa = true;
            } else {
                $error_msg = "Este alimento ya no se encuentra disponible.";
            }
        } catch (PDOException $e) {
            $error_msg = "Error en la base de datos al procesar la solicitud.";
        }
    }
}

// 4. Cargar la lista de donaciones disponibles
try {
    $stmt = $pdo->query("SELECT * FROM donaciones WHERE LOWER(estado) = 'disponible' ORDER BY fecha_vencimiento ASC");
    $donaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $donaciones = [];
}
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
        h1 { text-align: center; color: var(--oscuro); margin-bottom: 30px; }

        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
        .card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-top: 5px solid var(--verde); }

        .badge { background: #e8f5e9; color: #27ae60; padding: 4px 8px; border-radius: 5px; font-size: 0.8rem; font-weight: 600; }
        .btn { background: var(--verde); color: white; border: none; padding: 12px; width: 100%; border-radius: 6px; cursor: pointer; font-weight: 600; margin-top: 15px; }
        .btn:hover { background: #27ae60; }

        .msg-error { padding: 15px; background: #f8d7da; color: #721c24; border-radius: 8px; margin-bottom: 20px; text-align: center; border: 1px solid #f5c6cb; }

        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed; top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.6);
            justify-content: center; align-items: center;
            z-index: 999;
        }
        .modal-overlay.active { display: flex; }
        .modal {
            background: white; border-radius: 16px;
            padding: 40px 30px; text-align: center;
            max-width: 380px; width: 90%;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            animation: popIn 0.3s ease;
        }
        @keyframes popIn { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        .modal .icono { font-size: 3.5rem; margin-bottom: 10px; }
    </style>
</head>
<body>

    <div class="modal-overlay <?= $solicitud_exitosa ? 'active' : '' ?>" id="modalExito">
        <div class="modal">
            <div class="icono">✅</div>
            <h2>¡Solicitud enviada!</h2>
            <p>El alimento está en camino. Serás redirigido en un momento...</p>
        </div>
    </div>

    <div class="container">
        <h1>🍎 Alimentos Disponibles</h1>

        <?php if ($error_msg): ?>
            <div class="msg-error">❌ <?= htmlspecialchars($error_msg) ?></div>
        <?php endif; ?>

        <div class="grid">
            <?php if (count($donaciones) > 0): ?>
                <?php foreach ($donaciones as $item): ?>
                    <div class="card">
                        <span class="badge"><?= htmlspecialchars($item['categoria']) ?></span>
                        <h3><?= htmlspecialchars($item['producto']) ?></h3>
                        <p><strong>Cantidad:</strong> <?= htmlspecialchars($item['cantidad']) ?></p>
                        <p><strong>Vence:</strong> <?= htmlspecialchars($item['fecha_vencimiento']) ?></p>

                        <form method="POST">
                            <input type="hidden" name="id_donacion" value="<?= $item['id'] ?>">
                            <button type="submit" class="btn">Solicitar ahora</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align:center; color:#666; grid-column: 1/-1;">No hay alimentos disponibles por ahora.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // REDIRECCIÓN CORREGIDA
        const modal = document.getElementById('modalExito');
        if (modal.classList.contains('active')) {
            setTimeout(() => {
                // Como este archivo está en 'src/', redirigimos directamente a 'main.php'
                window.location.href = 'main.php'; 
            }, 2500);
        }
    </script>

</body>
</html>