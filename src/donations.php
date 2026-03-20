<?php
include("../config/database.php"); // Asegúrate de que la ruta sea correcta

// Lógica para cambiar el estado si se recibe una solicitud (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['donation_id'])) {
    $id = $_POST['donation_id'];
    
    // Actualizamos a "En camino"
    $query = "UPDATE donaciones SET estado = 'En camino' WHERE id = :id AND estado = 'Disponible'";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['id' => $id]);
    
    header("Location: donations.php?success=1");
    exit();
}

// Consultar solo las disponibles
$query = "SELECT * FROM donaciones WHERE estado = 'Disponible'";
$stmt = $pdo->prepare($query);
$stmt->execute();
$donaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <title>Donaciones Disponibles</title>
</head>
<body>
    <h1>Donaciones Disponibles</h1>
    
    <div class="container">
        <?php if (count($donaciones) > 0): ?>
            <?php foreach ($donaciones as $donacion): ?>
                <div class="card">
                    <h3><?php echo htmlspecialchars($donacion['nombre_alimento']); ?></h3>
                    <p>Cantidad: <?php echo $donacion['cantidad']; ?></p>
                    
                    <form method="POST">
                        <input type="hidden" name="donation_id" value="<?php echo $donacion['id']; ?>">
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