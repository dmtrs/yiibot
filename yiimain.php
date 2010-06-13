<?php
require_once('./yiidocdb.php');

$db = new DOCDb();
$m = new DOCMain();
$m->printClass($db->selectClass("CController"));
$m->printClass($db->selectClass("CController") , "l" );


class DOCMain {
	public $params;
	public function __construct() {
		$this->params=$this->initParams();	
	}
	public function initParams() {
		return array(
			'n'=>'Name',
			'p'=>'Package',
			'i'=>'Inheritance',
			'c'=>'Subclasses',
			's'=>'Since',
			'v'=>'Version',
			'd'=>'Description',
			'l'=>'Link'
		);
	}

	/**
	 * This method formats and print class details.
	 * @paran array an array retuned from DOCDb::selectClass()
	 **/
	public function printClass( $class , $what="ndl" ) {
		$fclass = array();
		foreach( $class as $name=>$value  ) {
			$name = substr( $name , 3 );
			if( $name == "inheritance" ) {
				$ex = explode( "»" , $value );
				$value = "";
				foreach( $ex as $k => $v ) {
					$value.= trim( $v )."->";
				}
				$value = substr( $value , 0 , -2  );
			}
			$fclass[ucfirst( $name )] = $value;
		}		
		$print = str_split( $what );
		foreach( $print as $k=>$value ) {
			$att =  $this->params[$value];
			echo "<".$att."> ".$fclass[$att]." ";
		}
		
		
	}
}
