<?php
// Paso 1: conexión a Supabase (usa tu archivo original)
require('../config/database.php');

// Paso 2: capturar los datos del formulario
$name = trim($_POST['name']);
$abbrev = trim($_POST['abbrev']);
$code = trim($_POST['code']);

// Paso 3: validación simple
if ($name == '') {
    echo "⚠️ El campo 'nombre' es obligatorio.";
    exit;
}

// Paso 4: insertar los datos en la base
$sql_insert = "
    INSERT INTO countries (name, abbrev, code, status, created_at)
    VALUES ('$name', '$abbrev', '$code', TRUE, NOW())
";
$res = pg_query($conn_supa, $sql_insert);

// Paso 5: mostrar resultado
if ($res) {
    echo "✅ País registrado con éxito.";
    header('refresh:1;url=add_country.html');
} else {
    echo "❌ Error al registrar país: " . pg_last_error($conn_supa);
}
?>
