<?php

$longStrings = [];
$longStrings['description'] = <<<HTML
Before configuring your database connection, please ensure that you have created an empty database on PostgreSQL with
PostGIS enabled, for example using the script:
<pre>
*code*
</pre>
Then, create a user login with full access to modify objects and data in this database, changing the username and
password as required.
<pre>
*code_user*
</pre>
Finally, connect to your new database and run the following script to prepare the required extensions and grant
permissions. Note that if installing the warehouse in a hosted environment, you may not be able to create the extensions
yourself using SQL and will either have to do that via your control panel or by asking your host.
<pre>
*code_perm*
</pre>
For a development installation you can use this user account for both the database connection user as well as the report
user. However for a live server it is recommended that you create a second user and only grant read only privileges to
the tables and views you want accessible from reports to use as the report user. The schema you define below will be
auto-created by the setup procedure. For more information, see the
<a href="http://indicia-docs.readthedocs.org/en/latest/administrating/warehouse/warehouse-installation.html">Installation Guide</a>
HTML;
$lang = [
  'database_setup' => 'Database Setup',
  'description' => $longStrings['description'],
  'database'    => 'Database',
  'db_schema'   => 'Schema for Indicia tables',
  'db_host'     => 'Host',
  'db_port'     => 'Port',
  'db_name'     => 'Existing Database Name',
  'db_user'     => 'Database Connection Username',
  'db_password' => 'Password',
  'db_report_user'     => 'Connection Username for Reporting',
  'db_report_password' => 'Password for Reporting',
  'indicia_administrator'   => 'Create Indicia administrator',
  'indicia_login'           => 'Login',
  'indicia_password'        => 'Password',
  'start_setup_title'       => 'Launch setup',
  'submit'                  => 'Submit',
  'warning'                 => 'Warning!!!',
  'error'                   => 'Error',
  'error_config_folder'     => 'The config folder must be writeable by php scripts:',
  'error_upload_folder'     => 'The upload folder must be writeable by php scripts:',
  'error_db_wrong_postgres_version1'  => 'Installed postgres version:',
  'error_db_wrong_postgres_version2'  => 'At least version 8.2 required.',
  'error_db_unknown_postgres_version' => 'Unknown postgres version.',
  'error_db_wrong_schema'   => 'A schema must be defined and it must be named something other than "public"',
  'error_db_schema'         => 'Schema connection problem. Verify the schema name.',
  'error_db_postgis'        => 'It seems that postgis scripts arent installed in the public schema.',
  'error_db_file'           => 'The indicia setup sql file must be readable by php scripts:',
  'error_db_user'           => 'The following user doesn\'t exist:',
  'error_db_connect'        => 'Could not connect to the database. Please verify database connection data.',
  'error_db_setup'          => 'Setup failed. Database transactions have been rolled back.',
  'error_db_database_config' => 'Setup failed. Database transactions have been rolled back. Could not write /application/config/database.php file. Please check file write permission.',
  'error_db_indicia_config'  => 'Setup failed. Database transactions have been rolled back. Could not create /application/config/indicia.php file. Please check file write permission.',
  'error_remove_folder'      => 'For continuing with the setup you have to remove or rename the config file /application/config/indicia.php',
  'error_file_read_permission'      => 'The following files must be readable by php scripts',
  'error_chars_not_allowed'         => 'wrong chars',
  'error_no_postgis'                => 'Postgis not installed',
  'error_wrong_postgis_version'     => 'Required Postgis version >= 1.3',
  'error_wrong_postgres_version'     => 'Required Postgresql version >= 8.2',
  'error_no_postgres_client_extension' => 'No php_pgsql extension found (postgresql). Check your php.ini file.',
  'error_no_php_curl_extension'        => 'No php_curl extension found. Check your php.ini file.',
  'error_downgrade_not_possible' => 'Current indicia script version is lower than the database version. Downgrade not possible.',
  'host_required' => 'Please specify the PopstgreSQL database host.',
  'port_required' => 'Please specify the PopstgreSQL database port.',
  'name_required' => 'Please specify the PopstgreSQL database name.',
  'user_required' => 'Please specify the PopstgreSQL database user.',
  'password_required' => 'Please specify the PopstgreSQL database password.',
  'test_email_title' => 'Email to test the Indicia server email configuration. Do not reply to this email.',
  'test_email_failed' => 'Please check your email configuration. The test email was not sent successfully.',
  'skip_email_config' => 'Skip Email Configuration',
  'ack_perm_problems' => 'Acknowledge Permissions Problems',
];