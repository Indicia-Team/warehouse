
<p>The import has completed successfully.</p>
<label>Output log:
<textarea style="width: 100%" rows="15">
<?php 
  if (count($log)>0)
    echo implode("\n", $log); 
  else {
    echo "No action was taken";  
  }
?>
</textarea>
</label>