<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Indicia, the OPAL Online Recording Toolkit.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses/gpl.html.
 *
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	https://github.com/indicia-team/warehouse/
 */

/**
 * SwiftMailer driver, used with the email helper.
 *
 * @see http://www.swiftmailer.org/wikidocs/v3/connections/nativemail
 * @see http://www.swiftmailer.org/wikidocs/v3/connections/sendmail
 * @see http://www.swiftmailer.org/wikidocs/v3/connections/smtp
 *
 * Valid drivers are: native, sendmail, smtp
 */
$config['driver'] = 'smtp';

/**
 * To use secure connections with SMTP, set "port" to 465 instead of 25.
 * To enable TLS, set "encryption" to "tls".
 *
 * Driver options:
 * @param   null    native: no options
 * @param   string  sendmail: executable path, with -bs or equivalent attached
 * @param   array   smtp: hostname, (username), (password), (port), (auth), (encryption)
 */
$config['options'] = array(
'hostname' => '*hostname*',
'username' => '*username*',
'password' => '*password*',
'port' => '*port*',
'auth' => '');

$config['address'] = '*address*';
$config['forgotten_passwd_title'] = '*forgotten_passwd_title*';
$config['server_name'] = '*server_name*';
$config['test_result'] = '*test_result*';

/**
 * Set to TRUE to enable logging of emails into the email_log_entries table.
 */
$config['log_emails'] = FALSE;

/*
// Uncomment these lines and populate the empty values if using the Microsoft
// Graph email helper.
$config['library'] = 'MsGraph';
$config['msgraph_tenant_id'] = '';
$config['msgraph_client_id'] = '';
$config['msgraph_client_secret'] = '';
// If MS Graph configured with an email footer, you can configure a spacer to
// add to the email body here to keep the body and footer separate.
$config['msgraph_footer_spacer_rows'] = 2;
*/

/*
// Uncomment these lines on a development server if you want emails to just get
// logged.
$config['library'] = 'DevLogger';
*/