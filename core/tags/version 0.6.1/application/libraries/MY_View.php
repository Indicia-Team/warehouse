<?php

/**
 * Indicia, the OPAL Online Recording Toolkit.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses/gpl.html.
 *
 * @package    Core
 * @subpackage Libraries
 * @author    Indicia Team
 * @license    http://www.gnu.org/licenses/gpl.html GPL
 * @link     http://code.google.com/p/indicia/
 */

/**
 * Extend Kohana's view implementation to automatically detect plugin modules which add tabs to existing pages.
 * To declare a plugin, create a module with a plugins folder, containing a php file named the same as the module.
 * Inside this module, write a method called (module_name)_extend_ui and return an array of user interface extensions.
 * Each extension is a child array, containing a view (the name of the view it is extending), type (='tab'), controller
 * (the path to the controller function which should be displayed on the tab), title (the title of the tab). 
 */
class View extends View_Core {

  protected $viewname='';

  /**
   * When a view is rendered, check for plugins which are adding tabs to the view. If any exist, then 
   * wrap the current view output in the first tab of a jQuery tabs implementation and add links to the plugin output
   * for the other tabs.
   */
  public function render($print = FALSE, $renderer = FALSE) {
    $output = parent::render($print, $renderer);
    $tabs = $this->get_tabs();
    // If only one tab, that is the current view, so don't bother tabifying it.
    if (count($tabs)>1) {
      $js = "<script type=\"text/javascript\">
jQuery(document).ready(function() {
  var $tabs=$('#tabs').tabs();
  var initTab='".(array_key_exists('tab', $_GET) ? $_GET['tab'] : '')."';
  if (initTab!='') {
    $tabs.tabs('select', '#' + initTab);
  }
});
</script>";
      $prefix = "<div id=\"tabs\"><ul>\n";
      $suffix = "</div>\n";
      $args = $this->get_args();
      foreach ($tabs as $tab=>$controller) {
        if ($controller==$this->viewname)
          // this is the default page
          $path="#main";
        else {
          // a plugin page
          $path=url::site()."$controller$args";
        }
        $prefix .= '<li><a href="'.$path.'" title="'.$tab.'"><span>'.$tab."</span></a></li>\n";
        $suffix .= '<div id="'.str_replace(' ','_', $tab).'"></div>';
      }
      $prefix .= "</ul>\n<div id=\"main\">";
      $suffix .= "</div>\n";
      $output = "$js$prefix$output$suffix";
    }
    return $output;
  }
  
  /**
   * Work out the current argument list so they can be passed through to the tab. E.g. the current record ID.
   */
  private function get_args() {
    $uri = URI::instance();
    if ($uri->total_arguments()) 
      $args = '/'.implode('/', $uri->argument_array());
    else
      $args = '';
    return $args;
  }
  
  /** 
   * Retrieve the list of tabs for the current view.
   */
  protected function get_tabs() {
    $uri = URI::instance();  
    // use caching, so things don't slow down if there are lots of plugins
    $cacheId = 'tabs-'.$this->viewname.'-'.$uri->segment(2);
    $cache = Cache::instance();
    if ($tabs = $cache->get($cacheId)) { 
      return $tabs;
    } else {
      // $this->tabs is set to the default tabs for the view - excluding module extensions.
      $tabs = array();
      if (isset($this->tabs)) {
        $this->extend_tabs($tabs, $this->tabs);
      }
      // now look for modules which plugin to add a tab.
      foreach (Kohana::config('config.modules') as $path) {
        $plugin = basename($path);
        if (file_exists("$path/plugins/$plugin.php")) {
          require_once("$path/plugins/$plugin.php");
          if (function_exists($plugin.'_extend_ui')) {
            $extends = call_user_func($plugin.'_extend_ui');
            $this->extend_tabs($tabs, $extends);
          }
        }
      }
      $tabs = array_merge(array('General'=>$this->viewname), $tabs);
      $cache->set($cacheId, $tabs);
      return $tabs;
    }    
  }
  
  /**
   * Takes a list of tabs and adds new tabs to them according to the supplied list of extensions.
   */
  protected function extend_tabs(&$tabs, $extends) {
    $uri = URI::instance();
    foreach ($extends as $extend) {
      // if on a new record, skip tabs that are disallowed for new.
      if (isset($extend['actions']) && !in_array($uri->segment(2), $extend['actions'])) 
        continue;
      if ((!isset($extend['type']) || $extend['type']=='tab') && (!isset($extend['view']) || $extend['view']==$this->viewname))
        $tabs[$extend['title']]=$extend['controller'];
    }
  }
  
  /**
   * Override the set_filename property accessor to keep a record of the view name, letting us check for
   * plugins which are linked to this view's path.
   */
  public function set_filename($name, $type = NULL) {
    parent::set_filename($name, $type);
    $this->viewname = $name;
  }

}

?>