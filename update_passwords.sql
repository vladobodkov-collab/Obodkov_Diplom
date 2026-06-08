-- Выполнить в phpMyAdmin, если БД уже импортирована со старыми паролями.
-- База: motors_komplektaciya → вкладка SQL → Вставить → Вперёд

USE `motors_komplektaciya`;

UPDATE `users` SET `password` = '$2y$10$O9FiI7qmNUOMZppeBqZuse.XIyi1hHyhpYjHVgxULFOEgsTqAd0Jq' WHERE `email` = 'admin@motors-k.ru';
UPDATE `users` SET `password` = '$2y$10$ISMT02PJ0JPwmgE87hcTLeDgJ1JeczHXfFbpiFFCDvAZV4V9Jrrua' WHERE `email` = 'petrov@motors-k.ru';
UPDATE `users` SET `password` = '$2y$10$TVIXyRhUU147RctQd2.aDOJuExmQnvlMD46UUjmiJiyD6oFQVfn3i' WHERE `email` IN ('ivanov@example.com', 'sidorova@example.com');
