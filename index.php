<?php

// This code is provided under a Creative Commons Attribution license (for
// details see: http://creativecommons.org/licenses/by/3.0/). In
// summary, you are free to use the code for any purpose as long as you remember
// to mention my name (Torben Sko) at some point. Also please note that my code
// is provided AS IS with NO WARRANTY OF ANY KIND, INCLUDING THE WARRANTY OF
// DESIGN, MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE.


// taken from the CSV file:
// 0    1      2        3     4     5           
// Date,Country,VideoID,Title,Views,Unique users,Unique users (7 days),Unique users (30 days),Popularity,Comments,Favorites,Rating 1,Rating 2,Rating 3,Rating 4,Rating 5 
define("INDEX_DATE",		0);
define("INDEX_COUNTRY",		1);
define("INDEX_VIEWS",		4);

define("LF", chr(10));

if(php_sapi_name() != 'cli') {
	echo "<b>Error:</b> this script should only be run via the command line using the command: ./run_me.sh";
	exit();
}

echo LF;
if(!file_exists('db_details.php')) {
	echo "please make sure you have copied db_details.php.bak to db_details.php".LF;
	echo "and replaced its contents with values appropriate to your setup".LF.LF;
	exit();
}

require('db_details.php');

echo "recreating the database '".DB_DATABASE."'".LF.LF;
$error = shell_exec('mysql -u root -e "DROP DATABASE IF EXISTS '.DB_DATABASE.'; CREATE DATABASE '.DB_DATABASE.'; USE '.DB_DATABASE.'; CREATE TABLE hits (id INTEGER NOT NULL AUTO_INCREMENT,video VARCHAR(16) NOT NULL,country VARCHAR(4) NOT NULL,views INTEGER default 0 NOT NULL,date DATE, PRIMARY KEY (id)) ENGINE=InnoDB;"');
if(strlen($error)) {
	echo "error: {$error}".LF;
	exit;
}

echo "processing the CSV data - expecting the directory structure:".LF;
echo "vid_CODE".LF;
echo "   insight_CODE_STARTDATE1-ENDDATE1_world".LF;
echo "      CODE_STARTDATE-ENDDATE_world_views_1.csv".LF;
echo "   insight_CODE_STARTDATE2-ENDDATE2_world".LF;
echo "      CODE_STARTDATE-ENDDATE_world_views_1.csv".LF;
echo "   ...".LF;	
echo "(please note that if a permission error is thrown, use: chmod -R 755 vid_*)".LF.LF;

function mysqlPerform(&$mysqli, $query) {
	if($echo) echo $query.LF;
	$mysqli->query($query);
	if(strlen($mysqli->error) > 0) {
		echo $mysqli->error.LF;
		debug_print_backtrace();
		$mysqli->close();
		exit();
	}
}

function processFile(&$mysqli, $videoCode, $csvContents) {
	foreach(explode("\r", $csvContents) as $num => $line) {
		$values = explode(",", $line);
		// first line is the header
		if($num > 0 && count($values) > INDEX_VIEWS && $values[INDEX_VIEWS] > 0) {
			// some files have overlap, so we potentially delete our previous entry first
			mysqlPerform($mysqli, "DELETE FROM hits WHERE video = '{$videoCode}' AND date = '{$values[INDEX_DATE]}' AND country = '{$values[INDEX_COUNTRY]}'");
			mysqlPerform($mysqli, "INSERT INTO hits (video, date, country, views) VALUES ('{$videoCode}','".trim($values[INDEX_DATE])."','{$values[INDEX_COUNTRY]}','{$values[INDEX_VIEWS]}')");
		}
	}
}

$mysqli = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
foreach(scandir('./') as $rootDir) {
	$video = array();
	if(preg_match("/^vid_(.+)$/", $rootDir, $video)) {
		foreach(scandir("./{$rootDir}") as $subDir) {		
			if(preg_match("/^insight_.*world$/", $subDir)) {
				foreach(scandir("./{$rootDir}/{$subDir}") as $file) {
					if(preg_match("/_world_views_1.csv$/", $file)) {
						echo "processing: {$file}".LF;
						processFile($mysqli, $video[1], file_get_contents("./{$rootDir}/{$subDir}/{$file}"));
					}
				}
			}
		}
	}
}
$mysqli->close();

echo LF;
echo "to get the complete per-day views for your video, run the following mysql".LF;
echo "query (remembering to replace VID-ID with the ID of your video):".LF;
echo "SELECT date, sum(views) as views FROM hits WHERE video = 'VID-ID' GROUP BY date".LF.LF;

?>
