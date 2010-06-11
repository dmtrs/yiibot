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
 * version 0.1
 *------------
 * + Can not take as parameter the class name yet.
 * + It returns the classname description and related table,
 *   and 'Public Properties','Public Methods','Protected Properties','Protected Methods'
 *   tables as well, with links.
 * + There are 2 functions getDescription and getTable.
 * + BUGS: In method getTable there are 2 comments related to bugs.
 **/



$html = file_get_contents("http://www.yiiframework.com/doc/api/CController/");
$dom = new DOMDocument();
$dom->loadHTML($html);
$dom->preserveWhiteSpace = false;
// Standard h2 headings. The method and propertied details are missing
$h2s = array('Public Properties','Public Methods','Protected Properties','Protected Methods');

//The content div
$content = $dom->getElementById("content");

//The document as an array
$classdoc = array();

//Get all the tables. The first table will be the one under the header.
$tables = $content->getElementsByTagName('table');

//Get name of the class.
$classname = $content->getElementsByTagName("h1");

$classdoc = array();
//Get first table under the header.There is a problem with the Inheritance.
$classdoc['basic'] = getTable( $tables->item(0) , false );

//Get Description
$classdoc['description'] = getParagraph( $dom->getElementById('classDescription') );

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

var_dump($classdoc);

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

        foreach( $rows as $rownumber=>$tr ) {
                $tds = $tr->getElementsByTagName('td');
		//If it's horizontal take the first td as name
		if ( $horizontal ) $rowname = $tds->item(0)->textContent;
                foreach( $tds as $key=>$td ) {
			if( $horizontal ) {
				//Something must do for first key ==0
				$des = $th->item($key)->textContent;
                        	$details[$rowname][$des] = trim( $td->textContent );
			} else {
				$des = $th->item($rownumber)->textContent;
                        	$details[$des] = trim( $td->textContent );
			}
                }
		//Get some undefined index here but don't know why
		if ( $horizontal ) $details[$rowname]['Link'] = "/".$details[$rowname]['Defined By']."#".trim( $rowname , "()" )."-detail";
        }
        return $details;
}
