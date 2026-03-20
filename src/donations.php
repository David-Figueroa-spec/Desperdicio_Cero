<?php
// 1. CONFIGURACIÓN Y LÓGICA (El "Cerebro")
include("../config/database.php");

// Lógica de actualización cuando alguien hace clic en "Solicitar"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_donacion'])) {
    $id = (int)$_POST['id_donacion'];
    try {
        $query = "UPDATE donaciones SET estado = 'En camino' WHERE id = :id AND estado = 'Disponible'";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['id' => $id]);
        header("Location: donations.php?success=1");
        exit();
    } catch (PDOException $e) {
        $error_msg = "Error al procesar la solicitud.";
    }
}

// Consulta de datos para mostrar en las tarjetas
try {
    $stmt = $pdo->query("SELECT * FROM donaciones WHERE estado = 'Disponible' ORDER BY fecha_vencimiento ASC");
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
        /* Estilos modernos y limpios */
        :root { --verde: #2ecc71; --oscuro: #2c3e50; --gris: #f4f7f6; }
        body { font-family: 'Poppins', sans-serif; background: var(--gris); margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: auto; }
        h1 { text-align: center; color: var(--oscuro); }
        
        /* Grid de Tarjetas */
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
        .card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-top: 5px solid var(--verde); }
        
        /* Detalles */
        .badge { background: #e8f5e9; color: #27ae60; padding: 4px 8px; border-radius: 5px; font-size: 0.8rem; font-weight: 600; }
        .btn { background: var(--verde); color: white; border: none; padding: 12px; width: 100%; border-radius: 6px; cursor: pointer; font-weight: 600; margin-top: 15px; }
        .btn:hover { background: #27ae60; }
        
        /* Mensajes */
        .msg { padding: 15px; background: #d4edda; color: #155724; border-radius: 8px; margin-bottom: 20px; text-align: center; }
    </style>
</head>
<body>

    <div class="container">
        <h1>🍎 Alimentos Disponibles</h1>

        <?php if(isset($_GET['success'])): ?>
            <div class="msg">✅ ¡Solicitud enviada! El alimento está en camino.</div>
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
                <p>No hay alimentos disponibles por el momento.</p>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>