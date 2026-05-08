<?php
session_start();
if (!isset($_SESSION['session_user_id'])) {
    header('Location: signin.html');
    exit();
}
if ($_SESSION['session_user_role'] !== 'jugador') {
    header('Location: main.php');
    exit();
}

$user_id  = $_SESSION['session_user_id'];
$fullname = $_SESSION['session_user_fullname'] ?? 'Jugador';

require('../config/database.php');

// ────────────────────────────────────────────────────────
// ENDPOINT AJAX: guardar puntuación y otorgar XP/semillas
// ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_score') {
    header('Content-Type: application/json');

    $score  = max(0, (int)($_POST['score']  ?? 0));
    $lineas = max(0, (int)($_POST['lineas'] ?? 0));

    // Regla: 1 XP por cada 10 puntos (máx 500 XP por partida), 1 semilla por cada 4 líneas (máx 20)
    $xp_ganado       = min(500,  (int)floor($score  / 10));
    $semillas_ganadas = min(20,  (int)floor($lineas / 4));

    try {
        // Obtener stats actuales
        $stmt = $pdo->prepare("SELECT xp, nivel, semillas FROM usuarios WHERE id = ?");
        $stmt->execute([$user_id]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);

        $nuevo_xp       = (int)$u['xp']       + $xp_ganado;
        $nuevo_semillas = (int)$u['semillas']  + $semillas_ganadas;
        $nuevo_nivel    = max(1, (int)floor($nuevo_xp / 1000) + 1);

        $upd = $pdo->prepare("UPDATE usuarios SET xp = ?, semillas = ?, nivel = ? WHERE id = ?");
        $upd->execute([$nuevo_xp, $nuevo_semillas, $nuevo_nivel, $user_id]);

        // Verificar retos relacionados con gamificación (tipo 'juego' si existe)
        $reto_check = $pdo->prepare("
            SELECT rc.id, rc.progreso, r.meta_valor, r.xp_recompensa, r.semillas_recompensa
            FROM reto_completados rc
            JOIN retos r ON r.id = rc.reto_id
            WHERE rc.jugador_id = ? AND rc.completado = FALSE AND r.tipo = 'juego'
            LIMIT 1
        ");
        $reto_check->execute([$user_id]);
        $reto = $reto_check->fetch(PDO::FETCH_ASSOC);
        $reto_completado = false;

        if ($reto) {
            $nuevo_progreso = (float)$reto['progreso'] + $lineas;
            if ($nuevo_progreso >= (float)$reto['meta_valor']) {
                // Completar reto y dar recompensa adicional
                $pdo->prepare("UPDATE reto_completados SET progreso = ?, completado = TRUE, fecha_completado = NOW() WHERE id = ?")
                    ->execute([$nuevo_progreso, $reto['id']]);
                $nuevo_xp       += (int)$reto['xp_recompensa'];
                $nuevo_semillas += (int)$reto['semillas_recompensa'];
                $pdo->prepare("UPDATE usuarios SET xp = ?, semillas = ? WHERE id = ?")
                    ->execute([$nuevo_xp, $nuevo_semillas, $user_id]);
                $reto_completado = true;
            } else {
                $pdo->prepare("UPDATE reto_completados SET progreso = ? WHERE id = ?")
                    ->execute([$nuevo_progreso, $reto['id']]);
            }
        }

        echo json_encode([
            'ok'              => true,
            'xp_ganado'       => $xp_ganado,
            'semillas_ganadas' => $semillas_ganadas,
            'nuevo_xp'        => $nuevo_xp,
            'nuevo_nivel'     => $nuevo_nivel,
            'reto_completado' => $reto_completado,
        ]);
    } catch (PDOException $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit();
}

// ────────────────────────────────────────────────────────
// Cargar stats del jugador para mostrar en pantalla
// ────────────────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT xp, nivel, semillas FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['xp' => 0, 'nivel' => 1, 'semillas' => 0];

// Top 5 mejores jugadores (ranking)
$ranking = $pdo->query("
    SELECT COALESCE(nombre_entidad, username, 'Anónimo') AS nombre, xp,
           RANK() OVER (ORDER BY xp DESC) AS pos
    FROM usuarios WHERE user_role = 'jugador'
    ORDER BY xp DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tetris Sostenible – Misión Desperdicio Cero</title>
    <link rel="icon" type="image/png" href="icons/market_main.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:      #0a1a0f;
            --surface: rgba(15,30,18,0.92);
            --border:  rgba(74,222,128,0.12);
            --green:   #4ade80;
            --orange:  #fb923c;
            --yellow:  #facc15;
            --purple:  #a78bfa;
            --red:     #f87171;
            --cyan:    #22d3ee;
            --blue:    #60a5fa;
            --text:    #e5e7eb;
            --muted:   #6b7280;
            --head:    #f0fdf4;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
            background: var(--bg);
            color: var(--text);
            display: flex;
            flex-direction: column;
        }

        /* BG effects */
        .orb { position: fixed; border-radius: 50%; pointer-events: none; z-index: 0; }
        .orb-1 { width: 500px; height: 500px; background: radial-gradient(circle, rgba(167,139,250,0.08) 0%, transparent 70%); top: -100px; left: -150px; filter: blur(80px); }
        .orb-2 { width: 400px; height: 400px; background: radial-gradient(circle, rgba(34,211,238,0.06) 0%, transparent 70%); bottom: -80px; right: -100px; filter: blur(80px); }

        /* Navbar */
        .navbar {
            position: relative; z-index: 10;
            display: flex; align-items: center; justify-content: space-between;
            padding: 1rem 2rem;
            border-bottom: 1px solid var(--border);
            background: rgba(10,26,15,0.8);
            backdrop-filter: blur(12px);
        }
        .nav-brand { display: flex; align-items: center; gap: 10px; text-decoration: none; color: var(--head); }
        .nav-brand img { width: 28px; height: 28px; }
        .nav-brand span { font-family: 'DM Serif Display', serif; font-size: 1.05rem; }
        .nav-right { display: flex; align-items: center; gap: 12px; }
        .player-info { font-size: 13px; color: var(--muted); }
        .player-info strong { color: var(--purple); }
        .btn-back {
            padding: 7px 16px; border-radius: 100px; font-size: 12px; font-weight: 500;
            letter-spacing: .06em; text-transform: uppercase; cursor: pointer;
            border: 1px solid rgba(167,139,250,0.3); background: rgba(167,139,250,0.08);
            color: var(--purple); text-decoration: none; transition: all .2s;
        }
        .btn-back:hover { background: rgba(167,139,250,0.15); }

        /* Layout principal */
        .game-layout {
            position: relative; z-index: 2;
            display: flex; gap: 24px; padding: 24px;
            max-width: 1100px; margin: 0 auto; width: 100%;
            flex: 1; align-items: flex-start;
        }

        /* Panel izquierdo */
        .side-panel {
            display: flex; flex-direction: column; gap: 16px;
            width: 200px; flex-shrink: 0;
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 16px;
        }
        .card-title {
            font-size: 11px; font-weight: 500; text-transform: uppercase;
            letter-spacing: .08em; color: var(--muted); margin-bottom: 12px;
        }

        /* Stats del jugador */
        .stat-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; font-size: 13px; }
        .stat-row:last-child { margin-bottom: 0; }
        .stat-val { font-weight: 500; color: var(--purple); font-size: 15px; }
        .stat-val.green { color: var(--green); }
        .stat-val.yellow { color: var(--yellow); }

        /* Preview siguiente pieza */
        #next-canvas {
            display: block; margin: 0 auto;
            border: 1px solid var(--border); border-radius: 8px;
            background: rgba(0,0,0,0.3);
        }

        /* Ranking lateral */
        .rank-item {
            display: flex; align-items: center; gap: 8px;
            padding: 6px 0; border-bottom: 1px solid rgba(74,222,128,0.06);
            font-size: 12px;
        }
        .rank-item:last-child { border-bottom: none; }
        .rank-num {
            width: 20px; height: 20px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 11px; font-weight: 500; flex-shrink: 0;
        }
        .r1 { background: rgba(250,204,21,0.2); color: var(--yellow); border: 1px solid rgba(250,204,21,0.4); }
        .r2 { background: rgba(156,163,175,0.15); color: #9ca3af; border: 1px solid rgba(156,163,175,0.3); }
        .r3 { background: rgba(251,146,60,0.15); color: var(--orange); border: 1px solid rgba(251,146,60,0.3); }
        .rX { background: rgba(107,114,128,0.1); color: var(--muted); border: 1px solid rgba(107,114,128,0.2); }
        .rank-name { flex: 1; color: var(--text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .rank-xp { color: var(--purple); font-weight: 500; font-size: 11px; }

        /* Canvas del juego */
        .game-center { display: flex; flex-direction: column; align-items: center; gap: 16px; flex: 1; }

        .game-title {
            font-family: 'DM Serif Display', serif;
            font-size: 1.6rem; color: var(--head);
            text-align: center;
        }
        .game-title span { color: var(--purple); }

        #game-canvas {
            border: 2px solid rgba(167,139,250,0.3);
            border-radius: 4px;
            background: rgba(0,0,0,0.5);
            box-shadow: 0 0 40px rgba(167,139,250,0.1);
            display: block;
        }

        /* Controles */
        .controls-info {
            display: flex; gap: 16px; flex-wrap: wrap; justify-content: center;
            font-size: 11px; color: var(--muted);
        }
        .key-hint { display: flex; align-items: center; gap: 5px; }
        .key {
            background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.12);
            border-radius: 5px; padding: 2px 7px; font-size: 11px; color: var(--text);
            font-family: monospace;
        }

        /* Botones del juego */
        .game-btns { display: flex; gap: 10px; }
        .btn-game {
            padding: 10px 24px; border-radius: 100px; font-size: 13px; font-weight: 500;
            letter-spacing: .06em; text-transform: uppercase; cursor: pointer;
            border: none; transition: all .2s;
        }
        .btn-start { background: var(--purple); color: white; }
        .btn-start:hover { background: #8b5cf6; }
        .btn-pause { background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.12); color: var(--text); }
        .btn-pause:hover { background: rgba(255,255,255,0.14); }

        /* Overlay juego */
        #game-overlay {
            position: absolute; inset: 0;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            background: rgba(10,26,15,0.88); backdrop-filter: blur(4px);
            border-radius: 4px; gap: 12px; text-align: center; padding: 20px;
        }
        #game-overlay h2 { font-family: 'DM Serif Display', serif; font-size: 1.5rem; color: var(--head); }
        #game-overlay p { font-size: 13px; color: var(--muted); max-width: 220px; }
        .overlay-score { font-size: 2rem; font-weight: 500; color: var(--purple); }
        .overlay-rewards { display: flex; gap: 16px; margin: 4px 0; }
        .reward-chip {
            padding: 6px 14px; border-radius: 100px; font-size: 12px; font-weight: 500;
        }
        .chip-xp { background: rgba(167,139,250,0.15); border: 1px solid rgba(167,139,250,0.3); color: var(--purple); }
        .chip-seed { background: rgba(74,222,128,0.12); border: 1px solid rgba(74,222,128,0.25); color: var(--green); }

        .canvas-wrap { position: relative; }

        /* Mobile controls */
        .mobile-controls {
            display: none;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 8px; width: 180px;
        }
        .mob-btn {
            padding: 14px; background: rgba(167,139,250,0.1); border: 1px solid rgba(167,139,250,0.25);
            border-radius: 12px; color: var(--purple); font-size: 18px; cursor: pointer;
            text-align: center; user-select: none; transition: background .15s;
        }
        .mob-btn:active { background: rgba(167,139,250,0.25); }
        .mob-btn-wide { grid-column: span 3; }
        .mob-btn-rotate { grid-column: 2; }

        @media (max-width: 700px) {
            .mobile-controls { display: grid; }
            .controls-info { display: none; }
            .game-layout { flex-direction: column; align-items: center; padding: 12px; }
            .side-panel { width: 100%; flex-direction: row; flex-wrap: wrap; }
            .side-panel .card { flex: 1; min-width: 140px; }
        }
    </style>
</head>
<body>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>

<!-- Navbar -->
<nav class="navbar">
    <a class="nav-brand" href="main.php">
        <img src="icons/market_main.png" alt="Logo">
        <span>Misión Desperdicio Cero</span>
    </a>
    <div class="nav-right">
        <span class="player-info">Jugando como <strong><?php echo htmlspecialchars($fullname); ?></strong></span>
        <a class="btn-back" href="main.php">← Volver</a>
    </div>
</nav>

<!-- Layout -->
<div class="game-layout">

    <!-- Panel izquierdo -->
    <div class="side-panel">

        <!-- Stats del jugador -->
        <div class="card">
            <div class="card-title">Tu perfil</div>
            <div class="stat-row">
                <span>Nivel</span>
                <span class="stat-val"><?php echo $stats['nivel']; ?></span>
            </div>
            <div class="stat-row">
                <span>XP Total</span>
                <span class="stat-val green" id="xp-display"><?php echo number_format($stats['xp']); ?></span>
            </div>
            <div class="stat-row">
                <span>Semillas</span>
                <span class="stat-val yellow" id="seeds-display"><?php echo $stats['semillas']; ?></span>
            </div>
        </div>

        <!-- Puntuación actual -->
        <div class="card">
            <div class="card-title">Partida actual</div>
            <div class="stat-row">
                <span>Puntos</span>
                <span class="stat-val" id="score-ui">0</span>
            </div>
            <div class="stat-row">
                <span>Líneas</span>
                <span class="stat-val green" id="lines-ui">0</span>
            </div>
            <div class="stat-row">
                <span>Nivel</span>
                <span class="stat-val yellow" id="level-ui">1</span>
            </div>
        </div>

        <!-- Siguiente pieza -->
        <div class="card">
            <div class="card-title">Siguiente</div>
            <canvas id="next-canvas" width="100" height="100"></canvas>
        </div>

        <!-- Ranking -->
        <div class="card">
            <div class="card-title">🏆 Ranking</div>
            <?php foreach ($ranking as $r):
                $pos = (int)$r['pos'];
                $css = match($pos) { 1=>'r1', 2=>'r2', 3=>'r3', default=>'rX' };
            ?>
            <div class="rank-item">
                <div class="rank-num <?php echo $css; ?>"><?php echo $pos; ?></div>
                <div class="rank-name"><?php echo htmlspecialchars($r['nombre']); ?></div>
                <div class="rank-xp"><?php echo number_format((int)$r['xp']); ?></div>
            </div>
            <?php endforeach; ?>
        </div>

    </div>

    <!-- Centro: Juego -->
    <div class="game-center">
        <div class="game-title">Tetris <span>Sostenible</span></div>

        <div class="canvas-wrap">
            <canvas id="game-canvas" width="240" height="480"></canvas>
            <div id="game-overlay">
                <div style="font-size:2.5rem;">🎮</div>
                <h2>Tetris Sostenible</h2>
                <p>Completa líneas para ganar XP y semillas para tu perfil.</p>
                <div style="font-size:12px;color:var(--purple);margin:4px 0;">
                    10 pts → 1 XP &nbsp;·&nbsp; 4 líneas → 1 🌱
                </div>
                <button class="btn-game btn-start" onclick="startGame()">▶ Jugar</button>
            </div>
        </div>

        <div class="game-btns">
            <button class="btn-game btn-start" onclick="startGame()">▶ Nueva partida</button>
            <button class="btn-game btn-pause" onclick="togglePause()">⏸ Pausa</button>
        </div>

        <div class="controls-info">
            <div class="key-hint"><span class="key">←→</span> Mover</div>
            <div class="key-hint"><span class="key">↑</span> Rotar</div>
            <div class="key-hint"><span class="key">↓</span> Bajar</div>
            <div class="key-hint"><span class="key">Espacio</span> Caída rápida</div>
            <div class="key-hint"><span class="key">P</span> Pausa</div>
        </div>

        <!-- Controles móvil -->
        <div class="mobile-controls" id="mob-ctrl">
            <div></div>
            <div class="mob-btn mob-btn-rotate" id="mb-rot">↻</div>
            <div></div>
            <div class="mob-btn" id="mb-left">←</div>
            <div class="mob-btn" id="mb-down">↓</div>
            <div class="mob-btn" id="mb-right">→</div>
            <div class="mob-btn mob-btn-wide" id="mb-drop">▼ Drop</div>
        </div>
    </div>

</div>

<script>
// ═══════════════════════════════════════════════════
//  TETRIS ENGINE
// ═══════════════════════════════════════════════════
const COLS = 12, ROWS = 20, BLOCK = 24;
const canvas    = document.getElementById('game-canvas');
const ctx       = canvas.getContext('2d');
const nextCvs   = document.getElementById('next-canvas');
const nextCtx   = nextCvs.getContext('2d');
const overlay   = document.getElementById('game-overlay');

// Resize canvas to match COLS/ROWS
canvas.width  = COLS * BLOCK;
canvas.height = ROWS * BLOCK;

// Tetromino shapes
const SHAPES = {
    I: [[1,1,1,1]],
    O: [[1,1],[1,1]],
    T: [[0,1,0],[1,1,1]],
    S: [[0,1,1],[1,1,0]],
    Z: [[1,1,0],[0,1,1]],
    J: [[1,0,0],[1,1,1]],
    L: [[0,0,1],[1,1,1]],
};

const COLORS = {
    I: '#22d3ee',   // cyan
    O: '#facc15',   // yellow
    T: '#a78bfa',   // purple
    S: '#4ade80',   // green
    Z: '#f87171',   // red
    J: '#60a5fa',   // blue
    L: '#fb923c',   // orange
};

const KEYS = Object.keys(SHAPES);

let board, currentPiece, nextPiece, score, lines, level, gameLoop, isPaused, isGameOver, lockTimeout;

function emptyBoard() {
    return Array.from({length: ROWS}, () => Array(COLS).fill(0));
}

function randomPiece() {
    const k = KEYS[Math.floor(Math.random() * KEYS.length)];
    return {
        type: k,
        color: COLORS[k],
        shape: SHAPES[k].map(r => [...r]),
        x: Math.floor(COLS / 2) - Math.floor(SHAPES[k][0].length / 2),
        y: 0,
    };
}

function rotate(shape) {
    return shape[0].map((_, i) => shape.map(row => row[i]).reverse());
}

function valid(piece, dx=0, dy=0, shape=null) {
    const s = shape || piece.shape;
    for (let r = 0; r < s.length; r++) {
        for (let c = 0; c < s[r].length; c++) {
            if (!s[r][c]) continue;
            const nx = piece.x + c + dx;
            const ny = piece.y + r + dy;
            if (nx < 0 || nx >= COLS || ny >= ROWS) return false;
            if (ny >= 0 && board[ny][nx]) return false;
        }
    }
    return true;
}

function placePiece() {
    currentPiece.shape.forEach((row, r) => {
        row.forEach((v, c) => {
            if (v) {
                const ny = currentPiece.y + r;
                const nx = currentPiece.x + c;
                if (ny >= 0) board[ny][nx] = currentPiece.color;
            }
        });
    });
    clearLines();
    currentPiece = nextPiece;
    nextPiece    = randomPiece();
    drawNext();
    if (!valid(currentPiece)) endGame();
}

function clearLines() {
    let cleared = 0;
    for (let r = ROWS - 1; r >= 0; r--) {
        if (board[r].every(c => c !== 0)) {
            board.splice(r, 1);
            board.unshift(Array(COLS).fill(0));
            cleared++;
            r++;
        }
    }
    if (cleared > 0) {
        const pts = [0, 100, 300, 500, 800][cleared] * level;
        score += pts;
        lines += cleared;
        level = Math.floor(lines / 10) + 1;
        document.getElementById('score-ui').textContent = score.toLocaleString();
        document.getElementById('lines-ui').textContent = lines;
        document.getElementById('level-ui').textContent = level;
    }
}

function drop() {
    if (valid(currentPiece, 0, 1)) {
        currentPiece.y++;
    } else {
        placePiece();
    }
}

function hardDrop() {
    while (valid(currentPiece, 0, 1)) currentPiece.y++;
    placePiece();
}

// ── Draw ──────────────────────────────────────────
function drawBlock(context, x, y, color, size=BLOCK) {
    context.fillStyle = color;
    context.fillRect(x * size + 1, y * size + 1, size - 2, size - 2);
    // highlight
    context.fillStyle = 'rgba(255,255,255,0.15)';
    context.fillRect(x * size + 1, y * size + 1, size - 2, 4);
    context.fillStyle = 'rgba(0,0,0,0.2)';
    context.fillRect(x * size + 1, y * size + size - 5, size - 2, 4);
}

function drawGhost() {
    let ghost = { ...currentPiece, shape: currentPiece.shape.map(r => [...r]) };
    while (valid(ghost, 0, 1)) ghost.y++;
    ghost.shape.forEach((row, r) => {
        row.forEach((v, c) => {
            if (v) {
                ctx.strokeStyle = 'rgba(167,139,250,0.3)';
                ctx.lineWidth = 1;
                ctx.strokeRect(
                    (ghost.x + c) * BLOCK + 1,
                    (ghost.y + r) * BLOCK + 1,
                    BLOCK - 2, BLOCK - 2
                );
            }
        });
    });
}

function draw() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    // Grid
    ctx.strokeStyle = 'rgba(74,222,128,0.05)';
    ctx.lineWidth = 0.5;
    for (let r = 0; r <= ROWS; r++) {
        ctx.beginPath(); ctx.moveTo(0, r*BLOCK); ctx.lineTo(canvas.width, r*BLOCK); ctx.stroke();
    }
    for (let c = 0; c <= COLS; c++) {
        ctx.beginPath(); ctx.moveTo(c*BLOCK, 0); ctx.lineTo(c*BLOCK, canvas.height); ctx.stroke();
    }

    // Board
    board.forEach((row, r) => {
        row.forEach((color, c) => {
            if (color) drawBlock(ctx, c, r, color);
        });
    });

    // Ghost
    if (currentPiece) drawGhost();

    // Current piece
    if (currentPiece) {
        currentPiece.shape.forEach((row, r) => {
            row.forEach((v, c) => {
                if (v) drawBlock(ctx, currentPiece.x + c, currentPiece.y + r, currentPiece.color);
            });
        });
    }
}

function drawNext() {
    nextCtx.clearRect(0, 0, nextCvs.width, nextCvs.height);
    if (!nextPiece) return;
    const s = 20;
    const ox = Math.floor((nextCvs.width  / s - nextPiece.shape[0].length) / 2);
    const oy = Math.floor((nextCvs.height / s - nextPiece.shape.length) / 2);
    nextPiece.shape.forEach((row, r) => {
        row.forEach((v, c) => {
            if (v) {
                nextCtx.fillStyle = nextPiece.color;
                nextCtx.fillRect((ox+c)*s+1, (oy+r)*s+1, s-2, s-2);
            }
        });
    });
}

// ── Game state ────────────────────────────────────
function startGame() {
    board        = emptyBoard();
    score        = 0; lines = 0; level = 1;
    isPaused     = false; isGameOver = false;
    currentPiece = randomPiece();
    nextPiece    = randomPiece();
    drawNext();
    document.getElementById('score-ui').textContent = '0';
    document.getElementById('lines-ui').textContent = '0';
    document.getElementById('level-ui').textContent = '1';
    overlay.style.display = 'none';
    clearInterval(gameLoop);
    gameLoop = setInterval(tick, 800);
}

function tick() {
    if (isPaused || isGameOver) return;
    drop();
    draw();
}

function togglePause() {
    if (isGameOver) return;
    isPaused = !isPaused;
    if (isPaused) {
        overlay.innerHTML = `
            <div style="font-size:2rem;">⏸</div>
            <h2>Pausa</h2>
            <p>Tu juego está guardado.</p>
            <button class="btn-game btn-start" onclick="resumeGame()">▶ Continuar</button>`;
        overlay.style.display = 'flex';
    } else {
        overlay.style.display = 'none';
    }
}

function resumeGame() {
    isPaused = false;
    overlay.style.display = 'none';
}

function endGame() {
    isGameOver = true;
    clearInterval(gameLoop);

    const xpEarned   = Math.min(500, Math.floor(score / 10));
    const seedsEarned = Math.min(20, Math.floor(lines / 4));

    // Show overlay with score
    overlay.innerHTML = `
        <div style="font-size:2rem;">💀</div>
        <h2>¡Juego terminado!</h2>
        <div class="overlay-score">${score.toLocaleString()} pts</div>
        <div style="font-size:12px;color:var(--muted);">${lines} líneas · Nivel ${level}</div>
        <div class="overlay-rewards">
            <div class="reward-chip chip-xp">+${xpEarned} XP</div>
            <div class="reward-chip chip-seed">+${seedsEarned} 🌱</div>
        </div>
        <p>Guardando tu progreso...</p>
        <button class="btn-game btn-start" onclick="startGame()" id="play-again-btn" disabled style="opacity:.5;">Cargando...</button>`;
    overlay.style.display = 'flex';

    // Save to DB
    const fd = new FormData();
    fd.append('action', 'save_score');
    fd.append('score',  score);
    fd.append('lineas', lines);

    fetch('tetris.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                // Update UI stats
                document.getElementById('xp-display').textContent   = Number(data.nuevo_xp).toLocaleString();
                document.getElementById('seeds-display').textContent = data.nuevo_semillas ?? '—';

                let msg = `<span style="color:var(--green);">✓ +${data.xp_ganado} XP y +${data.semillas_ganadas} semillas guardados</span>`;
                if (data.reto_completado) {
                    msg += `<br><span style="color:var(--yellow);">🏆 ¡Reto completado!</span>`;
                }
                overlay.querySelector('p').innerHTML = msg;
            } else {
                overlay.querySelector('p').textContent = 'Error al guardar (revisa conexión)';
            }
            const btn = document.getElementById('play-again-btn');
            btn.disabled = false;
            btn.style.opacity = '1';
            btn.textContent = '▶ Jugar de nuevo';
        })
        .catch(() => {
            overlay.querySelector('p').textContent = 'Sin conexión – puntos no guardados';
            const btn = document.getElementById('play-again-btn');
            btn.disabled = false; btn.style.opacity = '1';
            btn.textContent = '▶ Jugar de nuevo';
        });
}

// ── Controls ──────────────────────────────────────
document.addEventListener('keydown', e => {
    if (isGameOver || !currentPiece) return;
    switch(e.key) {
        case 'ArrowLeft':  if(valid(currentPiece,-1)) currentPiece.x--; break;
        case 'ArrowRight': if(valid(currentPiece, 1)) currentPiece.x++; break;
        case 'ArrowDown':  drop(); break;
        case 'ArrowUp': {
            const r = rotate(currentPiece.shape);
            if (valid(currentPiece, 0, 0, r)) currentPiece.shape = r;
            break;
        }
        case ' ': e.preventDefault(); hardDrop(); break;
        case 'p': case 'P': togglePause(); break;
    }
    draw();
});

// Mobile controls
document.getElementById('mb-left').addEventListener('click', () => {
    if (currentPiece && valid(currentPiece,-1)) { currentPiece.x--; draw(); }
});
document.getElementById('mb-right').addEventListener('click', () => {
    if (currentPiece && valid(currentPiece, 1)) { currentPiece.x++; draw(); }
});
document.getElementById('mb-down').addEventListener('click', () => { drop(); draw(); });
document.getElementById('mb-drop').addEventListener('click', () => { hardDrop(); draw(); });
document.getElementById('mb-rot').addEventListener('click', () => {
    if (!currentPiece) return;
    const r = rotate(currentPiece.shape);
    if (valid(currentPiece, 0, 0, r)) currentPiece.shape = r;
    draw();
});

// Speed up as level increases
setInterval(() => {
    if (isGameOver || isPaused || !gameLoop) return;
    const spd = Math.max(100, 800 - (level - 1) * 70);
    clearInterval(gameLoop);
    gameLoop = setInterval(tick, spd);
}, 2000);

// Initial draw (empty board for preview)
board = emptyBoard();
draw();
</script>
</body>
</html>
