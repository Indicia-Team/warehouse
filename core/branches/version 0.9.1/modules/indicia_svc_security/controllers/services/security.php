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
 * @package Services
 * @author  Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link  http://code.google.com/p/indicia/
 */

/**
 * Class to allow the client website code to obtain authorisation tokens.
 * @author Indicia Team
 * @package Services
 * @subpackage Security
 */
class Security_Controller extends Service_Base_Controller {

  /**
   * Obtain a write nonce (authorisation token). Uses the posted webiste_id to store the nonce against.
   * @return string Nonce token
   */
  public function get_nonce() {
    $nonce = security::create_nonce('write', $_POST['website_id']);
    echo $nonce;
  }

  /**
   * Obtain a read nonce (authorisation token). Uses the posted webiste_id to store the nonce against.
   * @return string Nonce token
   */
  public function get_read_nonce() {
    $nonce = security::create_nonce('read', $_POST['website_id']);
    echo $nonce;
  }
  
  /**
   * Obtain a pair of read and write nonces (authorisation tokens). Uses the posted website_id to store the nonces against.
   * @return string Nonce tokens in a JSON format.
   */
  public function get_read_write_nonces() {
    $writeNonce = security::create_nonce('write', $_POST['website_id']);
    $readNonce = security::create_nonce('read', $_POST['website_id']);
    echo '{"read":"'.$readNonce.'","write":"'.$writeNonce.'"}';
  }
 


}

?>
