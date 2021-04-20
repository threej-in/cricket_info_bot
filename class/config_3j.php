<?php

//comment below line for better debbuging
//error_reporting(0);

define('DEBUG_MODE', true);
//bots id
define('SELFID', 1662811622);
/**
 * Telegram bot token should be replaced with your own bots unique token which
 * you can get from bot father.
 * 
 * @link to botfather http://t.me/botfather
 */

$BOT_TOKEN= '';

/**
 * Set your unique chat id if you want to receive logs/notification from the bot
 * when something odd happens or when a new user starts your bot.
 * 
 * You can get your chat id form [@forwardinfobot](http://t.me/forwardinfobot")
 */
define('ADMINID',11111111);

/**
 * Database configuration
 */

/**
 * set your database server name if you are unaware about your db server name then
 * you can get it from your hosting provider.
 */
define('DBSERVER', 'localhost');
/**
 * set your database user name you can get it from your hosting provider.
 */
define('DBUSERNAME', 'root');
/**
 * set your database password you can get it from your hosting provider.
 */
define('DBPASSWORD', '');
/**
 * set your database name.
 */
define('DBNAME', '');

define('TGUSER_TABLE_3J','CREATE TABLE TGUSER_TABLE_3J(
ID INTEGER UNSIGNED AUTO_INCREMENT PRIMARY KEY,
CHAT_ID DOUBLE UNIQUE NOT NULL,
USERNAME varchar(128),
FIRST_NAME varchar(128),
MESSAGE_ID int,
J_DATE datetime
)CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;');

define('TGDATA_TABLE_3J','CREATE TABLE TGDATA_TABLE_3J(
DID INTEGER AUTO_INCREMENT PRIMARY KEY,
UID VARCHAR(20) not null,
DATA TEXT not null,
DATE INTEGER not null
)CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;');

define('USER_TABLE_NAME','TGUSER_TABLE_3J');
define('USERID_COL_NAME','CHATID');