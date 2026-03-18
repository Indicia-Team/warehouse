<?php

/**
 * @file
 * Controller for email queue admin summary.
 *
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
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
 */

/**
 * Controller for Email queue admin page.
 */
class Email_Queue_Controller extends Indicia_Controller {

  /**
   * Display queue counts split by escalation priority.
   */
  public function index() {
    if (!(kohana::config('email.enable_send_rate_limit') ?? FALSE)) {
      throw new Kohana_404_Exception('The page you requested, email_queue, could not be found.');
    }
    $this->template->title = 'Email queue';
    $view = new View('email_queue/index');
    $view->counts = [
      'queued_no_escalation' => 0,
      'queued_immediate' => 0,
      'queued_immediate_high_priority' => 0,
      'failed' => 0,
    ];
    $view->tableExists = TRUE;
    $view->error = NULL;
    try {
      $counts = $this->db->query(<<<SQL
        SELECT
          COUNT(*) FILTER (WHERE status='Q' AND escalate_email_priority IS NULL) queued_no_escalation,
          COUNT(*) FILTER (WHERE status='Q' AND escalate_email_priority=1) queued_immediate,
          COUNT(*) FILTER (WHERE status='Q' AND escalate_email_priority=2) queued_immediate_high_priority,
          COUNT(*) FILTER (WHERE status='F') failed
        FROM email_send_queue
      SQL)->current();
      $view->counts = [
        'queued_no_escalation' => (int) $counts->queued_no_escalation,
        'queued_immediate' => (int) $counts->queued_immediate,
        'queued_immediate_high_priority' => (int) $counts->queued_immediate_high_priority,
        'failed' => (int) $counts->failed,
      ];
    }
    catch (Exception $e) {
      $view->tableExists = FALSE;
      $view->error = $e->getMessage();
    }
    $this->template->content = $view;
  }

  /**
   * Restrict page to core admin users.
   *
   * @return bool
   *   True if user has core admin role.
   */
  protected function page_authorised() {
    return $this->auth->logged_in('CoreAdmin');
  }

}
