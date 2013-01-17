<?php
if ($error!=null) {
  echo html::page_error('Demo pages configuration problem', $error);
}
?>
<p> The following options configure the demonstration pages provided with this Warehouse installation.</p>
<form class="cmxform widelabels" name="setup" action="config_demo_save" method="post">
<fieldset>
  <legend><?php echo Kohana::lang('setup.demo_configuration'); ?></legend>
  <ol>
    <li class="ui-widget-content ui-corner-all page-notice" style="margin: 1em;">
      <div class="ui-widget-header ui-corner-all">GeoServer</div>
      <p>If you have installed GeoServer to provide spatial access to the data in this Indicia Warehouse, enter the
      URL to the GeoServer instance here, for example http://www.mysite.com:8080/geoserver/.</p>
      <label for="geoserver_url">GeoServer URL:</label>
      <input name="geoserver_url" type="text"/>
    </li>
    <li class="ui-widget-content ui-corner-all page-notice" style="margin: 1em;">
      <div class="ui-widget-header ui-corner-all">Yahoo! GeoPlanet API</div>
      <p>The GeoPlanet API key allows Indicia to lookup place names entered during data entry. You can obtain a key by
      following the Application ID link from <a href="http://developer.yahoo.com/geo/geoplanet/">Yahoo! GeoPlanet</a>.</p>
      <label for="geoplanet_api_key">GeoPlanet API Key:</label>
      <input name="geoplanet_api_key" type="text"/>
    </li>
    <li class="ui-widget-content ui-corner-all page-notice" style="margin: 1em;">
      <div class="ui-widget-header ui-corner-all">Google Search API</div>
      <p>The Google Search API key allows Indicia to resolve postcodes to places on a map. You can
      <a href="http://code.google.com/apis/ajaxsearch/signup.html">sign up for an AJAX Search API Key</a>.</p>
      <label for="google_search_api_key">Google Search API Key:</label>
      <input name="google_search_api_key" type="text"/>
    </li>
    <li class="ui-widget-content ui-corner-all page-notice" style="margin: 1em;">
      <div class="ui-widget-header ui-corner-all">Bing Maps API</div>
      <p>The Bing Maps API key allows Indicia to use Bing Maps as background layers on the maps.
      You can <a href="http://www.bingmapsportal.com/">sign Up for the Bing Maps API</a>.</p>
      <label for="bing_api_key">Bing API Key:</label>
      <input name="bing_api_key" type="text"/>
    </li>
    <li class="ui-widget-content ui-corner-all page-notice" style="margin: 1em;">
      <div class="ui-widget-header ui-corner-all">Flickr API</div>
      <p>The Flickr API allows Indicia link records to photographs stored on Flickr.
      You can <a href="http://www.flickr.com/services/api/key.gne">sign Up for the Flickr API</a>.</p>
      <label for="flickr_api_key">Flickr API Key:</label>
      <input name="flickr_api_key" type="text"/><br/>
      <label for="flickr_api_secret">Flickr API Secret:</label>
      <input name="flickr_api_secret" type="text"/>
    </li>
  </ol>
</fieldset>

<input type="submit" role="button" value="<?php echo html::specialchars(Kohana::lang('setup.submit')); ?>"
    class="button ui-state-default ui-corner-all" />

</form>