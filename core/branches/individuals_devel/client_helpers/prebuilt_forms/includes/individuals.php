<?php
class wwt_individual{
public $sjoId;
public $subjectTypeId;
public $parentId,$fieldPrefix;
public $positionInFamily;
public $recordNumber;
public $familyMembers;
public $indivsInFamily;
public $identifiers;
public $highlight;
static $options;
static $args;
static $auth;
static $tabalias;

  protected static $loadedSampleId;
  protected static $loadedOccurrenceId;
  protected static $occurrenceIds = array();
  protected static $loadedSubjectObservationId;
  protected static $subjectObservationIds;
public static $mode;

	public function __construct($taxIdx,$auth=null,$args=null,$options=null,$tabalias=null){
	// actions when created
	//only set settings variables once
	if(!isset($this->auth)) {
		$this->auth=$auth;
		$this->options=$options;
		$this->args=$args;
		$this->tabalias=$tabalias;
	}
	$this->setupOptions();
	if(isset($taxIdx)&&$taxIdx>-1){ //from actual record
	//set field prefix
	 $this->fieldPrefix='idn:'.$taxIdx.':';
	 $this->sjoId=data_entry_helper::$entity_to_load[$this->fieldPrefix.'subject_observation:id'];
 	 $this->subjectTypeId=data_entry_helper::$entity_to_load[$this->fieldPrefix.'subject_observation:subject_type_id'];
	 $this->recordNumber=$taxIdx;
	 $this->familyMembers=array(); //Initial state
  	 if (isset(data_entry_helper::$entity_to_load[$this->fieldPrefix.'subject_observation:parent_id'])) {
	  $this->parentId=data_entry_helper::$entity_to_load[$this->fieldPrefix.'subject_observation:parent_id'];
 	  }	 
 	  else $this->parentId='';
	 }
	 else {} // simply a placeholder ready for replacement
	}
	public function setupOptions(){
	//copied from colourmark - should set up all the identifier settings for form
    // we need to control which items are lockable if locking requested
    if (!empty($this->options['lockable']) && $this->options['lockable']==true) {
      $this->options['identifiers_lockable'] = $this->options['lockable'];
    } else {
      $this->options['identifiers_lockable'] = '';
    }
    unset($this->options['lockable']);

    // get the identifier type data
    $filter = array('termlist_external_key' => 'indicia:assoc:identifier_type',);
    $dataOpts = array(
      'table' => 'termlists_term',
      'extraParams' => $this->auth['read'] + $filter,
    );
    $this->options['identifierTypes'] = data_entry_helper::get_population_data($dataOpts);
        // get the identifier attribute type data
    $dataOpts = array(
      'table' => 'identifier_attribute',
      'extraParams' => $this->auth['read'],
    );
    $this->options['idnAttributeTypes'] = data_entry_helper::get_population_data($dataOpts);
        // set up the known system types for identifier attributes
    $this->options['baseColourId'] = -1;
    $this->options['textColourId'] = -1;
    $this->options['sequenceId'] = -1;
    $this->options['positionId'] = -1;
    foreach ($this->options['idnAttributeTypes'] as $idnAttributeType) {
      if (!empty($idnAttributeType['system_function'])) {
        switch ($idnAttributeType['system_function']) {
          case 'base_colour' :
            $this->options['baseColourId'] = $idnAttributeType['id'];
            break;
          case 'text_colour' :
            $this->options['textColourId'] = $idnAttributeType['id'];
            break;
          case 'sequence' :
            $this->options['sequenceId'] = $idnAttributeType['id'];
            break;
          case 'position' :
            $this->options['positionId'] = $idnAttributeType['id'];
            break;
        }
      }
    }
    
    // get the subject observation attribute type data
    $dataOpts = array(
      'table' => 'subject_observation_attribute',
      'extraParams' => $this->auth['read'],
    );
    $this->options['sjoAttributeTypes'] = data_entry_helper::get_population_data($dataOpts);
//    file_put_contents('/var/www/vhosts/monitoring.wwt.org.uk/httpdocs/recording/tmp/debug.txt',print_r($options['sjoAttributeTypes'],true));

    // set up the known system types for subject_observation attributes
    $this->options['attachmentId'] = -1;
    $this->options['genderId'] = -1;
    $this->options['stageId'] = -1;
    $this->options['lifeStatusId'] = -1;
    $this->options['unmarkedAdults'] = -1; // not a system function
    foreach ($this->options['sjoAttributeTypes'] as $sjoAttributeType) {
      if (!empty($sjoAttributeType['system_function'])) {
        switch ($sjoAttributeType['system_function']) {
          case 'attachment' :
            $this->options['attachmentId'] = $sjoAttributeType['id'];
            break;
          case 'gender' :
            $this->options['genderId'] = $sjoAttributeType['id'];
            break;
          case 'stage' :
            $this->options['stageId'] = $sjoAttributeType['id'];
            break;
          case 'life_status' :
            $this->options['lifeStatusId'] = $sjoAttributeType['id'];
            break;
//          case 'unmarked_adults' :
//            $options['unmarkedAdults'] = $sjoAttributeType['id'];
//            break;
        }
      }
    }
        // get the identifiers subject observation attribute type data
    $dataOpts = array(
      'table' => 'identifiers_subject_observation_attribute',
      'extraParams' => $this->auth['read'],
    );
    $this->options['isoAttributeTypes'] = data_entry_helper::get_population_data($dataOpts);
    
    // set up the known system types for subject_observation attributes
    $this->options['conditionsId'] = -1;
    foreach ($this->options['isoAttributeTypes'] as $isoAttributeType) {
      if (!empty($isoAttributeType['system_function'])) {
        switch ($isoAttributeType['system_function']) {
          case 'identifier_condition' :
            $this->options['conditionsId'] = $isoAttributeType['id'];
            break;
        }
      }
    }



	}
	
	
	public function setRecordNumber($recordNumber){
	$this->recordNumber=$recordNumber;
	}
	public function getRecordNumber(){
	return $this->recordNumber;
	}
	public function setFieldPrefix($fieldPrefix){
	$this->fieldPrefix=$fieldPrefix;
	}
	public function getFieldPrefix(){
	return $this->fieldPrefix;
	}
	public function setSjoId($sjoId){
	$this->sjoId=$sjoId;
	//also set parent_id
	if (isset(data_entry_helper::$entity_to_load[$this->fieldPrefix.'subject_observation:parent_id'])) {
	 $this->parentId=data_entry_helper::$entity_to_load[$this->fieldPrefix.'subject_observation:parent_id'];
 	} 
	 else $this->parentId='';
	 //also set subject_type_id

	if (isset(data_entry_helper::$entity_to_load[$this->fieldPrefix.'subject_observation:subject_type_id'])) {
	 $this->subjectTypeId=data_entry_helper::$entity_to_load[$this->fieldPrefix.'subject_observation:subject_type_id'];
	}

	}
	public function getSjoId(){
	 return $this->sjoId;
	}
	public function getSubjectTypeId(){
	 return $this->subjectTypeId;
	}
	public function getParentId(){
	 return $this->parentId;
	}
	public function addIndivs($indivs){
	if(!is_array($indivs)) $this->familyMembers[]=$indivs;
	else $this->familyMembers=array_merge($this->familyMembers,$indivs);
	$this->setCountOfIndivsInFamily();
	$this->setFamilyOrder();
	}
	public function setFamilyOrder(){
	$x=0;
	foreach($this->familyMembers as $indiv){
	 $indiv->positionInFamily=$x++;
	 $indiv->highlight=($x % 2)?'odd':'even'; // odd/even
	 }
	}
	public function getHighlight(){
	return $this->highlight;
	}
	public function setCountOfIndivsInFamily(){
	// only if family!!!!
	if(isset($this->$familyMembers))
		$this->indivsInFamily=count($this->$familyMembers);
	else $this->indivsInFamily=0;
	}
	public function getCountOfIndivsInFamily(){
	return $this->indivsInFamily;
	}

	public function getIndivs(){
	return $this->familyMembers;
	}
	public function isPartOfFamily(){
	if(isset($this->parentId))
		return $this->parentId;
	else return false;
	}
	public function isFamily(){
	if($this->subjectTypeId==113) return true;
	else return false;
	}
	
	public function showIdentifier($identType,$identName,$identFormat,$taxIdx,$marktype){
//	    // setup and call function for identifier
	$options=$this->options;    
	$args=$this->args;    
    $options['identifierName'] = '';    $options['identifierTypeId'] = '';
    foreach ($options['identifierTypes'] as $identifier_type) {
    unset($hidepos,$hidecolour,$hiddenValue,$baseColourId,$textColourId);
      if ($identifier_type['id']==$args[$identType."_type"]) {
// Want to use identifier position - not type 
        $options['identifierName'] = lang::get($marktype);
//        $options['identifierName'] = $identifier_type['term'];
        $options['identifierTypeId'] = $identifier_type['id'];
        break;
      }
    }
    switch($marktype){
    	case 'Neck':
    		$hidepos=true;
	    	$hidecolour=false;
#	    	$hiddenValue=$args['left_'.$identType.'_position'];
	    	$hiddenValue=176;// set position to neck
	    	break;
    	case 'Metal Ring':
	    	$hidepos=false;
	    	$hidecolour=true;
	    	$baseColourId=149;
	    	$textColourId=137;
	    	//need to force black and grey colours
	    	break;
    	case 'Colour Leg Ring':
    	case 'Colour Leg Ring 2':
	    	$hidepos=false;
	    	$hidecolour=false;
	    	break;
   	default:
    		$hidepos=true;
	    	$hidecolour=false;
    }


$options['attrList'][]=iform_wwt_colour_marked_clone::setIdnArray($options['positionId'],false,$hidepos,$hiddenValue);

$options['attrList'][]=iform_wwt_colour_marked_clone::setIdnArray($options['baseColourId'],!$hidecolour,$hidecolour,$baseColourId);
$options['attrList'][]=iform_wwt_colour_marked_clone::setIdnArray($options['textColourId'],!$hidecolour,$hidecolour,$textColourId);
$options['attrList'][]=iform_wwt_colour_marked_clone::setIdnArray($options['sequenceId'],false,false,$hiddenValue);

$options['attrList'][]=array('attrType' => 'iso', 'typeId' => $options['conditionsId'], 'lockable' => false, 'hidden' => false,);
    
    
    $options['fieldprefix'] = 'idn:'.$taxIdx.":$identName:";
    $options['classprefix'] = "idn-$identName-";

    $options['seq_maxlength'] = (!empty($args[$identType.'_max_length'])) ? $args[$identType.'_max_length'] : '';

        if (!empty($args[$identType.'_regex'])) {
      $options['seq_format_class'] = $identFormat.'Format';
    }
    $r = self::get_control_identifier($options);

    if (!empty($args[$identType.'_regex'])) {
      unset($options['seq_format_class']);
    }

return $r;
	
	}
	public function get_control_identifier($options){
	$args=$this->args;
	$auth=$this->auth;
	$tabalias=$this->tabalias;
	
	    #creates a new accordion panel for an identifier
    #fieldprefix is the bird number for the form - i.e. starts at 0
    $fieldPrefix = !empty($options['fieldprefix']) ? $options['fieldprefix'] : '';
    $r = '';
    $r .= '<h3 id="'.$fieldPrefix.'header" class="idn:accordion:header"><a href="#">'.$options['identifierName'].'</a></h2>';
    $r .= '<div id="'.$fieldPrefix.'panel" class="idn:accordion:panel">';
    $r .= '<input type="hidden" name="'.$fieldPrefix.'identifier:identifier_type_id" value="'.$options['identifierTypeId'].'" />'."\n";
    $r .= '<input type="hidden" name="'.$fieldPrefix.'identifier:coded_value" id="'.$fieldPrefix.'identifier:coded_value" class="identifier_coded_value" value="" />'."\n";
    $val = isset(data_entry_helper::$entity_to_load[$fieldPrefix.'identifier:id']) ? data_entry_helper::$entity_to_load[$fieldPrefix.'identifier:id'] : '0';
    $r .= '<input type="hidden" name="'.$fieldPrefix.'identifier:id" id="'.$fieldPrefix.'identifier:id" class="identifier_id" value="'.$val.'" />'."\n";
    if (isset(data_entry_helper::$entity_to_load[$fieldPrefix.'identifiers_subject_observation:id'])) {
      $r .= '<input type="hidden" id="'.$fieldPrefix.'identifiers_subject_observation:id" name="'.$fieldPrefix.'identifiers_subject_observation:id" '.
        'value="'.data_entry_helper::$entity_to_load[$fieldPrefix.'identifiers_subject_observation:id'].'" />'."\n";    
    }
    
    // checkbox - (now hidden by CSS, probably should refactor to hidden input?)
    $r .= data_entry_helper::checkbox(array_merge(array(
      // 'label' => lang::get('Is this identifier being recorded?'),
      'label' => '',
      'fieldname' => $fieldPrefix.'identifier:checkbox',
      'lockable'=>false,
      'class'=>'identifier_checkbox identifierRequired noDuplicateIdentifiers',
    ), $options));
      
    // loop through the requested attributes and output an appropriate control
    $classes = $options['class'];
    foreach ($options['attrList'] as $attribute) {
      // find the definition of this attribute
      $found = false;
      if ($attribute['attrType']==='idn') {
        foreach ($options['idnAttributeTypes'] as $attrType) {
          if ($attrType['id']===$attribute['typeId']) { // Is allowable attribute
            $found = true;
            break;
          }
        }
      } else if ($attribute['attrType']==='iso') {
        foreach ($options['isoAttributeTypes'] as $attrType) {
          if ($attrType['id']===$attribute['typeId']) {
            $found = true;
            break;
          }
        }
      }
      if (!$found) {
        throw new exception(lang::get('Unknown '.$attribute['attrType'].' attribute type id ['.$attribute['typeId'].'] specified for '.
          $options['identifierName'].' in Identifier Attributes array.'));
      }
      // setup any locking
      if (!empty($attribute['lockable']) && $attribute['lockable']===true) {
        $options['lockable'] = $options['identifiers_lockable'];
      }
      // setup any data filters
      if ($attribute['attrType']==='idn' && $options['baseColourId']==$attribute['typeId']) {
        if (!empty($args['base_colours'])) {
//                  $r.=print_r($attribute,true);
//                  $r.=print_r($args['baseColourId'],true);
          // filter the colours available
          $query = array('in'=>array('id', $args['base_colours']));
        }
        $attr_name = 'base-colour';
      } elseif ($attribute['attrType']==='idn' && $options['textColourId']==$attribute['typeId']) {
//                  $r.=print_r($attribute,true);
//                  $r.=print_r($args['textColourId'],true);
        if (!empty($args['text_colours'])) {
          // filter the colours available
          $query = array('in'=>array('id', $args['text_colours']));
        }
        $attr_name = 'text-colour';
      } elseif ($attribute['attrType']==='idn' && $options['positionId']==$attribute['typeId']) {
      $options['class'] = strstr($options['class'], 'select_position') ? $options['class'] : $options['class'].' select_position';

//                  $r.=print_r($attribute,true);
//                  $r.=print_r($args['position'],true);
        $attr_name = 'position';
        if (count($args['position']) > 0) {
          // filter the identifier position available
          $query = array('in'=>array('id', $args['position']));
        }
      } elseif ($attribute['attrType']==='idn' && $options['sequenceId']==$attribute['typeId']) {
        $attr_name = 'sequence';
        $options['maxlength'] = $options['seq_maxlength'] ? $options['seq_maxlength'] : '';
        if ($options['seq_format_class']) {
          $options['class'] = empty($options['class']) ? $options['seq_format_class'] : 
            (strstr($options['class'], $options['seq_format_class']) ? $options['class'] : $options['class'].' '.$options['seq_format_class']);
        }
      } elseif ($attribute['attrType']==='iso' && $options['conditionsId']==$attribute['typeId']) {
        // filter the identifier conditions available
        if ($options['identifierTypeId']==$args['neck_collar_type'] && !empty($args['neck_collar_conditions'])) {
          $query = array('in'=>array('id', $args['neck_collar_conditions']));
        } elseif ($options['identifierTypeId']==$args['enscribed_colour_ring_type'] && !empty($args['coloured_ring_conditions'])) {
          $query = array('in'=>array('id', $args['coloured_ring_conditions']));
        } elseif ($options['identifierTypeId']==$args['metal_ring_type'] && !empty($args['metal_ring_conditions'])) {
          $query = array('in'=>array('id', $args['metal_ring_conditions']));
        }
        $attr_name = 'conditions';
      }

      // add classes as identifiers
      $options['class'] = empty($options['class']) ? $options['classprefix'].$attr_name : 
        (strstr($options['class'], $options['classprefix'].$attr_name) ? $options['class'] : $options['class'].' '.$options['classprefix'].$attr_name);
      $options['class'] = $options['class'].' idn-'.$attr_name;
      if ($attribute['attrType']==='idn' && ($options['baseColourId']==$attribute['typeId'] || $options['textColourId']==$attribute['typeId'])) {
        $options['class'] = strstr($options['class'], 'select_colour') ? $options['class'] : $options['class'].' select_colour';
        $options['class'] = strstr($options['class'], '
        textAndBaseMustDiffer') ? $options['class'] : $options['class'].' textAndBaseMustDiffer';
      }
      if ($attribute['attrType']==='idn' && $options['sequenceId']==$attribute['typeId']) {
        $options['class'] = strstr($options['class'], 'identifier_sequence') ? $options['class'] : $options['class'].' identifier_sequence';
      }
    
      if (!empty($attribute['hidden']) && $attribute['hidden']===true) {
        $dataType = 'H'; // hidden
        if (!empty($attribute['hiddenValue'])) {
          $dataDefault = $attribute['hiddenValue'];
        } else {
          $dataDefault = '';
        }
      } else {
        $dataType = $attrType['data_type'];
      }
      
      // output an appropriate control for the attribute data type
      switch ($dataType) {
        case 'D': //Date
        case 'V':
          $r .= data_entry_helper::date_picker(array_merge(array(
            'label' => lang::get($attrType['caption']),
            'fieldname' => $fieldPrefix.$attribute['attrType'].'Attr:'.$attrType['id'],
          ), $options));
          break;
        case 'L': // Lookup
          $filter = array('termlist_id'=>$attrType['termlist_id'],);
          if (!empty($query)) {
            $filter += array('query'=>json_encode($query),);
          }
          $extraParams = array_merge($filter, $auth['read']);
          if ($attribute['attrType']==='iso' && $options['conditionsId']==$attribute['typeId']) {
            $fieldname = $fieldPrefix.$attribute['attrType'].'Attr:'.$attrType['id'];
            $default = array();
            // if this attribute exists on DB, we need to write a hidden with id appended to fieldname and set defaults for checkboxes
            if (is_array(data_entry_helper::$entity_to_load)) {
              $stored_keys = preg_grep('/^'.$fieldname.':[0-9]+$/', array_keys(data_entry_helper::$entity_to_load));
              foreach ($stored_keys as $stored_key) {
                $r .= '<input type="hidden" name="'.$stored_key.'" value="" />';
                $default[] = array('fieldname' => $stored_key, 'default' => data_entry_helper::$entity_to_load[$stored_key]);
                unset(data_entry_helper::$entity_to_load[$stored_key]);
              }
            }
            $r .= data_entry_helper::checkbox_group(array_merge(array(
              'label' => lang::get($attrType['caption']),
              'fieldname' => $fieldname,
              'table'=>'termlists_term',
              'captionField'=>'term',
              'valueField'=>'id',
              'default'=>$default,
              'extraParams' => $extraParams,
            ), $options));
          } else {
            $r .= data_entry_helper::select(array_merge(array(
              'label' => lang::get($attrType['caption']),
              'fieldname' => $fieldPrefix.$attribute['attrType'].'Attr:'.$attrType['id'],
              'table'=>'termlists_term',
              'captionField'=>'term',
              'valueField'=>'id',
              'blankText' => '<Please select>',
              'extraParams' => $extraParams,
            ), $options));
          }
          break;
        case 'B': //checkbox
          $r .= data_entry_helper::checkbox(array_merge(array(
            'label' => lang::get($attrType['caption']),
            'fieldname' => $fieldPrefix.$attribute['attrType'].'Attr:'.$attrType['id'],
          ), $options));
          break;
        case 'H': //hidden
          // Any multi-value attributes shown as hidden will be single-valued
          // so transform the array to a scalar
          $fieldname = $fieldPrefix.$attribute['attrType'].'Attr:'.$attrType['id'];
          if (!empty(data_entry_helper::$entity_to_load[$fieldname])
            && is_array(data_entry_helper::$entity_to_load[$fieldname])) {
            data_entry_helper::$entity_to_load[$fieldname]
              = data_entry_helper::$entity_to_load[$fieldname][0];
          }
          $r .= data_entry_helper::hidden_text(array_merge(array(
            'fieldname' => $fieldname,
            'default' => $dataDefault,
          ), $options));
          break;
        default: //text input
          $r .= data_entry_helper::text_input(array_merge(array(
            'label' => lang::get($attrType['caption']),
            'fieldname' => $fieldPrefix.$attribute['attrType'].'Attr:'.$attrType['id'],
          ), $options));
      }
      $options['class'] = $classes;
      if (isset($options['maxlength'])) {
        unset($options['maxlength']);
      }
      if (isset($options['lockable'])) {
        unset($options['lockable']);
      }
    }
    $r .= '</div>';
	return $r;
	}
	
	public function showIdentifiers($taxIdx,$tabalias){
	$options=$this->options;
	$args=$this->args;
	$auth=$this->auth;
$r= '<div id="'.$options['fieldprefix'].'accordion" class="idn-accordion">';
$r.=self::showIdentifier("neck_collar","neck-collar","collar",$taxIdx,'Neck');
$r.=self::showIdentifier("enscribed_colour_ring","colour-ring","colourRing",$taxIdx,'Colour Leg Ring');
$r.=self::showIdentifier("metal_ring","metal","metalRing",$taxIdx,'Metal Ring');
$r .= '</div>'; // end of identifier accordion

// other devices (trackers etc.)
if ($options['attachmentId'] > 0        && !empty($args['other_devices']) && count($args['other_devices']) > 0) {
		$r.=iform_wwt_colour_marked_clone::rfj_other_devices($options,$taxIdx,$this->args,$auth)    ;    
		}
  

return $r;
	}
	
	
public function showItem($fieldId,$label,$lookup,$default,$attribPrefix,$customoptions=array()){
if($fieldId=="taxa"){
      $sort_column='';
      $query = array('
      	in'=>array('id',array(11,12,13,14,15,16,17,18,19)),
      	'select'=>'taxon_list_id=1',
      	);
     
      $filter = array('query'=>json_encode($query),'orderby'=>$sort_column,);
//      $extraParams = array_merge($filter, $this->auth['read']);
      $extraParams = $this->auth['read'];

        $options=array_merge(array(
        'label' => 'Species of the bird',
//        'fieldname' => 'idn:0:subject_observation:taxa',// determines value from post vars
        'fieldname' => 'idn:0:occurrence:taxa_taxon_list_id',// determines value from post vars

        'id' => 'idn:0:occurrence:taxa_taxon_list_id', // determines the id for the html element
        'table'=>'taxa_taxon_list',
        'captionField'=>'taxon',
        'valueField'=>'id',
        'extraParams' => $extraParams,
         ), $this->options,$customoptions);
//      $r .= data_entry_helper::text_input($options);
      $r .= data_entry_helper::select($options);

	}
else {
$sort_column='sort_order';
$table='termlists_term';
$fieldprefix=$this->getFieldPrefix();
$options=$this->options;

    if ($options[$fieldId] > 0 && !empty($this->args[$lookup]) && count($this->args[$lookup]) > 0
    ) {
      // filter the types available
      $query = array('in'=>array('id', $this->args[$lookup]));
      $filter = array('query'=>json_encode($query),'orderby'=>$sort_column,);
      $extraParams = array_merge($filter, $this->auth['read']);
      $fieldname = $fieldprefix.$attribPrefix.':'.$this->options[$fieldId];
      $idname = $fieldname;
      // if this attribute exists on DB, we need to append id to fieldname
      if (is_array(data_entry_helper::$entity_to_load)) {
        $stored_keys = preg_grep('/^'.$fieldname.':[0-9]+$/', array_keys(data_entry_helper::$entity_to_load));
        if (count($stored_keys)===1) {
          foreach ($stored_keys as $stored_key) {
            $fieldname = $stored_key;
          }
        }
      }
}      
      $options=array_merge(array(
        'label' => lang::get($label),
        'fieldname' => $fieldname,
        'id' => $idname,
        'table'=>$table,
        'captionField'=>'term',
        'valueField'=>'id',
        'extraParams' => $extraParams,
      ), $this->options,$customoptions);
      if(isset($this->args[$default])) $options['default'] = $this->args[$default];


      $r .= data_entry_helper::select($options);

    }
//$r.=print_r($options,true);    
//[idn:0:occurrence:taxa_taxon_list_id]
//[idn:0:occurrence:taxon]
//[idn:0:occurrence:taxon_list_id] 
//[idn:0:subject_observation:taxa] => Anser brachyrhynchus 
//[idn:0:subject_observation:taxa_taxon_list_ids] => 16 
return $r;
}
public function getAvailableTaxa(){
return array('Anser brachyrhynchus','Anser xxxx');
}
	public function showSpecies(){
        $this->options['lockable'] = $this->options['identifiers_lockable'];
	$options=$this->options;
	$args=$this->args;
	$auth=$this->auth;
	$tabalias=$this->tabalias;
// output the species selection control
    $options['blankText'] = '<Please select>';
    if ($args['species_ctrl']=='autocomplete') $temp = data_entry_helper::$javascript;


   $r = iform_wwt_colour_marked_clone::get_control_species($auth, $args, $tabalias, $options+array('validation' => array('required'), 'class' => 'select_taxon'));
//data_entry_helper::$entity_to_load[$this->fieldPrefix.'subject_observation:taxa'];

    if ($args['species_ctrl']=='autocomplete') {
      if (!$options['inNewIndividual']) {
        $autoJavascript = substr(data_entry_helper::$javascript, strlen($temp));
      } else {
        data_entry_helper::$javascript = $temp;
      }
      unset($temp);
    } else {
      $autoJavascript = '';
    }
    
//self::getAvailableTaxa()    
$r.=$this->showItem('taxa','Species of the bird','taxa_taxon_lists',null,'subject_observation',array('validation' => array('required'), 'class' => 'select_taxon'));
$r.=$this->showItem('genderId','Sex of the bird','request_gender_values','default_gender','sjoAttr');
$r.=$this->showItem('stageId','Age of the bird','request_stage_values','default_stage','sjoAttr');
$r.=$this->showItem('lifeStatusId','The bird was','request_life_status_values','default_life_status','sjoAttr');
return $r;
	}
	
	



	public function getIndividualHeader(){
	$fieldprefix=$this->getFieldPrefix();
		$r = '<div id="'.$fieldprefix.'individual:panel" class="individual_panel ui-corner-all">';
		$r.=iform_wwt_colour_marked_clone::rfj_individual_imagediv($this->getRecordNumber());
		$r .= '<div class="ui-helper-clearfix">';
		$r .= '<fieldset id="'.$fieldprefix.'individual:fieldset" class="taxon_individual taxon_individual_'.$this->getHighlight().' ui-corner-all">';
		$r .= '<legend id="'.$fieldprefix.'individual:legend" class="individual_header">Individual details</legend>';
    // output the hiddens 
    $fields=array(
     "subject_observation"=>array(
    	array("fieldname"=>'subject_type_id'),
    	array("fieldname"=>'id'),
    	array("fieldname"=>'parent_id'),
    	array("fieldname"=>'subject_count',"default"=>1)),
     'occurrences_subject_observation'=>array(
    	array("fieldname"=>'id')),
     'occurrence'=>array(
    	array("fieldname"=>'id'))
    );
    // Check if Record Status is included as a control. If not, then add it as a hidden.
    $arr = helper_base::explode_lines($this->args['structure']);
    if (!in_array('[record status]', $arr)) 
	    $fields['occurrence'][]=array("fieldname"=>'record_status',"default"=>isset($this->args['defaults']['occurrence:record_status']) ? $this->args['defaults']['occurrence:record_status'] : 'C');

    $r.=self::displayHiddenFields($fields);
    return $r;
   }
	
	
	public function displayHiddenFields($tables){
	$fieldprefix=$this->getFieldPrefix();
	$r='';
	foreach($tables as $tablename=>$fields){
 	 foreach($fields as $field){
	 $fieldname=$field["fieldname"];
//	 $tablename=$field["tablename"];
	 $value = '';
         if (isset(data_entry_helper::$entity_to_load[$fieldprefix.$tablename.':'.$fieldname])) {
          $value = data_entry_helper::$entity_to_load[$fieldprefix.$tablename.':'.$fieldname];
         } else if (isset($this->args[$fieldname])) {
          $value = $args[$fieldname]; 
         }
    	 else if (isset($field["default"])) $value=$field["default"];
         if ($value!=='') {
          $r .= '<input type="hidden" id="'.$fieldprefix.$tablename.':'.$fieldname.'" '.'name="'.$fieldprefix.$tablename.':'.$fieldname.'" value="'.$value.'" />'."\n";
         }    
       }
      }
      return $r;
    }
	
	
	
	
	
	public function getIndividualFooter(){
	$r="</fieldset></div></div>";
	return $r;
	}
	
	public function getSectionHeader(){
	$type=($this->getSubjectTypeId()==113)?"family":"single";
//	$type="family";

	$r = '<fieldset id="'.$this->getFieldPrefix().$type.':fieldset" class="taxon_family taxon_'.$type.'_'.$this->getHighlight().' ui-corner-all">';
	$r .= '<legend id="'.$this->getFieldPrefix().$type.':legend">'.$type.' details</legend>';
	return $r;
	}
	public function getSectionFooter(){
	$r="</fieldset>";
	return $r;
	}

public function setGlobals($auth, $args, $tabalias, $options){
	if(!isset(self::$auth)) {
		self::$auth=$auth;
		self::$options=$options;
		self::$args=$args;
		self::$tabalias=$tabalias;
	}
}

  public function rfj_check_speciesidentifier_attributes_present(){
    // throw an exception if any of the required custom attributes is missing
    $errorMessages = array();
    foreach (array('baseColourId', 'textColourId', 'sequenceId', 'positionId', 
      'attachmentId', 'genderId', 'stageId', 'lifeStatusId', 'conditionsId', ) as $attrId) {
      if (self::$options[$attrId]===-1) {
        $errorMessages[] = lang::get('Required custom attribute for '.$attrId.' has not been found. '
        .'Please check this has been created on the warehouse and is associated with the correct system function.');
      }
    }
    if (count($errorMessages)>0) {
      $errorMessage = implode('<br />', $errorMessages);
      throw new exception($errorMessage);
    }
    return self::$options;
  }
public static function rfj_fixed_args(&$args) {
    // hard-wire some 'dynamic' options to simplify the form. Todo: take out the dynamic code for these
    $args['subjectAccordion'] = false;
    $args['emailShow'] = false;
    $args['nameShow'] = false;
    $args['copyFromProfile'] = false;
    $args['multiple_subject_observation_mode'] = 'single';
    $args['extra_list_id'] = '';
    $args['occurrence_comment'] = false;
    $args['col_widths'] = '';
    $args['includeLocTools'] = false;
    $args['loctoolsLocTypeID'] = 0;
    $args['subject_observation_confidential'] = false;
    $args['observation_images'] = false;
   }  
   
public function rfj_do_grid($args,$node,$tabs,$svcUrl,$submission,$auth){
      $r = '';
      // debug section
      if (!empty($args['debug_info']) && $args['debug_info']) {
        $r .= '<input type="button" value="Debug info" onclick="$(\'#debug-info-div\').slideToggle();" /><br />'.
          '<div id="debug-info-div" style="display: none;">';
        $r .= '<p>$_GET is:<br /><pre>'.print_r($_GET, true).'</pre></p>';
        $r .= '<p>$_POST is:<br /><pre>'.print_r($_POST, true).'</pre></p>';
        $r .= '<p>Entity to load is:<br /><pre>'.print_r(data_entry_helper::$entity_to_load, true).'</pre></p>';
        $r .= '<p>Submission was:<br /><pre>'.print_r($submission, true).'</pre></p>';
        $r .= '<input type="button" value="Hide debug info" onclick="$(\'#debug-info-div\').slideToggle();" />';
        $r .= '</div>';
      }
      if (method_exists(get_called_class(), 'getHeaderHTML')) {
        $r .= call_user_func(array(get_called_class(), 'getHeaderHTML'), true, $args);
      }
      $attributes = data_entry_helper::getAttributes(array(
        'valuetable'=>'sample_attribute_value'
       ,'attrtable'=>'sample_attribute'
       ,'key'=>'sample_id'
       ,'fieldprefix'=>'smpAttr'
       ,'extraParams'=>$auth['read']
       ,'survey_id'=>$args['survey_id']
      ), false);
      $tabs = array('#sampleList'=>lang::get('LANG_Main_Samples_Tab'));
      if($args['includeLocTools'] 
        && function_exists('iform_loctools_checkaccess') 
        && iform_loctools_checkaccess($node,'admin')) {
        $tabs['#setLocations'] = lang::get('LANG_Allocate_Locations');
      }
      if (method_exists(get_called_class(), 'getExtraGridModeTabs')) {
        $extraTabs = call_user_func(array(get_called_class(), 'getExtraGridModeTabs'), false, $auth['read'], $args, $attributes);
        if(is_array($extraTabs)) {
          $tabs = $tabs + $extraTabs;
        }
      }
      if(count($tabs) > 1) {
        $r .= "<div id=\"controls\">".(data_entry_helper::enable_tabs(array('divId'=>'controls','active'=>'#sampleList')))."<div id=\"temp\"></div>";
        $r .= data_entry_helper::tab_header(array('tabs'=>$tabs));
      }
      $r .= "<div id=\"sampleList\">".call_user_func(array(get_called_class(), 'getSampleListGrid'), $args, $node, $auth, $attributes)."</div>";
      if($args['includeLocTools'] 
        && function_exists('iform_loctools_checkaccess') 
        && iform_loctools_checkaccess($node,'admin')) {
        $r .= '
  <div id="setLocations">
    <form method="post">
      <input type="hidden" id="mnhnld1" name="mnhnld1" value="mnhnld1" /><table border="1"><tr><td></td>';
        $url = $svcUrl.'/data/location?mode=json&view=detail&auth_token='.$auth['read']['auth_token']."&nonce=".$auth['read']["nonce"]."&parent_id=NULL&orderby=name".(isset($args['loctoolsLocTypeID'])&&$args['loctoolsLocTypeID']<>''?'&location_type_id='.$args['loctoolsLocTypeID']:'');
        $session = curl_init($url);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
        $entities = json_decode(curl_exec($session), true);
        $userlist = iform_loctools_listusers($node);
        foreach($userlist as $uid => $a_user) {
          $r .= '<td>'.$a_user->name.'</td>';
        }
        $r .= "</tr>";
        if(!empty($entities)) {
          foreach($entities as $entity) {
            if(!$entity["parent_id"]) { // only assign parent locations.
              $r .= "<tr><td>".$entity["name"]."</td>";
              $defaultuserids = iform_loctools_getusers($node, $entity["id"]);
              foreach($userlist as $uid => $a_user) {
                $r .= '<td><input type="checkbox" name="location:'.$entity["id"].':'.$uid.(in_array($uid, $defaultuserids) ? '" checked="checked"' : '"').'></td>';
              }
              $r .= "</tr>";
            }
          }
        }
        $r .= "</table>
      <input type=\"submit\" class=\"ui-state-default ui-corner-all\" value=\"".lang::get('LANG_Save_Location_Allocations')."\" />
    </form>
  </div>";
      }
      if (method_exists(get_called_class(), 'getExtraGridModeTabs')) {
        $r .= call_user_func(array(get_called_class(), 'getExtraGridModeTabs'), true, $auth['read'], $args, $attributes);
      }
      if(count($tabs)>1) { // close tabs div if present
        $r .= "</div>";
      }
      if (method_exists(get_called_class(), 'getTrailerHTML')) {
        $r .= call_user_func(array(get_called_class(), 'getTrailerHTML'), true, $args);
      }
      return $r;
   }
  /*
   * helper function to return a proxy-aware warehouse url
   */
  public function warehouseUrl() {
    return !empty(data_entry_helper::$warehouse_proxy) ? data_entry_helper::$warehouse_proxy : data_entry_helper::$base_url;
  }

   public static function rfj_set_mode() {
    wwt_individual::$mode = (isset($args['no_grid']) && $args['no_grid'])     
        ? MODE_NEW_SAMPLE // default mode when no_grid set to true - display new sample
        : MODE_GRID; // default mode when no grid set to false - display grid of existing data
                // mode MODE_EXISTING: display existing sample
    if ($_POST) {
      if(!array_key_exists('website_id', $_POST)) { // non Indicia POST, in this case must be the location allocations. add check to ensure we don't corrupt the data by accident
        if(function_exists('iform_loctools_checkaccess') && iform_loctools_checkaccess(self::$node,'admin') && array_key_exists('mnhnld1', $_POST)){
          iform_loctools_deletelocations(self::$node);
          foreach($_POST as $key => $value){
            $parts = explode(':', $key);
            iform_loctools_insertlocation(self::$node, $parts[2], $parts[1]);
          }
        }
      } else if(!is_null(data_entry_helper::$entity_to_load)) {
        self::$mode = MODE_EXISTING; // errors with new sample, entity populated with post, so display this data.
      } // else valid save, so go back to gridview: default mode 0
    }

    if (array_key_exists('sample_id', $_GET) && $_GET['sample_id']!='{sample_id}') {
      self::$mode = MODE_EXISTING;
      self::$loadedSampleId = $_GET['sample_id'];
    }
    //Subject id from get params
    if (array_key_exists('subject_observation_id', $_GET) && $_GET['subject_observation_id']!='{subject_observation_id}') {
      self::$mode = MODE_EXISTING;
      // single subject_observation case
      self::$loadedSubjectObservationId = $_GET['subject_observation_id'];

    } 

    if (self::$mode!=MODE_EXISTING && array_key_exists('newSample', $_GET)) {
      self::$mode = MODE_NEW_SAMPLE;
      data_entry_helper::$entity_to_load = array();
      self::$subjectObservationIds = array(self::$loadedSubjectObservationId);
    } // else default to mode MODE_GRID or MODE_NEW_SAMPLE depending on no_grid parameter
//    self::$mode = $mode;
 

   }
  public static function getSampleListGrid($args, $node, $auth, $attributes) {
    global $user;
    // get the CMS User ID attribute so we can filter the grid to this user
    /*
    foreach($attributes as $attrId => $attr) {
      if (strcasecmp($attr['caption'],'CMS User ID')==0) {
        $userIdAttr = $attr['attributeId'];
        break;
      }
    }
    */
    if ($user->uid===0) {
      // Return a login link that takes you back to this form when done.
      return lang::get('Before using this facility, please <a href="'.url('user/login', array('query'=>'destination=node/'.($node->nid))).'">login</a> to the website.');
    }
    // use drupal profile to get warehouse user id
    if (function_exists('profile_load_profile')) {
      profile_load_profile($user);
      $userId = $user->profile_indicia_user_id;
    }
    if (!isset($userId)) {
      return lang::get('This form must be used with the indicia \'Easy Login\' module so records can '.
          'be tagged against the warehouse user id.');
    }
    if (isset($args['grid_report']))
      $reportName = $args['grid_report'];
    else
      // provide a default in case the form settings were saved in an old version of the form
      $reportName = 'reports_for_prebuilt_forms/simple_subject_observation_identifier_list_1';
    if(method_exists(get_called_class(), 'getSampleListGridPreamble'))
      $r = call_user_func(array(get_called_class(), 'getSampleListGridPreamble'));
    else
      $r = '';
    $grid= data_entry_helper::report_grid(array(
      'id' => 'samples-grid',
      'dataSource' => $reportName,
      'mode' => 'report',
      'readAuth' => $auth['read'],
      'columns' => call_user_func(array(get_called_class(), 'getReportActions')),
      'itemsPerPage' =>(isset($args['grid_num_rows']) ? $args['grid_num_rows'] : 10),
      'autoParamsForm' => true,
      'extraParams' => array(
        'survey_id'=>$args['survey_id'], 
        'userID'=>$userId,
      )
    ));    
    
/*
How about colouring the ring info?
###    <td class="data codes">LBM(XXXXX);LBGW(YYY);NCBW(ABC)</td>
*/


    $r.=self::colourgrid($grid);
//    $r.=$grid; // add normal grid
    $r .= '<form>';    
    if (isset($args['multiple_subject_observation_mode']) && $args['multiple_subject_observation_mode']=='either') {
      $r .= '<input type="button" value="'.lang::get('LANG_Add_Sample_Single').'" onclick="window.location.href=\''.url('node/'.($node->nid), array('query' => 'newSample')).'\'">';
      $r .= '<input type="button" value="'.lang::get('LANG_Add_Sample_Grid').'" onclick="window.location.href=\''.url('node/'.($node->nid), array('query' => 'newSample&gridmode')).'\'">';
    } else {
      $r .= '<input type="button" value="'.lang::get('LANG_Add_Sample').'" onclick="window.location.href=\''.url('node/'.($node->nid), array('query' => 'newSample')).'\'">';    
    }
    $r .= '</form>';
    return $r;
  }
private function colourgrid($text){
// This function decodes the identifier code and cooses colour, ringtype and position of div in grid
$result='';
$needle='<td class="data codes">';
$needle2='</td>';
foreach(explode("\n",$text) as $line){

if(substr_count($line,$needle)==1){
$newcode='';# new bird
//	extract the data
	$data=str_replace($needle2,'',str_replace($needle,'',$line));
	foreach(explode(";",$data)as $identifier){
$identifier=trim($identifier);
$start=strpos($identifier,'(');
$stop=strpos($identifier,')');
	$code=substr($identifier,0,$start);
	
$gridpos=substr($identifier,0,2);	
if($gridpos=='?B')$gridpos='X';
switch(strlen($code)){
		case 4:
		$basecolour=substr($code,-2,1);
		$textcolour=substr($code,-1);
			break;
		case 3: //metal
		$basecolour='S';
		$textcolour='B';
			break;
	}
	


$sequence=substr($identifier,$start+1,$stop-$start-1);
$newcode.="<div class=\"gridbg_$basecolour gridfg_$textcolour gridpos_$gridpos\">$sequence</div>";
}

$line=$needle.$newcode.$needle2;
}
$result.="$line\n";
}
return $result;
}

  /**
   * When a form version is upgraded introducing new parameters, old forms will not get the defaults for the 
   * parameters unless the Edit and Save button is clicked. So, apply some defaults to keep those old forms
   * working.
   */
 
  protected function getReportActions() {
    return array(array('display' => 'Actions', 'actions' => 
        array(array('caption' => lang::get('Edit'), 'url'=>'{currentUrl}', 'urlParams'=>array('sample_id'=>'{sample_id}','subject_observation_id'=>'{subject_observation_id}')))));
  }
  
   
}

