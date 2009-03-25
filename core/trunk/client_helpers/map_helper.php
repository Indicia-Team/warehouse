<?php

require_once "helper_config.php";
/**
* <p> Class abstracting a mapping component - will support methods to add layers,
* controls etc.</p>
*/
Class Map extends helper_config
{
  
  // Name of the control
  public $name = 'map';
  // Internal object name
  private $internalObjectName;
  // Height of the map control
  public $height = '600px';
  // Width of the map control
  public $width = '850px';
  // Latitude
  public $latitude = 6700000;
  // Longitude
  public $longitude = -100000;
  // Zoom
  public $zoom = 7;
  // Base URL of the Indicia Core GeoServer instance to use - defaults to localhost
  public $indiciaCore = 'http://localhost:8080/geoserver/';
  // Proxy host to use for cross-site requests - false if not to use
  public $proxy = 'http://localhost/cgi-bin/proxy.cgi?url=';
  // Private array of options - passed in similar style to the javascript
  private $options = Array
  (
  'projection' => 'new OpenLayers.Projection("EPSG:900913")',
  'displayProjection' => 'new OpenLayers.Projection("EPSG:4326")',
  'units' => '"m"',
  'numZoomLevels' => '18',
  'maxResolution' => '156543.0339',
  'maxExtent' => 'new OpenLayers.Bounds(-20037508,-20037508,20037508,20037508.34)'
  );
  // Map display format
  public $format = 'image/png';
  // Javascript map helper - a reference to the name
  public $jsMapHelper;  
  // Private array of layers
  private $layers = Array();
  // Private array of map controls
  private $mapControls = Array();
  // Private array of controls
  private $controls = Array();
  // Private array of libraries which may be included
  private $library_sources = Array();
  private $libraries = Array();
  private $haskey = Array('google' => true, 'multimap' => true);
  private $editable = false;
  private $editoptions = Array
  (
  'indicia_url' => 'http://localhost/indicia',
  'input_field_id' => 'entered_sref',
  'geom_field_id' => 'geom',
  'systems' => array('osgb'=>'British National Grid','4326'=>'Latitude and Longitude (WGS84)'),
  'init_value' => null,
  'instruct' => 'Click something, please.',
  'jsOpts' => array(
  'indicia_url' => '"http://localhost/indicia"',
  'input_field_id' => '"entered_sref"',
  'geom_field_id' => '"geom"')
  );
  
  // Constants used to add default layers
  const LAYER_GOOGLE_PHYSICAL = 0;
  const LAYER_GOOGLE_STREETS = 1;
  const LAYER_GOOGLE_SATELLITE = 2;
  const LAYER_GOOGLE_HYBRID = 3;
  const LAYER_OPENLAYERS_WMS = 4;
  const LAYER_NASA_MOSAIC = 5;
  const LAYER_VIRTUAL_EARTH = 6;
  const LAYER_MULTIMAP_DEFAULT = 7;
  const LAYER_MULTIMAP_LANDRANGER = 8;
  
  // Constants used to define position
  const POSITION_ABOVE = 0;
  const POSITION_BELOW = 1;
  /**
  * <p>Returns a new map. This will not display the map until the render() method is
  * called.</p>
  *
  * @param String $indiciaCore URL of the Indicia Core geoServer instance.
  * @param Mixed $layers Indicates preset layers to load automatically - by default will load
  * all preset layers (calling true) but may also specify a single layer or array of
  * layers to display. Non-preset layers should be added later.
  */
  public function __construct($indiciaCore = null, $layers = true, $options = null, $editoptions = false)
  {
    if ($indiciaCore != null) $this->indiciaCore = $indiciaCore;
    if ($options != null) $this->options = array_merge($this->options, $options);
    $google_api_key = parent::$google_api_key;
    // if ($google_api_key == '...') $this->haskey['google'] = false;
    $multimap_api_key = parent::$multimap_api_key;
    if ($multimap_api_key == '...') $this->haskey['multimap'] = false;
    $this->library_sources = Array
    (
    'jquery' => parent::$base_url.'/media/js/jquery.js',
    'mapmethods' => parent::$base_url.'/media/js/map_helper.js',
    'openLayers' => parent::$base_url.'/media/js/OpenLayers.js',
    'google' => "http://maps.google.com/maps?file=api&v=2&key=$google_api_key",
    'virtualearth' => 'http://dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=6.1',
    'multimap' => "http://developer.multimap.com/API/maps/1.2/$multimap_api_key"
    );
    $lta = array();
    $this->addLibrary('openLayers');
    if ($layers === true)
    {
      $lta = array(0,1,2,3,4,5,6);
    }
    else if (is_array($layers))
    {
      $lta = $layers;
    }
    else
    {
      $lta = array($layers);
    }
    foreach ($lta as $layer)
    {
      $this->addPresetLayer($layer);
    }
    // If it's editable, we need to reference the js library, 
    if ($editoptions)
    {
      $this->editable = true;
      $this->editoptions = array_merge($this->editoptions, $editoptions);
      $this->addLibrary('jquery');
      $this->addLibrary('mapmethods');
    }
    $this->internalObjectName = "map".rand();
  }
  
  public function addPresetLayer($layer)
  {
    switch ($layer)
    {
      case self::LAYER_GOOGLE_PHYSICAL:
	if ($this->haskey['google'])
	{
	  $this->addLayer("OpenLayers.Layer.Google
	  (
	  'Google Physical',
	  {type: G_PHYSICAL_MAP, 'sphericalMercator': 'true'})");
	  $this->addLibrary('google');
	}
	break;
      case self::LAYER_GOOGLE_STREETS:
	if ($this->haskey['google'])
	{
	  $this->addLayer("OpenLayers.Layer.Google('Google Streets',
	  {numZoomLevels : 20, 'sphericalMercator': true})");
	  $this->addLibrary('google');
	}
	break;
      case self::LAYER_GOOGLE_HYBRID:
	if ($this->haskey['google'])
	{
	  $this->addLayer("OpenLayers.Layer.Google('Google Hybrid',
	  {type: G_HYBRID_MAP, numZoomLevels: 20, 'sphericalMercator': true})");
	  $this->addLibrary('google');
	}
	break;
      case self::LAYER_GOOGLE_SATELLITE:
	if ($this->haskey['google'])
	{
	  $this->addLayer("OpenLayers.Layer.Google('Google Satellite',
	  {type: G_SATELLITE_MAP, numZoomLevels: 20, 'sphericalMercator': true})");
	  $this->addLibrary('google');
	}
	break;
      case self::LAYER_OPENLAYERS_WMS:
	$this->addLayer("OpenLayers.Layer.WMS('OpenLayers WMS',
			     'http://labs.metacarta.com/wms/vmap0',
			     {layers: 'basic', 'sphericalMercator': true})");
			     break;
      case self::LAYER_NASA_MOSAIC:
	$this->addLayer("OpenLayers.Layer.WMS('NASA Global Mosaic',
	'http://t1.hypercube.telascience.org/cgi-bin/landsat7',
	{layers: 'landsat7', 'sphericalMercator': true})");
	break;
      case self::LAYER_VIRTUAL_EARTH:
	$this->addLayer("OpenLayers.Layer.VirtualEarth('Virtual Earth',
	{'type': VEMapStyle.Aerial, 'sphericalMercator': true})");
	$this->addLibrary('virtualearth');
	break;
      case self::LAYER_MULTIMAP_DEFAULT:
	if ($this->haskey['multimap'])
	{
	  $this->addLayer("OpenLayers.Layer.MultiMap(
	  'MultiMap', {sphericalMercator: true})");
	  $this->addLibrary('multimap');
	}
	break;
      case self::LAYER_MULTIMAP_LANDRANGER:
	if ($this->haskey['multimap'])
	{
	  $this->addLayer("OpenLayers.Layer.MultiMap(
	  'OS Landranger', {sphericalMercator: true, dataSource: 904})");
	  $this->addLibrary('multimap');
	}
	break;
  }
}

/**
* <p> Adds a WMS layer from the Indicia Core to the map. </p>
*/
public function addIndiciaWMSLayer($title, $layer, $base = false)
{
  $base = $base ? 'true' : 'false';
  $this->addLayer("OpenLayers.Layer.WMS('$title', '".$this->indiciaCore."wms', { layers: '$layer', transparent: true }, 
		   { isBaseLayer: $base, sphericalMercator: true})");
}

/**
* <p> Adds a layer from the Indicia Core to the map control.</p>
*/
public function addIndiciaWFSLayer($title, $type)
{
  $this->addLayer("OpenLayers.Layer.WFS('$title', '".$this->indiciaCore."wfs', { typename: '$type', request: 'GetFeature' },
		   { sphericalMercator: true })");
}

/**
* <p> Adds a layer to the map control.</p>
*
* @param String $layerDef Javascript definition (appropriate to the OpenLayers
* library) for the layer to be added. This will be called as a new object and
* as such should be parsable in this way.
*/
public function addLayer($layerDef)
{
  $this->layers[] = $layerDef;
}

/**
* <p> Adds a PHP control to the map, either above or below. The control should respond to at least these methods:
* <ol><li> registerWithMap(Map map) </li><li> render() </li></ol>
* 
* @param Object $control Control to be added to the map.
* @param int $position
*/
public function addControl($control, $position)
{
  $control->registerWithMap($this);
  $this->controls[$position][] = $control;
}

/**
* <p> Adds a control to the map.</p>
*
* @param String $controlDef Javascript definition for the control to be added. This will be called
* as a new object and should be parsable in this way.
*/
public function addMapControl($controlDef)
{
  $this->mapControls[] = $controlDef;
}

/**
* <p> Adds a library to the libraries collection. </p>
*/
private function addLibrary($libName)
{
  if (! array_key_exists($libName, $this->libraries))
  {
    if (array_key_exists($libName, $this->library_sources))
    {
      $this->libraries[$libName] = $this->library_sources[$libName];
    }
  }
}

// Renders the control
public function render()
{
  $r = "";
  $intLayers = array();
  $ion = $this->internalObjectName;
  foreach ($this->options as $key => $val)
  {
    $opt[] = $key.": ".$val;
  }
  // Renders the libraries
  foreach ($this->libraries as $lib)
  {
    $r .= "<script type='text/javascript' src='$lib' ></script>\n";
  }
  // Render the main javascript
  $r .= "<script type='text/javascript'>";
  $r .= "function init(){\n"
  ."var options = {".implode(",\n", $opt)."};\n";
  if ($this->proxy) $r .= "OpenLayers.ProxyHost = '".$this->proxy."';\n";
  $r .= "$ion = new OpenLayers.Map('".$this->name."', options);\n";
  if ($this->editable)
  {
    foreach ($this->editoptions['jsOpts'] as $key => $val)
    {
      $eopt[] = $key.": ".$val;
    }
    $r .= "var boundary_style = OpenLayers.Util.applyDefaults({
		strokeWidth: 1,
		strokeColor: '#ff0000',
		fillOpacity: 0.3,
		fillColor:'#ff0000'
	}, OpenLayers.Feature.Vector.style['default']);\n";
    $editlayer = "layer".rand();
    $r .= "var editopts = {".implode(",\n", $eopt)."};\n".
    "var $editlayer = new OpenLayers.Layer.Vector('Current location boundary', {style: boundary_style, 'sphericalMercator': true});\n";
    $r .= "$ion.addLayers([$editlayer]);";
    $this->jsMapHelper = "jsMapHelper".rand();
    $r .= "var ".$this->jsMapHelper." = new MapMethods($ion, $editlayer, editopts);\n";
    // Other functions we need to create
    $exit_sref = "exit_sref".rand();
    $enter_sref = "enter_sref".rand();
    $r .= "var $exit_sref = ".$this->jsMapHelper.".exit_sref();\n"
    ."var $enter_sref = ".$this->jsMapHelper.".enter_sref();\n";
    }
    foreach ($this->layers as $layer)
    {
      $a = "layer".rand();
      $intLayers[] = $a;
      $r .= "var $a = new $layer;\n";
    }
    $r .= "$ion.addLayers([".implode(',', $intLayers)."]);\n";
    if (count($this->layers) >=2 )
    {
      $r .= "$ion.addControl(new OpenLayers.Control.LayerSwitcher());\n";
    }
    foreach ($this->mapControls as $control)
    {
      $a = "control".rand();
      $r .= "var $a = new $control;\n";
    }
    list ($lat, $long, $zoom) = array($this->latitude, $this->longitude, $this->zoom);
    $r .= "$ion.setCenter(new OpenLayers.LonLat($long,$lat),$zoom);";
    $r .= "}";
    $r .= "</script>\n";
    if ($this->editable)
    {
      // Need to place the controls 
      $field_name = $this->editoptions['input_field_name'];
      $geom_field_name = $this->editoptions['geom_field_name'];
      $r .= "<input id='$field_name' name='$field_name' value='".$this->editoptions['init_value']."' ".
      "onblur='$exit_sref();' onclick='$enter_sref();'/>";
      if (count($systems)==1)
      {
	$srids = array_keys($this->editoptions['systems']);
	// only 1 spatial reference system, so put it into a hidden input
	$r .= '<input id="'.$field_name.'_system" name="'.$field_name.'_system" type="hidden" class="hidden" value="'.$srids[0].'" />';
      } else {
	$r .= '<select id="'.$field_name.'_system" name="'.$field_name.'_system">';
	foreach($systems as $srid=>$desc)
	$r .= "<option value=\"$srid\">$desc</option>";
	$r .= '</select>';
      }
      $r .= "<input type=\"hidden\" class=\"hidden\" id=\"$geom_field_name\" name=\"$geom_field_name\" />";
      $r .= '<p class="instruct">'.$this->editoptions['instruct'].'</p>';
    }
    // Render further controls in the 'above' position
    foreach ($this->controls[0] as $foo)
    {
      $r .= $foo->render();
    }
    $r .= "<div class='smallmap' id='".$this->name
    ."' style='width: ".$this->width."; height: "
    .$this->height.";'></div>\n";
    // Render further controls in the 'below' position
    foreach ($this->controls[1] as $foo)
    {
      $r .= $foo->render();
    }
    $r .= "<script type='text/javascript'>init();</script>";
    return $r;
    }
    }
    
    Class Place_Finder {
      
      public function __construct($id='place_search', $link_text='find on map', $pref_area='gb', $country='United Kingdom')
      {
	$this->id = $id;
	$this->link_text = $link_text;
	$this->pref_area = $pref_area;
	$this->country = $country;
      }
      
      public function registerWithMap(Map $map)
      {
	$this->map = map;
      }
      
      public function render()
      {
	// Variable storing the stuff we want to write out
	$mapHelper = $this->map->jsMapHelper;
	$cfe = "check_find_enter".rand();
	$find_place = "find_place".rand();
	$r .= "var $cfe = $mapHelper.check_find_enter();\n";
	$r .= "var $find_place = $mapHelper.find_place('place_search_box', 'place_search_output', 'place_search');\n";
	
	$r .= '<input name="'.$this->id.'" id="'.$this->id.'" onkeypress="return $cfe(event, \''.$this->pref_area.'\', \''.$this->country.'\')"/>' .
	'<input type="button" id="find_place_button" style="margin-top: -2px;" value="find" onclick="'.$find_place.'(\''.$this->pref_area.'\', \''.$this->country.'\');"/>' .
	'<div id="place_search_box" style="display: none"><div id="place_search_output"></div>' .
	'<a href="#" id="place_close_button" onclick="jQuery(\'#place_search_box\').hide(\'fast\');">Close</a></div>';
	return $r;
      }
    }