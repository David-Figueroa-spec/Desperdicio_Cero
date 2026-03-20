<?php
session_start(); // Opcional: para manejar mensajes de sesión
include("../config/database.php");

// 1. Lógica de actualización (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['donation_id'])) {
    $id = $_POST['donation_id'];
    
    try {
        $query = "UPDATE donaciones SET estado = 'En camino' WHERE id = :id AND estado = 'Disponible'";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['id' => $id]);

        // Verificamos si realmente se cambió algo (si el ID existía y estaba Disponible)
        if ($stmt->rowCount() > 0) {
            header("Location: donations.php?success=1");
        } else {
            header("Location: donations.php?error=not_found");
        }
        exit();
    } catch (PDOException $e) {
        // En producción, no muestres $e->getMessage(), regístralo en un log.
        die("Error en la base de datos.");
    }
}

// 2. Consulta de donaciones
try {
    $query = "SELECT * FROM donaciones WHERE estado = 'Disponible'";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $donaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $donaciones = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Donaciones Disponibles</title>
    <style>
        .card { border: 1px solid #ccc; padding: 15px; margin-bottom: 10px; border-radius: 8px; }
        .alert { padding: 10px; margin-bottom: 20px; color: white; border-radius: 5px; }
        .success { background-color: #28a745; }
        .error { background-color: #dc3545; }
    </style>
</head>
<body>
    <h1>Donaciones Disponibles</h1>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert success">¡La donación ha sido solicitada con éxito!</div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="alert error">No se pudo procesar la solicitud. Intenta de nuevo.</div>
    <?php endif; ?>
    
    <div class="container">
        <?php if (count($donaciones) > 0): ?>
            <?php foreach ($donaciones as $donacion): ?>
                <div class="card">
                    <h3><?php echo htmlspecialchars($donacion['nombre_alimento']); ?></h3>
                    <p>Cantidad: <?php echo htmlspecialchars($donacion['cantidad']); ?></p>
                    
                    <form method="POST" onsubmit="return confirm('¿Estás seguro de solicitar esta donación?');">
                        <input type="hidden" name="donation_id" value="<?php echo (int)$donacion['id']; ?>">
                        <button type="submit">Solicitar Donación</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No hay donaciones disponibles en este momento.</p>
        <?php endif; ?>
    </div>
</body>
</html>