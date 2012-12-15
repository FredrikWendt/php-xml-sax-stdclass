<?php
/*
 * Copyright 2012 Fredrik Wendt
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * A SAX parser that parses XML and return objects very much like SoapClient and SoapServer.
 *
 * It is largely influenced and based on Adam A. Flynn' XMLParser:
 * http://www.criticaldevelopment.net/xml/parser_php5.phps
 * 
 * Any attributes discovered are simply ignored - there's currently no support for the 
 * class mapping available in the SoapServer and SoapClient classes.
 */
class XMLToStdClassParser {

	/** Holds the result of the parsing. */
	public $root;

	/** A stack of nodes in the parsed XML tree. */
	private $stack = array();

	/** Reference to the "current" node in the XML tree, while parsing. */
	private $current;

	/** Reference to the "current" node's "parent" in the XML tree, while parsing. */
	private $parent;

	/**
	 * Reads the specified file using file_get_contents, parses the content and returns
	 * an stdClass object containing the root element of the XML document.
	 *
	 * @param string $file_name the file to read
	 * @return an stdClass object representation of the file's XML content
         */
	public static function parseFile($file_name) {
		// TODO: hey, this is a SAX parser - why read all content to memory?
		$file_content = file_get_contents($file_name);
		return XMLToStdClassParser::parseString($file_content);
	}

	/**
	 * Reads the specified file using file_get_contents, parses the content and returns
	 * an stdClass object containing the root element of the XML document.
	 *
	 * @param string $file_name the file to read
	 * @return an stdClass object representation of the file's XML content
         */
	public static function parseString($string) {
		$p = new XMLToStdClassParser($string);
		$r = $p->getRoot();
		return $r;
	}

	private function error($code, $line) {
		throw new Exception("XML parsing error at line $line. Error code $code: " . xml_error_string($code));
	}

	private function startElement($parser, $name, $attributes = array()) {
		$this->state("in  <$name>");

		$name = $this->stripNameSpaceFromElement($name);

		$this->push($this->current);
		$this->parent = $this->current;

		$this->current = new stdClass();
		if (!isset($this->parent->$name)) {
			$this->parent->$name = $this->current;
		} else {
			$previous = $this->parent->$name;
			if ($previous instanceof stdClass) {
				$list = array();
				$list[] = $previous;
				$previous = &$list;
				$this->parent->$name = &$previous;
				array_push($previous, $this->current);
			}
		}

		$this->state("out <$name>");
	}

	private function endElement($parser, $name) {
		$this->state("in  </$name>");

		$name = $this->stripNameSpaceFromElement($name);

		if (isset($this->cdata)) {
			$previous = $this->parent->$name;
			if ($previous instanceof stdClass) {
				$this->parent->$name = $this->cdata;
			} else if (is_array($previous)) {
				$previous[] = $this->cdata;
			} else /* is cdata */ {
				$list = array();
				$list[] = $previous;
				$previous = $list;
				$this->parent->$name = &$previous;
				array_push($previous, $this->cdata);
			}
			unset($this->cdata);
		}
		$this->current = $this->pop();
		$this->parent  = $this->peek();

		$this->state("out </$name>");
	}

	private function characterData($parser, $data) {
		$data = trim($data);
		if (strlen($data) > 0) {
			$this->log("CDATA: $data");
			$this->cdata .= $data;
		}
	}

	private function stripNameSpaceFromElement($name) {
		$ns_separator_index = strpos($name, ':');
		if ($ns_separator_index === FALSE) {
			return $name;
		}
		return substr($name, $ns_separator_index + 1);
	}

	private function peek() {
		$node = array_pop($this->stack);
		array_push($this->stack, $node);
		return $node;
	}
	private function pop() {
		$node = array_pop($this->stack);
		//print "  pop  ". json_encode($this->stack) ."\n";
		return $node;
	}
	private function push($node){
		//print "  push ". json_encode($this->stack) ."\n";
		array_push($this->stack, $node);
	}

	/** Intended for testing/debugging only. */
	private function state($msg) {
		$this->log("$msg ". json_encode($this->root));
	}

	/** Intended for testing/debugging only. */
	private function log($msg) {
		//print($msg ."\n");
	}

	/**
	 * Returns an stdClass object "above" the root element of the parsed XML document.
	 *
	 * @return stdClass instance, holding the XML document's root node
	 */
	public function getRoot() {
		return $this->root;
	}

	private function __construct($string) {
		$this->parent  = new stdClass();
		$this->root    = $this->parent;
		$this->current = $this->parent;
		$this->push($this->current); 

		//Create the parser resource
		$this->parser = xml_parser_create();
		
		//Set the handlers
		xml_set_object($this->parser, $this);
		xml_set_element_handler($this->parser, 'startElement', 'endElement');
		xml_set_character_data_handler($this->parser, 'characterData');

		// set proper options
		xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($this->parser, XML_OPTION_SKIP_WHITE, 1);

		//Error handling
		if (!xml_parse($this->parser, $string))
		    $this->error(xml_get_error_code($this->parser), xml_get_current_line_number($this->parser), xml_get_current_column_number($this->parser));

		//Free the parser
		xml_parser_free($this->parser);
	}
}

?>

