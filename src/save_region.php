<?php
// ============================
// Archivo: save_region.php
// ============================

require('../config/database.php');

// Capturar datos del formulario
$name = trim($_POST['name'] ?? '');
$abbrev = trim($_POST['abbrev'] ?? '');
$code = trim($_POST['code'] ?? '');
$id_countries = (int)($_POST['id_countries'] ?? 0);


// Insertar en la base de datos de manera segura
$sql_insert = "
    INSERT INTO regions (name, abbrev, code, id_countries, status, created_at)
    VALUES ($1, $2, $3, $4, TRUE, NOW())
";
$res = pg_query_params($conn_supa, $sql_insert, [$name, $abbrev, $code, $id_countries]);

// Mostrar resultado
if ($res) {
    echo "<script>
            alert('✅ Región registrada correctamente.');
            // Redirigir al formulario o a donde necesites
            window.location.href = 'index.html';
          </script>";
} else {
    $error = addslashes(pg_last_error($conn_supa));
    echo "<script>
            alert('❌ Error al registrar en Supabase:\\n$error');
            window.history.back();
          </script>";
}
?>
