# perbudget
PerBudget is a PHP/MySQL based personal budgeting application.

Requirements: 
 * MySQL (Tested with version 14.14 - 5.7.17) / Linux
 * Apache 2 or similar web server
 * PHP 7 (untested with PHP5, but should also work).
 
Installation:

	1) Create the perbudget database in MySQL (eg CREATE DATABASE perbudget)
	
	2) GRANT your desired MySQL user acess to the perbudget database.
	
	3) Edit dbconnect.php with your MySQL username, password and database name
	
	4) Insert the contents of the perbudget.sql file into your database (eg mysql -u <user> -p < perbudget.sql ). This will set up the database schema for you.
	
	5) Copy the files into your webroot (eg /var/www/html/perbudget). Browse to that particular webroot.

