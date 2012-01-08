<?php

/**
 * Modifications to initial api made by ciss
 **/
class Phergie_Plugin_Api extends Phergie_Plugin_Abstract
{
	/**
	 * Database with the api documentation
	 *
	 * @var PDO
	 */
	protected $database;
	//'/^(?:[\w\d]+|\*(?!\*))+$/'
	private $queryTypes = array(
		'help'			=> '#^help(\s*?(?<command>.+))?$#',
		'property'	=> '#^(?<class>\w[\w\d]*?)::\$(?<property>\w[\w\d]*?)$#',
		'method'		=> '#^(?<class>\w[\w\d]*?)::(?<method>[A-Za-z0-9]+)\s*?(\(\))?#',
		'class'			=> '#^(?<class>\w[\w\d]*?)$#',
	);
	
	// Default message
	private $unknown = 'Sorry, never heard of this.';
	
	private $textFormats = array(
		'n'	=> "\017",
		'b'	=> "\002",
		'i'	=> "\026",
		'u'	=> "\037",
	);
	

	/**
	 * Check for dependencies.
	 *
	 * @return void
	 */
	public function onLoad()
	{
		if (!extension_loaded('PDO') || !extension_loaded('pdo_sqlite')) {
			$this->fail('PDO and pdo_sqlite extensions must be installed');
		}
		else
		{
			$path = dirname(__FILE__);
			try
			{
				$this->database = new PDO('sqlite:' . $path . '/Api/api.db');    
			}
			catch (PDOException $e)
			{
			}
		}

		$this->getPluginHandler()->getPlugin('Command');
	}
	public function onCommandApi($args)
	{
		if(preg_match('#(?<args>^[^, ]++)\s*,?\s*(?:(?:@|at|to|oh)\s+|@|)(?<nick>[a-zA-Z0-9_-]+)\s*?$#', $args, $matches))
		{
			$args = $matches['args'];
			$nick = $matches['nick'];		
		}
 	
		$args = trim($args);
		$message = $this->unknown;

		foreach($this->queryTypes as $name => $pattern)
		{
			if(preg_match($pattern, $args, $matches))
			{
				$functionName = 'query' . ucfirst($name);
				if(!method_exists($this, $functionName))
					continue;
				$message = (empty($nick) ? '' : "$nick: ") . $this->$functionName($matches);
				break;
			}
		}
		$this->doPrivmsg($this->getEvent()->getSource(), $message);
	}	
        private function queryMethod($args)
	{
		$args['method'] .= '()';
		
		$query = $this->database->prepare("
			SELECT
				`me_class`,
				`me_name`,
				`me_description`,
				`me_link`
			FROM
				`doc_cl_methods`
			WHERE
				`me_class` LIKE :class
				AND `me_name` LIKE :method
		");

		$query->bindParam(':class', $args['class']);
		$query->bindParam(':method', $args['method']);
		$query->execute();
		$results = $query->fetch(PDO::FETCH_ASSOC);

		if(empty($results))
			return 'Sorry, unknown method.';
			
		extract($results);
		extract($this->textFormats);
		return "$b$me_class::$me_name$n $i$me_description$n $me_link";
	}
	
	
	private function queryProperty($args)
	{
		$query = $this->database->prepare("
			SELECT
				`pr_type`,
				`pr_access`,
				`pr_class`,
				`pr_name`,
				`pr_description`,
				`pr_link`
			FROM
				`doc_cl_properties`
			WHERE
				`pr_class` LIKE :class
				AND `pr_name` LIKE :property
		");
		$query->bindParam(':class', $args['class']);
		$query->bindParam(':property', $args['property']);
		$query->execute();
		$results = $query->fetch(PDO::FETCH_ASSOC);
		
		if(empty($results))
			return 'Sorry, unknown property.';
	
		extract($results);
		return "$pr_access $pr_type $pr_class::\$$pr_name $pr_description $pr_link";
	}
	
	
	private function queryClass($args)
	{
		$query = $this->database->prepare("
			SELECT
				`cl_package`,
				`cl_name`,
				`cl_description`,
				`cl_link`
			FROM
				`doc_class`
			WHERE
				`cl_name` LIKE :class
		");
		$query->bindParam(':class', $args['class']);
		$query->execute();
		$results = $query->fetch(PDO::FETCH_ASSOC);

		if(empty($results))
			return 'Sorry, unknown class.';
			
		extract($results);
		return "$cl_package $cl_name $cl_description $cl_link";

			
		return !empty($results)
			? implode(' ', array_slice($results, 0, 2))
			: 'Sorry, unknown class.';
		
	}
	
	
	private function queryHelp($args)
	{
		switch($command = trim($args['command']))
		{
			case 'class'		: return 'For class info: api class';
			case 'method'		: return 'For method info: api class::method';
			case 'property'	: return 'For property info: api class::$property';
			case 'api'			:
			case ''					: return
					"Hello, my name is yiibot. I am a Phergie ( http://phergie.org ) IRC bot programmed to help you with the Yii documentation."
				 ." The main command to call me is: api. "
				 ." For class info: api class, for method info: api class::method & for property info: api class::\$property"
				 ." -- For reporting issues on my functionality visit: http://github.com/dmtrs/yiibot/issues or /msg tydeas."
				 ."Authors of Api Plugin for Phergie bot: tydeas, ciss and thanks to rawtaz."
				 ."Have a nice day :)";                                 
			default					: return "Sorry, I've never heard of \"$command.\""; 
		}
	}
}	
?>
