<p>The following list of fields are available for submission when building code which
  posts records into this survey. Those marked with a <span class="deh-required">*</span>
  are mandatory.</p>
<table class="ui-widget">
  <thead class="ui-widget-header">
  <tr>
    <th>Database field name</th>
    <th>Description</th>
    <th>Notes</th>
  </tr>
  </thead>
  <tbody class="ui-widget-content">
  <?php
  $odd = false;
  foreach ($fields as $field=>$description) {
    $isRequired = in_array($field, $requiredFields);
    $note = '';
    if (!$description)
      $description = ucFirst(preg_replace('/[\s_:]+/', ' ', str_replace(array('fk_', '_id'), array('', ''), $field)));
    if (strpos($field, ':fk_')!==false) {
      // also output a row for an ID submission rather than a lookup
      $field .= ' or ' . str_replace(':fk_', ':', $field);
      $note = 'Use fk_* to submit text for automatic lookup, otherwise a record ID';
    }
    if ($isRequired)
      $description .= ' <span class="deh-required">*</span>';
    $oddClass = $odd ? ' class="odd"' : '';
    echo "<tr$oddClass><td>$field</td><td>$description</td><td>$note</td></tr>";
    $odd = !$odd;
  }
  ?>
  </tbody>
</table>
