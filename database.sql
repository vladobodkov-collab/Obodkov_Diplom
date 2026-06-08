-- ============================================================
-- БАЗА ДАННЫХ: motors_komplektaciya
-- ООО «МОТОРЫ И КОМПЛЕКТАЦИЯ»
-- Импорт: phpMyAdmin → Импорт → выбрать этот файл → Вперёд
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+03:00";
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `motors_komplektaciya`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `motors_komplektaciya`;

-- ============================================================
-- roles
-- ============================================================
CREATE TABLE `roles` (
  `id`          TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(20)  NOT NULL,
  `description` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_role_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` VALUES
(1,'guest',    'Гость — просмотр каталога'),
(2,'client',   'Клиент — заказы, корзина, ЛК'),
(3,'employee', 'Сотрудник — обработка заказов, склад'),
(4,'admin',    'Администратор — полный доступ');

-- ============================================================
-- users  (ПРАВИЛЬНЫЕ bcrypt-хеши)
-- admin123 / employee123 / client123
-- ============================================================
CREATE TABLE `users` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id`     TINYINT UNSIGNED NOT NULL DEFAULT 2,
  `last_name`   VARCHAR(60)  NOT NULL,
  `first_name`  VARCHAR(60)  NOT NULL,
  `middle_name` VARCHAR(60)  DEFAULT NULL,
  `email`       VARCHAR(120) NOT NULL,
  `phone`       VARCHAR(20)  DEFAULT NULL,
  `password`    VARCHAR(255) NOT NULL,
  `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login`  DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email` (`email`),
  KEY `fk_users_role` (`role_id`),
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`,`role_id`,`last_name`,`first_name`,`email`,`phone`,`password`) VALUES
(1,4,'Администратор','Главный','admin@motors-k.ru',    '+7(485)1234567','$2y$10$O9FiI7qmNUOMZppeBqZuse.XIyi1hHyhpYjHVgxULFOEgsTqAd0Jq'),
(2,3,'Петров',      'Сергей', 'petrov@motors-k.ru',   '+7(905)1112233','$2y$10$ISMT02PJ0JPwmgE87hcTLeDgJ1JeczHXfFbpiFFCDvAZV4V9Jrrua'),
(3,2,'Иванов',      'Иван',   'ivanov@example.com',   '+7(903)9876543','$2y$10$TVIXyRhUU147RctQd2.aDOJuExmQnvlMD46UUjmiJiyD6oFQVfn3i'),
(4,2,'Сидорова',    'Мария',  'sidorova@example.com', '+7(910)5554433','$2y$10$TVIXyRhUU147RctQd2.aDOJuExmQnvlMD46UUjmiJiyD6oFQVfn3i');

-- ============================================================
-- engine_models
-- ============================================================
CREATE TABLE `engine_models` (
  `id`          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `brand`       ENUM('ЯМЗ','ТМЗ') NOT NULL,
  `model`       VARCHAR(20) NOT NULL,
  `full_name`   VARCHAR(50) NOT NULL,
  `cylinders`   TINYINT UNSIGNED DEFAULT NULL,
  `description` VARCHAR(200) DEFAULT NULL,
  `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_engine` (`brand`,`model`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `engine_models` (`brand`,`model`,`full_name`,`cylinders`,`description`) VALUES
('ЯМЗ','236',  'ЯМЗ-236',  6,'6-цилиндровый V-образный дизель, 180 л.с.'),
('ЯМЗ','236М', 'ЯМЗ-236М', 6,'Модернизированная версия ЯМЗ-236'),
('ЯМЗ','238',  'ЯМЗ-238',  8,'8-цилиндровый V-образный дизель, 240 л.с.'),
('ЯМЗ','238М', 'ЯМЗ-238М', 8,'Модернизированная версия ЯМЗ-238'),
('ЯМЗ','7511', 'ЯМЗ-7511', 8,'8-цилиндровый турбодизель, 400 л.с.'),
('ЯМЗ','7512', 'ЯМЗ-7512', 8,'Модификация ЯМЗ-7511'),
('ТМЗ','843',  'ТМЗ-843',  8,'8-цилиндровый дизель Тутаевского завода'),
('ТМЗ','844',  'ТМЗ-844',  8,'Модификация ТМЗ-843');

-- ============================================================
-- categories
-- ============================================================
CREATE TABLE `categories` (
  `id`         SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(80) NOT NULL,
  `parent_id`  SMALLINT UNSIGNED DEFAULT NULL,
  `sort_order` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `fk_cat_parent` (`parent_id`),
  CONSTRAINT `fk_cat_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories`(`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categories` (`id`,`name`,`parent_id`,`sort_order`) VALUES
(1, 'Поршневая группа',       NULL,1),
(2, 'Поршни',                 1,   1),
(3, 'Поршневые кольца',       1,   2),
(4, 'Гильзы цилиндров',       1,   3),
(5, 'Вкладыши и подшипники',  NULL,2),
(6, 'Вкладыши коренные',      5,   1),
(7, 'Вкладыши шатунные',      5,   2),
(8, 'Система охлаждения',     NULL,3),
(9, 'Термостаты',             8,   1),
(10,'Водяные насосы',         8,   2),
(11,'Радиаторы',              8,   3),
(12,'Система смазки',         NULL,4),
(13,'Масляные насосы',        12,  1),
(14,'Масляные фильтры',       12,  2),
(15,'Сцепление',              NULL,5),
(16,'Диски сцепления',        15,  1),
(17,'Корзины сцепления',      15,  2),
(18,'Прокладки и уплотнения', NULL,6),
(19,'Прокладки ГБЦ',          18,  1),
(20,'Сальники',               18,  2);

-- ============================================================
-- products
-- ============================================================
CREATE TABLE `products` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `article`     VARCHAR(30)   NOT NULL,
  `name`        VARCHAR(200)  NOT NULL,
  `category_id` SMALLINT UNSIGNED NOT NULL,
  `description` TEXT DEFAULT NULL,
  `price`       DECIMAL(10,2) NOT NULL,
  `stock_qty`   INT NOT NULL DEFAULT 0,
  `unit`        VARCHAR(10) NOT NULL DEFAULT 'шт.',
  `is_original` TINYINT(1) NOT NULL DEFAULT 1,
  `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_article` (`article`),
  KEY `fk_product_cat` (`category_id`),
  CONSTRAINT `fk_product_cat` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `products` (`id`,`article`,`name`,`category_id`,`description`,`price`,`stock_qty`) VALUES
(1, '236-1000128', 'Поршень двигателя ЯМЗ-236',          2, 'Оригинальный поршень из высококачественного алюминиевого сплава. Диаметр 130 мм. Высота компрессионной канавки 4 мм. Совместим с гильзами стандартного размера.', 4850.00, 25),
(2, '238-1000128', 'Поршень двигателя ЯМЗ-238',          2, 'Оригинальный поршень. Диаметр 130 мм. Подходит для капитального ремонта двигателя.', 4850.00, 18),
(3, '238-1004045', 'Поршень с кольцами ЯМЗ-238 (компл.)',2, 'Комплект: поршень + поршневые кольца (компрессионные + маслосъёмное). Готов к установке.', 6700.00, 12),
(4, '236-1004058', 'Вкладыш коренной ЯМЗ-236 (компл.)',  6, 'Комплект 7 штук. Размер стандарт. Сталеалюминиевые.', 2100.00, 40),
(5, '238-1004058', 'Вкладыш коренной ЯМЗ-238 (компл.)',  6, 'Комплект 9 штук. Размер стандарт.', 2350.00, 30),
(6, '7511-1307010','Термостат ЯМЗ-7511',                  9, 'Термостат системы охлаждения. Температура начала открытия 82°C, полного открытия 95°C.', 1650.00, 15),
(7, '843-1303000', 'Водяной насос ТМЗ-843',               10,'Насос охлаждающей жидкости в сборе. Производительность 120 л/мин.', 5400.00, 8),
(8, '236-1011010', 'Масляный насос ЯМЗ-236/238',          13,'Насос смазочной системы. Давление масла 4,5 кгс/см². Секционный, односекционный.', 3200.00, 20),
(9, '843-1601098', 'Диск сцепления ТМЗ-843',              16,'Ведомый диск сцепления. Диаметр 350 мм. Накладки из фрикционного материала.', 8300.00, 6),
(10,'7511-1003020','Прокладка ГБЦ ЯМЗ-7511',              19,'Паронитовая прокладка головки блока цилиндров. Толщина 1,5 мм.', 950.00, 0),
(11,'236-1003020', 'Прокладка ГБЦ ЯМЗ-236',               19,'Паронитовая прокладка головки блока. Толщина 1,5 мм.', 870.00, 22),
(12,'238-1011244', 'Масляный фильтр ЯМЗ-238',             14,'Полнопоточный фильтр масляной системы. Тонкость фильтрации 40 мкм.', 480.00, 50);

-- ============================================================
-- product_engines (совместимость)
-- ============================================================
CREATE TABLE `product_engines` (
  `product_id`      INT UNSIGNED NOT NULL,
  `engine_model_id` SMALLINT UNSIGNED NOT NULL,
  PRIMARY KEY (`product_id`,`engine_model_id`),
  KEY `fk_pe_engine` (`engine_model_id`),
  CONSTRAINT `fk_pe_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pe_engine`  FOREIGN KEY (`engine_model_id`) REFERENCES `engine_models`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `product_engines` VALUES
(1,1),(1,2),
(2,3),(2,4),
(3,3),(3,4),
(4,1),(4,2),
(5,3),(5,4),
(6,5),(6,6),
(7,7),(7,8),
(8,1),(8,2),(8,3),(8,4),
(9,7),(9,8),
(10,5),
(11,1),(11,2),
(12,3),(12,4);

-- ============================================================
-- product_images  (НОВАЯ ТАБЛИЦА)
-- ============================================================
CREATE TABLE `product_images` (
  `id`         INT NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `file_path`  VARCHAR(255) NOT NULL,
  `is_main`    TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `fk_img_product` (`product_id`),
  CONSTRAINT `fk_img_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Главные фото товаров (файлы в uploads/products/, копировать вместе с проектом)
INSERT INTO `product_images` (`product_id`,`file_path`,`is_main`) VALUES
(1,  'uploads/products/porshen_236.png',      1),
(2,  'uploads/products/porshen_238.jpg',      1),
(3,  'uploads/products/PorshenKolc_238.jpg',  1),
(4,  'uploads/products/Vkladish_236.png',     1),
(5,  'uploads/products/vkladish_238.png',     1),
(6,  'uploads/products/termostat_7511.png',   1),
(7,  'uploads/products/Vod_nasos.jpg',        1),
(8,  'uploads/products/masl_nasos.png',       1),
(9,  'uploads/products/disc_scep.jpg',        1),
(10, 'uploads/products/prokl_7511.jpg',       1),
(11, 'uploads/products/prokl_236.jpg',        1),
(12, 'uploads/products/masl_filt.jpg',        1);

-- ============================================================
-- orders
-- ============================================================
CREATE TABLE `orders` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`          INT UNSIGNED NOT NULL,
  `employee_id`      INT UNSIGNED DEFAULT NULL,
  `status`           ENUM('new','accepted','assembling','shipped','delivered','cancelled') NOT NULL DEFAULT 'new',
  `total_amount`     DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `delivery_address` VARCHAR(300) DEFAULT NULL,
  `delivery_method`  ENUM('pickup','transport','courier') NOT NULL DEFAULT 'pickup',
  `payment_method`   ENUM('cash','card','bank_transfer') NOT NULL DEFAULT 'cash',
  `comment`          TEXT DEFAULT NULL,
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_order_user`     (`user_id`),
  KEY `fk_order_employee` (`employee_id`),
  KEY `idx_order_status`  (`status`),
  CONSTRAINT `fk_order_user`     FOREIGN KEY (`user_id`)     REFERENCES `users`(`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_order_employee` FOREIGN KEY (`employee_id`) REFERENCES `users`(`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `orders` (`id`,`user_id`,`employee_id`,`status`,`total_amount`,`delivery_address`,`delivery_method`,`payment_method`) VALUES
(1,3,2,'delivered',4850.00,'Ярославль, ул. Ленина, 5','transport','card'),
(2,3,2,'accepted', 9050.00,'Ярославль, ул. Ленина, 5','transport','bank_transfer'),
(3,4,NULL,'new',   8300.00,'Рыбинск, пр. Победы, 12','pickup','cash');

-- ============================================================
-- order_items
-- ============================================================
CREATE TABLE `order_items` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id`   INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `qty`        SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `price`      DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_oi_order`   (`order_id`),
  KEY `fk_oi_product` (`product_id`),
  CONSTRAINT `fk_oi_order`   FOREIGN KEY (`order_id`)   REFERENCES `orders`(`id`)   ON DELETE CASCADE,
  CONSTRAINT `fk_oi_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `order_items` VALUES
(1,1,1,1,4850.00),
(2,2,1,1,4850.00),
(3,2,6,1,1650.00),
(4,2,8,1,2550.00),
(5,3,9,1,8300.00);

-- ============================================================
-- stock_arrivals
-- ============================================================
CREATE TABLE `stock_arrivals` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id`     INT UNSIGNED NOT NULL,
  `employee_id`    INT UNSIGNED NOT NULL,
  `qty`            INT UNSIGNED NOT NULL,
  `price_per_unit` DECIMAL(10,2) NOT NULL,
  `supplier`       VARCHAR(150) DEFAULT NULL,
  `invoice_num`    VARCHAR(50)  DEFAULT NULL,
  `arrived_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `note`           VARCHAR(300) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_arr_product`  (`product_id`),
  KEY `fk_arr_employee` (`employee_id`),
  CONSTRAINT `fk_arr_product`  FOREIGN KEY (`product_id`)  REFERENCES `products`(`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_arr_employee` FOREIGN KEY (`employee_id`) REFERENCES `users`(`id`)    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `stock_arrivals` (`product_id`,`employee_id`,`qty`,`price_per_unit`,`supplier`,`invoice_num`) VALUES
(1, 2,30,3200.00,'ПАО Автодизель (ЯМЗ)',          'АД-2026-001'),
(3, 2,15,4500.00,'ПАО Автодизель (ЯМЗ)',          'АД-2026-002'),
(7, 2,10,3600.00,'ПАО Тутаевский моторный завод', 'ТМЗ-2026-001'),
(12,2,60,280.00, 'Торговый дом ЯМЗ-Запчасти',     'ТД-2026-005');

-- ============================================================
-- cart
-- ============================================================
CREATE TABLE `cart` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `qty`        SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `added_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cart_item` (`user_id`,`product_id`),
  KEY `fk_cart_product` (`product_id`),
  CONSTRAINT `fk_cart_user`    FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE CASCADE,
  CONSTRAINT `fk_cart_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- contact_messages  (НОВАЯ ТАБЛИЦА)
-- ============================================================
CREATE TABLE `contact_messages` (
  `id`         INT NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(100) NOT NULL,
  `email`      VARCHAR(120) NOT NULL,
  `phone`      VARCHAR(20)  DEFAULT NULL,
  `message`    TEXT NOT NULL,
  `is_read`    TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ПРЕДСТАВЛЕНИЯ
-- ============================================================
CREATE OR REPLACE VIEW `v_orders_full` AS
SELECT o.id AS order_id, o.status, o.total_amount, o.delivery_method,
       o.payment_method, o.created_at,
       CONCAT(cu.last_name,' ',cu.first_name) AS client_name,
       cu.email AS client_email, cu.phone AS client_phone,
       CONCAT(eu.last_name,' ',eu.first_name) AS employee_name,
       o.delivery_address
FROM orders o
JOIN users cu ON cu.id=o.user_id
LEFT JOIN users eu ON eu.id=o.employee_id;

CREATE OR REPLACE VIEW `v_stock_status` AS
SELECT p.id, p.article, p.name, c.name AS category, p.stock_qty, p.price,
  CASE WHEN p.stock_qty=0 THEN 'Нет в наличии'
       WHEN p.stock_qty<=5 THEN 'Мало'
       ELSE 'В наличии' END AS stock_label
FROM products p JOIN categories c ON c.id=p.category_id
WHERE p.is_active=1 ORDER BY p.stock_qty ASC;
