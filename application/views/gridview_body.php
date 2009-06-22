<?php
$i = 0;
foreach ($table as $item)
{
  echo "<tr class='";
  echo ($i%2 == 0) ? "evenRow" : "oddRow";
  echo "'>";
  $fields = array();
  $a = $item->as_array();
  foreach ($columns as $col => $name)
  {
    if (array_key_exists($col, $a))
    {
      $fields[$col] = $a[$col];
    }
  }
  foreach ($fields as $field) {
    echo "<td>";
    if ($field!==NULL)
    {
      if (preg_match('/^http/', $field))
      echo html::anchor($field, $field);
      else
  echo $field;
    }
    echo "</td>";
  }
  foreach ($actionColumns as $name => $action)
  {
    echo "<td>";
    $action = preg_replace("/£([a-zA-Z_\-]+)£/e", "\$item->__get('$1')", $action);
    echo html::anchor($action, $name);
    echo "</td>";
  }
  $i++;
  echo "</tr>";
}
?>
