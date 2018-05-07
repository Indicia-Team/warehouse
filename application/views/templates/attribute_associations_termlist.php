<fieldset>
  <legend><?php echo $other_data['name']; ?> attribute term list allocation</legend>
  <div class="alert alert-info">
    Tick the term lists below that you would like this attribute to be made available for.
  </div>
  <ol>
    <?php
    foreach ($other_data['termLists'] as $list) {
      $checked = !empty($list->termlists_termlists_term_attributes_id) ? ' checked="checked"' : '';
      echo <<<HTML
<div class="checkbox">
  <label>
    <input type="checkbox" name="termlist_$list->id" value="1"$checked>
    $list->title
  </label>
</div>
HTML;
    }
    ?>
  </ol>
</fieldset>
