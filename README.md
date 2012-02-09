Installation
============

Unix Based
----------
Navigate into the directory of your choice and use the git clone command with the read only git address.

I put mine in a utilities directory so I use the following commands in sequence.

`cd /var/www/utilities/`

`git clone git://github.com/kcalmes/Drupal-Site-Backup.git`

`mv Drupal-Site-Backup/ drupal_site_backup/`

Note: The biggest benefit of installing it with git is that you can execute `git pull` to update the script from the repo.

Windows Based
-------------
Download the zip of the content from [the project home page](https://github.com/kcalmes/Drupal-Site-Backup).

Unzip and place in the directory of your choice.


Usage
=====
Unix Based
----------
1.	Configure the backup
	
	These instructions can be found in the script file itself too      
	//MYSQL_USER is of course the mysql username that will be used for backups.  Should be read only for security.       
	define("MYSQL_USER", "ccc");      
	
	//MYSQL_PASSWORD is the password that goes with the username from above.      
	define("MYSQL_PASSWORD", "*******");       
	
	//MYSQL_HOST is the host of the database.  For the most part this will be localhost.       
	define("MYSQL_HOST", "localhost");        
	
	//DRUPAL_DB is the database where the drupal tables are located.       
	define("DRUPAL_DB", "drupal");        
	
	//BACKUP_LIFESPAN is the time length in seconds to keep each backup file      
	define("BACKUP_LIFESPAN","604800");//In seconds       


2.	Schedule it to run

	Open the cron scheduler and add a job to run the script

	`sudo crontab -e`

	Then insert a command with the following syntax

	'minute hour day_of_month month day_of_week	php backupsql.php /full/path/for/backups/storage email@domain.com [ [another@email.com [...]]]'

	For more clarification on cron job options [click here](http://ss64.com/osx/crontab.html).

	To run it every 2 hours starting at 8AM and ending at 6PM Monday - Friday add the following command

	`0 8,10,12,14,16,18 * * 1-6 php /var/www/utilities/drupal_site_backup/backupsql.php /var/www/utilities/drupal_site_backup/backups email@domain.com`


Windows Based
-------------
1.	Configure the backup     
	
	These instructions can be found in the script file itself too      
	//MYSQL_USER is of course the mysql username that will be used for backups.  Should be read only for security.       
	define("MYSQL_USER", "ccc");      
	
	//MYSQL_PASSWORD is the password that goes with the username from above.      
	define("MYSQL_PASSWORD", "*******");       
	
	//MYSQL_HOST is the host of the database.  For the most part this will be localhost.       
	define("MYSQL_HOST", "localhost");        
	
	//DRUPAL_DB is the database where the drupal tables are located.       
	define("DRUPAL_DB", "drupal");        
	
	//BACKUP_LIFESPAN is the time length in seconds to keep each backup file      
	define("BACKUP_LIFESPAN","604800");//In seconds       

2.	Schedule it to run

	Use the task scheduler to execute the crawler.  No other support is available for windows.

Security Concerns
=================
This is designed to execute a command with the password for the mysql user on the command line.  If there are multiple users who logon to the server, this could present a risk.  If there is only 1 user for the server, this shouldn't be a concern as no one else can see the information.  To minimize the possible exploitation of this issue, use a mysql account with read-only access.  This will at least ensure that your database cannot be tampered with in the case of a breach.  

Known Issues
============
*none*

To Do
=====
*none*