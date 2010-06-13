<?php
require_once('./yiidocdb.php');

$db = new DOCDb();
printClass($db->selectClass("CController"));


/**
 * This method formats and print class details.
 * @paran array an array retuned from DOCDb::selectClass()
 **/
function printClass( $class  ) {
	foreach( $class as $name=>$value  ) {
		$label = substr( $name , 3 );
		if( $label=="inheritance" ) {
			$ex = explode( "»" , $value );
			$value = "";
			foreach( $ex as $k => $v ) {
				$value.= trim( $v )."->";
			}
			$value = substr( $value , 0 , -2  );
		}
		echo ucfirst( $label ).": ".$value."\n";
	}
}
