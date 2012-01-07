<?php
class DOCDb {
	public $db;
	public $errors;
	public function __construct( $create=false ) {
		if( !file_exists('./db/docbotdb.sqlite') OR $create  ) {
			$this->createDB();
		} else {
			$this->db = new PDO('sqlite:./db/docbotdb.sqlite');
		}
	}
	/**
	 * This method is used to create and return a DB with the certain sql schema for this applcation.
	 * @return PDO the database created.
	 */

	function createDB() {
		echo $unlinkdb = ( unlink('./db/docbotdb.sqlite') ) ? "> Previous Database deleted.\n" : "> No previous Database found.\n"  ;

		$this->db = new PDO('sqlite:./db/docbotdb.sqlite');
		$this->db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		//$db = new SQLite3('./db/docbotdb.sqlite');
		
		$createdbquery = explode( "\n" , file_get_contents('./db/docbotdb.sql') );
		foreach( $createdbquery as $table => $q  ) {
			if( strlen($q) > 0 ) { 
				echo $q."\n";
				echo $creation = ( $this->db->query( $q ) ) ? "> $table table created.\n" : 
					"> $table unable to create table.\n"  ;
			}
		}
		unset( $creation , $unlinkdb , $createdbquery );
	}
	function fillDB( $class ) {

		reset( $class );
		$classname = key( $class );

		echo $mess = "Inserting class ". $classname."\n";
		for( $i = 0; $i < strlen($mess); $i++ ) {
			echo "-";
		}
		echo "\n";

		foreach( $class as $key => $subarr ) {
					
			$keyexplo = explode( " " , $key );
				
			if( $keyexplo[0] == $classname ) {
				echo "***".$key."***\n";
				$this->insertClass( $class[$classname] , $classname );		
			} else {
				echo "***".$key."***\n";
				$this->insertProMeth( $class[$key] , $keyexplo , $classname );
			}
		}
	}
	function insertClass( $classdl  , $classname ) {
		
		$stmt = $this->db->prepare("INSERT INTO `doc_class` VALUES (?,?,?,?,?,?,?,?);");
		//$stmt = $this->db->prepare('SELECT * FROM "doc_class";');
		
		$stmt->bindParam(1, $classname );
		$stmt->bindParam(2, $classdl["Package"] );
		$stmt->bindParam(3, $classdl["Inheritance"] );
		$stmt->bindParam(4, $classdl["Subclasses"] );
		$stmt->bindParam(5, $classdl["Since"] );
		$stmt->bindParam(6, $classdl["Version"] );
		$stmt->bindParam(7, $classdl["Description"][0] );
		$stmt->bindParam(8, $classdl["Link"] );

		echo $result = ( $stmt->execute() ) ? "* $classname details inserted.\n" : "* $classname details could not be inserted.\n" ;
	}
	//I get some undefined method names don't know why.
	function insertProMeth( $methodsdl , $what , $classname ) {
		if ( $what[1]=="Methods" ) {
			$stmt = $this->db->prepare("INSERT INTO `doc_cl_methods` VALUES (?,?,?,?,?,?,?,?,?);");
			//Have not returns yet :)
			$empt='-';
			foreach( $methodsdl as $key => $value ) {
				$stmt->bindParam(1, $classname );
				if( !isset( $value['Method'] ) ) { 
					$value['Method']=mt_rand();
					echo "***ERROR***\n";
					$this->errors[]="$classname undefined method name try to insert: ".$value['Method'];
				}
				$stmt->bindParam(2, $value['Method'] );
                if(isset($value['Returns'])) { 
				    $stmt->bindParam(3, $value['Returns']);
                } else { 
				    $stmt->bindParam(3, $empt);
                }
				$stmt->bindParam(4, $value['Description'] );
				$stmt->bindParam(5, $value['Defined By'] );
				$stmt->bindParam(6, $what[0] );
				$stmt->bindParam(7, $value['Link'] );
                //TODO: source
                if(isset($value['Source'])) { 
				    $stmt->bindParam(8, $value['Source']);
                } else { 
				    $stmt->bindParam(8, $empt);
                }
                if(isset($value['Signature'])) { 
				    $stmt->bindParam(9, $value['Signature']);
                } else { 
				    $stmt->bindParam(9, $empt);
                }
				
				echo $result = ( $stmt->execute() ) ? "> $classname::".$value['Method']." inserted.\n" 
					: "> $classname::".$value['Method']." could not be inserted.\n";
			}	
		} else if( $what[1]=="Properties" ) {
			$stmt = $this->db->prepare("INSERT INTO `doc_cl_properties` VALUES (?,?,?,?,?,?,?);");
                        foreach( $methodsdl as $key => $value ) {
                                $stmt->bindParam(1, $classname );

                                if( !isset( $value['Property'] ) ) {
                                        $value['Property']=mt_rand();
                                        echo "***ERROR***\n";
                                        $this->errors[]="$classname undefined property name try to insert: ".$value['Property'];
                                }
				$stmt->bindParam(2, $value['Property'] );
                                $stmt->bindParam(3, $value['Type'] );
                                $stmt->bindParam(4, $value['Description'] );
				$stmt->bindParam(5, $value['Defined By'] );
                                $stmt->bindParam(6, $what[0] );
                                $stmt->bindParam(7, $value['Link'] );
                                
                                echo $result = ( $stmt->execute() ) ? "> $classname->".$value['Property']." inserted.\n" 
					: "> $classname->".$value['Property']." could not be inserted.\n";
                        }  
		}
	}
	public function selectClass( $classname ) {
		try {
			$sel = $this->db->prepare( "SELECT * FROM `doc_class` WHERE `cl_name`=:cname" );
			$sel->bindParam( ':cname' , $classname );
			$sel->execute();

			return $sel->fetch(PDO::FETCH_ASSOC);
  		} catch (PDOException $e) {
    			print $e->getMessage();
  		}	
		
	}
	public function selectProMeth( $classname , $pmname , $pm  ) {
		try {
			//if $pm true -> method
			if( $pm  ) { $table = "`doc_cl_methods`"; $cc = "`me_class`"; $pmn = "`me_name`"; }
			//else $pm false -> property
			else { $table = "`doc_cl_properties`"; $cc = "`pr_class`"; $pmn = "`pr_name`"; }
			$sel = $this->db->prepare("SELECT * FROM $table WHERE $cc = :classname AND $pmn = :pmname ;");

			$sel->bindParam( ':classname' , $classname );
			$sel->bindParam( ':pmname' , $pmname );
			
			$sel->execute();	
			return $sel->fetch(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                        print $e->getMessage();
                }

	}
}
