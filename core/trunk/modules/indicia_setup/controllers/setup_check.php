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
 * @subpackage Controllers
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

defined('SYSPATH') or die('No direct script access.');

/**
 * Controller class for the setup_check page. Displays results
 * of a system configuration check.
 *
 * @package	Modules
 * @subpackage setup_check
 */
class Setup_Check_Controller extends Indicia_Controller {

  private $error=null;

  /**
   * Load the page which lists configuration checks.
   */
  public function index()
  {
    $this->template->title = 'Configuration Check';
    $this->template->content = new View('setup_check');
    $this->template->content->checks = config_test::check_config();
  }

  /**
   * Load the configuration of emails view.
   */
  public function config_email()
  {
    $this->template->title = 'Email Configuration';
    $this->template->content = new View('fixers/config_email');
    $this->template->content->error = $this->error;
    $this->error=null;
  }

  /**
   * Save the results of a configuration of emails form.
   */
  public function config_email_save()
  {
    $source = dirname(dirname(dirname(dirname(__file__)))) . "/application/config/email.php.example";
    $dest = dirname(dirname(dirname(dirname(__file__)))) . "/application/config/email.php";
    try {
      unlink($dest);
    } catch (Exception $e) {
      // file doesn't exist?'
    }
    if(false === ($_source_content = file_get_contents($source)))
    {
        $this->view_var['error_general'][] = Kohana::lang('setup.error_db_setup');
        Kohana::log("error", "Cant read file: ". $source);
        return false;
    }
    // Now save the POST form values into the config file
    foreach ($_POST as $field => $value) {
      $_source_content = str_replace("*$field*", $value, $_source_content);
    }

    if(false === file_put_contents($dest, $_source_content))
    {
      $this->error = "Email configuration failed as the file $file could not be written.";
      Kohana::log("error", "Can't write file: $file");
      $this->config_email();
      return;
    }
    try {
      $swift = email::connect();
      $message = new Swift_Message('Setup test',
          'Email to test the Indicia server email configuration. Do not reply to this email.',
          'text/html');
      $person = ORM::factory('person', $_SESSION['auth_user']->person_id);
      $recipients = new Swift_RecipientList();
      $recipients->addTo($person->email_address, $person->first_name.' '.$person->surname);
      if ($swift->send($message, $recipients, $_POST['address'])==1) {
         $_source_content = str_replace("*test_result*", 'pass', $_source_content);
         file_put_contents($dest, $_source_content);
         url::redirect('setup_check');
      } else {
        $this->error = "Please check your email configuration. The test email was not sent successfully.";
        $this->config_email();
      }
    } catch (ErrorException $e) {
      // Swift mailer messages tend to have the error message as the last part, with each part colon separated.
      $msg = explode(':', $e->getMessage());
      $this->error = $msg[count($msg)-1];
      $this->config_email();
    }
  }

}

?>