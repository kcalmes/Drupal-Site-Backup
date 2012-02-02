<?php
/****************************
* Purpose:
*   Perform an intelligent mysqldump and upon success, remove the old dump files then contact contact some administrator unconditionally
* 	We do this so we have a flat file of the mysql db.  Having this allows the backup software to backup the flat files of the dumped db.  
*	OIT does not yet have the capability to back up open files.
* 
* Notes: 
*  Sendmail now utilizes the email aliases that we set up for 'mysql' in the file: /etc/aliases (be sure to read the instructions at the top
*	of the alias file when making changes 
* 
* 
* Author:	Adam Holsinger
* Coauthor:	Michael Gabriel Bean (comment author)
* Strip it and change most of it Author: Kory Calmes
* Last edited:	01/10/2012
* 
* Changelog:
* 	improved administrative email information
* 	comments, Changelog, and Future implementations sections added for clarification
* 	advanced email structure--email is created based on users in the 'mysql' user group on the server
* 	the db user and password correspond to a mysql user that has "SELECT" access only (the user cannot make changes to the db)
* 	
* Future implementations: 	
* 	db password isn't hard-coded into this script but rather place in another php file which is then 'included'
* 	
****************************/
//this is a array of the result emails that will be send out when the job is run
//$email = array('mysqldump');
$email = array();
$email[]="email@domain";

//Defines for args
define("PATH_ARG", 1);
define("MYSQL_USER_ARG", 2);
define("MYSQL_PASSWORD_ARG", 3);
define("MYSQL_HOST_ARG", 4);
define("DRUPAL_DB_ARG", 5);
define("BACKUP_LIFESPAN_ARG",6);//In seconds

if(!isset($argv[0])){
	print "Error - Incorrect arguments, usage example: \nphp backupsql.php full/path/for/backups/storage\n";
	exit(0);
}

//define
//define("PATH", "/var/www/utilities/mysqlbackup/backups");
define("PATH", $argv[PATH_ARG]);
define("MYSQL_USER", "ccc");
define("MYSQL_PASSWORD", "*******");
define("MYSQL_HOST", "localhost");
define("DRUPAL_DB", "drupal");
define("BACKUP_LIFESPAN","604800");//In seconds

//log into mysql and get the list of databases
$conn = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASSWORD) or die(mysqlerrno().': ' .mysql_error); //database connect
//the backUp user has select on all databases
$databases_result = mysql_query("SHOW DATABASES");//get list of databases from mysql
$dbs = array();  //empty array to store result

//$dbs is an array with the key as the database name and the value is an array of tables.  The idea here is to isolate the groups of tables into subsets
while ($row = mysql_fetch_array($databases_result)) { 
	$db_name = $row[0];
	mysql_select_db($db_name);
	$tables_result = mysql_query("SHOW TABLES");//get list of tables in the given database
	while ($row = mysql_fetch_array($tables_result)) {
		$table_name = $row[0];
		$prefix = substr($table_name, 0, strpos($table_name, "_"));
		if( strpos($table_name,'d7_') === 0 ){
			$prefix = substr($table_name, 0, strpos($table_name, "_", 3));
		}
		//print $prefix."\n";
		$dbs[$db_name][$prefix][] = $table_name;
		$dbs[$db_name]['all'][] = $table_name;
	}
}

$message_errors = false;
foreach ($dbs as $database => $prefixs) { //main foreach loop that keeps track of the files
	$tables = ' ';
	if($database == "drupal"){
		foreach($prefixs as $prefix => $values){
			foreach($values as $table){
				$tables .= ' '.$table;
			}
			create_dump($database, $prefix, $tables);
		}
	} else {
		create_dump($database);
	}
}

// housekeep for email
$serverName = shell_exec('hostname');
$serverName = str_replace("\n", '', $serverName);
$message = "Mysql backup completed on $serverName.\n";
$from = shell_exec('whoami');
$from = str_replace("\n", '', $from);
$headers  = "From: $from\r\n";
$headers .= "Content-type: text/html\r\n";

if($message_errors){
	$message .= "The following did not back up:\n\n".$message_errors;
}else{
	$message .= 'All databases were backed up successfully.';
}

//everything is complete send the result email. 
echo "\n\n************delete complete, sending message***********\n";
//An email will only be sent if there is a problem so the server admin can fix it.
if($message_errors){
	foreach ($email as $mail) {
		print "mail sending to: " . $mail . "\n";
		mail($mail, "MYSQL Backup Problem", $message);
	}
}
//This function creates the dump file and deletes a previous one on success based on the numbers of seconds to keep each backup
function create_dump($database, $prefix = '', $tables = ''){
	if($prefix != ''){
		$prefix = "_$prefix";
	}
	//$date is used to make the file names
	$date = date("m-d-y@G");
	$exec = "cd ".PATH." && mysqldump --single-transaction -h ".MYSQL_HOST." -u ".MYSQL_USER." -p".MYSQL_PASSWORD." $database $tables > $database$prefix$date.sql";
   	//print "\n\n".$exec;
	$result = 0;	// var that hold the lines
	exec($exec, $output, $result); // executing above command. 
	
	//The flowwing will remove old backups that have been replaced by the new one
	if ($result == 0)  {  //here we are storing the result of the file creaction. 
		if(is_dir(PATH)){
			if($handle = opendir(PATH)){
				//cycles through all files in the dir
        		while(($file = readdir($handle)) !== false){                          
		  					/*These conditions verify that the $database that backed up succesfully is the
		  					 only one being deleted and that the filename doesnt contain the current day.
		  					 This will ensure cleanup of all files earlier than the current day.*/
		  				//If the file does not contain the database that was just successfully backed up, don't delete it
					if (strpos($file,$database) === false)
		            	continue;
		        	$backup_date = substr($file, strpos($file,'@')-8,8 );
		          	$backup_date = str_replace("-","/",$backup_date);
		          	$backup_date = strtotime($backup_date);
		          	//If the time stamp for the current time is less than the time stamp of the file plus 1 week then dont't delete
		          	if(time() < ($backup_date + 604800) )
		            	continue;
					unlink(PATH."/".$file);
		      	}
		      	closedir($handle);
		    }
		}
		return true;
	} elseif ($result == 2 ){
		return "\tFailure to create backup for the following database: $database \n";
	}
}
?>

