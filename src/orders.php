<?php
session_start();

if (!isset($_SESSION['session_user_id'])) {
    header('Location: signin.html');
    exit();
}

require('../config/database.php');

$usuario_id = $_SESSION['session_user_id'];
$confirmado = false;

// ── Confirmar entrega ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_entrega'])) {
    $id_donacion = (int)$_POST['id_entrega'];

    $sql_update = "UPDATE donaciones
                      SET estado = 'entregado'
                    WHERE id = $1
                      AND LOWER(estado) = 'en camino'
                      AND receptor_id = $2";
    $res = pg_query_params($conn_supa, $sql_update, [$id_donacion, $usuario_id]);

    if ($res && pg_affected_rows($res) > 0) {
        $confirmado = true;
    }
}

// ── Traer pedidos ───────────────────────────────────────────
$sql_select = "
    SELECT d.id, d.producto, d.cantidad, d.estado,
           TO_CHAR(d.fecha_vencimiento, 'DD Mon YYYY') AS vence,
           TO_CHAR(d.fecha_registro,   'DD Mon YYYY') AS registrado,
           COALESCE(u.nombre_entidad, 'Donante anónimo') AS donante
    FROM donaciones d
    LEFT JOIN usuarios u ON u.id = d.donante_id
    WHERE d.receptor_id = $1
    ORDER BY (LOWER(d.estado) = 'en camino') DESC, d.id DESC
";
$res_pedidos = pg_query_params($conn_supa, $sql_select, [$usuario_id]);
$mis_pedidos = pg_fetch_all($res_pedidos) ?: [];

$en_camino  = array_filter($mis_pedidos, fn($p) => strtolower($p['estado']) === 'en camino');
$entregados = array_filter($mis_pedidos, fn($p) => strtolower($p['estado']) === 'entregado');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos – Misión Desperdicio Cero</title>
    <link rel="icon" type="image/png" href="icons/market_main.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:      #0a1a0f;
            --surface: rgba(15,30,18,0.85);
            --border:  rgba(74,222,128,0.10);
            --green:   #4ade80;
            --orange:  #fb923c;
            --yellow:  #facc15;
            --text:    #e5e7eb;
            --muted:   #6b7280;
            --head:    #f0fdf4;
        }

        body { font-family: 'DM Sans', sans-serif; min-height: 100vh; background: var(--bg); color: var(--text); }

        .orb { position: fixed; border-radius: 50%; pointer-events: none; filter: blur(80px); z-index: 0; }
        .orb-1 { width:500px;height:500px; background:radial-gradient(circle,rgba(74,222,128,.10) 0%,transparent 70%); top:-100px;left:-120px; }
        .orb-2 { width:350px;height:350px; background:radial-gradient(circle,rgba(251,146,60,.08) 0%,transparent 70%); bottom:-80px;right:-80px; }
        .bg-grid { position:fixed;inset:0; background-image:linear-gradient(rgba(74,222,128,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(74,222,128,.04) 1px,transparent 1px); background-size:60px 60px; pointer-events:none;z-index:0; }

        header { position:sticky;top:0;z-index:100; background:rgba(10,26,15,.90);backdrop-filter:blur(16px);border-bottom:1px solid var(--border);padding:.9rem 5%;display:flex;justify-content:space-between;align-items:center; }
        .logo { display:flex;align-items:center;gap:10px;font-family:'DM Serif Display',serif;font-size:1.05rem;color:var(--head);text-decoration:none; }
        .logo svg { width:30px;height:30px;flex-shrink:0; }
        .back-btn { display:inline-flex;align-items:center;gap:8px;color:rgba(255,255,255,.5);text-decoration:none;font-size:13px;font-weight:500;transition:color .2s; }
        .back-btn:hover { color:var(--head); }

        .container { position:relative;z-index:2;padding:36px 5%;max-width:1100px;margin:auto;animation:fadeUp .7s ease both; }
        @keyframes fadeUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }

        .section-title { font-family:'DM Serif Display',serif;font-size:1.3rem;font-weight:400;color:var(--head);padding-left:14px;border-left:3px solid var(--green);margin-bottom:16px;margin-top:36px; }
        .page-title { font-family:'DM Serif Display',serif;font-size:clamp(1.6rem,3vw,2.2rem);font-weight:400;color:var(--head);letter-spacing:-.02em;margin-bottom:6px; }
        .page-sub   { font-size:13px;color:var(--muted);margin-bottom:32px; }

        .msg-ok { padding:14px 18px;background:rgba(74,222,128,.10);color:#86efac;border:1px solid rgba(74,222,128,.25);border-radius:12px;margin-bottom:24px;font-size:14px;display:flex;align-items:center;gap:10px; }

        .grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:20px; }

        .order-card { background:var(--surface);backdrop-filter:blur(16px);border:1px solid var(--border);border-radius:20px;padding:22px;display:flex;flex-direction:column;gap:14px;transition:border-color .2s,transform .2s; }
        .order-card:hover { transform:translateY(-2px); }
        .order-card.en-camino { border-color:rgba(250,204,21,.2); }
        .order-card.entregado { border-color:rgba(74,222,128,.15);opacity:.75; }

        .order-card h3 { font-size:16px;font-weight:500;color:var(--head); }

        .badge-camino    { padding:3px 10px;border-radius:100px;font-size:11px;font-weight:500;background:rgba(250,204,21,.12);color:var(--yellow); }
        .badge-entregado { padding:3px 10px;border-radius:100px;font-size:11px;font-weight:500;background:rgba(74,222,128,.12);color:var(--green); }

        .order-detail { display:flex;flex-direction:column;gap:6px; }
        .order-detail p { font-size:13px;color:#d1d5db;display:flex;justify-content:space-between; }
        .order-detail p span { color:var(--head);font-weight:500; }

        .btn-confirmar { display:block;width:100%;padding:12px;border:none;border-radius:12px;cursor:pointer;font-family:'DM Sans',sans-serif;font-size:13px;font-weight:500;background:var(--green);color:var(--bg);transition:all .25s;letter-spacing:.03em; }
        .btn-confirmar:hover { background:#22c55e;transform:translateY(-1px);box-shadow:0 8px 24px rgba(74,222,128,.3); }

        .empty { color:var(--muted);font-size:14px;font-style:italic;padding:20px 0; }

        /* ── Contador de confirmación ── */
        .toast { position:fixed;bottom:28px;right:28px;z-index:999; background:rgba(15,30,18,.95);border:1px solid rgba(74,222,128,.3);border-radius:14px;padding:16px 22px;display:flex;align-items:center;gap:12px;font-size:14px;color:var(--head);animation:slideIn .4s ease; box-shadow:0 8px 32px rgba(0,0,0,.5); }
        @keyframes slideIn { from{transform:translateY(20px);opacity:0} to{transform:translateY(0);opacity:1} }
    </style>
</head>
<body>

<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="bg-grid"></div>

<?php if ($confirmado): ?>
<div class="toast" id="toast">✅ ¡Entrega confirmada con éxito!</div>
<?php endif; ?>

<header>
    <a href="main.php" class="logo">
        <svg viewBox="0 0 56 56" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="28" cy="28" r="28" fill="rgba(74,222,128,0.10)"/>
            <path d="M28 10C28 10 20 18 20 26C20 30.418 23.582 34 28 34C32.418 34 36 30.418 36 26C36 18 28 10 28 10Z" fill="#4ade80"/>
            <path d="M22 30C22 30 16 33 16 39C16 43 19.5 46 24 46C28 46 30 43 28 39" stroke="#fb923c" stroke-width="2" stroke-linecap="round" fill="none"/>
            <circle cx="28" cy="34" r="3" fill="#f0fdf4"/>
        </svg>
        Misión Desperdicio Cero
    </a>
    <a href="main.php" class="back-btn">← Volver al panel</a>
</header>

<div class="container">

    <h1 class="page-title">📦 Mis Pedidos</h1>
    <p class="page-sub">
        <?php echo count($en_camino); ?> en camino ·
        <?php echo count($entregados); ?> en historial
    </p>

    <!-- ── EN CAMINO ── -->
    <div class="section-title">🚚 En camino</div>
    <?php if (count($en_camino) > 0): ?>
    <div class="grid">
        <?php foreach ($en_camino as $p): ?>
        <div class="order-card en-camino">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <h3><?php echo htmlspecialchars($p['producto']); ?></h3>
                <span class="badge-camino">En camino</span>
            </div>
            <div class="order-detail">
                <p>Cantidad <span><?php echo htmlspecialchars($p['cantidad']); ?> uds.</span></p>
                <p>Donante  <span><?php echo htmlspecialchars($p['donante']); ?></span></p>
                <p>Vence    <span><?php echo htmlspecialchars($p['vence']); ?></span></p>
            </div>
            <form method="POST">
                <input type="hidden" name="id_entrega" value="<?php echo (int)$p['id']; ?>">
                <button type="submit" class="btn-confirmar">✔ Confirmar recepción</button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
        <p class="empty">No tienes pedidos pendientes en camino.</p>
    <?php endif; ?>

    <!-- ── HISTORIAL ── -->
    <div class="section-title" style="border-color:var(--muted);">✅ Historial de entregas</div>
    <?php if (count($entregados) > 0): ?>
    <div class="grid">
        <?php foreach ($entregados as $p): ?>
        <div class="order-card entregado">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <h3><?php echo htmlspecialchars($p['producto']); ?></h3>
                <span class="badge-entregado">Entregado</span>
            </div>
            <div class="order-detail">
                <p>Cantidad  <span><?php echo htmlspecialchars($p['cantidad']); ?> uds.</span></p>
                <p>Donante   <span><?php echo htmlspecialchars($p['donante']); ?></span></p>
                <p>Registrado <span><?php echo htmlspecialchars($p['registrado']); ?></span></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
        <p class="empty">No tienes entregas registradas aún.</p>
    <?php endif; ?>

</div>

<script>
    // Auto-ocultar toast
    const toast = document.getElementById('toast');
    if (toast) setTimeout(() => toast.style.opacity = '0', 3000);
</script>
</body>
</html>