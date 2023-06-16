<?php

warehouse::loadHelpers(['data_entry_helper']);
helper_base::add_resource('indiciaFns');
helper_base::$indiciaData['siteUrl'] = url::site();
echo data_entry_helper::dump_javascript();

?>

<button type="button" class="btn btn-primary" id="move-batch">Move batch</button>

<button type="button" class="btn btn-primary" id="delete-batch">Delete batch</button>

<div class="alert alert-info" id="current-status"></div>

<textarea class="form-control" id="output"></textarea>
