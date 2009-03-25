<div>
<table>
<thead>
<th>Report</th><th>Description</th>
</thead>
<tbody>
<?php 
foreach ($localReports['reportList'] as $lr)
{
  echo "<tr>";
  echo "<td>".html::anchor("report/local/".$lr['name'], $lr['title'])."</td>";
  echo "<td>".$lr['description']."</td>";
  echo "</tr>";
}
?>
</tbody>
</table>
</div>

