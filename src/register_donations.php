<?php
session_start();

if (!isset($_SESSION['session_user_id'])) {
    header('Location: signin.html');
    exit();
}

require('../config/database.php');

$exito = false;
$error_msg = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (!isset($_POST['producto'], $_POST['cantidad'], $_POST['categoria'], $_POST['fecha_vencimiento'])) {
        $error_msg = "Faltan campos obligatorios.";
    } else {

        $producto    = trim($_POST['producto']);
        $cantidad    = intval($_POST['cantidad']);
        $descripcion = trim($_POST['descripcion'] ?? '');
        $fecha_venc  = $_POST['fecha_vencimiento'];
        $categoria   = $_POST['categoria'];
        $donante_id  = $_SESSION['session_user_id'];

        $sql_insert = "INSERT INTO donaciones (
                            producto,
                            cantidad,
                            descripcion,
                            fecha_vencimiento,
                            categoria,
                            estado,
                            donante_id,
                            fecha_registro
                        ) VALUES ($1, $2, $3, $4, $5, 'disponible', $6, NOW())";

        $params = array($producto, $cantidad, $descripcion, $fecha_venc, $categoria, $donante_id);
        $res_insert = pg_query_params($conn_supa, $sql_insert, $params);

        if ($res_insert) {
            $exito = true;
        } else {
            $error_msg = pg_last_error($conn_supa);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Donación - Desperdicio Cero</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root { --verde: #2ecc71; --oscuro: #2c3e50; --gris: #f4f7f6; }

        body { font-family: 'Poppins', sans-serif; background: var(--gris);
               display: flex; justify-content: center; padding: 40px; margin: 0; }

        .card { background: white; padding: 35px; border-radius: 12px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 100%; max-width: 500px; }

        h2 { color: var(--oscuro); text-align: center; margin-bottom: 25px; }

        .form-group { margin-bottom: 18px; }
        label { display: block; margin-bottom: 6px; font-weight: 600; color: #555; font-size: 0.9rem; }

        input, select, textarea {
            width: 100%; padding: 10px 12px; border: 1px solid #ddd;
            border-radius: 6px; box-sizing: border-box;
            font-family: 'Poppins', sans-serif; font-size: 0.95rem;
            transition: border 0.2s;
        }
        input:focus, select:focus, textarea:focus {
            outline: none; border-color: var(--verde);
        }
        textarea { height: 85px; resize: vertical; }

        .btn-submit { width: 100%; padding: 13px; background: var(--verde); color: white;
                      border: none; border-radius: 6px; cursor: pointer; font-size: 1rem;
                      font-weight: 600; font-family: 'Poppins', sans-serif;
                      margin-top: 10px; transition: background 0.2s; }
        .btn-submit:hover { background: #27ae60; }

        .btn-back { display: block; text-align: center; margin-top: 14px;
                    color: #7f8c8d; text-decoration: none; font-size: 0.88rem; }
        .btn-back:hover { color: var(--oscuro); }

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
        .btn-volver { background: var(--verde); color: white; border: none;
                      padding: 10px 24px; border-radius: 6px; cursor: pointer;
                      font-family: 'Poppins', sans-serif; font-weight: 600; margin-top: 16px; }
        .btn-volver:hover { background: #27ae60; }
    </style>
</head>
<body>

    <!-- Modal éxito -->
    <div class="modal-overlay <?= $exito ? 'active' : '' ?>" id="modalExito">
        <div class="modal">
            <div class="icono">✅</div>
            <h2>¡Donación registrada!</h2>
            <p>Tu donación fue guardada exitosamente. Serás redirigido en un momento...</p>
        </div>
    </div>

    <!-- Modal error -->
    <div class="modal-overlay <?= $error_msg ? 'active' : '' ?>" id="modalError">
        <div class="modal">
            <div class="icono">❌</div>
            <h2 style="color:#c0392b;">Error al registrar</h2>
            <p><?= htmlspecialchars($error_msg) ?></p>
            <button class="btn-volver" onclick="window.history.back()">Volver e intentar</button>
        </div>
    </div>

    <!-- Formulario -->
    <div class="card">
        <h2>🥦 Nueva Donación</h2>

        <form action="register_donations.php" method="POST">

            <div class="form-group">
                <label for="producto">Nombre del Producto</label>
                <input type="text" id="producto" name="producto" required
                       placeholder="Ej: Arroz Diana 1kg">
            </div>

            <div class="form-group">
                <label for="cantidad">Cantidad (unidades/paquetes)</label>
                <input type="number" id="cantidad" name="cantidad" min="1" required placeholder="1">
            </div>

            <div class="form-group">
                <label for="categoria">Categoría</label>
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
                <label for="fecha_vencimiento">Fecha de Vencimiento</label>
                <input type="date" id="fecha_vencimiento" name="fecha_vencimiento" required>
            </div>

            <div class="form-group">
                <label for="descripcion">Descripción adicional <span style="font-weight:300;">(opcional)</span></label>
                <textarea id="descripcion" name="descripcion"
                          placeholder="Ej: El empaque está sellado pero tiene una pequeña arruga."></textarea>
            </div>

            <button type="submit" class="btn-submit">Registrar Alimento</button>
            <a href="main.php" class="btn-back">← Volver al Panel Principal</a>

        </form>
    </div>

    <script>
        // Evitar fechas pasadas
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('fecha_vencimiento').setAttribute('min', today);

        // Redirigir si fue exitoso
        const modalExito = document.getElementById('modalExito');
        if (modalExito.classList.contains('active')) {
            setTimeout(() => {
                window.location.href = 'main.php';
            }, 2500);
        }
    </script>

</body>
</html>