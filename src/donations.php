<?php
session_start();

if (!isset($_SESSION['session_user_id'])) {
    header('Location: signin.html');
    exit();
}

// Solo receptores pueden solicitar donaciones
if ($_SESSION['session_user_role'] !== 'receptor') {
    header('Location: main.php');
    exit();
}

require('../config/database.php');

if (!isset($pdo) || $pdo === null) {
    die("Error: No se pudo establecer la conexión PDO.");
}

$solicitud_exitosa = false;
$error_msg         = null;

// ── Procesar solicitud ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_donacion'])) {
    $id          = (int)$_POST['id_donacion'];
   $receptor_id = $_SESSION['session_user_id'];

    try {
        $stmt = $pdo->prepare("
            UPDATE donaciones
               SET estado = 'En camino', receptor_id = :receptor_id
             WHERE id = :id AND LOWER(estado) = 'disponible'
        ");
        $stmt->execute(['id' => $id, 'receptor_id' => $receptor_id]);

        if ($stmt->rowCount() > 0) {
            $solicitud_exitosa = true;
        } else {
            $error_msg = "Este alimento ya no se encuentra disponible.";
        }
    } catch (PDOException $e) {
        $error_msg = "Error en la base de datos al procesar la solicitud.";
    }
}

// ── Cargar donaciones disponibles ──────────────────────────
try {
    $stmt = $pdo->query("
        SELECT d.*, COALESCE(u.nombre_entidad, 'Donante anónimo') AS donante
        FROM donaciones d
        LEFT JOIN usuarios u ON u.id = d.donante_id
        WHERE LOWER(d.estado) = 'disponible'
        ORDER BY d.fecha_vencimiento ASC
    ");
    $donaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $donaciones = [];
}

// ── Helpers ────────────────────────────────────────────────
function diasParaVencer(string $fecha): int {
    $hoy  = new DateTime();
    $venc = new DateTime($fecha);
    return max(0, (int)$hoy->diff($venc)->days);
}

function urgenciaColor(int $dias): string {
    if ($dias <= 2)  return '#ef4444';  // rojo
    if ($dias <= 5)  return '#facc15';  // amarillo
    return '#4ade80';                   // verde
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alimentos Disponibles – Misión Desperdicio Cero</title>
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
            --red:     #ef4444;
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

        .page-title { font-family:'DM Serif Display',serif;font-size:clamp(1.6rem,3vw,2.2rem);font-weight:400;color:var(--head);letter-spacing:-.02em;margin-bottom:6px; }
        .page-sub   { font-size:13px;color:var(--muted);margin-bottom:32px; }

        .msg-error { padding:14px 18px;background:rgba(239,68,68,.10);color:#fca5a5;border:1px solid rgba(239,68,68,.3);border-radius:12px;margin-bottom:24px;font-size:14px; }

        .grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:20px; }

        .food-card { background:var(--surface);backdrop-filter:blur(16px);border:1px solid var(--border);border-radius:20px;padding:22px;transition:border-color .2s,transform .2s;display:flex;flex-direction:column;gap:14px; }
        .food-card:hover { border-color:rgba(74,222,128,.25);transform:translateY(-3px); }

        .food-card-header { display:flex;align-items:flex-start;justify-content:space-between;gap:8px; }
        .food-card-header h3 { font-size:16px;font-weight:500;color:var(--head);line-height:1.3; }
        .badge { padding:3px 10px;border-radius:100px;font-size:11px;font-weight:500;background:rgba(74,222,128,.12);color:var(--green);white-space:nowrap; }

        .food-detail { display:flex;flex-direction:column;gap:6px; }
        .food-detail p { font-size:13px;color:#d1d5db;display:flex;justify-content:space-between; }
        .food-detail p span { color:var(--head);font-weight:500; }

        .vence-bar { height:4px;border-radius:100px;background:rgba(255,255,255,.06);overflow:hidden;margin-top:2px; }
        .vence-fill { height:100%;border-radius:100px; }

        .btn-solicitar { display:block;width:100%;padding:12px;border:none;border-radius:12px;cursor:pointer;font-family:'DM Sans',sans-serif;font-size:13px;font-weight:500;background:var(--green);color:var(--bg);transition:all .25s;letter-spacing:.03em; }
        .btn-solicitar:hover { background:#22c55e;transform:translateY(-1px);box-shadow:0 8px 24px rgba(74,222,128,.3); }

        .empty { text-align:center;color:var(--muted);padding:64px 20px;grid-column:1/-1; }
        .empty p { font-size:14px;margin-top:8px; }

        /* ── Modal ── */
        .modal-overlay { display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);backdrop-filter:blur(4px);justify-content:center;align-items:center;z-index:999; }
        .modal-overlay.active { display:flex; }
        .modal { background:#0f1e12;border:1px solid rgba(74,222,128,.2);border-radius:20px;padding:40px 30px;text-align:center;max-width:380px;width:90%;animation:popIn .3s ease; }
        @keyframes popIn { from{transform:scale(.8);opacity:0} to{transform:scale(1);opacity:1} }
        .modal .icono { font-size:3.5rem;margin-bottom:12px; }
        .modal h2 { font-family:'DM Serif Display',serif;font-size:1.4rem;color:var(--head);margin-bottom:8px; }
        .modal p  { font-size:14px;color:var(--muted); }
    </style>
</head>
<body>

<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="bg-grid"></div>

<!-- Modal de éxito -->
<div class="modal-overlay <?php echo $solicitud_exitosa ? 'active' : ''; ?>" id="modalExito">
    <div class="modal">
        <div class="icono">✅</div>
        <h2>¡Solicitud enviada!</h2>
        <p>El alimento está en camino. Te redirigimos en un momento…</p>
    </div>
</div>

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

    <h1 class="page-title">🍎 Alimentos Disponibles</h1>
    <p class="page-sub"><?php echo count($donaciones); ?> donación<?php echo count($donaciones) !== 1 ? 'es' : ''; ?> esperando un receptor</p>

    <?php if ($error_msg): ?>
        <div class="msg-error">❌ <?php echo htmlspecialchars($error_msg); ?></div>
    <?php endif; ?>

    <div class="grid">
        <?php if (count($donaciones) > 0): ?>
            <?php foreach ($donaciones as $item): ?>
            <?php
                $dias  = diasParaVencer($item['fecha_vencimiento']);
                $color = urgenciaColor($dias);
                $pct   = min(100, round($dias / 14 * 100)); // 14 días = barra llena
                $vence_fmt = (new DateTime($item['fecha_vencimiento']))->format('d M Y');
            ?>
            <div class="food-card">
                <div class="food-card-header">
                    <h3><?php echo htmlspecialchars($item['producto']); ?></h3>
                    <span class="badge"><?php echo htmlspecialchars($item['categoria']); ?></span>
                </div>

                <div class="food-detail">
                    <p>Cantidad <span><?php echo htmlspecialchars($item['cantidad']); ?> uds.</span></p>
                    <p>Donante  <span><?php echo htmlspecialchars($item['donante']); ?></span></p>
                    <p>
                        Vence
                        <span style="color:<?php echo $color; ?>;">
                            <?php echo $vence_fmt; ?>
                            <?php if ($dias <= 2): ?> ⚠️<?php endif; ?>
                        </span>
                    </p>
                </div>

                <!-- Barra de urgencia -->
                <div>
                    <p style="font-size:11px;color:var(--muted);margin-bottom:4px;">
                        <?php if ($dias === 0): ?>
                            Vence hoy
                        <?php elseif ($dias === 1): ?>
                            Vence mañana
                        <?php else: ?>
                            <?php echo $dias; ?> días restantes
                        <?php endif; ?>
                    </p>
                    <div class="vence-bar">
                        <div class="vence-fill" style="width:<?php echo $pct; ?>%;background:<?php echo $color; ?>;"></div>
                    </div>
                </div>

                <?php if (!empty($item['descripcion'])): ?>
                <p style="font-size:12px;color:var(--muted);font-style:italic;border-top:1px solid var(--border);padding-top:10px;">
                    <?php echo htmlspecialchars($item['descripcion']); ?>
                </p>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="id_donacion" value="<?php echo (int)$item['id']; ?>">
                    <button type="submit" class="btn-solicitar">Solicitar ahora →</button>
                </form>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty">
                <div style="font-size:3rem;">📦</div>
                <p>No hay alimentos disponibles en este momento.<br>¡Vuelve más tarde!</p>
            </div>
        <?php endif; ?>
    </div>

</div>

<script>
    const modal = document.getElementById('modalExito');
    if (modal.classList.contains('active')) {
        setTimeout(() => { window.location.href = 'main.php'; }, 2500);
    }
</script>
</body>
</html>