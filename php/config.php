<?php
// ============================================================
//  SPORTSWARE – php/config.php
//  Conexión central a la base de datos mediante PDO + AUTO-SETUP
//
//  CORRECCIONES APLICADAS:
//  1. El array $productos está completo con los 55 productos.
//  2. Las rutas de imagen usan '../images/...' (relativas desde html/).
//  3. json_encode() aplicado al campo 'tallas' antes del INSERT,
//     porque la columna MySQL es de tipo JSON y PDO envía los arrays
//     PHP como texto plano sin esta conversión, causando error silencioso.
// ============================================================

define('DB_HOST',    'localhost');
define('DB_NAME',    'sportsware_db');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

// ------------------------------------------------------------
//  BASE_URL – Ajusta al nombre de tu carpeta en XAMPP/htdocs.
//  Si el proyecto está en htdocs/sportsware/ → '/sportsware/'
//  Si está en la raíz → '/'
// ------------------------------------------------------------
define('BASE_URL', '/sportsware/');   // <--- AJUSTA AQUÍ SI ES NECESARIO

// ------------------------------------------------------------
//  FUNCIÓN QUE ASEGURA QUE LA BD, TABLAS Y PRODUCTOS EXISTAN
// ------------------------------------------------------------
function ensureDatabaseSetup(): void
{
    try {
        // Conectar sin seleccionar BD para poder crearla
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';charset=' . DB_CHARSET,
            DB_USER,
            DB_PASS
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Crear BD si no existe
        $pdo->exec(
            "CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "`
             CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
        $pdo->exec("USE `" . DB_NAME . "`");

        // ── Crear tablas ──────────────────────────────────────
        $tablas = [
            "usuarios" => "
                CREATE TABLE IF NOT EXISTS usuarios (
                    id             INT          NOT NULL AUTO_INCREMENT,
                    nombre         VARCHAR(100) NOT NULL,
                    email          VARCHAR(100) NOT NULL,
                    password       VARCHAR(255) NOT NULL,
                    telefono       VARCHAR(20)  DEFAULT NULL,
                    direccion      TEXT         DEFAULT NULL,
                    fecha_registro TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY uq_email (email)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            "productos" => "
                CREATE TABLE IF NOT EXISTS productos (
                    id           VARCHAR(20)                          NOT NULL,
                    nombre       VARCHAR(200)                         NOT NULL,
                    marca        VARCHAR(100)                         DEFAULT NULL,
                    categoria    VARCHAR(50)                          DEFAULT NULL,
                    genero       ENUM('hombre','mujer','neutro')      DEFAULT 'neutro',
                    precio       DECIMAL(10,2)                        NOT NULL,
                    precio_antes DECIMAL(10,2)                        DEFAULT NULL,
                    descuento    INT                                  NOT NULL DEFAULT 0,
                    es_oferta    TINYINT(1)                           NOT NULL DEFAULT 0,
                    imagen       VARCHAR(255)                         DEFAULT NULL,
                    descripcion  TEXT                                 DEFAULT NULL,
                    tallas       JSON                                 DEFAULT NULL,
                    stock        INT                                  NOT NULL DEFAULT 0,
                    PRIMARY KEY (id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            "carrito" => "
                CREATE TABLE IF NOT EXISTS carrito (
                    id             INT         NOT NULL AUTO_INCREMENT,
                    usuario_id     INT         NOT NULL,
                    producto_id    VARCHAR(20) NOT NULL,
                    talla          VARCHAR(10) DEFAULT '',
                    cantidad       INT         NOT NULL DEFAULT 1,
                    fecha_agregado TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY uq_carrito_item (usuario_id, producto_id, talla),
                    FOREIGN KEY (usuario_id)  REFERENCES usuarios(id)  ON DELETE CASCADE  ON UPDATE CASCADE,
                    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE  ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            "pedidos" => "
                CREATE TABLE IF NOT EXISTS pedidos (
                    id              INT          NOT NULL AUTO_INCREMENT,
                    usuario_id      INT          NOT NULL,
                    fecha           TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    total           DECIMAL(10,2) NOT NULL,
                    estado          ENUM('pendiente','pagado','enviado','entregado','cancelado')
                                                 NOT NULL DEFAULT 'pendiente',
                    direccion_envio TEXT         NOT NULL,
                    metodo_pago     VARCHAR(50)  DEFAULT NULL,
                    PRIMARY KEY (id),
                    KEY idx_pedidos_usuario (usuario_id),
                    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            "detalles_pedido" => "
                CREATE TABLE IF NOT EXISTS detalles_pedido (
                    id              INT          NOT NULL AUTO_INCREMENT,
                    pedido_id       INT          NOT NULL,
                    producto_id     VARCHAR(20)  NOT NULL,
                    cantidad        INT          NOT NULL,
                    precio_unitario DECIMAL(10,2) NOT NULL,
                    talla           VARCHAR(10)  DEFAULT '',
                    PRIMARY KEY (id),
                    KEY idx_detalles_pedido (pedido_id),
                    FOREIGN KEY (pedido_id)   REFERENCES pedidos(id)   ON DELETE CASCADE  ON UPDATE CASCADE,
                    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE RESTRICT ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
        ];

        foreach ($tablas as $sqlCreate) {
            $pdo->exec($sqlCreate);
        }

        // ── Insertar productos solo si la tabla está vacía ────
        $checkProductos = $pdo->query("SELECT COUNT(*) FROM productos")->fetchColumn();

        if ((int) $checkProductos === 0) {

            // --------------------------------------------------
            //  CATÁLOGO COMPLETO – 55 productos
            //  Claves en snake_case para coincidir con columnas MySQL.
            //  CORRECCIÓN: rutas de imagen relativas '../images/...'
            //  CORRECCIÓN: tallas como array PHP → se serializa con
            //              json_encode() antes del INSERT.
            // --------------------------------------------------
            $productos = [
                // ── CAMISETAS HOMBRE (5) ──────────────────────
                ['id'=>'CAM-H-01','nombre'=>'Camiseta Deportiva Hombre Nike Pro Dri-FIT','marca'=>'Nike','categoria'=>'camisetas','genero'=>'hombre','precio'=>159900,'precio_antes'=>null,'descuento'=>0,'es_oferta'=>0,'imagen'=>'../images/camisetas/CAM-H-01.jpg','descripcion'=>'Tecnología Dri-FIT que absorbe el sudor para mantenerte seco. Ideal para entrenamientos intensos.','tallas'=>['S','M','L','XL','XXL'],'stock'=>20],
                ['id'=>'CAM-H-02','nombre'=>'Camiseta Hombre Adidas Own the Run','marca'=>'Adidas','categoria'=>'camisetas','genero'=>'hombre','precio'=>139900,'precio_antes'=>null,'descuento'=>0,'es_oferta'=>0,'imagen'=>'../images/camisetas/CAM-H-02.jpg','descripcion'=>'Ligera y transpirable, ideal para correr. Tecnología Climalite.','tallas'=>['S','M','L','XL'],'stock'=>18],
                ['id'=>'CAM-H-03','nombre'=>'Camiseta Hombre Under Armour Tech 2.0','marca'=>'Under Armour','categoria'=>'camisetas','genero'=>'hombre','precio'=>129900,'precio_antes'=>169900,'descuento'=>23,'es_oferta'=>1,'imagen'=>'../images/camisetas/CAM-H-03.jpg','descripcion'=>'Tejido suave y de secado rápido. Perfecta para el gimnasio.','tallas'=>['S','M','L','XL'],'stock'=>15],
                ['id'=>'CAM-H-04','nombre'=>'Camiseta Hombre Puma Deportiva Manga Corta','marca'=>'Puma','categoria'=>'camisetas','genero'=>'hombre','precio'=>109900,'precio_antes'=>null,'descuento'=>0,'es_oferta'=>0,'imagen'=>'../images/camisetas/CAM-H-04.jpg','descripcion'=>'Corte clásico, algodón y poliéster. Para entrenamiento diario.','tallas'=>['S','M','L','XL','XXL'],'stock'=>25],
                ['id'=>'CAM-H-05','nombre'=>'Camiseta Hombre Reebok Training','marca'=>'Reebok','categoria'=>'camisetas','genero'=>'hombre','precio'=>119900,'precio_antes'=>149900,'descuento'=>20,'es_oferta'=>1,'imagen'=>'../images/camisetas/CAM-H-05.jpg','descripcion'=>'Resistente y cómoda para entrenamientos intensos. Tecnología Speedwick.','tallas'=>['S','M','L','XL'],'stock'=>12],

                // ── CAMISETAS MUJER (5) ───────────────────────
                ['id'=>'CAM-M-01','nombre'=>'Camiseta Deportiva Mujer Nike Dri-FIT','marca'=>'Nike','categoria'=>'camisetas','genero'=>'mujer','precio'=>159900,'precio_antes'=>null,'descuento'=>0,'es_oferta'=>0,'imagen'=>'../images/camisetas/CAM-M-01.jpg','descripcion'=>'Ajuste ligero y frescura duradera. Corte entallado.','tallas'=>['XS','S','M','L'],'stock'=>22],
                ['id'=>'CAM-M-02','nombre'=>'Camiseta Mujer Adidas Own the Run','marca'=>'Adidas','categoria'=>'camisetas','genero'=>'mujer','precio'=>139900,'precio_antes'=>null,'descuento'=>0,'es_oferta'=>0,'imagen'=>'../images/camisetas/CAM-M-02.jpg','descripcion'=>'Malla transpirable y reflectante. Para correr con seguridad.','tallas'=>['XS','S','M','L'],'stock'=>19],
                ['id'=>'CAM-M-03','nombre'=>'Camiseta Mujer Under Armour Tech 2.0','marca'=>'Under Armour','categoria'=>'camisetas','genero'=>'mujer','precio'=>129900,'precio_antes'=>159900,'descuento'=>18,'es_oferta'=>1,'imagen'=>'../images/camisetas/CAM-M-03.jpg','descripcion'=>'Suave y de secado rápido. Tecnología antiolor.','tallas'=>['XS','S','M','L','XL'],'stock'=>14],
                ['id'=>'CAM-M-04','nombre'=>'Camiseta Mujer Puma Deportiva','marca'=>'Puma','categoria'=>'camisetas','genero'=>'mujer','precio'=>109900,'precio_antes'=>null,'descuento'=>0,'es_oferta'=>0,'imagen'=>'../images/camisetas/CAM-M-04.jpg','descripcion'=>'Diseño ajustado y cómodo. Ideal para yoga y pilates.','tallas'=>['XS','S','M','L'],'stock'=>27],
                ['id'=>'CAM-M-05','nombre'=>'Camiseta Mujer Reebok Training','marca'=>'Reebok','categoria'=>'camisetas','genero'=>'mujer','precio'=>119900,'precio_antes'=>139900,'descuento'=>14,'es_oferta'=>1,'imagen'=>'../images/camisetas/CAM-M-05.jpg','descripcion'=>'Elasticidad y libertad de movimiento. Tejido que absorbe el sudor.','tallas'=>['XS','S','M','L','XL'],'stock'=>11],

                // ── LEGGINGS HOMBRE (5) ───────────────────────
                ['id'=>'LEG-H-01','nombre'=>'Leggings Hombre Nike Pro Dri-FIT','marca'=>'Nike','categoria'=>'leggings','genero'=>'hombre','precio'=>179900,'precio_antes'=>null,'descuento'=>0,'es_oferta'=>0,'imagen'=>'../images/leggings/LEG-H-01.jpg','descripcion'=>'Compresión y soporte muscular. Tecnología Dri-FIT.','tallas'=>['S','M','L','XL'],'stock'=>16],
                ['id'=>'LEG-H-02','nombre'=>'Leggings Hombre Adidas Own the Run','marca'=>'Adidas','categoria'=>'leggings','genero'=>'hombre','precio'=>169900,'precio_antes'=>null,'descuento'=>0,'es_oferta'=>0,'imagen'=>'../images/leggings/LEG-H-02.jpg','descripcion'=>'Ajuste ceñido y transpirable. Bolsillo para llaves.','tallas'=>['S','M','L','XL'],'stock'=>13],
                ['id'=>'LEG-H-03','nombre'=>'Leggings Hombre Under Armour HeatGear','marca'=>'Under Armour','categoria'=>'leggings','genero'=>'hombre','precio'=>159900,'precio_antes'=>199900,'descuento'=>20,'es_oferta'=>1,'imagen'=>'../images/leggings/LEG-H-03.jpg','descripcion'=>'Fresco en climas cálidos. Compresión ligera.','tallas'=>['S','M','L','XL','XXL'],'stock'=>10],
                ['id'=>'LEG-H-04','nombre'=>'Leggings Hombre Puma Training','marca'=>'Puma','categoria'=>'leggings','genero'=>'hombre','precio'=>149900,'precio_antes'=>null,'descuento'=>0,'es_oferta'=>0,'imagen'=>'../images/leggings/LEG-H-04.jpg','descripcion'=>'Elasticidad y durabilidad. Costuras planas.','tallas'=>['S','M','L','XL'],'stock'=>18],
                ['id'=>'LEG-H-05','nombre'=>'Leggings Hombre Reebok Training','marca'=>'Reebok','categoria'=>'leggings','genero'=>'hombre','precio'=>139900,'precio_antes'=>169900,'descuento'=>17,'es_oferta'=>1,'imagen'=>'../images/leggings/LEG-H-05.jpg','descripcion'=>'Costuras planas para evitar rozaduras. Tejido de secado rápido.','tallas'=>['S','M','L','XL'],'stock'=>14],

                // ── LEGGINGS MUJER (5) ────────────────────────
                ['id'=>'LEG-M-01','nombre'=>'Leggings Mujer Nike One Dri-FIT','marca'=>'Nike','categoria'=>'leggings','genero'=>'mujer','precio'=>179900,'precio_antes'=>null,'descuento'=>0,'es_oferta'=>0,'imagen'=>'../images/leggings/LEG-M-01.jpg','descripcion'=>'Alta cintura y sujeción. Bolsillo lateral.','tallas'=>['XS','S','M','L','XL'],'stock'=>23],
                ['id'=>'LEG-M-02','nombre'=>'Leggings Mujer Adidas Own the Run','marca'=>'Adidas','categoria'=>'leggings','genero'=>'mujer','precio'=>169900,'precio_antes'=>null,'descuento'=>0,'es_oferta'=>0,'imagen'=>'../images/leggings/LEG-M-02.jpg','descripcion'=>'Transpirables y reflectantes. Para correr de noche.','tallas'=>['XS','S','M','L'],'stock'=>17],
                ['id'=>'LEG-M-03','nombre'=>'Leggings Mujer Under Armour HeatGear','marca'=>'Under Armour','categoria'=>'leggings','genero'=>'mujer','precio'=>159900,'precio_antes'=>189900,'descuento'=>15,'es_oferta'=>1,'imagen'=>'../images/leggings/LEG-M-03.jpg','descripcion'=>'Ligeros y de secado rápido. Cinturilla alta.','tallas'=>['XS','S','M','L','XL'],'stock'=>12],
                ['id'=>'LEG-M-04','nombre'=>'Leggings Mujer Puma Training','marca'=>'Puma','categoria'=>'leggings','genero'=>'mujer','precio'=>149900,'precio_antes'=>null,'descuento'=>0,'es_oferta'=>0,'imagen'=>'../images/leggings/LEG-M-04.jpg','descripcion'=>'Ajuste perfecto para yoga. Tejido suave.','tallas'=>['XS','S','M','L'],'stock'=>21],
                ['id'=>'LEG-M-05','nombre'=>'Leggings Mujer Reebok Training','marca'=>'Reebok','categoria'=>'leggings','genero'=>'mujer','precio'=>139900,'precio_antes'=>159900,'descuento'=>12,'es_oferta'=>1,'imagen'=>'../images/leggings/LEG-M-05.jpg','descripcion'=>'Resistente a las sentadillas. Tejido opaco.','tallas'=>['XS','S','M','L','XL'],'stock'=>15],

                // ── TENIS HOMBRE (5) ──────────────────────────
                ['id'=>'TEN-H-01','nombre'=>'Tenis Hombre Nike Revolution 6','marca'=>'Nike','categoria'=>'tenis','genero'=>'hombre','precio'=>239900,'precio_antes'=>289900,'descuento'=>17,'es_oferta'=>1,'imagen'=>'../images/tenis/TEN-H-01.jpg','descripcion'=>'Amortiguación suave y duradera. Upper transpirable.','tallas'=>['39','40','41','42','43','44','45'],'stock'=>20],
                ['id'=>'TEN-H-02','nombre'=>'Tenis Hombre Adidas Runfalcon 3.0','marca'=>'Adidas','categoria'=>'tenis','genero'=>'hombre','precio'=>229900,'precio_antes'=>null,'descuento'=>0,'es_oferta'=>0,'imagen'=>'../images/tenis/TEN-H-02.jpg','descripcion'=>'Ligeros y flexibles. Suela de caucho.','tallas'=>['40','41','42','43','44','45'],'stock'=>18],
                ['id'=>'TEN-H-03','nombre'=>'Tenis Hombre Under Armour Charged Assert 9','marca'=>'Under Armour','categoria'=>'tenis','genero'=>'hombre','precio'=>219900,'precio_antes'=>null,'descuento'=>0,'es_oferta'=>0,'imagen'=>'../images/tenis/TEN-H-03.jpg','descripcion'=>'Soporte y comodidad. Amortiguación Charged.','tallas'=>['39','40','41','42','43','44','45','46'],'stock'=>14],
                ['id'=>'TEN-H-04','nombre'=>'Tenis Hombre Puma Smash V2','marca'=>'Puma','categoria'=>'tenis','genero'=>'hombre','precio'=>199900,'precio_antes'=>239900,'descuento'=>16,'es_oferta'=>1,'imagen'=>'../images/tenis/TEN-H-04.jpg','descripcion'=>'Diseño clásico de tenis. Piel sintética.','tallas'=>['40','41','42','43','44'],'stock'=>22],
                ['id'=>'TEN-H-05','nombre'=>'Tenis Hombre Reebok Nano X2','marca'=>'Reebok','categoria'=>'tenis','genero'=>'hombre','precio'=>279900,'precio_antes'=>null,'descuento'=>0,'es_oferta'=>0,'imagen'=>'../images/tenis/TEN-H-05.jpg','descripcion'=>'Para entrenamiento de alto rendimiento. Durabilidad extrema.','tallas'=>['40','41','42','43','44','45'],'stock'=>10],

                // ── TENIS MUJER (5) ───────────────────────────
                ['id'=>'TEN-M-01','nombre'=>'Tenis Mujer Nike Revolution 6','marca'=>'Nike','categoria'=>'tenis','genero'=>'mujer','precio'=>239900,'precio_antes'=>279900,'descuento'=>14,'es_oferta'=>1,'imagen'=>'../images/tenis/TEN-M-01.jpg','descripcion'=>'Amortiguación suave para correr. Colores vibrantes.','tallas'=>['36','37','38','39','40','41'],'stock'=>16],
                ['id'=>'TEN-M-02','nombre'=>'Tenis Mujer Adidas Runfalcon 3.0','marca'=>'Adidas','categoria'=>'tenis','genero'=>'mujer','precio'=>229900,'precio_antes'=>null,'descuento'=>0,'es_oferta'=>0,'imagen'=>'../images/tenis/TEN-M-02.jpg','descripcion'=>'Ligeros y transpirables. Diseño femenino.','tallas'=>['36','37','38','39','40','41','42'],'stock'=>19],
                ['id'=>'TEN-M-03','nombre'=>'Tenis Mujer Under Armour Charged Assert 9','marca'=>'Under Armour','categoria'=>'tenis','genero'=>'mujer','precio'=>219900,'precio_antes'=>null,'descuento'=>0,'es_oferta'=>0,'imagen'=>'../images/tenis/TEN-M-03.jpg','descripcion'=>'Estabilidad y confort. Ideal para entrenamiento diario.','tallas'=>['36','37','38','39','40','41'],'stock'=>13],
                ['id'=>'TEN-M-04','nombre'=>'Tenis Mujer Puma Smash V2','marca'=>'Puma','categoria'=>'tenis','genero'=>'mujer','precio'=>199900,'precio_antes'=>229900,'descuento'=>13,'es_oferta'=>1,'imagen'=>'../images/tenis/TEN-M-04.jpg','descripcion'=>'Estilo versátil. Combinación de gamuza y malla.','tallas'=>['36','37','38','39','40'],'stock'=>21],
                ['id'=>'TEN-M-05','nombre'=>'Tenis Mujer Reebok Nano X2','marca'=>'Reebok','categoria'=>'tenis','genero'=>'mujer','precio'=>279900,'precio_antes'=>null,'descuento'=>0,'es_oferta'=>0,'imagen'=>'../images/tenis/TEN-M-05.jpg','descripcion'=>'Durabilidad extrema. Para crossfit y entrenamiento funcional.','tallas'=>['36','37','38','39','40','41','42'],'stock'=>9],

                // ── ACCESORIOS HOMBRE (5) ─────────────────────
                ['id'=>'ACC-H-01','nombre'=>'Gorra Deportiva Hombre Nike Dri-FIT','marca'=>'Nike','categoria'=>'accesorios','genero'=>'hombre','precio'=>89900,'precio_antes'=>null,'descuento'=>0,'es_oferta'=>0,'imagen'=>'../images/accesorios/ACC-H-01.jpg','descripcion'=>'Absorbe el sudor, ajuste cómodo. Cierre trasero ajustable.','tallas'=>['Único'],'stock'=>35],
                ['id'=>'ACC-H-02','nombre'=>'Muñequeras Deportivas Hombre Adidas (Par)','marca'=>'Adidas','categoria'=>'accesorios','genero'=>'hombre','precio'=>49900,'precio_antes'=>null,'descuento'=>0,'es_oferta'=>0,'imagen'=>'../images/accesorios/ACC-H-02.jpg','descripcion'=>'Algodón, absorben sudor. Pack de 2 unidades.','tallas'=>['Único'],'stock'=>50],
                ['id'=>'ACC-H-03','nombre'=>'Gorra Hombre Under Armour Blitzing','marca'=>'Under Armour','categoria'=>'accesorios','genero'=>'hombre','precio'=>79900,'precio_antes'=>99900,'descuento'=>20,'es_oferta'=>1,'imagen'=>'../images/accesorios/ACC-H-03.jpg','descripcion'=>'Ligera y transpirable. Paneles de malla.','tallas'=>['Único'],'stock'=>28],
                ['id'=>'ACC-H-04','nombre'=>'Riñonera Hombre Puma','marca'=>'Puma','categoria'=>'accesorios','genero'=>'hombre','precio'=>69900,'precio_antes'=>null,'descuento'=>0,'es_oferta'=>0,'imagen'=>'../images/accesorios/ACC-H-04.jpg','descripcion'=>'Pequeña, para correr. Bolsillo frontal con cremallera.','tallas'=>['Único'],'stock'=>40],
                ['id'=>'ACC-H-05','nombre'=>'Gorra Hombre Reebok Training','marca'=>'Reebok','categoria'=>'accesorios','genero'=>'hombre','precio'=>79900,'precio_antes'=>89900,'descuento'=>11,'es_oferta'=>1,'imagen'=>'../images/accesorios/ACC-H-05.jpg','descripcion'=>'Ajuste trasero, diseño deportivo. Tejido de secado rápido.','tallas'=>['Único'],'stock'=>32],

                // ── ACCESORIOS MUJER (5) ──────────────────────
                ['id'=>'ACC-M-01','nombre'=>'Gorra Deportiva Mujer Nike Dri-FIT','marca'=>'Nike','categoria'=>'accesorios','genero'=>'mujer','precio'=>89900,'precio_antes'=>null,'descuento'=>0,'es_oferta'=>0,'imagen'=>'../images/accesorios/ACC-M-01.jpg','descripcion'=>'Ligera y fresca. Colores pastel.','tallas'=>['Único'],'stock'=>30],
                ['id'=>'ACC-M-02','nombre'=>'Muñequeras Deportivas Mujer Adidas (Par)','marca'=>'Adidas','categoria'=>'accesorios','genero'=>'mujer','precio'=>49900,'precio_antes'=>null,'descuento'=>0,'es_oferta'=>0,'imagen'=>'../images/accesorios/ACC-M-02.jpg','descripcion'=>'Suaves y absorbentes. Lavables a máquina.','tallas'=>['Único'],'stock'=>45],
                ['id'=>'ACC-M-03','nombre'=>'Visera Mujer Under Armour','marca'=>'Under Armour','categoria'=>'accesorios','genero'=>'mujer','precio'=>69900,'precio_antes'=>null,'descuento'=>0,'es_oferta'=>0,'imagen'=>'../images/accesorios/ACC-M-03.jpg','descripcion'=>'Protección solar, ajuste elástico. Cinta interior que absorbe el sudor.','tallas'=>['Único'],'stock'=>25],
                ['id'=>'ACC-M-04','nombre'=>'Riñonera Mujer Puma','marca'=>'Puma','categoria'=>'accesorios','genero'=>'mujer','precio'=>69900,'precio_antes'=>79900,'descuento'=>12,'es_oferta'=>1,'imagen'=>'../images/accesorios/ACC-M-04.jpg','descripcion'=>'Compacta y resistente al agua. Varios compartimentos.','tallas'=>['Único'],'stock'=>38],
                ['id'=>'ACC-M-05','nombre'=>'Diadema Deportiva Mujer Reebok (Pack 3)','marca'=>'Reebok','categoria'=>'accesorios','genero'=>'mujer','precio'=>39900,'precio_antes'=>59900,'descuento'=>33,'es_oferta'=>1,'imagen'=>'../images/accesorios/ACC-M-05.jpg','descripcion'=>'Pack de 3 colores, antideslizante. Tejido suave.','tallas'=>['Único'],'stock'=>60],

                // ── SHORTS HOMBRE (5) ─────────────────────────
                ['id'=>'SHO-H-01','nombre'=>'Short Hombre Nike Dri-FIT','marca'=>'Nike','categoria'=>'shorts','genero'=>'hombre','precio'=>129900,'precio_antes'=>null,'descuento'=>0,'es_oferta'=>0,'imagen'=>'../images/shorts/SHO-H-01.jpg','descripcion'=>'Tejido de malla, interior tipo licra. Bolsillos laterales.','tallas'=>['S','M','L','XL'],'stock'=>20],
                ['id'=>'SHO-H-02','nombre'=>'Short Hombre Adidas Own the Run','marca'=>'Adidas','categoria'=>'shorts','genero'=>'hombre','precio'=>119900,'precio_antes'=>null,'descuento'=>0,'es_oferta'=>0,'imagen'=>'../images/shorts/SHO-H-02.jpg','descripcion'=>'Con bolsillo para llaves. Cinturilla elástica.','tallas'=>['S','M','L','XL','XXL'],'stock'=>18],
                ['id'=>'SHO-H-03','nombre'=>'Short Hombre Under Armour Tech','marca'=>'Under Armour','categoria'=>'shorts','genero'=>'hombre','precio'=>109900,'precio_antes'=>139900,'descuento'=>21,'es_oferta'=>1,'imagen'=>'../images/shorts/SHO-H-03.jpg','descripcion'=>'Ligeros y de secado rápido. Forro interior.','tallas'=>['S','M','L','XL'],'stock'=>14],
                ['id'=>'SHO-H-04','nombre'=>'Short Hombre Puma Deportivo','marca'=>'Puma','categoria'=>'shorts','genero'=>'hombre','precio'=>99900,'precio_antes'=>null,'descuento'=>0,'es_oferta'=>0,'imagen'=>'../images/shorts/SHO-H-04.jpg','descripcion'=>'Corte holgado, cintura elástica. Con cordón.','tallas'=>['S','M','L','XL'],'stock'=>25],
                ['id'=>'SHO-H-05','nombre'=>'Short Hombre Reebok Training','marca'=>'Reebok','categoria'=>'shorts','genero'=>'hombre','precio'=>99900,'precio_antes'=>119900,'descuento'=>16,'es_oferta'=>1,'imagen'=>'../images/shorts/SHO-H-05.jpg','descripcion'=>'Resistente a la abrasión. Tejido elástico.','tallas'=>['S','M','L','XL','XXL'],'stock'=>16],

                // ── SHORTS MUJER (5) ──────────────────────────
                ['id'=>'SHO-M-01','nombre'=>'Short Mujer Nike Dri-FIT','marca'=>'Nike','categoria'=>'shorts','genero'=>'mujer','precio'=>129900,'precio_antes'=>null,'descuento'=>0,'es_oferta'=>0,'imagen'=>'../images/shorts/SHO-M-01.jpg','descripcion'=>'Corte corto, interior integrado. Cinturilla alta.','tallas'=>['XS','S','M','L'],'stock'=>22],
                ['id'=>'SHO-M-02','nombre'=>'Short Mujer Adidas Own the Run','marca'=>'Adidas','categoria'=>'shorts','genero'=>'mujer','precio'=>119900,'precio_antes'=>null,'descuento'=>0,'es_oferta'=>0,'imagen'=>'../images/shorts/SHO-M-02.jpg','descripcion'=>'Con reflectivos para correr de noche. Bolsillo interior.','tallas'=>['XS','S','M','L','XL'],'stock'=>17],
                ['id'=>'SHO-M-03','nombre'=>'Short Mujer Under Armour Tech','marca'=>'Under Armour','categoria'=>'shorts','genero'=>'mujer','precio'=>109900,'precio_antes'=>129900,'descuento'=>15,'es_oferta'=>1,'imagen'=>'../images/shorts/SHO-M-03.jpg','descripcion'=>'Ultra ligeros. Forro interior de malla.','tallas'=>['XS','S','M','L'],'stock'=>13],
                ['id'=>'SHO-M-04','nombre'=>'Short Mujer Puma Deportivo','marca'=>'Puma','categoria'=>'shorts','genero'=>'mujer','precio'=>99900,'precio_antes'=>null,'descuento'=>0,'es_oferta'=>0,'imagen'=>'../images/shorts/SHO-M-04.jpg','descripcion'=>'Cintura alta, suave al tacto. Para yoga y pilates.','tallas'=>['XS','S','M','L','XL'],'stock'=>24],
                ['id'=>'SHO-M-05','nombre'=>'Short Mujer Reebok Training','marca'=>'Reebok','categoria'=>'shorts','genero'=>'mujer','precio'=>99900,'precio_antes'=>109900,'descuento'=>9,'es_oferta'=>1,'imagen'=>'../images/shorts/SHO-M-05.jpg','descripcion'=>'Elástico y cómodo. Tejido de secado rápido.','tallas'=>['XS','S','M','L'],'stock'=>19],

                // ── SUPLEMENTOS NEUTRO (5) ────────────────────
                ['id'=>'SUP-01','nombre'=>'Proteína Whey Gold Standard (2 lbs)','marca'=>'Optimum Nutrition','categoria'=>'suplementos','genero'=>'neutro','precio'=>189900,'precio_antes'=>239900,'descuento'=>20,'es_oferta'=>1,'imagen'=>'../images/suplementos/SUP-01.jpg','descripcion'=>'Proteína de suero de leche, 24g por porción. Sabor chocolate.','tallas'=>['2 lb'],'stock'=>45],
                ['id'=>'SUP-02','nombre'=>'Creatina Monohidratada 300g','marca'=>'Elite Supplements','categoria'=>'suplementos','genero'=>'neutro','precio'=>124900,'precio_antes'=>null,'descuento'=>0,'es_oferta'=>0,'imagen'=>'../images/suplementos/SUP-02.jpg','descripcion'=>'Aumenta fuerza y rendimiento. Sin sabor.','tallas'=>['300 g'],'stock'=>50],
                ['id'=>'SUP-03','nombre'=>'Aminoácidos BCAA (200 cápsulas)','marca'=>'Universal Nutrition','categoria'=>'suplementos','genero'=>'neutro','precio'=>99900,'precio_antes'=>129900,'descuento'=>23,'es_oferta'=>1,'imagen'=>'../images/suplementos/SUP-03.jpg','descripcion'=>'Previene el catabolismo muscular. Relación 2:1:1.','tallas'=>['200 cápsulas'],'stock'=>38],
                ['id'=>'SUP-04','nombre'=>'Multivitamínico Deportivo (60 tabletas)','marca'=>'Animal Pak','categoria'=>'suplementos','genero'=>'neutro','precio'=>149900,'precio_antes'=>null,'descuento'=>0,'es_oferta'=>0,'imagen'=>'../images/suplementos/SUP-04.jpg','descripcion'=>'Vitaminas y minerales para deportistas. Paquete completo.','tallas'=>['60 tabletas'],'stock'=>30],
                ['id'=>'SUP-05','nombre'=>'Proteína Vegana (1.5 lbs)','marca'=>'Plant-Based','categoria'=>'suplementos','genero'=>'neutro','precio'=>159900,'precio_antes'=>189900,'descuento'=>15,'es_oferta'=>1,'imagen'=>'../images/suplementos/SUP-05.jpg','descripcion'=>'Mezcla de guisante y arroz, sabor chocolate. Sin lácteos.','tallas'=>['1.5 lb'],'stock'=>28],
            ];

            $insertStmt = $pdo->prepare(
                "INSERT INTO productos
                 (id, nombre, marca, categoria, genero, precio, precio_antes,
                  descuento, es_oferta, imagen, descripcion, tallas, stock)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );

            foreach ($productos as $p) {
                $insertStmt->execute([
                    $p['id'],
                    $p['nombre'],
                    $p['marca'],
                    $p['categoria'],
                    $p['genero'],
                    $p['precio'],
                    $p['precio_antes'],          // null → guardado como NULL en BD
                    $p['descuento'],
                    $p['es_oferta'],
                    $p['imagen'],
                    $p['descripcion'],
                    // ─────────────────────────────────────────────────────────
                    //  CORRECCIÓN CLAVE: la columna 'tallas' es de tipo JSON.
                    //  PDO no serializa arrays automáticamente; sin json_encode()
                    //  el INSERT falla silenciosamente o guarda "Array" como texto.
                    // ─────────────────────────────────────────────────────────
                    json_encode($p['tallas'], JSON_UNESCAPED_UNICODE),
                    $p['stock'],
                ]);
            }
        }

    } catch (PDOException $e) {
        http_response_code(503);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'mensaje' => 'Error crítico en la configuración de la base de datos. Revisa que MySQL esté activo.',
        ]);
        error_log('[SPORTSWARE][setup] ' . $e->getMessage());
        exit;
    }
}

ensureDatabaseSetup();

// ── Conexión PDO global ($pdo) para el resto del proyecto ────
$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
$opciones = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $opciones);
} catch (PDOException $e) {
    http_response_code(503);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'mensaje' => 'No se pudo conectar a la base de datos. Intenta más tarde.',
    ]);
    error_log('[SPORTSWARE][conexion] ' . $e->getMessage());
    exit;
}
