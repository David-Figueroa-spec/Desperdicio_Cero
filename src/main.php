<?php
session_start();
if(!isset($_SESSION['session_user_id'])){
    header('Location: signin.html');
    exit();
}

$fullname = $_SESSION['session_user_fullname'] ?? 'Sin nombre';
$contacto = $_SESSION['session_user_contact']  ?? '';
$role     = $_SESSION['session_user_role']     ?? 'invitado';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Misión Desperdicio Cero</title>
    <link rel="icon" type="image/png" href="icon/market_main.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
            background: #0a1a0f;
            color: #e5e7eb;
        }

        .orb {
            position: fixed;
            border-radius: 50%;
            pointer-events: none;
            filter: blur(80px);
            z-index: 0;
        }
        .orb-1 {
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(74,222,128,0.10) 0%, transparent 70%);
            top: -100px; left: -120px;
        }
        .orb-2 {
            width: 350px; height: 350px;
            background: radial-gradient(circle, rgba(251,146,60,0.08) 0%, transparent 70%);
            bottom: -80px; right: -80px;
        }
        .bg-grid {
            position: fixed; inset: 0;
            background-image:
                linear-gradient(rgba(74,222,128,0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(74,222,128,0.04) 1px, transparent 1px);
            background-size: 60px 60px;
            pointer-events: none; z-index: 0;
        }

        header {
            position: sticky; top: 0; z-index: 100;
            background: rgba(10,26,15,0.90);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(74,222,128,0.10);
            padding: 0.9rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex; align-items: center; gap: 10px;
            font-family: 'DM Serif Display', serif;
            font-size: 1.05rem; font-weight: 400;
            color: #f0fdf4; text-decoration: none;
        }
        .logo svg { width: 30px; height: 30px; flex-shrink: 0; }

        .user-nav { display: flex; align-items: center; gap: 16px; font-size: 13px; }
        .user-nav span { color: #9ca3af; }
        .user-nav strong { color: #f0fdf4; font-weight: 500; }

        .role-badge {
            padding: 3px 10px; border-radius: 100px;
            font-size: 11px; font-weight: 500;
            letter-spacing: 0.05em; text-transform: uppercase;
        }
        .badge-donador  { background: rgba(251,146,60,0.12); border: 1px solid rgba(251,146,60,0.3); color: #fb923c; }
        .badge-receptor { background: rgba(74,222,128,0.12); border: 1px solid rgba(74,222,128,0.3); color: #4ade80; }
        .badge-jugador  { background: rgba(167,139,250,0.12); border: 1px solid rgba(167,139,250,0.3); color: #a78bfa; }

        .logout-link {
            color: rgba(251,146,60,0.6); text-decoration: none;
            font-size: 13px; font-weight: 500;
            transition: color 0.2s;
        }
        .logout-link:hover { color: #fb923c; }

        .container {
            position: relative; z-index: 2;
            padding: 36px 5%; max-width: 1100px;
            margin: auto;
            animation: fadeUp 0.7s ease both;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .welcome-bar {
            display: flex; align-items: center;
            justify-content: space-between;
            flex-wrap: wrap; gap: 16px;
            margin-bottom: 32px;
        }
        .welcome-bar h1 {
            font-family: 'DM Serif Display', serif;
            font-size: clamp(1.6rem, 3vw, 2.2rem);
            font-weight: 400; color: #f0fdf4;
            letter-spacing: -0.02em;
            line-height: 1.1;
        }
        .welcome-bar h1 em { font-style: italic; color: var(--role-color, #4ade80); }
        .welcome-bar p { font-size: 13px; color: #6b7280; font-weight: 300; margin-top: 4px; }

        .card {
            background: rgba(15,30,18,0.85);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(74,222,128,0.10);
            border-radius: 20px;
            padding: 24px 28px;
            transition: border-color 0.2s;
        }
        .card:hover { border-color: rgba(74,222,128,0.2); }

        .card-title {
            font-family: 'DM Serif Display', serif;
            font-size: 1.1rem; font-weight: 400;
            color: #f0fdf4; margin-bottom: 16px;
            letter-spacing: -0.01em;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 16px; margin-bottom: 24px;
        }
        .stat-box {
            background: rgba(15,30,18,0.85);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(74,222,128,0.10);
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            position: relative; overflow: hidden;
            transition: transform 0.2s, border-color 0.2s;
        }
        .stat-box:hover { transform: translateY(-2px); border-color: rgba(74,222,128,0.22); }
        .stat-box::after {
            content: '';
            position: absolute; bottom: 0; left: 0; right: 0;
            height: 3px;
            background: var(--accent, #4ade80);
            border-radius: 0 0 16px 16px;
        }
        .stat-box h3 {
            font-family: 'DM Serif Display', serif;
            font-size: 1.9rem; font-weight: 400;
            color: var(--accent, #4ade80);
            letter-spacing: -0.02em; margin-bottom: 4px;
        }
        .stat-box p { font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.07em; font-weight: 500; }

        .btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 22px; border-radius: 100px;
            font-family: 'DM Sans', sans-serif;
            font-size: 12px; font-weight: 500;
            letter-spacing: 0.06em; text-transform: uppercase;
            border: none; cursor: pointer;
            transition: all 0.25s ease; text-decoration: none;
        }
        .btn-orange { background: #fb923c; color: white; }
        .btn-orange:hover { background: #f97316; transform: translateY(-1px); box-shadow: 0 8px 24px rgba(251,146,60,0.35); }
        .btn-green { background: #4ade80; color: #0a1a0f; }
        .btn-green:hover { background: #22c55e; transform: translateY(-1px); box-shadow: 0 8px 24px rgba(74,222,128,0.3); }
        .btn-purple { background: #a78bfa; color: #0a1a0f; }
        .btn-purple:hover { background: #8b5cf6; color: white; transform: translateY(-1px); box-shadow: 0 8px 24px rgba(167,139,250,0.3); }
        .btn-ghost {
            background: transparent;
            border: 1px solid rgba(255,255,255,0.12);
            color: #d1fae5;
        }
        .btn-ghost:hover { background: rgba(255,255,255,0.06); border-color: rgba(255,255,255,0.25); transform: translateY(-1px); }

        .actions { display: flex; gap: 12px; flex-wrap: wrap; }

        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; }
        @media (max-width: 640px) { .two-col { grid-template-columns: 1fr; } }

        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        thead th {
            text-align: left; padding: 10px 14px;
            font-size: 11px; font-weight: 500;
            text-transform: uppercase; letter-spacing: 0.07em;
            color: #6b7280;
            border-bottom: 1px solid rgba(74,222,128,0.08);
        }
        tbody tr { transition: background 0.15s; }
        tbody tr:hover { background: rgba(74,222,128,0.04); }
        tbody td { padding: 12px 14px; color: #d1d5db; border-bottom: 1px solid rgba(255,255,255,0.04); }

        .tag {
            display: inline-block; padding: 3px 10px;
            border-radius: 100px; font-size: 11px; font-weight: 500;
        }
        .tag-active  { background: rgba(74,222,128,0.12); color: #4ade80; }
        .tag-pending { background: rgba(250,204,21,0.12); color: #facc15; }
        .tag-done    { background: rgba(156,163,175,0.12); color: #9ca3af; }

        .donation-list { display: flex; flex-direction: column; gap: 12px; }
        .donation-item {
            display: flex; align-items: center;
            justify-content: space-between; gap: 16px;
            padding: 14px 18px;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(74,222,128,0.08);
            border-radius: 12px;
            transition: border-color 0.2s, background 0.2s;
            flex-wrap: wrap;
        }
        .donation-item:hover { border-color: rgba(74,222,128,0.2); background: rgba(74,222,128,0.04); }
        .donation-item .info { flex: 1; min-width: 120px; }
        .donation-item .info strong { display: block; font-size: 14px; color: #f0fdf4; font-weight: 500; margin-bottom: 2px; }
        .donation-item .info span { font-size: 12px; color: #6b7280; }

        .progress-wrap { margin-bottom: 8px; }
        .progress-label {
            display: flex; justify-content: space-between;
            font-size: 12px; color: #9ca3af; margin-bottom: 6px;
        }
        .progress-bar {
            height: 6px; background: rgba(255,255,255,0.06);
            border-radius: 100px; overflow: hidden;
        }
        .progress-fill {
            height: 100%; border-radius: 100px;
            background: var(--fill-color, #a78bfa);
            transition: width 1s ease;
        }

        .ranking-list { display: flex; flex-direction: column; gap: 10px; }
        .ranking-item {
            display: flex; align-items: center; gap: 14px;
            padding: 10px 14px;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 12px;
        }
        .rank-num {
            width: 28px; height: 28px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 500; flex-shrink: 0;
        }
        .rank-1     { background: rgba(250,204,21,0.15); color: #facc15; }
        .rank-2     { background: rgba(156,163,175,0.15); color: #9ca3af; }
        .rank-3     { background: rgba(251,146,60,0.15); color: #fb923c; }
        .rank-other { background: rgba(255,255,255,0.05); color: #6b7280; }
        .rank-name { flex: 1; font-size: 13px; color: #d1d5db; }
        .rank-pts  { font-size: 13px; font-weight: 500; color: #a78bfa; }

        .retos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 14px;
        }
        .reto-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(167,139,250,0.12);
            border-radius: 14px; padding: 18px;
            transition: border-color 0.2s, transform 0.2s;
        }
        .reto-card:hover { border-color: rgba(167,139,250,0.3); transform: translateY(-2px); }
        .reto-icon { font-size: 1.6rem; margin-bottom: 10px; display: block; }
        .reto-card h4 { font-size: 14px; font-weight: 500; color: #f0fdf4; margin-bottom: 4px; }
        .reto-card p  { font-size: 12px; color: #6b7280; line-height: 1.5; margin-bottom: 12px; }
        .reto-pts { font-size: 11px; font-weight: 500; color: #a78bfa; text-transform: uppercase; letter-spacing: 0.06em; }

        .donate-banner {
            background: rgba(251,146,60,0.07);
            border: 1px solid rgba(251,146,60,0.18);
            border-radius: 16px; padding: 20px 24px;
            display: flex; align-items: center;
            justify-content: space-between; gap: 16px;
            flex-wrap: wrap; margin-top: 24px;
        }
        .donate-banner p { font-size: 14px; color: #d1d5db; font-weight: 300; line-height: 1.5; }
        .donate-banner strong { color: #fb923c; font-weight: 500; }
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
        <span class="role-badge badge-<?php echo $role; ?>"><?php echo ucfirst($role); ?></span>
        <span>
            <?php if($contacto): ?>
                <?php echo htmlspecialchars($contacto); ?> · 
            <?php endif; ?>
            <strong><?php echo htmlspecialchars($fullname); ?></strong>
        </span>
        <a href="logout.php" class="logout-link">Salir →</a>
    </div>
</header>

<div class="container">

<?php if ($role == 'donador'): ?>
<!-- ═══════════════════════════════════════════
     DASHBOARD DONADOR
═══════════════════════════════════════════ -->
    <div class="welcome-bar" style="--role-color: #fb923c;">
        <div>
            <h1>Bienvenido, <em><?php echo htmlspecialchars($fullname); ?></em></h1>
            <p>Gracias por reducir el desperdicio en Pasto 🌱</p>
        </div>
        <a href="register_donations.php" class="btn btn-orange">+ Registrar Donación</a>
    </div>

    <div class="stats-grid">
        <div class="stat-box" style="--accent: #fb923c;"><h3>42</h3><p>Total donaciones</p></div>
        <div class="stat-box" style="--accent: #4ade80;"><h3>385 kg</h3><p>Alimentos donados</p></div>
        <div class="stat-box" style="--accent: #facc15;"><h3>8</h3><p>Donaciones activas</p></div>
        <div class="stat-box" style="--accent: #a78bfa;"><h3>120</h3><p>Personas beneficiadas</p></div>
    </div>

    <div class="card" style="margin-bottom: 24px;">
        <div class="card-title">📋 Historial de donaciones</div>
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
                    <tr>
                        <td>Pan integral</td><td>20 kg</td><td>14 mar 2025</td>
                        <td>Fundación Sol</td><td><span class="tag tag-active">Entregado</span></td>
                    </tr>
                    <tr>
                        <td>Lácteos variados</td><td>15 kg</td><td>12 mar 2025</td>
                        <td>Comedor El Prado</td><td><span class="tag tag-pending">En camino</span></td>
                    </tr>
                    <tr>
                        <td>Frutas de temporada</td><td>30 kg</td><td>10 mar 2025</td>
                        <td>—</td><td><span class="tag tag-pending">Disponible</span></td>
                    </tr>
                    <tr>
                        <td>Verduras mixtas</td><td>18 kg</td><td>05 mar 2025</td>
                        <td>Fundación Sol</td><td><span class="tag tag-done">Completado</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($role == 'receptor'): ?>
<!-- ═══════════════════════════════════════════
     DASHBOARD RECEPTOR
═══════════════════════════════════════════ -->
    <div class="welcome-bar" style="--role-color: #4ade80;">
        <div>
            <h1>Hola, <em><?php echo htmlspecialchars($fullname); ?></em></h1>
            <p>Estas son las donaciones disponibles para hoy 🧺</p>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-box" style="--accent: #4ade80;">
            <h3>38</h3>
            <p>Alimentos recibidos</p>
        </div>
        <div class="stat-box" style="--accent: #fb923c;">
            <h3>850</h3>
            <p>Personas ayudadas</p>
        </div>
        <div class="stat-box" style="--accent: #facc15;">
            <h3>3</h3>
            <p>Solicitudes activas</p>
        </div>
    </div>

    <!-- Botones de navegación -->
    <div style="
        display: flex;
        gap: 14px;
        flex-wrap: wrap;
        margin-bottom: 28px;
        padding: 20px 24px;
        background: rgba(15,30,18,0.85);
        backdrop-filter: blur(16px);
        border: 1px solid rgba(74,222,128,0.10);
        border-radius: 20px;
    ">
        <a href="donations.php" class="btn btn-green" style="flex: 1; justify-content: center; min-width: 160px;">
            🟢 Alimentos disponibles
        </a>
        <a href="orders.php" class="btn btn-ghost" style="flex: 1; justify-content: center; min-width: 160px;">
            📦 Mis pedidos
        </a>
        <a href="help.php" class="btn btn-ghost" style="flex: 1; justify-content: center; min-width: 160px;">
            🤝 Personas ayudadas
        </a>
    </div>

<?php else: ?>
<!-- ═══════════════════════════════════════════
     DASHBOARD JUGADOR
═══════════════════════════════════════════ -->
    <div class="welcome-bar" style="--role-color: #a78bfa;">
        <div>
            <h1>¡Hola, <em><?php echo htmlspecialchars($fullname); ?></em>!</h1>
            <p>Nivel 12 · Guardián del Sabor 🎮</p>
        </div>
        <div class="actions">
            <button class="btn btn-purple">🎮 Minijuegos</button>
            <button class="btn btn-ghost">Ver todos los retos</button>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-box" style="--accent: #a78bfa;"><h3>Niv. 12</h3><p>Guardián del Sabor</p></div>
        <div class="stat-box" style="--accent: #4ade80;"><h3>1,240</h3><p>Puntos totales</p></div>
        <div class="stat-box" style="--accent: #facc15;"><h3>145</h3><p>Semillas</p></div>
        <div class="stat-box" style="--accent: #fb923c;"><h3>18</h3><p>Retos completados</p></div>
    </div>

    <div class="two-col" style="margin-bottom: 24px;">
        <div class="card">
            <div class="card-title">📈 Tu progreso</div>
            <div class="progress-wrap" style="margin-bottom: 18px;">
                <div class="progress-label"><span>Nivel 12</span><span>Nivel 13</span></div>
                <div class="progress-bar"><div class="progress-fill" style="width: 65%; --fill-color: #a78bfa;"></div></div>
                <div style="font-size: 11px; color: #6b7280; margin-top: 5px;">650 / 1000 XP</div>
            </div>
            <div class="progress-wrap" style="margin-bottom: 18px;">
                <div class="progress-label"><span>Semillas recolectadas</span><span>145 / 200</span></div>
                <div class="progress-bar"><div class="progress-fill" style="width: 72%; --fill-color: #4ade80;"></div></div>
            </div>
            <div class="progress-wrap">
                <div class="progress-label"><span>Retos del mes</span><span>18 / 25</span></div>
                <div class="progress-bar"><div class="progress-fill" style="width: 72%; --fill-color: #facc15;"></div></div>
            </div>
        </div>

        <div class="card">
            <div class="card-title">🏆 Ranking global</div>
            <div class="ranking-list">
                <div class="ranking-item">
                    <div class="rank-num rank-1">1</div>
                    <div class="rank-name">EcoHéroe_Pasto</div>
                    <div class="rank-pts">3,420 pts</div>
                </div>
                <div class="ranking-item">
                    <div class="rank-num rank-2">2</div>
                    <div class="rank-name">VerdeNariño</div>
                    <div class="rank-pts">2,980 pts</div>
                </div>
                <div class="ranking-item">
                    <div class="rank-num rank-3">3</div>
                    <div class="rank-name">PlantaPoder</div>
                    <div class="rank-pts">2,750 pts</div>
                </div>
                <div class="ranking-item" style="border-color: rgba(167,139,250,0.25); background: rgba(167,139,250,0.06);">
                    <div class="rank-num rank-other">8</div>
                    <div class="rank-name" style="color: #a78bfa;"><?php echo htmlspecialchars($fullname); ?> (tú)</div>
                    <div class="rank-pts">1,240 pts</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom: 24px;">
        <div class="card-title">⚡ Retos activos</div>
        <div class="retos-grid">
            <div class="reto-card">
                <span class="reto-icon">🥦</span>
                <h4>Rescata 5 kg</h4>
                <p>Participa en la recogida de alimentos esta semana.</p>
                <div class="progress-bar" style="margin-bottom: 8px;"><div class="progress-fill" style="width: 60%; --fill-color: #a78bfa;"></div></div>
                <span class="reto-pts">+150 XP · 3 / 5 kg</span>
            </div>
            <div class="reto-card">
                <span class="reto-icon">📸</span>
                <h4>Comparte tu misión</h4>
                <p>Sube una foto de tu acción contra el desperdicio.</p>
                <div class="progress-bar" style="margin-bottom: 8px;"><div class="progress-fill" style="width: 0%; --fill-color: #a78bfa;"></div></div>
                <span class="reto-pts">+80 XP · Pendiente</span>
            </div>
            <div class="reto-card">
                <span class="reto-icon">🎯</span>
                <h4>Completa 3 minijuegos</h4>
                <p>Juega y aprende sobre economía circular.</p>
                <div class="progress-bar" style="margin-bottom: 8px;"><div class="progress-fill" style="width: 33%; --fill-color: #a78bfa;"></div></div>
                <span class="reto-pts">+200 XP · 1 / 3</span>
            </div>
        </div>
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
</body>
</html>