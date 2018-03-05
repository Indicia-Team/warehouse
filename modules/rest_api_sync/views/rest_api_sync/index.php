<?php if ($noServerConfig) : ?>
<div class="alert alert-warning"><strong>Warning!</strong> no configurations defined for the rest_api_sync module.</div>
<?php else : ?>
<button type="button" class="button button-primary" id="start-sync">Go</button>
<div id="output">
  <div id="progress"></div>
</div>
<?php endif; ?>
