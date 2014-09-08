<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');
/**
 * ResourceRegistry3
 * 
 * @package     RR3
 * @author      Middleware Team HEAnet 
 * @copyright   Copyright (c) 2012, HEAnet Limited (http://www.heanet.ie)
 * @license     MIT http://www.opensource.org/licenses/mit-license.php
 *  
 */

/**
 * View_attribute_matrix Class
 * 
 * @package     RR3
 * @author      Janusz Ulanowski <janusz.ulanowski@heanet.ie>
 */

class View_attribute_matrix extends MY_Controller
{
      function __construct()
      {	
        parent::__construct();
      }

      function show($img,$color,$Sideways)
      {
             if (
                !preg_match('/^[ \(\)a-zA-Z0-9\.]+$/', $img)
                || !preg_match('/^[a-fA-F0-9]+$/', $color)
                ){
                exit;
        }

        $text = $img;
        $color = $color;

        header("Content-type: image/png");

	 if($Sideways=="yes"){

        $img = imagecreate(20, 220);
        imagecolorallocate($img, 255, 255, 255);
        $red = hexdec('0x' . $color[0].$color[1]);
        $green = hexdec('0x' . $color[2].$color[3]);
        $blue = hexdec('0x' . $color[4].$color[5]);

        $text_color = imagecolorallocate($img, $red, $green, $blue);

        imagestringup($img, 3, 3, 210, $text, $text_color);
	}
	else{	
        $img = imagecreate(220, 20);
        imagecolorallocate($img, 255, 255, 255);
        $red = hexdec('0x' . $color[0].$color[1]);
        $green = hexdec('0x' . $color[2].$color[3]);
        $blue = hexdec('0x' . $color[4].$color[5]);

        $text_color = imagecolorallocate($img, $red, $green, $blue);

	imagestring($img, 3, 3, 3, $text, $text_color);
	}


        imagepng($img);

        exit;

      }
}
