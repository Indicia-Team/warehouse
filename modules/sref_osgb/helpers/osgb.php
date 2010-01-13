<?php

class osgb {

	/**
	 * Returns true if the spatial reference is a recognised British National Grid square.
	 *
	 * @param $sref string Spatial reference to validate
	 */
	public static function is_valid($sref)
	{
		$sq100 = strtoupper(substr($sref, 0, 2));
		if (!preg_match('(H[L-Z]|J[LMQR]|N[A-HJ-Z]|O[ABFGLMQRVW]|S[A-HJ-Z]|T[ABFGLMQRVW])', $sq100))
			return FALSE;
		$eastnorth=substr($sref, 2);
		// 2 cases - either remaining chars must be all numeric and an equal number, up to 10 digits
		// OR for DINTY Tetrads, 2 numbers followed by a letter (Excluding O, including I)
		if ((!preg_match('/^[0-9]*$/', $eastnorth) || strlen($eastnorth) % 2 != 0 || strlen($eastnorth)>10) AND
				(!preg_match('/^[0-9][0-9][A-NP-Z]$/', $eastnorth)))
			return FALSE;
		return TRUE;
	}

	/**
	 * Converts a spatial reference in OSGB notation into the WKT text for the polygon, in
	 * OSGB easting northings.
	 */
	public static function sref_to_wkt($sref)
	{
		if (!self::is_valid($sref))
			throw new Exception('Spatial reference is not a recognisable grid square.');
		$sq_100 = self::get_100k_square($sref);
		if (strlen($sref)==5) {
			// Assume DINTY Tetrad format 2km squares
			// extract the easting and northing
			$east  = substr($sref, 2, 1);
			$north = substr($sref, 3, 1);
 			$sq_code_letter_ord = ord(substr($sref, 4, 1));
			if ($sq_code_letter_ord > 79) $sq_code_letter_ord--; // Adjust for no O
 			$sq_size = 2000;
			$east = $east * 10000 + floor(($sq_code_letter_ord - 65) / 5) * 2000;
			$north = $north * 10000 + (($sq_code_letter_ord - 65) % 5) * 2000;
  	} else {
			// Normal Numeric Format
			$coordLen = (strlen($sref)-2)/2;
			// extract the easting and northing
			$east  = substr($sref, 2, $coordLen);
			$north = substr($sref, 2+$coordLen);
			// if < 10 figure the easting and northing need to be multiplied up to the power of 10
			$sq_size = pow(10, 5-$coordLen);
			$east = $east * $sq_size;
			$north = $north * $sq_size;
		}
		$westEdge=$east + $sq_100['x'];
		$southEdge=$north + $sq_100['y'];
		$eastEdge=$westEdge+$sq_size;
		$northEdge=$southEdge+$sq_size;
		return 	"POLYGON(($westEdge $southEdge,$westEdge $northEdge,".
			 "$eastEdge $northEdge,$eastEdge $southEdge,$westEdge $southEdge))";
	}

	/**
	 * Converts a WKT polygon for a grid square (easting northing OSGB) into the
	 * spatial reference notation. Only accepts POINT & POLYGON WKT at the moment.
	 */
	public static function wkt_to_sref($wkt, $precision=null)
	{
		if (substr($wkt, 0, 7) == 'POLYGON')
			$points = substr($wkt, 9, -2);
		elseif (substr($wkt, 0, 5) == 'POINT') {
			$points = substr($wkt, 6, -1);
			if ($precision===null)
				throw new Exception('wkt_to_sref translation for POINTs requires an accuracy.');
		}
		else
			throw new Exception('wkt_to_sref translation only works for POINT or POLYGON wkt.');
		$points = explode(',',$points);
		// use the first point to do the conversion
		$point = explode(' ',$points[0]);
		$easting = $point[0];
		$northing = $point[1];
		if ($precision===null) {
			// find the distance in metres from point 2 to point 1 (assuming a square is passed).
			// This is the accuracy of the polygon.
			$point_2 = explode(' ',$points[1]);
			$accuracy = abs(($point_2[0]-$point[0]) + ($point_2[1]-$point[1]));
			$precision = 12 - strlen($accuracy)*2;
		} else if ($precision==3) {
			// DINTY TETRADS
			// no action as all fixed.
		} else
		  $accuracy = pow(10, (10-$precision)/2);

		$hundredKmE = floor($easting / 100000);
  	$hundredKmN = floor($northing / 100000);
  	$firstLetter = "";
    if ($hundredKmN < 5) {
	    if ($hundredKmE < 5) {
	      $firstLetter = "S";
	    } else {
	      $firstLetter = "T";
	    }
	  } else if ($hundredKmN < 10) {
	    if ($hundredKmE < 5) {
	      $firstLetter = "N";
	    } else {
	      $firstLetter = "O";
	    }
	  } else {
	    $firstLetter = "H";
	  }
    $secondLetter = "";
    $index = 65 + ((4 - ($hundredKmN % 5)) * 5) + ($hundredKmE % 5);
    $ti = $index;
    // Shift index along if letter is greater than I, since I is skipped
    if ($index >= 73) $index++;
    $secondLetter = chr($index);
    if ($precision == 3) {
		  // DINTY TETRADS
    	// 2 numbers at start equivalent to precision = 2
    	$e = floor(($easting - (100000 * $hundredKmE)) / 10000);
    	$n = floor(($northing - (100000 * $hundredKmN)) / 10000);
    	$letter = 65 + floor(($northing - (100000 * $hundredKmN) - ($n * 10000)) / 2000) + 5 * floor(($easting - (100000 * $hundredKmE) - ($e * 10000)) / 2000);
			if ($letter >= 79) $letter++; // Adjust for no O
    	return $firstLetter.$secondLetter.str_pad($e, 1, '0', STR_PAD_LEFT).str_pad($n, 1, '0', STR_PAD_LEFT).chr($letter);
    }
    $e = floor(($easting - (100000 * $hundredKmE)) / $accuracy);
    $n = floor(($northing - (100000 * $hundredKmN)) / $accuracy);
    return $firstLetter.$secondLetter.str_pad($e, $precision/2, '0', STR_PAD_LEFT).str_pad($n, $precision/2, '0', STR_PAD_LEFT);
	}

	/**
	 * Return the underying EPSG code for the datum this notation is based on (Airy 1830)
	 */
	public static function get_srid()
	{
		return 27700;
	}

	/** Retrieve the easting and northing of the sw corner of a
	 * 100km square, indicated by the first 2 chars of the grid ref.
	 *
	 * @param string $sref Spatial reference string to parse (OSGB)
	 * @return array Array containing (x, y)
	 */
	protected static function get_100k_square($sref)
	{
		$north = 0;
		$east = 0;
		$char1 =substr($sref, 0, 1);
		switch ($char1){
			case 'H':
				$north += 1000000;
				break;
			case 'N':
				$north += 500000;
				break;
			case 'O':
				$north += 500000;
    			$east  += 500000;
    			break;
    		case 'T':
    			$east += 500000;
    			break;
		}
  		$char2ord = ord(substr($sref, 1, 1));
		if ($char2ord > 73) $char2ord--; // Adjust for no I
		$east += (($char2ord - 65) % 5) * 100000;
  		$north += (4 - floor(($char2ord - 65) / 5)) * 100000;
  		$output['x']=$east;
  		$output['y']=$north;
		return $output;
	}

}
?>
