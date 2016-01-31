<?php

/**
 * Save Your Database
 *
 * @author Qexy (http://qexy.org)
 *
 * @version public 1.0
 *
 * @copyright © Qexy 2016
 *
 * @license GNU GPL v2 http://www.gnu.org/licenses/old-licenses/gpl-2.0.ru.html
*/

ini_set("memory_limit", "256M");						// Лимит оперативной памяти, используемой скриптом
ini_set('display_errors', 1);							// Отображение ошибок <0|1>
error_reporting(E_ALL);									// Вывод ошибок	см. http://php.net/manual/ru/function.error-reporting.php	
date_default_timezone_set('Europe/Moscow');				// Выставление временной зоны по умолчанию
header('Content-Type: text/html; charset=UTF-8');		// Выставление кодировки в UTF8

define("DIR_ROOT", dirname(__FILE__).'/');				// Назначение корневой директории

// Настройки
$cfg = array(
	'db_host' => 'localhost',							// Хост баз данных
	'db_port' => 3306,									// Порт баз данных
	'db_user' => 'root',								// Имя пользователя баз данных
	'db_pass' => '',									// Пароль пользователя баз данных
	'db_base' => 'minecraft',							// База данных, откуда делать бекап
	'path' => DIR_ROOT.'backups/',						// Директория сохранения бекапа
	'filename' => time().'_'.date("d.m.Y_H-i-s"),		// Имя файла бекапа
	'gzip' => true,										// gzip сжатие файла
);


// Создание соединения с базой
$db = @new mysqli($cfg['db_host'], $cfg['db_user'], $cfg['db_pass'], $cfg['db_base'], $cfg['db_port']);

// Выявление ошибок соединения
if($db->connect_errno){ exit("Ошибка соединения с базой"); }

// Выставление кодировки соединения и проверка
if(!$db->set_charset("utf8")){ exit("Ошибка кодировки"); }


$result = "-- ---------------------------------------------------------
--
-- SQL Dump
-- 
-- http://qexy.org
--
-- Host connection info: ".$db->host_info."
-- Generation time: ".date('F d, Y \a\t H:i A ( e )')."
-- Server version: ".$db->server_info."
-- PHP version: ".PHP_VERSION."
--
-- ---------------------------------------------------------\n\n";

$result .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n SET time_zone = \"+00:00\";";

// Получение списка таблиц
$query = $db->query("SHOW TABLES");

// Проверка
if(!$query || $query->num_rows<=0){ exit("База пуста"); }

$tables = array();

// Помещение списка в массив
while($ar = $query->fetch_array()){ $tables[] = $ar[0]; }

// Получение данных из каждой таблицы и занесение в $result
foreach($tables as $key => $table){
	$query = $db->query("SHOW CREATE TABLE `$table`");
	if(!$query || $query->num_rows<=0){ continue; }
	$ar = $query->fetch_array();

	$result .= "-- DUMP TABLE `$table` --\n";
	$result .= $ar[1]."\n\n\n";

	$fields = array();

	$query = $db->query("SHOW COLUMNS FROM `$table`");
	if(!$query || $query->num_rows<=0){ continue; }

	while($ar = $query->fetch_array()){ $fields[] = "`".$ar[0]."`"; }

	$fields = implode(', ', $fields);

	$query = $db->query("SELECT * FROM `$table`");
	if(!$query || $query->num_rows<=0){ continue; }

	$num = $query->num_rows;

	$result .= "INSERT INTO `$table` ($fields) VALUES\n";

	$line = 0;

	while($ar = $query->fetch_array()){

		$line++;

		$rows = array();

		for ($i=0; $i < $query->field_count; $i++){
			$row = $db->real_escape_string($ar[$i]);
			$rows[] = "'$row'";
		}

		$rows = implode(', ', $rows);

		$result .= "($rows)";

		$result .= ($line < $num) ? ",\n" : "\n";
	}

	$result .= "\n\n\n\n\n";
}

if($cfg['gzip']){
	// GZip сжатие
	$fp = gzopen($cfg['path'].$cfg['filename'].".sql.gz", 'w9');
	gzwrite($fp, $result);
	gzclose($fp);
}else{
	// Без сжатия (просто помещение в файл .sql)
	file_put_contents($cfg['path'].$cfg['filename'].".sql", $result);
}

?>