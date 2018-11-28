<fieldset>
  <legend><?php echo $other_data['name']; ?> Attribute website allocation</legend>
  <p>Please tick the boxes for the websites that this attribute is available for.</p>
  <?php
  if (!is_null($this->auth_filter) && $this->auth_filter['field'] === 'website_id') {
    $websites = ORM::factory('website')
      ->in('id', $this->auth_filter['values'])
      ->where(array('deleted' => 'f'))
      ->orderby('title', 'asc')
      ->find_all();
    // Output a hidden input to list the websites we are allowed update against.
    echo '<input type="hidden" name="restricted-to-websites" value="' . implode(',', $this->auth_filter['values']) . '"/>';
  }
  else {
    $websites = ORM::factory('website')
      ->where(array('deleted' => 'f'))
      ->orderby('title', 'asc')
      ->find_all();
  }
  foreach ($websites as $website) {
    $webrec = ORM::factory($other_data['webrec_entity'])->where([
      $other_data['webrec_key'] => $model->id,
      'website_id' => $website->id,
      'deleted' => 'f',
    ])->find();
    $checked = $webrec->loaded ? ' checked="checked"' : '';
    echo <<<HTML
<div class="checkbox">
  <label>
    <input type="checkbox" name="website_$website->id" value="1"$checked>
    $website->title
  </label>
</div>

HTML;
  }
  ?>
</fieldset>
