<?php      
session_start();

if(!isset($_SESSION['session_user_id'])){
    header('Location: signin.html');
    exit();
}

require('../config/database.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    if (!isset($_POST['producto']) || !isset($_POST['cantidad']) || !isset($_POST['categoria'])) {
        echo "<script>alert('Faltan campos obligatorios'); window.history.back();</script>";
        exit();
    }

    $producto     = trim($_POST['producto']);
    $cantidad     = intval($_POST['cantidad']);
    $descripcion  = trim($_POST['descripcion'] ?? '');
    $fecha_venc   = $_POST['fecha_vencimiento'];
    $categoria    = $_POST['categoria'];
    $usuario_id   = $_SESSION['session_user_id']; 

    $sql_insert = "INSERT INTO donaciones (
                    producto, cantidad, descripcion, fecha_vencimiento,
                    categoria, estado, donante_id, fecha_registro
                ) VALUES ($1, $2, $3, $4, $5, 'disponible', $6, NOW())";

    $params = array($producto, $cantidad, $descripcion, $fecha_venc, $categoria, $usuario_id);
    $res_insert = pg_query_params($conn_supa, $sql_insert, $params);    

    if($res_insert){
        echo "<script>alert('Donación registrada exitosamente'); window.location.href='main.php';</script>";
        exit();
    } else {
        $error = pg_last_error($conn_supa);
        echo "<script>alert('Error al registrar: " . addslashes($error) . "'); window.history.back();</script>";
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Donación - Desperdicio Cero</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; display: flex; justify-content: center; padding: 40px; }
        .card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 500px; }
        h2 { color: #2c3e50; text-align: center; margin-bottom: 10px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        textarea { height: 80px; resize: vertical; }
        button[type="submit"] { width: 100%; padding: 12px; background-color: #27ae60; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; margin-top: 10px; }
        button[type="submit"]:hover { background-color: #219150; }
        .btn-back { display: block; text-align: center; margin-top: 15px; color: #7f8c8d; text-decoration: none; font-size: 14px; }

        /* Botón de ayuda */
        .help-btn { display: block; width: 100%; margin-bottom: 20px; padding: 10px; background: transparent; border: 1px solid rgba(251,146,60,0.4); border-radius: 20px; color: #e67e22; font-size: 13px; font-weight: 500; cursor: pointer; letter-spacing: 0.03em; }
        .help-btn:hover { background: rgba(251,146,60,0.06); border-color: #fb923c; }
    </style>
</head>
<body>

<div class="card">
    <h2>Nueva Donación</h2>

    <!-- Botón de ayuda -->
    <button type="button" class="help-btn" onclick="toggleHelp()">❓ ¿Cómo funciona? Ver instructivo paso a paso</button>

    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
        
        <div class="form-group">
            <label for="producto">Nombre del Producto:</label>
            <input type="text" id="producto" name="producto" required placeholder="Ej: Arroz Diana 1kg">
        </div>

        <div class="form-group">
            <label for="cantidad">Cantidad (unidades/paquetes):</label>
            <input type="number" id="cantidad" name="cantidad" min="1" required placeholder="1">
        </div>

        <div class="form-group">
            <label for="categoria">Categoría:</label>
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
            <label for="fecha_vencimiento">Fecha de Vencimiento:</label>
            <input type="date" id="fecha_vencimiento" name="fecha_vencimiento" required>
        </div>

        <div class="form-group">
            <label for="descripcion">Descripción adicional (opcional):</label>
            <textarea id="descripcion" name="descripcion" placeholder="Ej: El empaque está sellado pero tiene una pequeña arruga."></textarea>
        </div>

        <button type="submit">Registrar Alimento</button>
        <a href="main.php" class="btn-back">Volver al Panel Principal</a>
    </form>
</div>

<!-- ══ MODAL DE AYUDA DONADOR ══ -->
<div id="help-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.60);z-index:999;align-items:center;justify-content:center;padding:1rem;">
  <div style="background:#0f1e12;border:1px solid rgba(74,222,128,0.18);border-radius:20px;max-width:560px;width:100%;max-height:85vh;overflow-y:auto;padding:2rem;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;">
      <div>
        <p style="font-size:11px;font-weight:500;text-transform:uppercase;letter-spacing:.08em;color:#6b7280;margin-bottom:4px;">Instructivo</p>
        <h2 style="font-family:'DM Serif Display',serif;font-size:1.4rem;font-weight:400;color:#f0fdf4;margin:0;">Cómo donar alimentos</h2>
      </div>
      <button type="button" onclick="closeHelp()" style="width:32px;height:32px;border-radius:50%;border:1px solid rgba(255,255,255,0.12);background:rgba(255,255,255,0.05);color:#9ca3af;font-size:18px;cursor:pointer;">×</button>
    </div>
    <div id="help-step-content"></div>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:1.5rem;padding-top:1.5rem;border-top:1px solid rgba(74,222,128,0.08);">
      <button type="button" id="help-prev" onclick="helpStep(-1)" style="padding:9px 18px;border-radius:100px;font-size:12px;font-weight:500;letter-spacing:.06em;text-transform:uppercase;border:1px solid rgba(255,255,255,0.12);background:transparent;color:#d1d5db;cursor:pointer;">← Anterior</button>
      <span id="help-counter" style="font-size:12px;color:#6b7280;"></span>
      <button type="button" id="help-next" onclick="helpStep(1)" style="padding:9px 18px;border-radius:100px;font-size:12px;font-weight:500;letter-spacing:.06em;text-transform:uppercase;background:#fb923c;color:white;border:none;cursor:pointer;">Siguiente →</button>
    </div>
  </div>
</div>

<script>
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('fecha_vencimiento').setAttribute('min', today);

    const helpSteps = [
      { n:'1', color:'#fb923c', title:'Inicia sesión con tu cuenta de donador', desc:'Accede con tu correo y contraseña seleccionando el rol <strong style="color:#fb923c">Donador</strong>. Tu panel personalizado se cargará automáticamente con tus estadísticas.', tip:'Solo los usuarios con rol donador pueden registrar alimentos en la plataforma.' },
      { n:'2', color:'#facc15', title:'Haz clic en "+ Registrar Donación"', desc:'En la parte superior de tu panel encontrarás el botón naranja <strong style="color:#fb923c">+ Registrar Donación</strong>. Al pulsarlo serás llevado a este formulario.', tip:'Puedes registrar varias donaciones distintas en el mismo día sin ningún límite.' },
      { n:'3', color:'#4ade80', title:'Completa los datos del alimento', desc:'Rellena: <strong style="color:#e5e7eb">nombre del producto</strong>, <strong style="color:#e5e7eb">cantidad</strong> en unidades o paquetes, <strong style="color:#e5e7eb">categoría</strong> y <strong style="color:#e5e7eb">fecha de vencimiento</strong>. Opcionalmente agrega una nota sobre el estado del empaque.', tip:'No se permiten fechas de vencimiento pasadas — el sistema lo valida automáticamente.' },
      { n:'4', color:'#a78bfa', title:'Envía el formulario', desc:'Haz clic en <strong style="color:#27ae60">Registrar Alimento</strong>. La donación quedará guardada con estado <em style="color:#4ade80">Disponible</em> y será visible para todos los receptores registrados.', tip:'Verás una confirmación en pantalla si el registro fue exitoso.' },
      { n:'5', color:'#4ade80', title:'Haz seguimiento desde tu panel', desc:'En el <strong style="color:#e5e7eb">Historial de donaciones</strong> puedes ver el estado de cada alimento:<br><br><span style="color:#4ade80">● Disponible</span> — nadie lo ha solicitado aún.<br><span style="color:#facc15">● En camino</span> — un receptor lo solicitó.<br><span style="color:#9ca3af">● Entregado</span> — la entrega fue completada.', tip:'Los contadores del panel (total kg, donaciones activas) se actualizan en tiempo real.' }
    ];

    let helpCurrent = 0;

    function renderHelp() {
      const s = helpSteps[helpCurrent];
      document.getElementById('help-step-content').innerHTML = `
        <div style="display:flex;align-items:flex-start;gap:16px;margin-bottom:1.25rem;">
          <div style="width:40px;height:40px;border-radius:50%;background:${s.color}20;border:1.5px solid ${s.color}60;display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:500;color:${s.color};flex-shrink:0;">${s.n}</div>
          <div>
            <h3 style="font-family:'DM Serif Display',serif;font-size:1.05rem;font-weight:400;color:#f0fdf4;margin:0 0 8px;">${s.title}</h3>
            <p style="font-size:14px;color:#d1d5db;line-height:1.7;margin:0;">${s.desc}</p>
          </div>
        </div>
        <div style="background:rgba(255,255,255,.03);border-left:2px solid ${s.color};padding:10px 14px;border-radius:0 10px 10px 0;font-size:13px;color:#9ca3af;">
          <span style="font-size:11px;font-weight:500;text-transform:uppercase;letter-spacing:.06em;color:${s.color};">Nota &nbsp;</span>${s.tip}
        </div>
        <div style="display:flex;gap:6px;justify-content:center;margin-top:1.25rem;">
          ${helpSteps.map((_,i) => `<div style="width:${i===helpCurrent?'20px':'6px'};height:6px;border-radius:100px;background:${i===helpCurrent?s.color:'rgba(255,255,255,.1)'};transition:all .2s;"></div>`).join('')}
        </div>`;
      document.getElementById('help-counter').textContent = `Paso ${helpCurrent+1} de ${helpSteps.length}`;
      document.getElementById('help-prev').style.opacity = helpCurrent===0 ? '0.3' : '1';
      document.getElementById('help-prev').disabled = helpCurrent===0;
      const nb = document.getElementById('help-next');
      if (helpCurrent === helpSteps.length-1) { nb.textContent='Entendido ✓'; nb.onclick=closeHelp; }
      else { nb.textContent='Siguiente →'; nb.onclick=()=>helpStep(1); }
    }

    function helpStep(d) { helpCurrent=Math.max(0,Math.min(helpSteps.length-1,helpCurrent+d)); renderHelp(); }
    function toggleHelp() { document.getElementById('help-overlay').style.display='flex'; renderHelp(); }
    function closeHelp()  { document.getElementById('help-overlay').style.display='none'; helpCurrent=0; }

    document.getElementById('help-overlay').addEventListener('click', function(e){ if(e.target===this) closeHelp(); });
</script>

</body>
</html>