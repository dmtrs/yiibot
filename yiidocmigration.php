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
$classes = getClasses();
$docdb =new  DOCDb;
$docdb->createDB();

foreach( $classes as $key=>$val  ) {
	$docdb->fillDB(getArrayClass($val));
}
var_dump($docdb->errors);

/**
 * Retrieve a list with all the classes from documentation
 * @returns array classes of the documentation
 **/
function getClasses() {
	$classes = array();
        $url = "http://www.yiiframework.com/doc/api/"; 
        if ( !file_get_contents( $url ) ) return array();
        $html = file_get_contents( $url );
        $dom = new DOMDocument();
        $dom->loadHTML($html);
        $dom->preserveWhiteSpace = false;
	$content = $dom->getElementById("content");	
	var_dump($content);
	$summary = $content->getElementsByTagName('table');
	var_dump($summary);
	$rows = $summary->item(0)->getElementsByTagName("tr");
	
	foreach( $rows as $key=>$row  ) {
		$tds = $row->getElementsByTagName("td");
		if ( $tds->length==2 ) $classes[] = $tds->item(0)->textContent;
		if ( $tds->length==3 ) $classes[] = $tds->item(1)->textContent;
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
	$url = "http://www.yiiframework.com/doc/api/";
	if ( !file_get_contents( $url.$class ) ) return array();
	$html = file_get_contents( $url.$class );
	$dom = new DOMDocument();
	$dom->loadHTML($html);
	$dom->preserveWhiteSpace = false;
	// Standard h2 headings. The method and propertied details are missing
	$h2s = array('Public Properties','Public Methods','Protected Properties','Protected Methods');

	//Get content
	$content = $dom->getElementById("content");

	//The document as an array
	$classdoc = array();

	//Get all the tables. The first table will be the one under the header.
	$tables = $content->getElementsByTagName('table');

	//Get name of the class.
	$classname = $content->getElementsByTagName("h1");

	$classdoc = array();
	//Get first table under the header.There is a problem with the Inheritance.
	$classdoc[$class] = getTable( $tables->item(0) , false );
	
	//Get Description
	$classdoc[$class]['Description'] = getParagraph( $dom->getElementById('classDescription') );
	
	//Add link
	$classdoc[$class]['Link'] = $url.$class;

	//Get all the h2 elements
	$h2 = $content->getElementsByTagName("h2");

	//What part of the doc exist
	$exist = array();
	foreach( $h2 as $value ) {
		$exist[] = $value->textContent;
	}

	//and what we will get comparte to the usuall standard h2s 
	$willget = array_intersect ($h2s , $exist );

	$tbln = 1;
	foreach( $willget as $value ) {
		$classdoc[$value] = getTable( $tables->item($tbln) , true );
		$tbln++;
	}

	return $classdoc;
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
        foreach( $rows as $rownumber=>$tr ) {
                $tds = $tr->getElementsByTagName('td');
		//If it's horizontal take the first td as name
		
                foreach( $tds as $key=>$td ) {
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
