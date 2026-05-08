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
            max-width: 1000px;
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

        .resources-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 3rem;
            align-items: start;
        }

        /* Estilo base de tarjetas */
        .resource-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(240, 253, 244, 0.1);
            border-radius: 24px;
            padding: 30px;
            text-decoration: none;
            color: white;
            transition: all 0.4s cubic-bezier(0.23, 1, 0.32, 1);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            position: relative;
            overflow: hidden;
        }

        .resource-card:hover {
            background: rgba(74, 222, 128, 0.05);
            border-color: rgba(74, 222, 128, 0.4);
            transform: translateY(-5px);
        }

        .resource-card i.main-icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .fa-file-pdf { color: #fb923c; }
        .fa-youtube { color: #ff4444; }
        .fa-bolt { color: #4ade80; }

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

        /* Lógica del Desplegable */
        .dropdown-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.5s ease, opacity 0.4s ease;
            width: 100%;
            opacity: 0;
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 10px;
        }

        .resource-card.active-dropdown {
            background: rgba(74, 222, 128, 0.08);
            border-color: rgba(74, 222, 128, 0.6);
        }

        .resource-card:hover .dropdown-content {
            max-height: 300px;
            opacity: 1;
            margin-top: 20px;
        }

        /* Links internos del desplegable */
        .sub-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(255, 255, 255, 0.05);
            padding: 12px 18px;
            border-radius: 14px;
            text-decoration: none;
            color: #d1fae5;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.2s ease;
            border: 1px solid transparent;
        }

        .sub-link:hover {
            background: rgba(74, 222, 128, 0.15);
            border-color: rgba(74, 222, 128, 0.3);
            transform: translateX(5px);
            color: white;
        }

        .sub-link i {
            font-size: 0.8rem;
            opacity: 0.7;
        }

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
            <!-- Manual PDF -->
            <a href="https://drive.google.com/file/d/1ShgbMV8NdSVUROm2_Kxl-BIy_MZnnk7c/view?usp=sharing" target="_blank" class="resource-card">
                <i class="fas fa-file-pdf main-icon"></i>
                <h3>Manual PDF</h3>
                <p>Consulta el manual detallado directamente en Google Docs.</p>
            </a>

            <!-- Video Tutorial -->
            <a href="https://www.youtube.com/watch?v=78XwtOaMZrE" target="_blank" class="resource-card">
                <i class="fab fa-youtube main-icon"></i>
                <h3>Video Tutorial</h3>
                <p>Aprende de forma visual cómo funciona la plataforma.</p>
            </a>

            <!-- Guía Rápida Desplegable -->
            <div class="resource-card">
                <i class="fas fa-bolt main-icon"></i>
                <h3>Guía Rápida</h3>
                <p>Selecciona tu tipo de perfil para ver la guía de acceso.</p>
                
                <div class="dropdown-content">
                    <a href="https://drive.google.com/file/d/1_AwCobN4taa5VxWhmciwIXeCsVsrISQc/view?usp=sharing" target="_blank" class="sub-link">
                        Donador <i class="fas fa-chevron-right"></i>
                    </a>
                    <a href="https://drive.google.com/file/d/1FBwGNflZsLxvWog0W3i7vYaKOtKTZMO-/view?usp=sharing" target="_blank" class="sub-link">
                        Empresa <i class="fas fa-chevron-right"></i>
                    </a>
                    <a href="https://drive.google.com/file/d/1jmhvEWMi_jJM9i3AB3Alu6gdNKr50j_-/view?usp=sharing" target="_blank" class="sub-link">
                        Jugador <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <a href="../index.html" class="btn-back">
            <i class="fas fa-arrow-left"></i> Volver al inicio
        </a>
    </div>

</body>
</html>