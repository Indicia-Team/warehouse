<?php

/**
 * @file
 * View template for the email queue admin summary page.
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

if (!$tableExists) {
  echo '<div class="alert alert-warning">Email queue table not available. Run database upgrade to create email_send_queue.</div>';
  if (!empty($error)) {
    echo '<div class="alert alert-info">' . html::specialchars($error) . '</div>';
  }
  return;
}

?>
<p>Summary of queued and failed emails.</p>

<table class="table table-striped table-bordered" style="max-width: 680px;">
  <thead>
    <tr>
      <th>Metric</th>
      <th style="width: 160px;">Count</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Queued emails: no escalation</td>
      <td><?php echo (int) $counts['queued_no_escalation']; ?></td>
    </tr>
    <tr>
      <td>Queued emails: immediate (escalation 1)</td>
      <td><?php echo (int) $counts['queued_immediate']; ?></td>
    </tr>
    <tr>
      <td>Queued emails: immediate high priority (escalation 2)</td>
      <td><?php echo (int) $counts['queued_immediate_high_priority']; ?></td>
    </tr>
    <tr>
      <td>Failed queued emails</td>
      <td><?php echo (int) $counts['failed']; ?></td>
    </tr>
  </tbody>
</table>
