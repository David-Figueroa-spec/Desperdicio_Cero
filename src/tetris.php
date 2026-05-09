<?php
session_start();
if (!isset($_SESSION['session_user_id'])) { header('Location: signin.html'); exit(); }
if ($_SESSION['session_user_role'] !== 'jugador') { header('Location: main.php'); exit(); }

$user_id  = $_SESSION['session_user_id'];
$fullname = $_SESSION['session_user_fullname'] ?? 'Jugador';
require('../config/database.php');

// ── AJAX: save score ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_score') {
    header('Content-Type: application/json');
    $score  = max(0, (int)($_POST['score']  ?? 0));
    $lineas = max(0, (int)($_POST['lineas'] ?? 0));
    $xp_g   = min(500,  (int)floor($score / 10));
    $sem_g  = min(20,   (int)floor($lineas / 4));
    try {
        $stmt = $pdo->prepare("SELECT xp, nivel, semillas FROM usuarios WHERE id = ?");
        $stmt->execute([$user_id]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);
        $nxp  = (int)$u['xp'] + $xp_g;
        $nsem = (int)$u['semillas'] + $sem_g;
        $nlvl = max(1, (int)floor($nxp / 1000) + 1);
        $pdo->prepare("UPDATE usuarios SET xp=?,semillas=?,nivel=? WHERE id=?")->execute([$nxp,$nsem,$nlvl,$user_id]);
        $rc = $pdo->prepare("SELECT rc.id,rc.progreso,r.meta_valor,r.xp_recompensa,r.semillas_recompensa FROM reto_completados rc JOIN retos r ON r.id=rc.reto_id WHERE rc.jugador_id=? AND rc.completado=FALSE AND r.tipo='juego' LIMIT 1");
        $rc->execute([$user_id]);
        $reto = $rc->fetch(PDO::FETCH_ASSOC);
        $reto_completado = false;
        if ($reto) {
            $np = (float)$reto['progreso'] + $lineas;
            if ($np >= (float)$reto['meta_valor']) {
                $pdo->prepare("UPDATE reto_completados SET progreso=?,completado=TRUE,fecha_completado=NOW() WHERE id=?")->execute([$np,$reto['id']]);
                $nxp += (int)$reto['xp_recompensa']; $nsem += (int)$reto['semillas_recompensa'];
                $pdo->prepare("UPDATE usuarios SET xp=?,semillas=? WHERE id=?")->execute([$nxp,$nsem,$user_id]);
                $reto_completado = true;
            } else {
                $pdo->prepare("UPDATE reto_completados SET progreso=? WHERE id=?")->execute([$np,$reto['id']]);
            }
        }
        echo json_encode(['ok'=>true,'xp_ganado'=>$xp_g,'semillas_ganadas'=>$sem_g,'nuevo_xp'=>$nxp,'nuevo_nivel'=>$nlvl,'nuevo_semillas'=>$nsem,'reto_completado'=>$reto_completado]);
    } catch (PDOException $e) { echo json_encode(['ok'=>false,'error'=>$e->getMessage()]); }
    exit();
}

// ── Cargar datos del jugador ───────────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT xp, nivel, semillas, COALESCE(nombre_entidad,username,'Jugador') AS nombre FROM usuarios WHERE id=?");
$stmt->execute([$user_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['xp'=>0,'nivel'=>1,'semillas'=>0,'nombre'=>$fullname];

$ranking = $pdo->query("SELECT COALESCE(nombre_entidad,username,'Anónimo') AS nombre, xp, RANK() OVER (ORDER BY xp DESC) AS pos FROM usuarios WHERE user_role='jugador' ORDER BY xp DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

function getTitulo(int $n): string {
    if ($n<=3)  return 'Aprendiz Verde';
    if ($n<=6)  return 'Guardián del Sabor';
    if ($n<=10) return 'Héroe Sostenible';
    return 'Leyenda Cero Residuos';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
<title>Tetris Sostenible – Misión Desperdicio Cero</title>
<link rel="icon" type="image/png" href="icons/market_main.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#0d0d18;--surface:#111122;--panel:#161628;
  --border:rgba(120,80,255,.2);--border2:rgba(120,80,255,.08);
  --accent:#7c3aed;--accent2:#a78bfa;--accent3:#c4b5fd;
  --green:#4ade80;--orange:#fb923c;--yellow:#facc15;--red:#f87171;--cyan:#22d3ee;--blue:#60a5fa;
  --text:#e2e8f0;--muted:#64748b;--head:#f8fafc;
}
html,body{height:100%;overflow:hidden}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);display:flex;flex-direction:column;user-select:none;}

.bg-fx{position:fixed;inset:0;pointer-events:none;z-index:0;}
.bg-fx::before{content:'';position:absolute;width:700px;height:700px;border-radius:50%;
  background:radial-gradient(circle,rgba(124,58,237,.1) 0%,transparent 70%);
  top:-200px;left:-200px;filter:blur(100px);}
.bg-fx::after{content:'';position:absolute;width:500px;height:500px;border-radius:50%;
  background:radial-gradient(circle,rgba(34,211,238,.06) 0%,transparent 70%);
  bottom:-100px;right:-80px;filter:blur(80px);}
.scanlines{position:fixed;inset:0;pointer-events:none;z-index:1;
  background:repeating-linear-gradient(0deg,transparent,transparent 2px,rgba(0,0,0,.025) 2px,rgba(0,0,0,.025) 4px);}

/* NAVBAR */
.navbar{position:relative;z-index:50;display:flex;align-items:center;justify-content:space-between;
  padding:.7rem 1.5rem;border-bottom:1px solid var(--border2);
  background:rgba(13,13,24,.95);backdrop-filter:blur(20px);flex-shrink:0;}
.nav-brand{display:flex;align-items:center;gap:8px;text-decoration:none;color:var(--head);}
.nav-brand img{width:24px;height:24px;}
.nav-brand span{font-family:'DM Serif Display',serif;font-size:.95rem;}
.nav-right{display:flex;align-items:center;gap:10px;}
.player-pill{display:flex;align-items:center;gap:6px;padding:4px 12px;border-radius:100px;
  background:rgba(124,58,237,.12);border:1px solid rgba(124,58,237,.28);font-size:11px;}
.player-pill strong{color:var(--accent2);}
.xp-live{color:var(--green);font-weight:700;}
.btn-back{padding:5px 12px;border-radius:100px;font-size:10px;font-weight:700;letter-spacing:.08em;
  text-transform:uppercase;border:1px solid var(--border);background:transparent;
  color:var(--accent2);text-decoration:none;transition:all .2s;}
.btn-back:hover{background:rgba(124,58,237,.15);}

/* LAYOUT */
.game-wrap{position:relative;z-index:2;display:flex;align-items:flex-start;justify-content:center;
  gap:14px;padding:10px 14px;flex:1;min-height:0;}

/* PANELS */
.side{display:flex;flex-direction:column;gap:10px;width:150px;flex-shrink:0;}
.panel{background:var(--panel);border:1px solid var(--border2);border-radius:14px;padding:12px;position:relative;overflow:hidden;}
.panel::before{content:'';position:absolute;top:0;left:0;right:0;height:1px;
  background:linear-gradient(90deg,transparent,var(--accent2),transparent);opacity:.5;}
.ptitle{font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:var(--muted);margin-bottom:8px;}

.sr{display:flex;justify-content:space-between;align-items:baseline;margin-bottom:5px;font-size:11px;}
.sr:last-child{margin-bottom:0;}
.sv{font-weight:700;font-size:15px;font-variant-numeric:tabular-nums;color:var(--accent3);}
.sv.g{color:var(--green);}.sv.y{color:var(--yellow);}.sv.o{color:var(--orange);}

.mini-cvs{display:block;margin:0 auto;image-rendering:pixelated;}

.rrow{display:flex;align-items:center;gap:6px;padding:4px 0;
  border-bottom:1px solid rgba(255,255,255,.04);font-size:10px;}
.rrow:last-child{border:none;}
.rn{width:17px;height:17px;border-radius:50%;display:flex;align-items:center;justify-content:center;
  font-size:9px;font-weight:700;flex-shrink:0;}
.rn1{background:rgba(250,204,21,.2);color:var(--yellow);}
.rn2{background:rgba(200,200,200,.1);color:#94a3b8;}
.rn3{background:rgba(251,146,60,.15);color:var(--orange);}
.rnx{background:rgba(100,116,139,.08);color:var(--muted);}
.rname{flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.rxp{color:var(--accent2);font-weight:700;font-size:9px;}

/* CANVAS */
.canvas-area{display:flex;flex-direction:column;align-items:center;gap:8px;flex-shrink:0;}
.cvs-wrap{position:relative;}
#gc{border:1px solid rgba(124,58,237,.5);border-radius:3px;background:#06060f;
  box-shadow:0 0 0 1px rgba(124,58,237,.08),0 0 50px rgba(124,58,237,.18),inset 0 0 30px rgba(0,0,0,.6);
  image-rendering:pixelated;display:block;}

/* combo popup */
.combo-pop{position:absolute;top:6px;left:50%;transform:translateX(-50%);
  background:linear-gradient(135deg,#7c3aed,#a855f7);color:#fff;
  padding:4px 14px;border-radius:20px;font-size:12px;font-weight:800;
  white-space:nowrap;opacity:0;transition:opacity .25s;pointer-events:none;z-index:30;
  text-shadow:0 1px 4px rgba(0,0,0,.4);}
.combo-pop.show{opacity:1;}

/* OVERLAY */
#ovl{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;
  justify-content:center;background:rgba(6,6,15,.93);backdrop-filter:blur(10px);
  border-radius:3px;gap:10px;padding:22px;text-align:center;z-index:20;}
#ovl h2{font-family:'DM Serif Display',serif;font-size:1.5rem;color:var(--head);}
#ovl .sub{font-size:11px;color:var(--muted);max-width:190px;line-height:1.5;}
.big-score{font-size:2.6rem;font-weight:800;color:var(--accent2);
  font-variant-numeric:tabular-nums;letter-spacing:-.03em;}
.chip-row{display:flex;gap:8px;justify-content:center;flex-wrap:wrap;}
.chip{padding:4px 10px;border-radius:100px;font-size:10px;font-weight:700;}
.chip-xp{background:rgba(167,139,250,.15);border:1px solid rgba(167,139,250,.3);color:var(--accent2);}
.chip-s{background:rgba(74,222,128,.1);border:1px solid rgba(74,222,128,.25);color:var(--green);}
.chip-c{background:rgba(250,204,21,.12);border:1px solid rgba(250,204,21,.3);color:var(--yellow);}

/* BUTTONS */
.ctrl-row{display:flex;gap:8px;}
.btn{padding:7px 16px;border-radius:100px;font-size:10px;font-weight:800;letter-spacing:.08em;
  text-transform:uppercase;cursor:pointer;border:none;transition:all .2s;}
.btn-p{background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;box-shadow:0 4px 14px rgba(124,58,237,.4);}
.btn-p:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(124,58,237,.5);}
.btn-g{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);color:var(--text);}
.btn-g:hover{background:rgba(255,255,255,.1);}

/* KEYS */
.keys{display:flex;gap:8px;flex-wrap:wrap;justify-content:center;}
.krow{display:flex;align-items:center;gap:3px;font-size:9px;color:var(--muted);}
.k{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);border-radius:3px;
  padding:1px 5px;font-family:monospace;font-size:9px;color:var(--text);}

/* DPAD mobile */
.dpad{display:none;flex-direction:column;align-items:center;gap:4px;}
.drow{display:flex;gap:4px;}
.dp{width:50px;height:50px;border-radius:12px;background:rgba(124,58,237,.1);
  border:1px solid rgba(124,58,237,.25);color:var(--accent2);font-size:18px;
  display:flex;align-items:center;justify-content:center;cursor:pointer;
  -webkit-tap-highlight-color:transparent;transition:background .1s;}
.dp:active{background:rgba(124,58,237,.28);}
.dp-w{width:108px;}
.dp-h{font-size:10px;font-weight:800;color:var(--yellow);}

@media(max-width:700px){
  .side{display:none;}.dpad{display:flex;}.keys{display:none;}
  .game-wrap{padding:6px;}
}
</style>
</head>
<body>
<div class="bg-fx"></div>
<div class="scanlines"></div>

<nav class="navbar">
  <a class="nav-brand" href="main.php">
    <img src="icons/market_main.png" alt="">
    <span>Misión Desperdicio Cero</span>
  </a>
  <div class="nav-right">
    <div class="player-pill">
      <strong><?php echo htmlspecialchars($stats['nombre']); ?></strong>
      &nbsp;·&nbsp; Niv.<?php echo $stats['nivel']; ?>
      &nbsp;·&nbsp; <span class="xp-live" id="nav-xp"><?php echo number_format($stats['xp']); ?></span>&nbsp;XP
    </div>
    <a class="btn-back" href="main.php">← Volver</a>
  </div>
</nav>

<div class="game-wrap">

  <!-- LEFT -->
  <div class="side">
    <div class="panel">
      <div class="ptitle">HOLD <span style="opacity:.5">[C]</span></div>
      <canvas class="mini-cvs" id="hold-cvs" width="80" height="80"></canvas>
    </div>
    <div class="panel">
      <div class="ptitle">PARTIDA</div>
      <div class="sr"><span>Score</span><span class="sv" id="ui-score">0</span></div>
      <div class="sr"><span>Líneas</span><span class="sv g" id="ui-lines">0</span></div>
      <div class="sr"><span>Nivel</span><span class="sv y" id="ui-level">1</span></div>
      <div class="sr"><span>Combo</span><span class="sv o" id="ui-combo">—</span></div>
    </div>
    <div class="panel">
      <div class="ptitle">PERFIL</div>
      <div class="sr"><span>XP Total</span><span class="sv" id="ui-xp"><?php echo number_format($stats['xp']); ?></span></div>
      <div class="sr"><span>Semillas</span><span class="sv g" id="ui-seeds"><?php echo $stats['semillas']; ?> 🌱</span></div>
      <div class="sr"><span>Nivel</span><span class="sv y"><?php echo $stats['nivel']; ?></span></div>
      <div style="font-size:9px;color:var(--muted);margin-top:4px;"><?php echo getTitulo($stats['nivel']); ?></div>
    </div>
  </div>

  <!-- CANVAS -->
  <div class="canvas-area">
    <div class="cvs-wrap">
      <canvas id="gc" width="240" height="480"></canvas>
      <div class="combo-pop" id="combo-pop"></div>
      <div id="ovl">
        <div style="font-size:3rem">🎮</div>
        <h2>Tetris Sostenible</h2>
        <p class="sub">Completa líneas para ganar XP y semillas reales en tu perfil.</p>
        <div style="font-size:10px;color:var(--accent2);display:flex;gap:12px;flex-wrap:wrap;justify-content:center;">
          <span>10 pts → 1 XP</span><span>4 líneas → 1 🌱</span><span>COMBO multiplica!</span>
        </div>
        <button class="btn btn-p" onclick="startGame()">▶ JUGAR</button>
      </div>
    </div>

    <div class="ctrl-row">
      <button class="btn btn-p" onclick="startGame()">▶ Nueva</button>
      <button class="btn btn-g" onclick="togglePause()" id="pbtn">⏸ Pausa</button>
    </div>

    <div class="keys">
      <div class="krow"><span class="k">←→</span>Mover</div>
      <div class="krow"><span class="k">↑/X</span>Rotar CW</div>
      <div class="krow"><span class="k">Z</span>Rotar CCW</div>
      <div class="krow"><span class="k">↓</span>Soft drop</div>
      <div class="krow"><span class="k">Esp</span>Hard drop</div>
      <div class="krow"><span class="k">C</span>Hold</div>
      <div class="krow"><span class="k">P</span>Pausa</div>
    </div>

    <div class="dpad" id="dpad">
      <div class="drow">
        <div class="dp dp-h" id="dp-hold">HOLD</div>
        <div class="dp" id="dp-ccw">↺</div>
        <div class="dp" id="dp-cw">↻</div>
      </div>
      <div class="drow">
        <div class="dp" id="dp-l">←</div>
        <div class="dp" id="dp-d">↓</div>
        <div class="dp" id="dp-r">→</div>
      </div>
      <div class="drow">
        <div class="dp dp-w" id="dp-hd">▼ HARD DROP</div>
      </div>
    </div>
  </div>

  <!-- RIGHT -->
  <div class="side">
    <div class="panel">
      <div class="ptitle">SIGUIENTE</div>
      <canvas class="mini-cvs" id="next-cvs" width="80" height="240"></canvas>
    </div>
    <div class="panel">
      <div class="ptitle">🏆 RANKING</div>
      <?php foreach($ranking as $r):
        $p=(int)$r['pos'];
        $cls=match($p){1=>'rn1',2=>'rn2',3=>'rn3',default=>'rnx'};
        $yo=($r['nombre']===$stats['nombre']);
      ?>
      <div class="rrow" <?php if($yo):?>style="background:rgba(124,58,237,.07);border-radius:6px;padding:4px 5px;"<?php endif;?>>
        <div class="rn <?php echo $cls;?>"><?php echo $p;?></div>
        <div class="rname" <?php if($yo):?>style="color:var(--accent2);"<?php endif;?>><?php echo htmlspecialchars($r['nombre']);?><?php if($yo):?><span style="opacity:.5"> ←</span><?php endif;?></div>
        <div class="rxp"><?php echo number_format((int)$r['xp']);?></div>
      </div>
      <?php endforeach;?>
    </div>
    <div class="panel">
      <div class="ptitle">PUNTOS</div>
      <div style="font-size:10px;color:var(--muted);line-height:2;">
        <div>1 línea → <span style="color:var(--accent2)">100</span></div>
        <div>2 líneas → <span style="color:var(--accent2)">300</span></div>
        <div>3 líneas → <span style="color:var(--accent2)">500</span></div>
        <div>TETRIS → <span style="color:var(--yellow)">800 ✨</span></div>
        <div style="margin-top:4px;">Combo → <span style="color:var(--orange)">×bonus</span></div>
        <div style="margin-top:6px;color:var(--green);">10pts → 1 XP</div>
        <div style="color:var(--green);">4 líneas → 1 🌱</div>
      </div>
    </div>
  </div>

</div>

<script>
// ══════════════════════════════════════════════════════════════════════
//   TETRIS — SRS rotations + Wall kicks + Hold + Next queue (5) + Ghost
//           + Combo + Soft/Hard drop + DAS/ARR + Line flash animation
// ══════════════════════════════════════════════════════════════════════
const COLS=10, ROWS=20, BS=24;
const gc=document.getElementById('gc');
const cx=gc.getContext('2d');
const holdCvs=document.getElementById('hold-cvs');
const hx=holdCvs.getContext('2d');
const nextCvs=document.getElementById('next-cvs');
const nx=nextCvs.getContext('2d');
const ovl=document.getElementById('ovl');
gc.width=COLS*BS; gc.height=ROWS*BS;

// ── Piece definitions (SRS shapes) ─────────────────────────────────────
const PD={
  I:{c:'#22d3ee',
     s:[[[0,0,0,0],[1,1,1,1],[0,0,0,0],[0,0,0,0]],
        [[0,0,1,0],[0,0,1,0],[0,0,1,0],[0,0,1,0]],
        [[0,0,0,0],[0,0,0,0],[1,1,1,1],[0,0,0,0]],
        [[0,1,0,0],[0,1,0,0],[0,1,0,0],[0,1,0,0]]]},
  O:{c:'#facc15',
     s:[[[1,1],[1,1]],[[1,1],[1,1]],[[1,1],[1,1]],[[1,1],[1,1]]]},
  T:{c:'#a78bfa',
     s:[[[0,1,0],[1,1,1],[0,0,0]],[[0,1,0],[0,1,1],[0,1,0]],[[0,0,0],[1,1,1],[0,1,0]],[[0,1,0],[1,1,0],[0,1,0]]]},
  S:{c:'#4ade80',
     s:[[[0,1,1],[1,1,0],[0,0,0]],[[0,1,0],[0,1,1],[0,0,1]],[[0,0,0],[0,1,1],[1,1,0]],[[1,0,0],[1,1,0],[0,1,0]]]},
  Z:{c:'#f87171',
     s:[[[1,1,0],[0,1,1],[0,0,0]],[[0,0,1],[0,1,1],[0,1,0]],[[0,0,0],[1,1,0],[0,1,1]],[[0,1,0],[1,1,0],[1,0,0]]]},
  J:{c:'#60a5fa',
     s:[[[1,0,0],[1,1,1],[0,0,0]],[[0,1,1],[0,1,0],[0,1,0]],[[0,0,0],[1,1,1],[0,0,1]],[[0,1,0],[0,1,0],[1,1,0]]]},
  L:{c:'#fb923c',
     s:[[[0,0,1],[1,1,1],[0,0,0]],[[0,1,0],[0,1,0],[0,1,1]],[[0,0,0],[1,1,1],[1,0,0]],[[1,1,0],[0,1,0],[0,1,0]]]}
};
const PK=Object.keys(PD);

// SRS wall kick tables
const WK={
  '0>1':[[0,0],[-1,0],[-1,1],[0,-2],[-1,-2]],
  '1>0':[[0,0],[1,0],[1,-1],[0,2],[1,2]],
  '1>2':[[0,0],[1,0],[1,-1],[0,2],[1,2]],
  '2>1':[[0,0],[-1,0],[-1,1],[0,-2],[-1,-2]],
  '2>3':[[0,0],[1,0],[1,1],[0,-2],[1,-2]],
  '3>2':[[0,0],[-1,0],[-1,-1],[0,2],[-1,2]],
  '3>0':[[0,0],[-1,0],[-1,-1],[0,2],[-1,2]],
  '0>3':[[0,0],[1,0],[1,1],[0,-2],[1,-2]],
};
const WKI={
  '0>1':[[0,0],[-2,0],[1,0],[-2,-1],[1,2]],
  '1>0':[[0,0],[2,0],[-1,0],[2,1],[-1,-2]],
  '1>2':[[0,0],[-1,0],[2,0],[-1,2],[2,-1]],
  '2>1':[[0,0],[1,0],[-2,0],[1,-2],[-2,1]],
  '2>3':[[0,0],[2,0],[-1,0],[2,1],[-1,-2]],
  '3>2':[[0,0],[-2,0],[1,0],[-2,-1],[1,2]],
  '3>0':[[0,0],[1,0],[-2,0],[1,-2],[-2,1]],
  '0>3':[[0,0],[-1,0],[2,0],[-1,2],[2,-1]],
};

// ── State ───────────────────────────────────────────────────────────────
let board,cur,holdKey,canHold,queue,
    score,lines,level,combo,
    gameOver,paused,rafId,lastTs,dropAcc,
    lockTimer,lockMoves,flashRows,maxCombo;
const LOCK=500, MAX_LM=15, SCORE_T=[0,100,300,500,800];

// ── Bag randomiser ──────────────────────────────────────────────────────
let bagBuf=[];
function fillBag(){const b=[...PK];for(let i=b.length-1;i>0;i--){const j=Math.floor(Math.random()*(i+1));[b[i],b[j]]=[b[j],b[i]];}bagBuf=[...bagBuf,...b];}
function nextKey(){if(bagBuf.length<7)fillBag();return bagBuf.shift();}

function refill(){while(queue.length<5)queue.push(nextKey());}
function emptyB(){return Array.from({length:ROWS},()=>Array(COLS).fill(0));}

function makePiece(k){
  const d=PD[k];
  return {k,c:d.c,sh:d.s[0],ss:d.s,rot:0,
    x:Math.floor(COLS/2)-Math.floor(d.s[0][0].length/2),y:0};
}

// ── Collision ────────────────────────────────────────────────────────────
function ok(p,dx=0,dy=0,sh=null){
  const s=sh||p.sh;
  for(let r=0;r<s.length;r++) for(let c=0;c<s[r].length;c++){
    if(!s[r][c])continue;
    const nx2=p.x+c+dx, ny=p.y+r+dy;
    if(nx2<0||nx2>=COLS||ny>=ROWS)return false;
    if(ny>=0&&board[ny][nx2])return false;
  }
  return true;
}

// ── SRS Rotation ─────────────────────────────────────────────────────────
function doRot(dir){
  const nr=(cur.rot+dir+4)%4;
  const ns=cur.ss[nr];
  const key=`${cur.rot}>${nr}`;
  const kicks=cur.k==='I'?(WKI[key]||[[0,0]]):(WK[key]||[[0,0]]);
  for(const [kx,ky] of kicks){
    if(ok(cur,kx,-ky,ns)){
      cur.x+=kx;cur.y-=ky;cur.rot=nr;cur.sh=ns;
      lockMoves++;if(lockMoves>=MAX_LM)lockTimer=0;
      drawAll();return true;
    }
  }
  return false;
}

// ── Hold ─────────────────────────────────────────────────────────────────
function doHold(){
  if(!canHold||gameOver||paused)return;
  canHold=false;
  const tmp=holdKey;
  holdKey=cur.k;
  cur=makePiece(tmp||queue.shift());
  if(!tmp)refill();
  lockTimer=LOCK;lockMoves=0;
  if(!ok(cur))endGame();
  drawHold();drawNext();drawAll();
}

// ── Spawn ─────────────────────────────────────────────────────────────────
function spawn(){
  cur=makePiece(queue.shift());
  refill();canHold=true;lockTimer=LOCK;lockMoves=0;
  if(!ok(cur))endGame();
  drawNext();
}

// ── Place & clear lines ───────────────────────────────────────────────────
function place(){
  cur.sh.forEach((row,r)=>row.forEach((v,c)=>{
    if(v&&cur.y+r>=0)board[cur.y+r][cur.x+c]=cur.c;
  }));
  const clr=[];
  for(let r=ROWS-1;r>=0;r--)if(board[r].every(v=>v!==0))clr.push(r);

  if(clr.length){
    flashRows=[...clr];
    drawAll();
    setTimeout(()=>{
      clr.sort((a,b)=>b-a).forEach(r=>{board.splice(r,1);board.unshift(Array(COLS).fill(0));});
      flashRows=[];
      combo++;maxCombo=Math.max(maxCombo,combo);
      const mult=1+Math.floor(combo*.5);
      const pts=SCORE_T[clr.length]*level*mult;
      score+=pts; lines+=clr.length; level=Math.floor(lines/10)+1;
      updateUI();showCombo(clr.length,combo);
      spawn();
    },100);
  } else {
    combo=0;updateUI();spawn();
  }
}

function showCombo(n,c){
  const el=document.getElementById('combo-pop');
  const lbl=['','SINGLE','DOUBLE','TRIPLE','TETRIS!! ✨'];
  let t=lbl[n]||`${n} LINES`;
  if(c>1)t+=` — ${c}× COMBO 🔥`;
  el.textContent=t;el.className='combo-pop show';
  clearTimeout(el._t);el._t=setTimeout(()=>el.className='combo-pop',1400);
}

// ── Ghost Y ───────────────────────────────────────────────────────────────
function ghostY(){let g={...cur,y:cur.y};while(ok(g,0,1))g.y++;return g.y;}

// ── Draw ───────────────────────────────────────────────────────────────────
function block(ctx2,x,y,col,sz=BS,alpha=1){
  ctx2.save();ctx2.globalAlpha=alpha;
  ctx2.fillStyle=col;
  ctx2.fillRect(x*sz+1,y*sz+1,sz-2,sz-2);
  ctx2.fillStyle='rgba(255,255,255,.22)';
  ctx2.fillRect(x*sz+1,y*sz+1,sz-2,3);
  ctx2.fillStyle='rgba(255,255,255,.08)';
  ctx2.fillRect(x*sz+1,y*sz+1,3,sz-2);
  ctx2.fillStyle='rgba(0,0,0,.35)';
  ctx2.fillRect(x*sz+1,y*sz+sz-4,sz-2,3);
  ctx2.restore();
}

function drawAll(){
  cx.clearRect(0,0,gc.width,gc.height);
  // grid lines
  cx.strokeStyle='rgba(120,80,255,.05)';cx.lineWidth=.5;
  for(let r=0;r<=ROWS;r++){cx.beginPath();cx.moveTo(0,r*BS);cx.lineTo(gc.width,r*BS);cx.stroke();}
  for(let c=0;c<=COLS;c++){cx.beginPath();cx.moveTo(c*BS,0);cx.lineTo(c*BS,gc.height);cx.stroke();}
  // board
  board.forEach((row,r)=>row.forEach((col,c)=>{
    if(!col)return;
    if(flashRows.includes(r)){cx.fillStyle='#fff';cx.globalAlpha=.9;cx.fillRect(c*BS,r*BS,BS,BS);cx.globalAlpha=1;}
    else block(cx,c,r,col);
  }));
  if(!cur||gameOver)return;
  // ghost
  const gy=ghostY();
  if(gy!==cur.y){
    cur.sh.forEach((row,r)=>row.forEach((v,c)=>{
      if(!v)return;
      cx.strokeStyle=cur.c;cx.lineWidth=1;cx.globalAlpha=.2;
      cx.strokeRect((cur.x+c)*BS+1,(gy+r)*BS+1,BS-2,BS-2);
      cx.globalAlpha=1;
    }));
  }
  // current piece
  cur.sh.forEach((row,r)=>row.forEach((v,c)=>{
    if(v&&cur.y+r>=0)block(cx,cur.x+c,cur.y+r,cur.c);
  }));
}

function drawHold(){
  hx.clearRect(0,0,holdCvs.width,holdCvs.height);
  if(!holdKey)return;
  const s=PD[holdKey].s[0],sc=16;
  const ox=Math.floor((holdCvs.width/sc-s[0].length)/2);
  const oy=Math.floor((holdCvs.height/sc-s.length)/2);
  s.forEach((row,r)=>row.forEach((v,c)=>{if(v)block(hx,ox+c,oy+r,PD[holdKey].c,sc);}));
}
function drawNext(){
  nx.clearRect(0,0,nextCvs.width,nextCvs.height);
  queue.slice(0,5).forEach((k,i)=>{
    const s=PD[k].s[0],sc=14;
    const ox=Math.floor((nextCvs.width/sc-s[0].length)/2);
    const oy=i*4+Math.floor((4-s.length)/2);
    s.forEach((row,r)=>row.forEach((v,c)=>{if(v)block(nx,ox+c,oy+r,PD[k].c,sc);}));
  });
}

function updateUI(){
  document.getElementById('ui-score').textContent=score.toLocaleString();
  document.getElementById('ui-lines').textContent=lines;
  document.getElementById('ui-level').textContent=level;
  document.getElementById('ui-combo').textContent=combo>0?`${combo}×`:'—';
}

// ── Game loop ──────────────────────────────────────────────────────────────
function spd(){return Math.max(48,1000-((level-1)*85));}

function loop(ts){
  if(gameOver||paused)return;
  const dt=ts-(lastTs||ts);lastTs=ts;
  dropAcc+=dt;
  if(dropAcc>=spd()){
    dropAcc=0;
    if(ok(cur,0,1)){cur.y++;lockTimer=LOCK;lockMoves=0;}
    else{lockTimer-=dt;if(lockTimer<=0)place();}
  }
  drawAll();
  rafId=requestAnimationFrame(loop);
}

// ── Controls ───────────────────────────────────────────────────────────────
let dasDir=0,das=0,arr=0,dasOn=false;
const DAS=133,ARR=10;

document.addEventListener('keydown',e=>{
  if(gameOver){if(e.code==='Space'||e.code==='Enter')startGame();return;}
  if(e.code==='KeyP'||e.code==='Escape'){togglePause();return;}
  if(paused)return;
  switch(e.code){
    case'ArrowLeft': moveH(-1);dasDir=-1;das=0;arr=0;dasOn=true;break;
    case'ArrowRight':moveH(1);dasDir=1;das=0;arr=0;dasOn=true;break;
    case'ArrowDown': softDrop();break;
    case'ArrowUp':case'KeyX':doRot(1);break;
    case'KeyZ':doRot(-1);break;
    case'Space':e.preventDefault();hardDrop();break;
    case'KeyC':doHold();break;
  }
});
document.addEventListener('keyup',e=>{
  if(e.code==='ArrowLeft'||e.code==='ArrowRight')dasOn=false;
});
setInterval(()=>{
  if(!dasOn||gameOver||paused)return;
  das+=16;if(das<DAS)return;
  arr+=16;if(arr<ARR)return;arr=0;
  moveH(dasDir);drawAll();
},16);

function moveH(d){
  if(!cur||!ok(cur,d))return;
  cur.x+=d;lockMoves++;if(lockMoves>=MAX_LM)lockTimer=0;
}
function softDrop(){
  if(!cur)return;
  if(ok(cur,0,1)){cur.y++;dropAcc=0;score+=1;document.getElementById('ui-score').textContent=score.toLocaleString();}
  else{lockTimer=0;}
  drawAll();
}
function hardDrop(){
  if(!cur)return;
  const gy=ghostY();score+=(gy-cur.y)*2;cur.y=gy;place();
}

// ── Mobile D-Pad ───────────────────────────────────────────────────────────
function bindRep(id,fn){
  const el=document.getElementById(id);if(!el)return;
  let iv;
  el.addEventListener('pointerdown',e=>{e.preventDefault();if(gameOver||paused)return;fn();iv=setInterval(fn,80);});
  const stop=()=>clearInterval(iv);
  el.addEventListener('pointerup',stop);el.addEventListener('pointercancel',stop);
}
function bindTap(id,fn){
  const el=document.getElementById(id);if(!el)return;
  el.addEventListener('pointerdown',e=>{e.preventDefault();if(!gameOver&&!paused)fn();});
}
bindRep('dp-l',()=>{moveH(-1);drawAll();});
bindRep('dp-r',()=>{moveH(1);drawAll();});
bindRep('dp-d',()=>softDrop());
bindTap('dp-ccw',()=>doRot(-1));
bindTap('dp-cw',()=>doRot(1));
bindTap('dp-hd',()=>hardDrop());
bindTap('dp-hold',()=>doHold());

// ── Start / Pause / End ────────────────────────────────────────────────────
function startGame(){
  cancelAnimationFrame(rafId);
  board=emptyB();bagBuf=[];queue=[];refill();
  score=0;lines=0;level=1;combo=0;maxCombo=0;
  holdKey=null;canHold=true;gameOver=false;paused=false;
  dropAcc=0;lastTs=null;lockTimer=LOCK;lockMoves=0;flashRows=[];
  ovl.style.display='none';
  spawn();updateUI();drawHold();drawNext();
  rafId=requestAnimationFrame(loop);
}

function togglePause(){
  if(gameOver)return;
  paused=!paused;
  document.getElementById('pbtn').textContent=paused?'▶ Continuar':'⏸ Pausa';
  if(paused){
    ovl.innerHTML=`<div style="font-size:2rem">⏸</div><h2>Pausa</h2><p class="sub">Presiona P para continuar</p><button class="btn btn-p" onclick="togglePause()">▶ CONTINUAR</button>`;
    ovl.style.display='flex';
  } else {
    ovl.style.display='none';lastTs=null;
    rafId=requestAnimationFrame(loop);
  }
}

function endGame(){
  gameOver=true;cancelAnimationFrame(rafId);
  const xpE=Math.min(500,Math.floor(score/10));
  const semE=Math.min(20,Math.floor(lines/4));
  ovl.innerHTML=`
    <div style="font-size:2.5rem">💀</div>
    <h2>Game Over</h2>
    <div class="big-score">${score.toLocaleString()}</div>
    <div style="font-size:11px;color:var(--muted)">${lines} líneas · Nivel ${level} · Combo max ${maxCombo}×</div>
    <div class="chip-row">
      <div class="chip chip-xp">+${xpE} XP</div>
      <div class="chip chip-s">+${semE} 🌱</div>
      ${maxCombo>1?`<div class="chip chip-c">Max combo ${maxCombo}×</div>`:''}
    </div>
    <p id="smsg" style="font-size:11px;color:var(--muted)">Guardando progreso...</p>
    <button class="btn btn-p" id="rbtn" disabled style="opacity:.4" onclick="startGame()">▶ JUGAR DE NUEVO</button>`;
  ovl.style.display='flex';

  const fd=new FormData();
  fd.append('action','save_score');fd.append('score',score);fd.append('lineas',lines);
  fetch('tetris.php',{method:'POST',body:fd})
    .then(r=>r.json())
    .then(d=>{
      if(d.ok){
        document.getElementById('ui-xp').textContent=Number(d.nuevo_xp).toLocaleString();
        document.getElementById('ui-seeds').textContent=(d.nuevo_semillas??'—')+' 🌱';
        document.getElementById('nav-xp').textContent=Number(d.nuevo_xp).toLocaleString();
        let m=`<span style="color:var(--green)">✓ +${d.xp_ganado} XP · +${d.semillas_ganadas} 🌱 guardados</span>`;
        if(d.reto_completado)m+=`<br><span style="color:var(--yellow)">🏆 ¡Reto completado!</span>`;
        document.getElementById('smsg').innerHTML=m;
      } else {
        document.getElementById('smsg').textContent='Error al guardar.';
      }
      const b=document.getElementById('rbtn');b.disabled=false;b.style.opacity='1';
    })
    .catch(()=>{document.getElementById('smsg').textContent='Sin conexión.';
      const b=document.getElementById('rbtn');b.disabled=false;b.style.opacity='1';});
}

// Initial empty render
board=emptyB();drawAll();
</script>
</body>
</html>