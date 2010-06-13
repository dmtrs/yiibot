<?php
require_once('./yiidocdb.php');


$m = new DOCMain();
echo "1.";
$m->printInfo( "CController" );
echo "\n2.";
$m->printInfo( "CController" , "l" );
echo "\n3.";
$m->printInfo( "CController" , "F");
$m->printInfo( "Yii::app" );
echo "\n";
$m->printInfo( "Yii::app()" );
echo "\n";
$m->printInfo( "CApplication.basePath");
//var_dump($db->selectProMeth("CApplication" , "basePath" , FALSE  ));
//var_dump($db->selectProMeth("Yii" , "app()" , TRUE  ));
class DOCMain {
	public $params;
	public $db;
	public function __construct() {
		$this->db = new DOCDb;
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
			'l'=>'Link',
			'a'=>'Access',
			'y'=>'Definedby',
			'F'=>'npicsvdl'
		);
	}

	/**
	 * This method formats and print class details.
	 * @param string class name
         * @param string chars from the initParams to now what to display.
	 **/
	public function printClass( $classname , $what="ndl" ) {
		$fclass = array();
		$class = $this->db->selectClass( $classname );
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
		$print = ( ctype_upper($what) ) ? str_split( $this->params[$what] )  : str_split( $what );
		foreach( $print as $k=>$value ) {
			$att =  $this->params[$value];
			echo $fclass[$att]." * ";
		}
		
		
	}
	/** 
	 * This method formats and print property or method details
         * @param string for Properties example Yii.basePath for Methods CController::app()
         * @param string chars from the initParams to now what to display.
	 **/
	public function printProMeth( $prometh , $what="ndl"  ) {
		if( strpos( $prometh , "::"  )) { 
			$needle = "::"; 
			$pm = true;
			if( !strpos( $prometh , "()")  ) $prometh.="()";
		}
		else if( strpos( $prometh , "." )) { $needle = "."; $pm = false; }
		else { echo "Nothing found.\n"; exit; }
		$cpmname = explode( $needle , $prometh );
		$found = $this->db->selectProMeth($cpmname[0],$cpmname[1],$pm);
		if( $found == false ) { echo "Nothing Found.\n"; exit; }
		else {
			
		}
	}
        public function printInfo( $var , $what="ndl"  ) {
                //User ask for class method
		if( strpos( $var , "::"  )) {
                        $needle = "::";
                        if( !strpos( $var , "()")  ) $var.="()";
			$cpname = explode( $needle , $var );
			$this->printMeth( $cpname[0] , $cpname[1] , $what );
                }
		//User ask for class property
                else if( strpos( $var , "." )) { 
			$needle = ".";
                        $cpname = explode( $needle , $var );
                        $this->printPro( $cpname[0] , $cpname[1] , $what );
		}
		//USer ask for class
                else { 
			$this->printClass( $var , $what );
			return; 
		}
        }
	public function printPro( $classname , $proname , $what ) {
		$found = $this->db->selectProMeth( $classname , $proname , false );
		var_dump($found);
	}
	public function printMeth( $classname , $mename , $what ) {
		$found  = $this->db->selectProMeth( $classname , $mename , true );
		var_dump($found);
	}

}
