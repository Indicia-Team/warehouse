<?php
/**
 * Organise attributes
 * 
 * The $values['attributes'] array has multi-value attributes on separate rows, so organise these into a sub array
 */
function organise_values_attribute_array($attributeModel, $valuesAttributes) {
  $readAuth = data_entry_helper::get_read_auth(0 - $_SESSION['auth_user']->id, kohana::config('indicia.private_key'));
  $attrsWithMulti = array();
  foreach ($valuesAttributes as $key => $attr) {
    if (!empty($attr['multi_value']) && $attr['multi_value'] === 't') {
      // The $valuesAttributes array actually has multi-value attributes on separate rows
      // This means we need to gather all the rows into sub array.
      $attrsWithMulti[$attr[$attributeModel.'_id']][] = $attr;
    } else {
      // Single value attributes have just one row, so can save that directly
      $attrsWithMulti[$attr[$attributeModel.'_id']] = $attr;
    }
  }
  return $attrsWithMulti;
}

/**
 * Handle multi value attributes
 * 
 * Draw multi value attributes to the screen
 */
function handle_multi_value_attributes($fieldPrefix, $attributeId, $multiAttr, &$values) {
  $readAuth = data_entry_helper::get_read_auth(0 - $_SESSION['auth_user']->id, kohana::config('indicia.private_key'));
  $valueToInsert = array();
  $attrCaption = '';
  $attrTermlistId = null;
  $default = array();
  // Multi-attributes are over multiple rows so cycle subarray
  foreach ($multiAttr as $multiAttrElementIdx => $multiAttrElement) {
    $attrCaption = $multiAttrElement['caption'];
    $name = $fieldPrefix . ':' . $attributeId.'[]';
    if (!empty($multiAttrElement['termlist_id'])) {
      $attrTermlistId = $multiAttrElement['termlist_id'];
      foreach ($values['terms_' . $multiAttrElement['termlist_id']] as $number => $word) {
        // Remove this element from the drop-downs as we don't need it and it causes problems
        if ($word === '-no value-') {
          unset($values['terms_' . $multiAttrElement['termlist_id']][$number]);
        }
      }
    }
    $datatype = '';
    switch ($multiAttrElement['data_type']) {
      case 'L':
        $datatype = 'lookup';
        break;
      case 'T':
        $datatype = 'text';
        break;
      default:
        $datatype = 'unsupported';
    }
    // Set up default value for existing data
    if (!empty($multiAttrElement['raw_value'])) {
      $valueToInsert = json_decode($multiAttrElement['raw_value']);
      $valueToInsert = (array)$valueToInsert;
      $valueToInsert = $valueToInsert[0];
      $default[]=array(
        'fieldname' => $fieldPrefix . ':' .$attributeId.':'.$multiAttrElement['id'],
        'default' => json_encode(array($valueToInsert)),
        'defaultUpper' => null,
      );
    }
  }
  if ($datatype !== 'unsupported') {
    if ($datatype === 'lookup') {
      $columns = [
        '0' => array('label' => $attrCaption,
        'datatype' => $datatype,
        'lookupValues' => $values['terms_' . $attrTermlistId],
        'termlist_id' => $attrTermlistId)
      ];
    } elseif ($datatype === 'text') {
      $columns = [
        '0' => array('label' => $attrCaption,
        'datatype' => $datatype)
      ];
    }
    echo data_entry_helper::complex_attr_grid([
      'fieldname' => $name,
      'columns' => $columns,
      'default' => $default,
      'defaultRows' => 1,
      'extraParams' => $readAuth
    ])."<br>";
  } else {
    echo "The multi-value attribute \"".$multiAttrElement['caption']."\" (of type \"".$multiAttrElement['data_type']."\") has been specified. 
    Please note that the Warehouse UI currently only supports text or lookup multi-value attribute data editing.<br>";
  }
}
?>