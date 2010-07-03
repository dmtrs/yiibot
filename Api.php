<?php
class Phergie_Plugin_Api extends Phergie_Plugin_Abstract {
    /**
     * Database with the api documentation
     *
     * @var PDO
     */
    protected $database;
    /**
     * Check for dependencies.
     *
     * @return void
     */
    public function onLoad()
    {
        if (!extension_loaded('PDO') || !extension_loaded('pdo_sqlite')) {
            $this->fail('PDO and pdo_sqlite extensions must be installed');
        } else {
            $path = dirname(__FILE__);
            
            try {
                $this->database = new PDO('sqlite:' . $path . '/Api/api.db');            
            } catch (PDOException $e) {
            }

        }

        $this->getPluginHandler()->getPlugin('Command');
    }
    public function onCommandApi($arg) {
        $event = $this->getEvent();
        $source = $event->getSource();
        //$nick = $event->getNick();
        $needle=" ";
	//Check for more arguments
	$args = explode(" " , $arg );
	var_dump($args);
	$arg = $args[0];
//	$nick = ( isset($args[1] ) ) ? $args[1] : $event->getNick();
	$nick = ( isset($args[1] ) ) ? $args[1] : 0;
        //var_dump($nick);
        $query = true;
        //User ask for class method
        if ( $arg=="help" ) {
            $message="Hello, my name is yiibot. I am a phergie ( http://phergie.org ) IRC bot programmed to help you with yii documentation.".
                    "The main command to call me is: api. For class info: api class , for method info: api class::method ".
                    "& for property info: api class.property . For reporting issue on my functionality visit: http://github.com/dmtrs/yiibot/issues or /msg tydeas.".
                    "Have a nice day :)";
            $query = false;
        }
        else if( strpos( $arg , "::"  )) {
                $needle = "::";
                if( !strpos( $arg , "()")  ) $arg.="()";
                $cpname = explode( $needle , $arg );
                $sel = $this->database->prepare("SELECT `me_class` , `me_name` , `me_description` , `me_link` FROM `doc_cl_methods` WHERE `me_class` LIKE :classname AND `me_name` LIKE :pmname ;");

                $sel->bindParam( ':classname' , $cpname[0] );
                $sel->bindParam( ':pmname' , $cpname[1] );
            }
            //User ask for class property
            else if( strpos( $arg , "." )) {
                    $needle = ".";
                    $cpname = explode( $needle , $arg );
                    $sel = $this->database->prepare("SELECT `pr_class` , `pr_name` , `pr_description` , `pr_link` FROM `doc_cl_properties` WHERE `pr_class` LIKE :classname AND `pr_name` LIKE :pmname ;");
                    $sel->bindParam( ':classname' , $cpname[0] );
                    $sel->bindParam( ':pmname' , $cpname[1] );
                }
                //User ask for class
                else {
                    $sel = $this->database->prepare("SELECT `cl_package` , `cl_name` , `cl_description` , `cl_link` FROM `doc_class` WHERE `cl_name` LIKE :classname ;");
                    $sel->bindParam( ':classname' , $arg );
                }
        
        if ( $query ) {
            $sel->execute();
            $found = $sel->fetch(PDO::FETCH_ASSOC);
            $message="";
            if ( empty( $found ) ) {
                $message = "I have not heard for this before, sorry :(";
            } else {

                $head = array_slice($found,0,2);
                $body = array_slice($found,2,2);
                $i=true;
                foreach( $head as $value ) {
                    $message .= $value;
                    if( $i ) $message .= $needle;
                    $i=false;
                }
                foreach( $body as $value ) {
                    $message .= " $value";

                }
            }
        }
        if ( !empty($nick) ) { $message=$nick.": ".$message; }
        $this->doPrivmsg($source, $message);
    }
}
