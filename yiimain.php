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
			'F'=>'anypicsvdl'
		);
	}

	/**
	 * This method formats and print class details.
	 * @param string class name
         * @param string chars from the initParams to now what to display.
	 
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
		
		
	}**/
        public function printClass( $classname , $what="ndl" ) {
                
		$fclass =  $this->formatArray( $this->db->selectClass( $classname ) );
                                       
                $print = ( ctype_upper($what) ) ? str_split( $this->params[$what] )  : str_split( $what );
		
                foreach( $print as $k=>$value ) {
                        $att =  $this->params[$value];
			if( isset( $fclass[$att]  ) ) echo $fclass[$att]." * "; 
                }
                        
                        
        }   	
	/** 
	 * Takes as param an array returned from a select query and returns this array 
	 * with better format for the key values.
	 * @param array from select fetch(PDO::FETCH_ASSOC )
	 * @return array better jey formats.
	 **/
	public function formatArray( $class  ) {
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
		
		return $fclass;
	}
	/** 
	 * This is the basic method to use when you want to print anything ( class, property , method details ).
	 * What this method does is parse 1st param and calls the proper  method ( printClass , printPro or printMeth ) 
	 * depends what you have supllied as 1st param.
         * @param string can be class: CController , method: CApplication::app or CApplication::app() or
	 * 	property: Yii.basePath
         * @param string chars from the initParams to now what to display.
	 **/
        public function printInfo( $var , $what="andl"  ) {
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
		echo "\n";
        }
	public function printPro( $classname , $proname , $what ) {
		$found = $this->db->selectProMeth( $classname , $proname , false );
		$found = $this->formatArray( $found );
		$found['Name'] = $found['Type']." ".$found['Class'].".".$found['Name'];

                $print = ( ctype_upper($what) ) ? str_split( $this->params[$what] )  : str_split( $what );

                foreach( $print as $k=>$value ) {
                        $att =  $this->params[$value];
                        if( isset( $found[$att]  ) ) echo $found[$att]." * ";
                }
	}
	public function printMeth( $classname , $mename , $what ) {
		$found  = $this->db->selectProMeth( $classname , $mename , true );
		$found = $this->formatArray( $found );
                $found['Name'] = "returns ".$found['Returns']." ".$found['Class'].".".$found['Name'];
                
                $print = ( ctype_upper($what) ) ? str_split( $this->params[$what] )  : str_split( $what );
                
                foreach( $print as $k=>$value ) {
                        $att =  $this->params[$value];
                        if( isset( $found[$att]  ) ) echo $found[$att]." * ";
                }
	}

}
