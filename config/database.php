<?php
// Configuración para el proyecto Desperdicio_Cero
$supa_host = "aws-0-us-west-2.pooler.supabase.com"; // Host del Pooler (más estable)
$supa_user = "postgres.ahmkzbnilvscjhjunqcd"; // Usuario con tu Project ID
$supa_password = "RXq3opSSzaoImRtq"; // La que definiste al crear el proyecto
$supa_dbname = "postgres";
$supa_port  = "6543"; // Puerto del Pooler

$supa_data_connection = "
    host=$supa_host
    user=$supa_user
    password=$supa_password
    dbname=$supa_dbname
    port=$supa_port
";

$conn_supa = pg_connect($supa_data_connection);

if(!$conn_supa){
    echo "Error de conexión: " . pg_last_error();
} else {
    // echo "Conexión exitosa a la base de datos";
}

try {
    $pdo = new PDO(
        "pgsql:host=$supa_host;port=$supa_port;dbname=$supa_dbname",
        $supa_user,
        $supa_password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión PDO: " . $e->getMessage());
}
?>