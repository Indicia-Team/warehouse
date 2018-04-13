<fieldset>
  <legend><?php echo $other_data['name']; ?> attribute species list allocation</legend>
  <div class="alert alert-info">
    Tick the species lists below that you would like this attribute to be made available for.
  </div>
  <ol>
    <?php
    foreach ($other_data['taxonLists'] as $list) {
      $checked = !empty($list->taxon_lists_taxa_taxon_list_attributes_id) ? ' checked="checked"' : '';
      echo <<<HTML
<div class="checkbox">
  <label>
    <input type="checkbox" name="taxon_list_$list->id" value="1"$checked>
    $list->title
  </label>
</div>
HTML;
    }
    ?>
  </ol>
</fieldset>
