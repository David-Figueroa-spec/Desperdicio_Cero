<?php
session_start();
if (!isset($_SESSION['session_user_id'])) {
    header('Location: signin.html');
    exit();
}

$user_id  = $_SESSION['session_user_id'];
$fullname = $_SESSION['session_user_fullname'] ?? 'Sin nombre';
$contacto = $_SESSION['session_user_contact']  ?? '';
$role     = $_SESSION['session_user_role']     ?? 'invitado';

require('../config/database.php');

// ════════════════════════════════════════════════
// Función auxiliar: título de nivel para jugadores
// ════════════════════════════════════════════════
function getTituloNivel(int $nivel): string {
    if ($nivel <= 3)  return 'Aprendiz Verde';
    if ($nivel <= 6)  return 'Guardián del Sabor';
    if ($nivel <= 10) return 'Héroe Sostenible';
    return 'Leyenda Cero Residuos';
}

// ════════════════════════════════════════════════
// DATOS PARA: DONADOR
// ════════════════════════════════════════════════
$st = []; $historial = [];
if ($role === 'donador') {

    // Stats generales
    $stmt = $pdo->prepare("
        SELECT
            COUNT(*)                                                          AS total,
            COALESCE(SUM(cantidad), 0)                                        AS total_kg,
            COUNT(CASE WHEN LOWER(estado) = 'disponible' THEN 1 END)         AS activas,
            COUNT(CASE WHEN LOWER(estado) = 'entregado'  THEN 1 END)         AS entregadas
        FROM donaciones
        WHERE donante_id = ?
    ");
    $stmt->execute([$user_id]);
    $st = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total'=>0,'total_kg'=>0,'activas'=>0,'entregadas'=>0];

    // Historial reciente con nombre del receptor
    $stmt2 = $pdo->prepare("
        SELECT
            d.producto,
            d.cantidad,
            TO_CHAR(d.fecha_registro, 'DD Mon YYYY')  AS fecha,
            d.estado,
            COALESCE(u.nombre_entidad, '—')           AS receptor
        FROM donaciones d
        LEFT JOIN usuarios u ON u.id = d.receptor_id
        WHERE d.donante_id = ?
        ORDER BY d.fecha_registro DESC
        LIMIT 8
    ");
    $stmt2->execute([$user_id]);
    $historial = $stmt2->fetchAll(PDO::FETCH_ASSOC);
}

// ════════════════════════════════════════════════
// DATOS PARA: RECEPTOR
// ════════════════════════════════════════════════
$disponibles = [];
if ($role === 'receptor') {

    // Stats del receptor
    $stmt = $pdo->prepare("
        SELECT
            COUNT(CASE WHEN LOWER(estado) = 'entregado' THEN 1 END)          AS recibidos,
            COUNT(CASE WHEN LOWER(estado) = 'en camino' THEN 1 END)          AS activas,
            COALESCE(SUM(CASE WHEN LOWER(estado) = 'entregado'
                              THEN cantidad ELSE 0 END), 0)                   AS kg_recibidos
        FROM donaciones
        WHERE receptor_id = ?
    ");
    $stmt->execute([$user_id]);
    $st = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['recibidos'=>0,'activas'=>0,'kg_recibidos'=>0];

    // Últimas 3 donaciones disponibles (preview rápido)
    $stmt2 = $pdo->query("
        SELECT producto, cantidad, categoria,
               TO_CHAR(fecha_vencimiento, 'DD Mon YYYY') AS vence
        FROM donaciones
        WHERE LOWER(estado) = 'disponible'
        ORDER BY fecha_vencimiento ASC
        LIMIT 3
    ");
    $disponibles = $stmt2->fetchAll(PDO::FETCH_ASSOC);
}

// ════════════════════════════════════════════════
// DATOS PARA: JUGADOR
// ════════════════════════════════════════════════
$jug_stats = []; $ranking = []; $mi_posicion = 0; $retos_activos = []; $retos_completados_count = 0;
if ($role === 'jugador') {

    // Estadísticas del jugador
    try {
        $stmt = $pdo->prepare("SELECT xp, nivel, semillas FROM usuarios WHERE id = ?");
        $stmt->execute([$user_id]);
        $jug_stats = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['xp'=>0,'nivel'=>1,'semillas'=>0];
    } catch (PDOException $e) {
        $jug_stats = ['xp'=>0, 'nivel'=>1, 'semillas'=>0];
    }

    $xp       = (int)$jug_stats['xp'];
    $nivel    = (int)$jug_stats['nivel'];
    $semillas = (int)$jug_stats['semillas'];
    $titulo   = getTituloNivel($nivel);

    // XP dentro del nivel actual (1000 XP por nivel)
    $xp_en_nivel       = $xp % 1000;
    $xp_pct            = $xp_en_nivel / 10; // % hacia el siguiente nivel

    // Retos completados (contador)
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM reto_completados WHERE jugador_id = ? AND completado = TRUE");
        $stmt->execute([$user_id]);
        $retos_completados_count = (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        $retos_completados_count = 0;
    }

    // Ranking top 5
    try {
        $stmt = $pdo->query("
            SELECT
                COALESCE(nombre_entidad, username, 'Anónimo') AS nombre,
                xp,
                RANK() OVER (ORDER BY xp DESC)                AS posicion
            FROM usuarios
            WHERE user_role = 'jugador'
            ORDER BY xp DESC
            LIMIT 5
        ");
        $ranking = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Posición del usuario actual
        $stmt2 = $pdo->prepare("
            SELECT posicion FROM (
                SELECT id, RANK() OVER (ORDER BY xp DESC) AS posicion
                FROM usuarios WHERE user_role = 'jugador'
            ) t WHERE id = ?
        ");
        $stmt2->execute([$user_id]);
        $mi_posicion = (int)($stmt2->fetchColumn() ?: 0);
    } catch (PDOException $e) {
        $ranking = []; $mi_posicion = 0;
    }

    // Retos activos (en progreso, no completados)
    try {
        $stmt = $pdo->prepare("
            SELECT
                r.titulo, r.descripcion, r.icono,
                r.xp_recompensa, r.meta_valor,
                rc.progreso
            FROM reto_completados rc
            JOIN retos r ON r.id = rc.reto_id
            WHERE rc.jugador_id = ? AND rc.completado = FALSE
            ORDER BY rc.id ASC
            LIMIT 3
        ");
        $stmt->execute([$user_id]);
        $retos_activos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $retos_activos = [];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard – Misión Desperdicio Cero</title>
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
            --purple:  #a78bfa;
            --text:    #e5e7eb;
            --muted:   #6b7280;
            --head:    #f0fdf4;
        }

        body { font-family: 'DM Sans', sans-serif; min-height: 100vh; background: var(--bg); color: var(--text); }

        .orb { position: fixed; border-radius: 50%; pointer-events: none; filter: blur(80px); z-index: 0; }
        .orb-1 { width:500px;height:500px; background:radial-gradient(circle,rgba(74,222,128,.10) 0%,transparent 70%); top:-100px;left:-120px; }
        .orb-2 { width:350px;height:350px; background:radial-gradient(circle,rgba(251,146,60,.08) 0%,transparent 70%); bottom:-80px;right:-80px; }
        .bg-grid { position:fixed;inset:0; background-image:linear-gradient(rgba(74,222,128,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(74,222,128,.04) 1px,transparent 1px); background-size:60px 60px; pointer-events:none;z-index:0; }

        /* ── HEADER ── */
        header { position:sticky;top:0;z-index:100; background:rgba(10,26,15,.90); backdrop-filter:blur(16px); border-bottom:1px solid var(--border); padding:.9rem 5%; display:flex;justify-content:space-between;align-items:center; }
        .logo { display:flex;align-items:center;gap:10px; font-family:'DM Serif Display',serif; font-size:1.05rem;font-weight:400; color:var(--head);text-decoration:none; }
        .logo svg { width:30px;height:30px;flex-shrink:0; }
        .user-nav { display:flex;align-items:center;gap:16px;font-size:13px; }
        .user-nav span { color:var(--muted); }
        .user-nav strong { color:var(--head);font-weight:500; }
        .role-badge { padding:3px 10px;border-radius:100px;font-size:11px;font-weight:500;letter-spacing:.05em;text-transform:uppercase; }
        .badge-donador  { background:rgba(251,146,60,.12);border:1px solid rgba(251,146,60,.3);color:var(--orange); }
        .badge-receptor { background:rgba(74,222,128,.12);border:1px solid rgba(74,222,128,.3);color:var(--green); }
        .badge-jugador  { background:rgba(167,139,250,.12);border:1px solid rgba(167,139,250,.3);color:var(--purple); }
        .logout-link { color:rgba(251,146,60,.6);text-decoration:none;font-size:13px;font-weight:500;transition:color .2s; }
        .logout-link:hover { color:var(--orange); }

        /* ── LAYOUT ── */
        .container { position:relative;z-index:2; padding:36px 5%;max-width:1100px;margin:auto; animation:fadeUp .7s ease both; }
        @keyframes fadeUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }

        /* ── WELCOME BAR ── */
        .welcome-bar { display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:32px; }
        .welcome-bar h1 { font-family:'DM Serif Display',serif;font-size:clamp(1.6rem,3vw,2.2rem);font-weight:400;color:var(--head);letter-spacing:-.02em;line-height:1.1; }
        .welcome-bar h1 em { font-style:italic;color:var(--role-color,var(--green)); }
        .welcome-bar p { font-size:13px;color:var(--muted);font-weight:300;margin-top:4px; }

        /* ── CARDS ── */
        .card { background:var(--surface);backdrop-filter:blur(16px);border:1px solid var(--border);border-radius:20px;padding:24px 28px;transition:border-color .2s; margin-bottom:24px; }
        .card:hover { border-color:rgba(74,222,128,.2); }
        .card-title { font-family:'DM Serif Display',serif;font-size:1.1rem;font-weight:400;color:var(--head);margin-bottom:16px;letter-spacing:-.01em; }

        /* ── STATS ── */
        .stats-grid { display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:16px;margin-bottom:24px; }
        .stat-box { background:var(--surface);backdrop-filter:blur(16px);border:1px solid var(--border);border-radius:16px;padding:20px;text-align:center;position:relative;overflow:hidden;transition:transform .2s,border-color .2s; }
        .stat-box:hover { transform:translateY(-2px);border-color:rgba(74,222,128,.22); }
        .stat-box::after { content:'';position:absolute;bottom:0;left:0;right:0;height:3px;background:var(--accent,var(--green));border-radius:0 0 16px 16px; }
        .stat-box h3 { font-family:'DM Serif Display',serif;font-size:1.9rem;font-weight:400;color:var(--accent,var(--green));letter-spacing:-.02em;margin-bottom:4px; }
        .stat-box p { font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.07em;font-weight:500; }

        /* ── BUTTONS ── */
        .btn { display:inline-flex;align-items:center;gap:8px;padding:12px 22px;border-radius:100px;font-family:'DM Sans',sans-serif;font-size:12px;font-weight:500;letter-spacing:.06em;text-transform:uppercase;border:none;cursor:pointer;transition:all .25s ease;text-decoration:none; }
        .btn-orange { background:var(--orange);color:white; }
        .btn-orange:hover { background:#f97316;transform:translateY(-1px);box-shadow:0 8px 24px rgba(251,146,60,.35); }
        .btn-green  { background:var(--green);color:var(--bg); }
        .btn-green:hover  { background:#22c55e;transform:translateY(-1px);box-shadow:0 8px 24px rgba(74,222,128,.3); }
        .btn-purple { background:var(--purple);color:var(--bg); }
        .btn-purple:hover { background:#8b5cf6;color:white;transform:translateY(-1px);box-shadow:0 8px 24px rgba(167,139,250,.3); }
        .btn-ghost  { background:transparent;border:1px solid rgba(255,255,255,.12);color:#d1fae5; }
        .btn-ghost:hover { background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.25);transform:translateY(-1px); }
        .actions { display:flex;gap:12px;flex-wrap:wrap; }

        /* ── TABLE ── */
        .table-wrap { overflow-x:auto; }
        table { width:100%;border-collapse:collapse;font-size:13px; }
        thead th { text-align:left;padding:10px 14px;font-size:11px;font-weight:500;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);border-bottom:1px solid rgba(74,222,128,.08); }
        tbody tr { transition:background .15s; }
        tbody tr:hover { background:rgba(74,222,128,.04); }
        tbody td { padding:12px 14px;color:#d1d5db;border-bottom:1px solid rgba(255,255,255,.04); }

        /* ── TAGS ── */
        .tag { display:inline-block;padding:3px 10px;border-radius:100px;font-size:11px;font-weight:500; }
        .tag-disponible { background:rgba(74,222,128,.12);color:var(--green); }
        .tag-camino     { background:rgba(250,204,21,.12);color:var(--yellow); }
        .tag-entregado  { background:rgba(156,163,175,.12);color:#9ca3af; }

        /* ── TWO COLS ── */
        .two-col { display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px; }
        @media(max-width:640px){ .two-col{grid-template-columns:1fr;} }

        /* ── PROGRESS ── */
        .progress-wrap { margin-bottom:14px; }
        .progress-label { display:flex;justify-content:space-between;font-size:12px;color:#9ca3af;margin-bottom:6px; }
        .progress-bar { height:6px;background:rgba(255,255,255,.06);border-radius:100px;overflow:hidden; }
        .progress-fill { height:100%;border-radius:100px;background:var(--fill-color,var(--purple));transition:width 1s ease; }

        /* ── RANKING ── */
        .ranking-list { display:flex;flex-direction:column;gap:10px; }
        .ranking-item { display:flex;align-items:center;gap:14px;padding:10px 14px;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.05);border-radius:12px; }
        .rank-num { width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:500;flex-shrink:0; }
        .rank-1 { background:rgba(250,204,21,.15);color:var(--yellow); }
        .rank-2 { background:rgba(156,163,175,.15);color:#9ca3af; }
        .rank-3 { background:rgba(251,146,60,.15);color:var(--orange); }
        .rank-other { background:rgba(255,255,255,.05);color:var(--muted); }
        .rank-name { flex:1;font-size:13px;color:#d1d5db; }
        .rank-pts  { font-size:13px;font-weight:500;color:var(--purple); }

        /* ── RETOS ── */
        .retos-grid { display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px; }
        .reto-card { background:rgba(255,255,255,.03);border:1px solid rgba(167,139,250,.12);border-radius:14px;padding:18px;transition:border-color .2s,transform .2s; }
        .reto-card:hover { border-color:rgba(167,139,250,.3);transform:translateY(-2px); }
        .reto-icon { font-size:1.6rem;margin-bottom:10px;display:block; }
        .reto-card h4 { font-size:14px;font-weight:500;color:var(--head);margin-bottom:4px; }
        .reto-card p  { font-size:12px;color:var(--muted);line-height:1.5;margin-bottom:12px; }
        .reto-pts { font-size:11px;font-weight:500;color:var(--purple);text-transform:uppercase;letter-spacing:.06em; }

        /* ── DISPONIBLES PREVIEW ── */
        .preview-list { display:flex;flex-direction:column;gap:10px; }
        .preview-item { display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 16px;background:rgba(255,255,255,.03);border:1px solid var(--border);border-radius:12px;flex-wrap:wrap; }
        .preview-item .info strong { display:block;font-size:14px;color:var(--head);font-weight:500; }
        .preview-item .info span   { font-size:12px;color:var(--muted); }

        /* ── DONATE BANNER ── */
        .donate-banner { background:rgba(251,146,60,.07);border:1px solid rgba(251,146,60,.18);border-radius:16px;padding:20px 24px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-top:24px; }
        .donate-banner p { font-size:14px;color:#d1d5db;font-weight:300;line-height:1.5; }
        .donate-banner strong { color:var(--orange);font-weight:500; }

        /* ── NAV BUTTONS ── */
        .nav-buttons { display:flex;gap:14px;flex-wrap:wrap;margin-bottom:28px;padding:20px 24px;background:var(--surface);backdrop-filter:blur(16px);border:1px solid var(--border);border-radius:20px; }
        .nav-buttons .btn { flex:1;justify-content:center;min-width:160px; }

        /* ── EMPTY STATE ── */
        .empty { text-align:center;color:var(--muted);padding:32px;font-size:14px; }
    </style>
</head>
<body>

<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="bg-grid"></div>

<header>
    <a href="#" class="logo">
        <svg viewBox="0 0 56 56" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="28" cy="28" r="28" fill="rgba(74,222,128,0.10)"/>
            <path d="M28 10C28 10 20 18 20 26C20 30.418 23.582 34 28 34C32.418 34 36 30.418 36 26C36 18 28 10 28 10Z" fill="#4ade80"/>
            <path d="M22 30C22 30 16 33 16 39C16 43 19.5 46 24 46C28 46 30 43 28 39" stroke="#fb923c" stroke-width="2" stroke-linecap="round" fill="none"/>
            <circle cx="28" cy="34" r="3" fill="#f0fdf4"/>
        </svg>
        Misión Desperdicio Cero
    </a>
    <div class="user-nav">
        <span class="role-badge badge-<?php echo htmlspecialchars($role); ?>"><?php echo ucfirst(htmlspecialchars($role)); ?></span>
        <span>
            <?php if ($contacto): ?>
                <?php echo htmlspecialchars($contacto); ?> &middot;
            <?php endif; ?>
            <strong><?php echo htmlspecialchars($fullname); ?></strong>
        </span>
        <a href="logout.php" class="logout-link">Salir →</a>
    </div>
</header>

<div class="container">

<?php if ($role === 'donador'): ?>
<!-- ═══════════════════════════════════════════
     DASHBOARD DONADOR
═══════════════════════════════════════════ -->
    <div class="welcome-bar" style="--role-color:#fb923c;">
        <div>
            <h1>Bienvenido, <em><?php echo htmlspecialchars($fullname); ?></em></h1>
            <p>Gracias por reducir el desperdicio en Pasto 🌱</p>
        </div>
        <div class="actions">
            <button class="btn btn-ghost" onclick="toggleHelp()">❓ Cómo funciona</button>
            <a href="register_donations.php" class="btn btn-orange">+ Registrar Donación</a>
        </div>
    </div>
    <div class="welcome-bar" style="--role-color:#fb923c;">
    <div>
        <h1>Bienvenido, <em><?php echo htmlspecialchars($fullname); ?></em></h1>
        <p>Gracias por reducir el desperdicio en Pasto 🌱</p>
    </div>
    <div class="actions">
        <button class="btn btn-ghost" onclick="toggleHelp()">❓ Cómo funciona</button>
        <a href="register_donations.php" class="btn btn-orange">+ Registrar Donación</a>
    </div>
</div>

    <!-- Stats reales desde la BD -->
    <div class="stats-grid">
        <div class="stat-box" style="--accent:#fb923c;">
            <h3><?php echo (int)$st['total']; ?></h3>
            <p>Total donaciones</p>
        </div>
        <div class="stat-box" style="--accent:var(--green);">
            <h3><?php echo number_format((float)$st['total_kg'], 0); ?> kg</h3>
            <p>Alimentos donados</p>
        </div>
        <div class="stat-box" style="--accent:var(--yellow);">
            <h3><?php echo (int)$st['activas']; ?></h3>
            <p>Donaciones activas</p>
        </div>
        <div class="stat-box" style="--accent:var(--purple);">
            <h3><?php echo (int)$st['entregadas']; ?></h3>
            <p>Entregas completadas</p>
        </div>
    </div>

    <div class="card">
        <div class="card-title">📋 Historial de donaciones</div>
        <?php if (count($historial) > 0): ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Fecha</th>
                        <th>Receptor</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historial as $d): ?>
                    <?php
                        $estado_lower = strtolower($d['estado']);
                        $tag_class = match(true) {
                            $estado_lower === 'disponible' => 'tag-disponible',
                            $estado_lower === 'en camino'  => 'tag-camino',
                            default                        => 'tag-entregado'
                        };
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($d['producto']); ?></td>
                        <td><?php echo htmlspecialchars($d['cantidad']); ?></td>
                        <td><?php echo htmlspecialchars($d['fecha']); ?></td>
                        <td><?php echo htmlspecialchars($d['receptor']); ?></td>
                        <td><span class="tag <?php echo $tag_class; ?>"><?php echo htmlspecialchars($d['estado']); ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <p class="empty">Aún no has registrado donaciones. ¡Empieza hoy! 🌿</p>
        <?php endif; ?>
    </div>

<?php elseif ($role === 'receptor'): ?>
<!-- ═══════════════════════════════════════════
     DASHBOARD RECEPTOR
═══════════════════════════════════════════ -->
    <div class="welcome-bar" style="--role-color:var(--green);">
        <div>
            <h1>Hola, <em><?php echo htmlspecialchars($fullname); ?></em></h1>
            <p>Estas son las donaciones disponibles para hoy 🧺</p>
        </div>
    </div>

    <!-- Stats reales desde la BD -->
    <div class="stats-grid">
        <div class="stat-box" style="--accent:var(--green);">
            <h3><?php echo (int)$st['recibidos']; ?></h3>
            <p>Entregas recibidas</p>
        </div>
        <div class="stat-box" style="--accent:var(--orange);">
            <h3><?php echo number_format((float)$st['kg_recibidos'], 0); ?> kg</h3>
            <p>Kg recibidos</p>
        </div>
        <div class="stat-box" style="--accent:var(--yellow);">
            <h3><?php echo (int)$st['activas']; ?></h3>
            <p>Pedidos en camino</p>
        </div>
    </div>

    <div class="nav-buttons">
        <a href="donations.php" class="btn btn-green">🟢 Alimentos disponibles</a>
        <a href="orders.php"    class="btn btn-ghost">📦 Mis pedidos</a>
    </div>

    <!-- Preview de donaciones disponibles -->
    <?php if (count($disponibles) > 0): ?>
    <div class="card">
        <div class="card-title">⚡ Disponibles ahora</div>
        <div class="preview-list">
            <?php foreach ($disponibles as $d): ?>
            <div class="preview-item">
                <div class="info">
                    <strong><?php echo htmlspecialchars($d['producto']); ?></strong>
                    <span><?php echo htmlspecialchars($d['categoria']); ?> · <?php echo htmlspecialchars($d['cantidad']); ?> uds.</span>
                </div>
                <div style="text-align:right;">
                    <span style="font-size:12px;color:var(--muted);">Vence <?php echo htmlspecialchars($d['vence']); ?></span><br>
                    <a href="donations.php" class="btn btn-green" style="margin-top:8px;padding:8px 16px;font-size:11px;">Solicitar</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

<?php else: ?>
<!-- ═══════════════════════════════════════════
     DASHBOARD JUGADOR
═══════════════════════════════════════════ -->
    <div class="welcome-bar" style="--role-color:var(--purple);">
        <div>
            <h1>¡Hola, <em><?php echo htmlspecialchars($fullname); ?></em>!</h1>
            <p>Nivel <?php echo $nivel; ?> · <?php echo $titulo; ?> 🎮</p>
        </div>
        <div class="actions">
            <button class="btn btn-purple">🎮 Minijuegos</button>
            <button class="btn btn-ghost">Ver todos los retos</button>
        </div>
    </div>

    <!-- Stats reales desde la BD -->
    <div class="stats-grid">
        <div class="stat-box" style="--accent:var(--purple);">
            <h3>Niv. <?php echo $nivel; ?></h3>
            <p><?php echo $titulo; ?></p>
        </div>
        <div class="stat-box" style="--accent:var(--green);">
            <h3><?php echo number_format($xp); ?></h3>
            <p>Puntos totales</p>
        </div>
        <div class="stat-box" style="--accent:var(--yellow);">
            <h3><?php echo $semillas; ?></h3>
            <p>Semillas</p>
        </div>
        <div class="stat-box" style="--accent:var(--orange);">
            <h3><?php echo $retos_completados_count; ?></h3>
            <p>Retos completados</p>
        </div>
    </div>

    <div class="two-col">
        <!-- Progreso -->
        <div class="card">
            <div class="card-title">📈 Tu progreso</div>
            <div class="progress-wrap">
                <div class="progress-label">
                    <span>Nivel <?php echo $nivel; ?></span>
                    <span>Nivel <?php echo $nivel + 1; ?></span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width:<?php echo $xp_pct; ?>%;--fill-color:var(--purple);"></div>
                </div>
                <div style="font-size:11px;color:var(--muted);margin-top:5px;">
                    <?php echo $xp_en_nivel; ?> / 1000 XP
                </div>
            </div>
            <div class="progress-wrap">
                <div class="progress-label">
                    <span>Semillas recolectadas</span>
                    <span><?php echo $semillas; ?> / 200</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width:<?php echo min(100, round($semillas/2)); ?>%;--fill-color:var(--green);"></div>
                </div>
            </div>
            <div class="progress-wrap" style="margin-bottom:0;">
                <div class="progress-label">
                    <span>Retos del mes</span>
                    <span><?php echo $retos_completados_count; ?> completados</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width:<?php echo min(100, $retos_completados_count * 4); ?>%;--fill-color:var(--yellow);"></div>
                </div>
            </div>
        </div>

        <!-- Ranking desde la BD -->
        <div class="card">
            <div class="card-title">🏆 Ranking global</div>
            <?php if (count($ranking) > 0): ?>
            <div class="ranking-list">
                <?php foreach ($ranking as $r): ?>
                <?php
                    $pos = (int)$r['posicion'];
                    $css = match($pos) { 1=>'rank-1', 2=>'rank-2', 3=>'rank-3', default=>'rank-other' };
                    $es_yo = ($r['nombre'] === $fullname);
                ?>
                <div class="ranking-item" <?php if($es_yo): ?>style="border-color:rgba(167,139,250,.25);background:rgba(167,139,250,.06);"<?php endif; ?>>
                    <div class="rank-num <?php echo $css; ?>"><?php echo $pos; ?></div>
                    <div class="rank-name" <?php if($es_yo): ?>style="color:var(--purple);"<?php endif; ?>>
                        <?php echo htmlspecialchars($r['nombre']); ?>
                        <?php if($es_yo): ?> <span style="font-size:11px;">(tú)</span><?php endif; ?>
                    </div>
                    <div class="rank-pts"><?php echo number_format((int)$r['xp']); ?> XP</div>
                </div>
                <?php endforeach; ?>
                <?php if ($mi_posicion > 5): ?>
                <div class="ranking-item" style="border-color:rgba(167,139,250,.25);background:rgba(167,139,250,.06);">
                    <div class="rank-num rank-other"><?php echo $mi_posicion; ?></div>
                    <div class="rank-name" style="color:var(--purple);"><?php echo htmlspecialchars($fullname); ?> (tú)</div>
                    <div class="rank-pts"><?php echo number_format($xp); ?> XP</div>
                </div>
                <?php endif; ?>
            </div>
            <?php else: ?>
                <p class="empty">Sé el primero en el ranking 🚀</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Retos activos desde la BD -->
    <div class="card">
        <div class="card-title">⚡ Retos activos</div>
        <?php if (count($retos_activos) > 0): ?>
        <div class="retos-grid">
            <?php foreach ($retos_activos as $rt): ?>
            <?php
                $meta = max(1, (float)$rt['meta_valor']);
                $prog = min(100, round((float)$rt['progreso'] / $meta * 100));
            ?>
            <div class="reto-card">
                <span class="reto-icon"><?php echo $rt['icono']; ?></span>
                <h4><?php echo htmlspecialchars($rt['titulo']); ?></h4>
                <p><?php echo htmlspecialchars($rt['descripcion']); ?></p>
                <div class="progress-bar" style="margin-bottom:8px;">
                    <div class="progress-fill" style="width:<?php echo $prog; ?>();--fill-color:var(--purple);"></div>
                </div>
                <span class="reto-pts">+<?php echo (int)$rt['xp_recompensa']; ?> XP · <?php echo (float)$rt['progreso']; ?> / <?php echo (float)$rt['meta_valor']; ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
            <p class="empty">No tienes retos activos en este momento. ¡Explora nuevos retos! 🌟</p>
        <?php endif; ?>
    </div>

    <div class="donate-banner">
        <div>
            <p><strong>¿Quieres ir un paso más allá?</strong><br>
            Aunque eres jugador, puedes hacer una donación directa y ganar <strong>+500 XP</strong> extra.</p>
        </div>
        <button class="btn btn-orange">Donar ahora</button>
    </div>

<?php endif; ?>

</div><!-- /container -->
<!-- ══ MODAL DE AYUDA DONADOR ══ -->
<div id="help-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.60);z-index:999;align-items:center;justify-content:center;padding:1rem;">
  <div style="background:#0f1e12;border:1px solid rgba(74,222,128,0.18);border-radius:20px;max-width:560px;width:100%;max-height:85vh;overflow-y:auto;padding:2rem;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;">
      <div>
        <p style="font-size:11px;font-weight:500;text-transform:uppercase;letter-spacing:.08em;color:#6b7280;margin-bottom:4px;">Instructivo</p>
        <h2 style="font-family:'DM Serif Display',serif;font-size:1.4rem;font-weight:400;color:#f0fdf4;margin:0;">Cómo donar alimentos</h2>
      </div>
      <button onclick="closeHelp()" style="width:32px;height:32px;border-radius:50%;border:1px solid rgba(255,255,255,0.12);background:rgba(255,255,255,0.05);color:#9ca3af;font-size:18px;cursor:pointer;">×</button>
    </div>
    <div id="help-step-content"></div>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:1.5rem;padding-top:1.5rem;border-top:1px solid rgba(74,222,128,0.08);">
      <button id="help-prev" onclick="helpStep(-1)" style="padding:9px 18px;border-radius:100px;font-size:12px;font-weight:500;letter-spacing:.06em;text-transform:uppercase;border:1px solid rgba(255,255,255,0.12);background:transparent;color:#d1d5db;cursor:pointer;">← Anterior</button>
      <span id="help-counter" style="font-size:12px;color:#6b7280;"></span>
      <button id="help-next" onclick="helpStep(1)" style="padding:9px 18px;border-radius:100px;font-size:12px;font-weight:500;letter-spacing:.06em;text-transform:uppercase;background:#fb923c;color:white;border:none;cursor:pointer;">Siguiente →</button>
    </div>
  </div>
</div>

<script>
const helpSteps = [
  { n:'1', color:'#fb923c', title:'Inicia sesión con tu cuenta de donador', desc:'Accede con tu correo y contraseña seleccionando el rol <strong style="color:#fb923c">Donador</strong>. Tu panel personalizado se cargará automáticamente con tus estadísticas.', tip:'Solo los usuarios con rol donador pueden registrar alimentos en la plataforma.' },
  { n:'2', color:'#facc15', title:'Haz clic en "+ Registrar Donación"', desc:'En la parte superior de tu panel encontrarás el botón naranja <strong style="color:#fb923c">+ Registrar Donación</strong>. Al pulsarlo serás llevado al formulario de registro.', tip:'Puedes registrar varias donaciones distintas en el mismo día sin ningún límite.' },
  { n:'3', color:'#4ade80', title:'Completa los datos del alimento', desc:'Rellena: <strong style="color:#e5e7eb">nombre del producto</strong>, <strong style="color:#e5e7eb">cantidad</strong> en unidades o paquetes, <strong style="color:#e5e7eb">categoría</strong> y <strong style="color:#e5e7eb">fecha de vencimiento</strong>. Opcionalmente agrega una nota sobre el estado del empaque.', tip:'No se permiten fechas de vencimiento pasadas — el sistema lo valida automáticamente.' },
  { n:'4', color:'#a78bfa', title:'Envía el formulario', desc:'Haz clic en <strong style="color:#fb923c">Registrar Alimento</strong>. La donación quedará guardada con estado <em style="color:#4ade80">Disponible</em> y será visible para todos los receptores registrados.', tip:'Verás una confirmación en pantalla si el registro fue exitoso.' },
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