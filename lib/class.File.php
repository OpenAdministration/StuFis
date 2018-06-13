<?php
namespace SILMPH;

/**
 * FRAMEWORK LIB Protocol
 * implement file class
 *
 * @package         Stura - Referat IT - ProtocolHelper
 * @category        model
 * @author 			michael gnehr
 * @author 			Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since 			07.05.2018
 * @copyright 		Copyright Michael Gnehr (C) 2018 - All rights reserved
 * @platform        PHP
 * @requirements    PHP 7.0 or higher
 */

/**
 * implement file class
 * @category        model
 * @author 			michael gnehr
 * @author 			Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since 			07.05.2018
 * @copyright 		Copyright Michael Gnehr (C) 2018 - All rights reserved
 */
class File
{
	/**
	 * 
	 * @var int
	 */
	public $id;
	
	/**
	 *
	 * @var string
	 */
	public $link;
	
	/**
	 *
	 * @var datetime
	 */
	public $added_on;
	
	/**
	 *
	 * @var string
	 */
	public $hashname;
	
	/**
	 *
	 * @var string
	 */
	public $filename;
	
	/**
	 *
	 * @var int
	 */
	public $size;
	
	/**
	 *
	 * @var string
	 */
	public $fileextension;
	
	/**
	 *
	 * @var string
	 */
	public $mime;
	
	/**
	 *
	 * @var string
	 */
	public $encoding;
	
	/**
	 *
	 * @var int
	 */
	public $data;
	
	/**
	 */
	function __construct()
	{
	}
	
	function getAddedOnDate(){
		if ($this->added_on){
			return date_create($this->added_on);
		} else {
			return null;
		}
	}
}

?>