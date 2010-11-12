<?php
/**
 * Factoids -  A plugin go add/view and delete factoids.
 * =====================================================
 *  Author: Dimitrios Meggidis [tydeas.dr@gmail.com]
 *  Thanks ciss, blindMoe and the rest of the #yii guys.
 *
 * version next
 * - add rating command to check the user with the most faq
 * version 0.2
 * - keep author of factoids
 * - author authentication on delete.
 * - fixed bug at remove of factoid
 * - no wildcards are allowed
 * version 0.1
 * - add,remove, view factoids
 *
 **/
class Phergie_Plugin_Factoids extends Phergie_Plugin_Abstract
{
    protected $database;
    //replace this and it's use with some regexp like [iI][sS]
    protected $delim = " is ";
    public function onLoad()
    {
        if (!extension_loaded('PDO') || !extension_loaded('pdo_sqlite')) {
            $this->fail('PDO and pdo_sqlite extensions must be installed');
        }
        else {
            $path = dirname(__FILE__);
            try	{
                $this->database = new PDO('sqlite:' . $path . '/Factoids/factoids.sqlite');
            }catch (PDOException $e) {
                //DO SOMETHING
            }
        }
        $this->getPluginHandler()->getPlugin('Command');
    }

    public function onCommandAdd($args)
    {
        $event = $this->getEvent();

        $factoid = explode($this->delim,  $args);
        $result = ($this->wildcardExist($args)) ?
            "No wildcards are allowed" : $this->addFaq($factoid[0], $factoid[1], $event->getNick());

        $this->doPrivmsg($event->getSource(), $result);
    }

    public function onCommandDel($args)
    {
        $result = 
          ( $this->wildcardExist($args) ) ?
            "No wildcards are allowed" : $this->deleteFaq($args);
        
        $this->doPrivmsg($this->getEvent()->getSource(), $result);
    }

    public function onCommandView($args)
    {
        $result = ($this->wildcardExist($args)) ?
            "No wildcards are allowed" : $this->getFaq($args);
        $this->doPrivmsg($this->getEvent()->getSource(), $result);
    }
    public function getFaq($factoid)
    {
        $result = $this->selectFaq($factoid);

        if($result == false )
            return "Sorry, don't know something about ".$factoid.".";

        return $result['fq_value'];
           
    }

    public function selectFaq($factoid)
    {
        $query = $this->database->prepare("
            SELECT
                `fq_value`, `fq_author`
            FROM
                `fq_index`
            WHERE
                `fq_key` LIKE :fkey
        ");
        $query->bindParam(':fkey', $factoid);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);

        if(empty($result))
            return false;
        var_dump($result);
        return $result;
    }

    public function addFaq($key, $value, $author)
    {
        $query = $this->database->prepare("
            INSERT INTO
                `fq_index`
            VALUES
                ( ?, ?, ?);
		");
        $query->bindParam(1, $key);
        $query->bindParam(2, $value);
        $query->bindParam(3, $author);

        return $result = ( $query->execute() ) ? "Factoid added." : "Couldn't add factoid." ;
    }

    public function deleteFaq($factoid)
    {   
        $faq = $this->selectFaq($factoid);
        if ( $faq !== false ) {
            if ( ($faq !== false) && ($this->allowDelete($faq, $this->getEvent()->getNick())) ) {
                $query = $this->database->prepare("
                    DELETE FROM
                        `fq_index`
                    WHERE
                        `fq_key` LIKE ?
                ");
                $query->bindParam(1, $factoid);
               return $result = ( $query->execute() ) ? "Factoid removed." : "Couldn't remove factoid." ;
            } else {
                return "You have no permissions to delete fact.";
           }
        } else {
           return "There is such a factoid in database.";
        }
    }

    private function wildcardExist($args)
    {
        if(strpos($args, "%") !== false )
            return true;
        return false;        
    }
    
    private function allowDelete($faq, $user)
    {
        if ( $faq['fq_author'] == $user )
            return true;
        return false;
    }

}
?>
