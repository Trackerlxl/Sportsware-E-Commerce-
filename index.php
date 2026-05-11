<?php session_start();
require_once __DIR__ . '/php/config.php'; // para BASE_URL
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>SPORTSWARE | Entrada</title>
    <link rel="stylesheet" href="css/slider.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;700;900&family=Poppins:wght@300;400;600;800;900&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { margin: 0; overflow-x: hidden; background: #000; }
    </style>
</head>
<body>
    <div class="slider">
        <div class="slide active">
            <img src="images/sliders/slide1.jpg" alt="Deportista corriendo al atardecer">
        </div>
        <div class="slide">
            <img src="images/sliders/slide2.jpg" alt="Ropa deportiva en acción">
        </div>
        <div class="slide">
            <img src="images/sliders/slide3.jpg" alt="Accesorios y equipo deportivo">
        </div>
        <div class="slider-overlay"></div>
        <div class="contenido">
            <div class="contenido-inner">
                <span class="tagline">PUSH YOUR LIMITS</span>
                <h1>SPORTSWARE</h1>
                <p class="desc">La mejor tienda deportiva para alcanzar tus metas</p>
                <a href="<?= BASE_URL ?>html/home.php" class="btn-entrar">
                    <span>ENTRAR AHORA</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
        </div>
        <div class="dots">
            <span class="dot active"></span>
            <span class="dot"></span>
            <span class="dot"></span>
        </div>
    </div>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="js/slider.js"></script>
    <script>window.baseUrl = '<?= BASE_URL ?>';</script>
</body>
</html>