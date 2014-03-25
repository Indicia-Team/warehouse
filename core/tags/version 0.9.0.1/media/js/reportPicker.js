/* Indicia, the OPAL Online Recording Toolkit.
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
 */

/**
 * When clicking on an entry in the list of reports available, display the title and description of that report
 * in the metadata panel on the report_picker.
 */
function displayReportMetadata(control, path) {
  // safe for Windows paths
  path = path.replace('\\','/');
  path = path.split('/');
  var current = indiciaData.reportList;
  jQuery.each(path, function(idx, item) {
    current = current[item];
    if (current.type === 'report') {
      jQuery('#' + control + ' .report-metadata').html('<strong>' + current.title+'</strong><br/>' +
          '<p>' + current.description + '</p>');
    } else {
      current = current['content'];
    }
  });
}