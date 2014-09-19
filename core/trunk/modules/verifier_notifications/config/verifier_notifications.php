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
 * @package	Verifier Notifications
 * @subpackage Config
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */
 
// The following configuration should hold the list of websites which host a verification portal and 
// where the verifiers should be automatically notified of incoming records. Each entry consists of
// a website ID, then name of the website, then the verification page URL path.
$config['verification_urls'] = array(
  array('website_id' => 1, 'title' => 'iRecord', 'url' => 'http://www.brc.ac.uk/irecord/verification')
);
// same again but for pending check/moderation tasks
$config['moderation_urls'] = array(
  array('website_id' => 1, 'title' => 'iRecord', 'url' => 'http://www.brc.ac.uk/irecord/moderation')
);
?>