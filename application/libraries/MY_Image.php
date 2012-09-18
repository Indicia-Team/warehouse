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
 * @package	Core
 * @subpackage Libraries
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/** 
 * Extension to the image library class which adds functionality to create multiple sized
 * images for an uploaded image file.
 */
class Image extends Image_Core {
  
/**
   * When an image file is uploaded, the indicia configuration file is used to determine what resized
   * versions of the file must also be created. This method creates those files and applies the relevant 
   * image manipulations.
   * @param string $uploadpath Path to the upload directory.
   * @param string $filename The file name of the original uploaded file.   *
   */
  public static function create_image_files($uploadpath, $filename) {
    // First check that the configured graphics library is available.
    // @todo Consider implementing checks if the driver is set to ImageMagick or GraphicsMagick.    
    if (kohana::config('image.driver') != 'GD' || function_exists('gd_info')) {      
      // tolerate path with or withoug trailing slash
      if (substr($uploadpath,-1) != '\\' && substr($uploadpath,-1) != '/')
        $uploadpath = $uploadpath.'/';      
      $fileParts = explode('.', $filename);
      $ext = strtolower(array_pop($fileParts));
      if (in_array($ext, Image::$allowed_types)) {
        $config = kohana::config('indicia.image_handling');
        if (!$config) {
          // Apply a default configuration if not in the file.
          $config = array(
            'thumb' => array('width'  => 100, 'height' => 100, 'crop' => true),
            'med' => array('width'  => 500),
            'default' => array('width'  => 1024, 'upscale'=>false)
          );
        }
        foreach ($config as $imageName => $settings) {
          $img = new Image($uploadpath.$filename);
          self::do_img_resize($img, $settings);
          // Create the correct image path as image name + '-' + destination file name. Default image setting
          // however is used to overwrite the original image.
          if ($imageName=='default') 
            $imagePath = $uploadpath.$filename;
          else
            $imagePath = $uploadpath.$imageName.'-'.$filename;
          $img->save($imagePath);
        }
      }
    }
  }
  
  /**
   * Resize an image according to the supplied resize settings array.
   * @access private
   */
  private static function do_img_resize($img, $settings) {
    if (array_key_exists('width', $settings) && array_key_exists('height', $settings)) {
      // both dimensions given. Crop only if requested, otherwise resize to fit the box but preserve aspect ratio
      if (array_key_exists('crop', $settings) && $settings['crop']===true) {
        // Is the cropped image wider aspect ratio than the original?
        $wider = $img->width/$img->height < $settings['width']/$settings['height'];
        if ($wider && 
            (!isset($settings['upscale']) || $settings['upscale'] || $img->width > $settings['width'])) {
          // Wider ratio, so we need to fit to this width, then crop the top and bottom.
          $img->resize($settings['width'], 0, Image::WIDTH);                
        } elseif (!isset($settings['upscale']) || $settings['upscale'] || $img->height > $settings['height']) {
          // Taller ratio, so we need to fit to this height, then crop the left and right.
          $img->resize(0, $settings['height'], Image::HEIGHT);                
        }
        // Now do the required crop
        $img->crop($settings['width'], $settings['height']);              
      } else {
        $img->resize($settings['width'], $settings['height']);
      }
    } else if (array_key_exists('width', $settings) && 
        (!isset($settings['upscale']) || $settings['upscale'] || $img->width > $settings['width'])) {
      // resize to a set width and preserve aspect ratio
      $img->resize($settings['width'], 0, Image::WIDTH);
    } else if (array_key_exists('height', $settings)  &&
        (!isset($settings['upscale']) || $settings['upscale'] || $img->height > $settings['height'])) {
      // resize to a set height and preserve aspect ratio
      $img->resize(0, $settings['height'], Image::HEIGHT);
    }
  }
  
}

?>