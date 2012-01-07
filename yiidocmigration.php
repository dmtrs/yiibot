<?php
/**
 * Yiidocumentation bot functionallity
 * ===================================
 * This script takes a site from the yiiframework.com/doc/api/
 * and makes it an array. This script will be used to power a 
 * yii documentation bot for the irc.freenode.org #yii
 * @author tydeas_dr mail: tydeas[at]gmail.copm
 * @version 0.1
 *
 * version 0.2
 * -----------
 * + Main method getArrayClass( "classname" ) created.
 * + class link added as attribute.
 * + bugs removed.
 * + if there is no site the script dies.
 * 
 * version 0.1
 *------------
 * + Can not take as parameter the class name yet.
 * + It returns the classname description and related table,
 *   and 'Public Properties','Public Methods','Protected Properties','Protected Methods'
 *   tables as well, with links.
 * + There are 2 functions getDescription and getTable.
 * + BUGS: In method getTable there are 2 comments related to bugs.
 **/
require_once('./yiidocdb.php');
$docdb =new  DOCDb;


 foreach(getClasses() as $class  ) { 
    if($class !== null ) {
        $docdb->fillDB(getArrayClass($class));
        $i++;
    }

}
var_dump($docdb->errors);

function getDom($class = null)
{
    $html = getFile($class);
    //Google plusone element
    $html = preg_replace('/g:plusone/', 'span', $html);
    $dom = new DOMDocument();
    $dom->loadHTML($html);
    $dom->preserveWhiteSpace = false;
	return $dom;//->getElementsByTagName("body")->item(0);
}
function getFile( $file = null, $path = './html/', $url = 'http://www.yiiframework.com/doc/api/')
{
    $path .= ($file === null) ? 'classes.html' : $file.'.html';
    $url  .= ($file === null) ? '' : $file;

    if(!file_exists($path)) {
        if ( !file_get_contents( $url ) ) return array();
        $return = file_get_contents($url);
        file_put_contents($path, $return );
        return $return;
    }
    return file_get_contents($path);
}
/**
 * Retrieve a list with all the classes from documentation
 * @returns array classes of the documentation
 **/
function getClasses()
{
	$classes = array();
    
    $body = getDom()->getElementsByTagName("body")->item(0);  
	$summary = $body->getElementsByTagName('table');
	$rows    = $summary->item(0)->getElementsByTagName("tr");
	
	foreach( $rows as $key=>$row  ) {
		$tds = $row->getElementsByTagName("td");
        $i = ($tds->length - 2);
        $classes[] = $tds->item($i)->textContent;
	}
	
	return $classes;
}
/**
 * This method is the main method. You supply a class name and you get an 
 * with all the documentation.
 * @para string name of class
 * @return array documentation of class
 **/
function getArrayClass( $class ) {
	//Get content
	$dom = getDom($class);
    $body = $dom->getElementsByTagName("body")->item(0);  
	// Standard h2 headings. The method and propertied details are missing
	$h2s = array('Public Properties','Public Methods','Protected Properties','Protected Methods');

	//The document as an array
	$classdoc = array();

	//Get all the tables. The first table will be the one under the header.
	$tables = $body->getElementsByTagName('table');

	//Get name of the class.
	$classname = $body->getElementsByTagName("h1");

	$classdoc = array();
	//Get first table under the header.There is a problem with the Inheritance.
	$classdoc[$class] = getTable( $tables->item(0) , false );
	
	//Get Description
	$classdoc[$class]['Description'] = getParagraph( $dom->getElementById('classDescription') );
	
	//Add link
	$classdoc[$class]['Link'] = $url.$class;

	//Get all the h2 elements
	$h2 = $dom->getElementsByTagName("h2");

    foreach($h2 as $i)
    {
        $h = $i->textContent;
        if(in_array($h, $h2s)) $willget[] = $h;
    }
    
    $tbln = 1;
	foreach( $willget as $value ) {
		$classdoc[$value] = getTable( $tables->item($tbln) , true );
		$tbln++;
	}

    foreach(array('Public Methods', 'Protected Methods') as $m)
    {
        if(isset($classdoc[$m])) { 
            foreach($classdoc[$m] as $i => $method)
            { 
                $classdoc[$m][$i] = getExtraDetails($method, $class, $dom);
            }
        }
    }
    return $classdoc;
}
function getExtraDetails($m, $class, $dom)
{
    $xpath = new DOMXPath($dom);

    if(isset($m['Defined By']) && $m['Defined By']==$class) {
        $id = substr($m['Method'], 0, -2).'-detail'; 
        $signature = $xpath->query('//div[@id="'.$id.'"]/following-sibling::table//div[@class="signature2"]')->item(0);
        $m['Signature'] = $signature->textContent;
        $lTr = $xpath->query('//div[@id="'.$id.'"]/following-sibling::table/tr[last()]/td');

        $i = 0;
        $r = '';
        if($lTr->item($i) !== null && strpos($lTr->item($i)->textContent, '{return}') !== false ) {             
//Fix the XPATH for the last tr
            while($lTr->item(++$i) != null && $i < 3) {
                $r .= $lTr->item($i)->textContent.' ';
            }
            $m['Returns'] = $r;
        }
    }
    return $m;
    
}
/**
 * Gets as a param a div with text and makes it an array by exploding the text on the \n\n
 * @param DOMNode a div which containts text
 * @return array of string.
 **/
function getParagraph( $div ) {
	return explode( "\n\n" , trim( $div->textContent ) );
}

/**
 * This method takes a DOMNode table element  and makes it an array.
 * The tables this method can handle depends on the $horizontal var.
 * For TRUE you can parse a table such as tbl 1
 *
 * +======+======+=====+======+
 * | th1  | th2  | ... | thN  |
 * +======+======+=====+======+
 * | td1  | td2  | ... | tdN  |
 * +------+------+-----+------+
 * | .... | .... | ... | .... |
 * +------+------+-----+------+
 * | td1M | td2M | ... | tdNM |
 * +------+------+-----+------+
 *            <tbl 1>
 * 
 * +=====+------+
 * | th1 | td 1 |
 * +=====+------+
 * | ... | .....|
 * +=====+------+
 * | ... | .... |
 * +=====+------+
 * | thN | thN  |
 * +=====+------+
 *    <tbl 2>
 *
 * @param DOMNode  a table item like: $table->item(0);
 * @param $horizontal=true if the th goes horizontal(tbl 1), give false if vertical(tbl 2) align
 * @return array 
 */

function getTable( $tbl , $horizontal=true ) {
    $details = array();
    $rows = $tbl->getElementsByTagName('tr');
    $th = $tbl->getElementsByTagName('th');
    $rowname='';
    foreach( $rows as $rownumber=>$tr )
    {
        $tds = $tr->getElementsByTagName('td');
        //If it's horizontal take the first td as name

        foreach( $tds as $key=>$td )
        {
            if( $horizontal ) {
                if( $key == 0 ) $rowname = trim( $td->textContent );
                $des = $th->item($key)->textContent;
                $details[$rownumber][$des] = trim( $td->textContent );
            } else {
                $des = $th->item($rownumber)->textContent;
                $details[$des] = trim( $td->textContent );
            }

        }
        if ( $horizontal && $rownumber !=0 ) $details[$rownumber]['Link'] = "http://www.yiiframework.com/doc/api/".$details[$rownumber]['Defined By']."#".trim( $rowname , "()" )."-detail";
    }

    return $details;
}
