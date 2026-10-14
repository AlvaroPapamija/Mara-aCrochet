-- ============================================================
--  MARAÑA CROCHET ATELIER - Base de Datos MySQL (XAMPP)
--  Versión: 1.0.0
--  Charset: utf8mb4
-- ============================================================

CREATE DATABASE IF NOT EXISTS `marana_crochet`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `marana_crochet`;

-- ============================================================
-- TABLA: usuarios
-- ============================================================
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id`          VARCHAR(36)  NOT NULL PRIMARY KEY,
  `nombre`      VARCHAR(120) NOT NULL,
  `username`    VARCHAR(60)  NOT NULL UNIQUE,
  `email`       VARCHAR(120) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `rol`         ENUM('admin','client') NOT NULL DEFAULT 'client',
  `rol_titulo`  VARCHAR(80)  NOT NULL DEFAULT 'Cliente',
  `avatar_url`  TEXT,
  `telefono`    VARCHAR(30),
  `ciudad`      VARCHAR(80),
  `direccion`   TEXT,
  `activo`      TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: productos (Stock)
-- ============================================================
CREATE TABLE IF NOT EXISTS `productos` (
  `sku`               VARCHAR(30)  NOT NULL PRIMARY KEY,
  `nombre`            VARCHAR(150) NOT NULL,
  `tag`               VARCHAR(80),
  `talla`             VARCHAR(20),
  `proveedor`         VARCHAR(200),
  `precio_cop`        INT          NOT NULL DEFAULT 0,
  `costo_lana_cop`    INT          NOT NULL DEFAULT 0,
  `stock_actual`      INT          NOT NULL DEFAULT 0,
  `stock_minimo`      INT          NOT NULL DEFAULT 0,
  `categoria`         ENUM('critico','agotado','optimo','pedido') NOT NULL DEFAULT 'optimo',
  `estado_label`      VARCHAR(120),
  `imagen_url`        TEXT,
  `lista_espera`      INT          NOT NULL DEFAULT 0,
  `tiempo_entrega_dias` INT,
  `cupo_mensual`      INT,
  `activo`            TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: pedidos
-- ============================================================
CREATE TABLE IF NOT EXISTS `pedidos` (
  `id`                  VARCHAR(20)  NOT NULL PRIMARY KEY,
  `fecha`               VARCHAR(60)  NOT NULL,
  `cliente_nombre`      VARCHAR(120) NOT NULL,
  `cliente_iniciales`   VARCHAR(5),
  `cliente_telefono`    VARCHAR(30),
  `cliente_ubicacion`   VARCHAR(200),
  `cliente_direccion`   TEXT,
  `estado`              ENUM('preparacion','listo','transito','entregado') NOT NULL DEFAULT 'preparacion',
  `estado_label`        VARCHAR(80),
  `prioridad_pct`       INT,
  `hora_lista`          VARCHAR(40),
  `transportista`       VARCHAR(120),
  `costo_envio`         INT          NOT NULL DEFAULT 0,
  `guia`                VARCHAR(60),
  `estado_guia`         VARCHAR(200),
  `nota_especial`       TEXT,
  `artesana`            VARCHAR(80),
  `pago_verificado`     TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at`          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: pedido_items
-- ============================================================
CREATE TABLE IF NOT EXISTS `pedido_items` (
  `id`              INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `pedido_id`       VARCHAR(20)  NOT NULL,
  `nombre`          VARCHAR(150) NOT NULL,
  `especificaciones` VARCHAR(200),
  `cantidad`        INT          NOT NULL DEFAULT 1,
  `precio`          INT          NOT NULL DEFAULT 0,
  `imagen_url`      TEXT,
  `tag`             VARCHAR(80),
  `nota_artesana`   TEXT,
  CONSTRAINT `fk_items_pedido` FOREIGN KEY (`pedido_id`)
    REFERENCES `pedidos`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: comprobantes (Vouchers)
-- ============================================================
CREATE TABLE IF NOT EXISTS `comprobantes` (
  `id`                VARCHAR(20)  NOT NULL PRIMARY KEY,
  `pedido_id`         VARCHAR(20)  NOT NULL,
  `tiempo_hace`       VARCHAR(40),
  `cliente_nombre`    VARCHAR(120) NOT NULL,
  `cliente_telefono`  VARCHAR(30),
  `cliente_direccion` TEXT,
  `metodo_envio`      VARCHAR(120),
  `monto_total`       INT          NOT NULL DEFAULT 0,
  `monto_esperado`    INT          NOT NULL DEFAULT 0,
  `metodo_pago`       VARCHAR(80),
  `cuenta_banco`      VARCHAR(120),
  `codigo_aprobacion` VARCHAR(40),
  `timestamp_banco`   VARCHAR(60),
  `estado`            ENUM('pendiente','aprobado','rechazado') NOT NULL DEFAULT 'pendiente',
  `recibo_thumb_url`  TEXT,
  `recibo_full_url`   TEXT,
  `nota_interna`      TEXT,
  `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_voucher_pedido` FOREIGN KEY (`pedido_id`)
    REFERENCES `pedidos`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: comprobante_items_resumen
-- ============================================================
CREATE TABLE IF NOT EXISTS `comprobante_items_resumen` (
  `id`             INT         NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `comprobante_id` VARCHAR(20) NOT NULL,
  `nombre`         VARCHAR(200),
  `tag`            VARCHAR(80),
  CONSTRAINT `fk_resumen_comprobante` FOREIGN KEY (`comprobante_id`)
    REFERENCES `comprobantes`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: comprobante_items_detalle
-- ============================================================
CREATE TABLE IF NOT EXISTS `comprobante_items_detalle` (
  `id`              INT         NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `comprobante_id`  VARCHAR(20) NOT NULL,
  `nombre`          VARCHAR(150),
  `especificaciones` VARCHAR(200),
  `cantidad`        INT         NOT NULL DEFAULT 1,
  `precio`          INT         NOT NULL DEFAULT 0,
  `imagen_url`      TEXT,
  CONSTRAINT `fk_detalle_comprobante` FOREIGN KEY (`comprobante_id`)
    REFERENCES `comprobantes`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DATOS INICIALES - Usuarios
-- ============================================================
INSERT INTO `usuarios` (`id`,`nombre`,`username`,`email`,`password_hash`,`rol`,`rol_titulo`,`avatar_url`,`telefono`,`ciudad`) VALUES
('u-001','Marana Admin','marana_admin','admin@marana.co',
  '$2y$12$LK7z.2X9RoMpAoRSbEuHi.5q8KtT1H4mSLXp3t9Ef7yG8QnUvZ2Oa',
  'admin','Artesana & Fundadora',
  'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=150&auto=format&fit=crop&q=80',
  '+57 311 000 0000','Neiva, Huila'),
('u-002','Sofia Restrepo','sofia_r','sofia@example.co',
  '$2y$12$LK7z.2X9RoMpAoRSbEuHi.5q8KtT1H4mSLXp3t9Ef7yG8QnUvZ2Oa',
  'client','Clienta VIP',NULL,'+57 318 692 8410','Neiva, Huila');

-- Contrasena por defecto: marana2024 (hash bcrypt)

-- ============================================================
-- DATOS INICIALES - Productos
-- ============================================================
INSERT INTO `productos` (`sku`,`nombre`,`tag`,`talla`,`proveedor`,`precio_cop`,`costo_lana_cop`,`stock_actual`,`stock_minimo`,`categoria`,`estado_label`,`imagen_url`,`lista_espera`) VALUES
('MAR-AMI-01','Amigurumi Zorro del Bosque','Edicion Bosque','22 cm','Hebras Andinas (Algodon Organico Mercerizado)',85000,22000,3,2,'optimo','Stock Optimo (3 unidades)','https://lh3.googleusercontent.com/aida-public/AB6AXuCVrTwhfZoNiOkPC-agTmLAg_YTnWGqfCZyRp3Cuj2BEoqGNe_Pq_KIDABt9_dBsCh1EVGJvX7-ZJanY8lqPB9-6S6NrqpcQm4bIDCso7Nzngp4yMOrtsHj-xSbUd5ecsc4hKEzAye36SdBUJztlQUU0Se-QkzPbVmpmD0dnwDO4mhdeRT-TeQZzr8RAgf92AqFnc5IzS6hL04-T5siuroOhiJIlrvsk2mXLZsx3SpoYEfxGJa3F5b5eA',0),
('MAR-AMI-02','Amigurumi Conejito Orejas Largas','Ternura Floral','26 cm','Hilados del Huila (Algodon Pima Peinado)',92000,24000,1,2,'critico','Stock Minimo alcanzado: 1 / 2','/images/bunny.jpg',4),
('MAR-AMI-03','Amigurumi Osita Miel y Vestido','Mas Vendido','24 cm','Hilanderia San Jacinto (Algodon Hipoalergenico)',98000,26000,4,2,'optimo','Stock Optimo (4 unidades)','/images/bear.jpg',0),
('MAR-AMI-04','Amigurumi Dinosaurio Rex Bebe','Favorito Ninez','20 cm','Hebras Andinas (Algodon Soft Pastel)',88000,23000,0,2,'agotado','5 clientas esperando reposicion','/images/dino.jpg',5),
('MAR-AMI-05','Amigurumi Ajolote Magico Rosa','Edicion Kawaii','18 cm','Artesanos Asociados del Huila',79000,19000,5,2,'optimo','Stock Optimo (5 unidades)','/images/axolotl.jpg',0),
('MAR-AMI-06','Amigurumi Dragon Fantasia Estelar','Bajo Pedido','32 cm','Taller Marana Neiva (Fibras Tenidas a Mano)',145000,38000,0,0,'pedido','Capacidad de Taller: 3 cupos este mes','/images/dragon.jpg',2),
('MAR-AMI-07','Amigurumi Mini Gatito Llavero','Mini y Llavero','10 cm','Hebras Andinas (Hilaza Delicada)',42000,11000,7,3,'optimo','Stock Optimo (7 unidades)','/images/cat.jpg',0);

-- ============================================================
-- DATOS INICIALES - Pedidos
-- ============================================================
INSERT INTO `pedidos` (`id`,`fecha`,`cliente_nombre`,`cliente_iniciales`,`cliente_telefono`,`cliente_ubicacion`,`cliente_direccion`,`estado`,`estado_label`,`transportista`,`costo_envio`,`guia`,`artesana`,`pago_verificado`) VALUES
('MC-2024-884','Hoy 09:32 AM','Sofia Restrepo','SR','+57 318 692 8410','Neiva, Huila (Barrio Quirinal)','Calle 14 # 6-32, Apto 402, Edificio Los Robles, Neiva, Huila','listo','Listo Despacho','Servientrega Nacional Estandar',14000,'9482-0198-4820',NULL,1),
('MC-2024-882','Ayer 04:15 PM','Valentina Hoyos','VH','+57 310 549 2218','Retiro en Taller Marana Neiva, Huila',NULL,'preparacion','En Taller 75%','Retiro en Tienda',0,NULL,'Lucia M.',0),
('MC-2024-879','Despachado ayer 02:40 PM','Mariana Duque','MD','+57 315 882 1093','Cali, Valle del Cauca Cra 2 Oeste #4-22',NULL,'transito','En Transito','Coordinadora Mercantil',14000,'9482019482',NULL,0);

INSERT INTO `pedido_items` (`pedido_id`,`nombre`,`especificaciones`,`cantidad`,`precio`,`imagen_url`) VALUES
('MC-2024-884','Amigurumi Zorro del Bosque','22 cm Algodon Organico Mercerizado',1,85000,'https://lh3.googleusercontent.com/aida-public/AB6AXuCVrTwhfZoNiOkPC-agTmLAg_YTnWGqfCZyRp3Cuj2BEoqGNe_Pq_KIDABt9_dBsCh1EVGJvX7-ZJanY8lqPB9-6S6NrqpcQm4bIDCso7Nzngp4yMOrtsHj-xSbUd5ecsc4hKEzAye36SdBUJztlQUU0Se-QkzPbVmpmD0dnwDO4mhdeRT-TeQZzr8RAgf92AqFnc5IzS6hL04-T5siuroOhiJIlrvsk2mXLZsx3SpoYEfxGJa3F5b5eA'),
('MC-2024-884','Amigurumi Conejito Orejas Largas','26 cm Tono Crema y Rosa Empolvado',1,92000,'/images/bunny.jpg'),
('MC-2024-882','Amigurumi Osita Miel y Vestido','24 cm Vestidito Floral Tejido a Mano',1,98000,'/images/bear.jpg'),
('MC-2024-882','Amigurumi Mini Gatito Llavero','10 cm Con Aro y Cascabel Artesanal',1,42000,'/images/cat.jpg'),
('MC-2024-879','Amigurumi Dinosaurio Rex Bebe','20 cm Verde Menta y Amarillo Pastel',1,88000,'/images/dino.jpg'),
('MC-2024-879','Amigurumi Ajolote Magico Rosa','18 cm Algodon Hipoalergenico Soft',1,79000,'/images/axolotl.jpg');

-- ============================================================
-- DATOS INICIALES - Comprobantes
-- ============================================================
INSERT INTO `comprobantes` (`id`,`pedido_id`,`tiempo_hace`,`cliente_nombre`,`cliente_telefono`,`cliente_direccion`,`metodo_envio`,`monto_total`,`monto_esperado`,`metodo_pago`,`cuenta_banco`,`codigo_aprobacion`,`timestamp_banco`,`estado`) VALUES
('VOUCH-884','MC-2024-884','Hace 12 min','Sofia Restrepo','+57 318 692 8410','Calle 14 # 6-32, Barrio Quirinal, Neiva, Huila','Envio Servientrega Prioritario',191000,191000,'Bancolombia Transfer','Bancolombia 912-384729-10','839201','24 Oct 2024 02:35 PM','pendiente'),
('VOUCH-882','MC-2024-882','Hace 35 min','Valentina Hoyos','+57 310 549 2218','Carrera 5 # 12-34 Retiro en Taller Marana, Neiva','Retiro en Taller Marana',140000,140000,'Nequi Movil','Nequi 312 849 2019','M490218','24 Oct 2024 01:10 PM','pendiente'),
('VOUCH-879','MC-2024-879','Hace 1 hora','Mariana Duque','+57 315 882 1093','Cra 2 Oeste #4-22, Cali, Valle del Cauca','Coordinadora Mercantil',181000,181000,'Daviplata App','Daviplata 312 849 2019','DP-110943','24 Oct 2024 12:20 PM','pendiente');
