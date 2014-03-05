<?php

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
 * @package	Core
 * @subpackage Views
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

?><h1>Forgotten Password</h1>
<p>This email was sent automatically by the <?php echo $server?> server in response to your request to recover your password. This process is designed for your protection - only you, the recipient of this email, can take the next step in the password recovery process.</p>
<p>To reset your password click on the link below,  enter your new password (twice) and click Submit. You will then be able to access the system.
<p><?php echo $new_password_link?></p>
<p>If nothing happens when you click on the link, copy and paste the link into the address bar of your web browser.</p>
<p>Please protect your password and never give your password to anyone.</p>
<p>Please do not reply to this email. This mailbox is not monitored and you will not receive a response.</p>
