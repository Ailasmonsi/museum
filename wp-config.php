<?php
/**
 * Основные параметры WordPress.
 *
 * Скрипт для создания wp-config.php использует этот файл в процессе установки.
 * Необязательно использовать веб-интерфейс, можно скопировать файл в "wp-config.php"
 * и заполнить значения вручную.
 *
 * Этот файл содержит следующие параметры:
 *
 * * Настройки базы данных
 * * Секретные ключи
 * * Префикс таблиц базы данных
 * * ABSPATH
 *
 * @link https://ru.wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Параметры базы данных: Эту информацию можно получить у вашего хостинг-провайдера ** //
/** Имя базы данных для WordPress */
define( 'DB_NAME', 'museum_bd' );

/** Имя пользователя базы данных */
define( 'DB_USER', 'root' );

/** Пароль к базе данных */
define( 'DB_PASSWORD', '' );

/** Имя сервера базы данных */
define( 'DB_HOST', 'localhost' );

/** Кодировка базы данных для создания таблиц. */
define( 'DB_CHARSET', 'utf8mb4' );

/** Схема сопоставления. Не меняйте, если не уверены. */
define( 'DB_COLLATE', '' );

/**#@+
 * Уникальные ключи и соли для аутентификации.
 *
 * Смените значение каждой константы на уникальную фразу. Можно сгенерировать их с помощью
 * {@link https://api.wordpress.org/secret-key/1.1/salt/ сервиса ключей на WordPress.org}.
 *
 * Можно изменить их, чтобы сделать существующие файлы cookies недействительными.
 * Пользователям потребуется авторизоваться снова.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '(=o~K/f4SgLDP}s*06[gy!:Odh:l.^-{fn6]AO.im$F}nn)%Lyi6#z96!C+4XV>[' );
define( 'SECURE_AUTH_KEY',  'NXXBxdWQ3^ j4cWs9^tbd?_ 6n(tGPUK7;4ob/L68BZ:6lf-&0@nd%xd_1#Y:L [' );
define( 'LOGGED_IN_KEY',    'A`E6Z[^M{rDU:ebgv>JGJeb#DAYS)!CRaJJWbSW1oP$3!B(C)$2;U6K*2-EygzWc' );
define( 'NONCE_KEY',        'I=!*I.2s1!?}=+,uHI5/mWDNyN-#plW]yE{b%w:OOnu}^sl</Tdx1MyZJ`Sg0aiu' );
define( 'AUTH_SALT',        't-(%[KyN s:IZ[b89#]rdmL7>!GKrr:flp?pD8vE yV6Rw{i[gson?-6R7pS8N%s' );
define( 'SECURE_AUTH_SALT', '{RWJ.GM2-?o(2jbr%4=JLU1;%J S-iWT4_b%XPLQbi2irH_xcwi,T:is@4L:J&T:' );
define( 'LOGGED_IN_SALT',   'm)+}2ryEB~QG;xhc3,&LgSr_}S)3<0Drl6gtAP*hPOtw[#,5q-/+WXd~t6ZWu>X ' );
define( 'NONCE_SALT',       'ZM?J&cJj[h{3BCufc+gcc:cRcZBlE0tDOiyu%5W63s5!g<v1t;RN)dDPFerAB/Lx' );

/**#@-*/

/**
 * Префикс таблиц в базе данных WordPress.
 *
 * Можно установить несколько сайтов в одну базу данных, если использовать
 * разные префиксы. Пожалуйста, указывайте только цифры, буквы и знак подчеркивания.
 */
$table_prefix = 'wp_';

/**
 * Для разработчиков: Режим отладки WordPress.
 *
 * Измените это значение на true, чтобы включить отображение уведомлений при разработке.
 * Разработчикам плагинов и тем настоятельно рекомендуется использовать WP_DEBUG
 * в своём рабочем окружении.
 *
 * Информацию о других отладочных константах можно найти в документации.
 *
 * @link https://ru.wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* Произвольные значения добавляйте между этой строкой и надписью "дальше не редактируем". */



/* Это всё, дальше не редактируем. Успехов! */

/** Абсолютный путь к директории WordPress. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Инициализирует переменные WordPress и подключает файлы. */
require_once ABSPATH . 'wp-settings.php';
