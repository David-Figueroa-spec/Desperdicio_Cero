<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soporte - Misión Desperdicio Cero</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
            background: #0a1a0f;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            color: white;
        }

        /* Reutilización de la estética original */
        .orb {
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
        }

        .orb-1 {
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(74,222,128,0.12) 0%, transparent 70%);
            top: -80px;
            left: -100px;
            filter: blur(80px);
        }

        .orb-2 {
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(251,146,60,0.10) 0%, transparent 70%);
            bottom: -60px;
            right: -60px;
            filter: blur(80px);
        }

        .bg-grid {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(74,222,128,0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(74,222,128,0.04) 1px, transparent 1px);
            background-size: 60px 60px;
            pointer-events: none;
        }

        .content {
            position: relative;
            z-index: 2;
            text-align: center;
            padding: 2rem;
            max-width: 900px;
            width: 100%;
            animation: fadeUp 0.8s ease both;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(74,222,128,0.08);
            border: 1px solid rgba(74,222,128,0.18);
            border-radius: 100px;
            padding: 5px 14px;
            font-size: 11px;
            font-weight: 500;
            color: #86efac;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            margin-bottom: 1.5rem;
        }

        h1 {
            font-family: 'DM Serif Display', serif;
            font-size: clamp(2rem, 5vw, 3rem);
            font-weight: 400;
            color: #f0fdf4;
            margin-bottom: 2.5rem;
        }

        /* Contenedor de Recursos */
        .resources-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 3rem;
        }

        .resource-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(240, 253, 244, 0.1);
            border-radius: 24px;
            padding: 30px;
            text-decoration: none;
            color: white;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }

        .resource-card:hover {
            background: rgba(74, 222, 128, 0.05);
            border-color: rgba(74, 222, 128, 0.4);
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.2);
        }

        .resource-card i {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        /* Colores específicos para iconos */
        .fa-file-pdf { color: #fb923c; } /* Naranja */
        .fa-youtube { color: #ff4444; }  /* Rojo Youtube */
        .fa-bolt { color: #4ade80; }     /* Verde */

        .resource-card h3 {
            font-family: 'DM Serif Display', serif;
            font-size: 1.4rem;
            font-weight: 400;
        }

        .resource-card p {
            font-size: 0.9rem;
            color: rgba(240, 253, 244, 0.6);
            line-height: 1.4;
        }

        /* Botón Volver */
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 100px;
            border: 1px solid rgba(240, 253, 244, 0.2);
            color: #d1fae5;
            text-decoration: none;
            font-size: 13px;
            transition: all 0.2s;
        }

        .btn-back:hover {
            background: rgba(240, 253, 244, 0.06);
            border-color: rgba(74, 222, 128, 0.5);
        }
    </style>
</head>
<body>

    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="bg-grid"></div>

    <div class="content">
        <div class="badge">Centro de soporte</div>
        <h1>Documentación y Ayuda</h1>

        <div class="resources-grid">
            <a href="https://drive.google.com/file/d/1ShgbMV8NdSVUROm2_Kxl-BIy_MZnnk7c/view?usp=sharing" target="_blank" class="resource-card">
                <i class="fas fa-file-pdf"></i>
                <h3>Manual PDF</h3>
                <p>Consulta el manual detallado directamente en Google Docs.</p>
            </a>

            <a href="https://www.youtube.com/watch?v=78XwtOaMZrE" target="_blank" class="resource-card">
                <i class="fab fa-youtube"></i>
                <h3>Video Tutorial</h3>
                <p>Aprende de forma visual cómo funciona la plataforma.</p>
            </a>

            <a href="https://drive.google.com/drive/folders/1r2EQ_cfHkBXOCyw6Ln04CtwPBcvMGscm?usp=sharing" class="resource-card">
                <i class="fas fa-bolt"></i>
                <h3>Guía Rápida</h3>
                <p>Pasos clave para empezar a actuar hoy mismo.</p>
            </a>
        </div>

        <a href="index.html" class="btn-back">
            <i class="fas fa-arrow-left"></i> Volver al inicio
        </a>
    </div>

</body>
</html>