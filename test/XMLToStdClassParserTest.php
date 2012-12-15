<?

include_once('../XMLToStdClassParser.php');
include_once('Testable.php');

class XMLToStdClassParserTest extends Testable {

	function setUp() {
	}

	function test_one_element_with_data() {
		$result = XmlToStdClassParser::parseString("<test>value</test>");
		$this->assertEquals("value", $result->test);
	}

	function test_one_element_with_new_data() {
		$result = XmlToStdClassParser::parseString("<test>new</test>");
		$this->assertEquals("new", $result->test);
	}

	function test_sub_element_with_cdata() {
		$result = XmlToStdClassParser::parseString("<one><two><three>four</three></two></one>");
		$this->assertEquals("four", $result->one->two->three);
	}

	function test_two_subtrees() {
		$result = XmlToStdClassParser::parseString("<root><fork><name>fork</name></fork><thread><name>thread</name></thread></root>");
		$this->assertEquals("fork", $result->root->fork->name);
		$this->assertEquals("thread", $result->root->thread->name);
	}

	function test_multiple_elements_of_same_name_with_cdata() {
		$result = XmlToStdClassParser::parseString("<kids><child>zero</child><child>one</child></kids>");
		$children = $result->kids->child;
		$this->assertEquals(2, count($children));
		$this->assertEquals("zero", $children[0]);
		$this->assertEquals("one", $children[1]);
	}

	function test_multiple_elements_of_same_name_on_same_level_with_subelements() {
		$result = XmlToStdClassParser::parseString("<kids><child><name>leia</name></child><child><name>luke</name></child></kids>");
		$children = $result->kids->child;
		$this->assertEquals(2, count($children));
		$this->assertEquals("leia", $children[0]->name);
		$this->assertEquals("luke", $children[1]->name);
	}

	function test_comparability_happy_path() {
		$result1 = XmlToStdClassParser::parseString("<kids><child><name>leia</name></child><child><name>luke</name></child></kids>");
		$result2 = XmlToStdClassParser::parseString("<kids><child><name>leia</name></child><child><name>luke</name></child></kids>");
		$this->assertEquals($result1, $result2);
	}

	function test_comparability_sad_path() {
		$result1 = XmlToStdClassParser::parseString("<kids><child><name>luke</name></child><child><name>leia</name></child></kids>");
		$result2 = XmlToStdClassParser::parseString("<kids><child><name>leia</name></child><child><name>luke</name></child></kids>");
		$this->assertNotEquals($result1, $result2);
	}

	function test_strip_namespace_prefix() {
		$result1 = XmlToStdClassParser::parseString("<soap><towel><paper>shower</paper></towel></soap>");
		$result2 = XmlToStdClassParser::parseString("<ns1:soap><ns2:towel><ns3:paper>shower</ns3:paper></ns2:towel></ns1:soap>");
		$this->assertEquals($result1, $result2);
	}

	// FIXME
	function xtest_unbalanced() {
		try {
			XmlToStdClassParser::parseString("<soap><towel><paper></paper></towel>");
			$this->fail("Unbalanced XML document should throw exception");
		} catch (Exception $e) {
			$this->assertExceptionContains($e, "unbalanced");
		}
	}

	function test_mismatching_element_names() {
		try {
			XmlToStdClassParser::parseString("<soap>shower</soappp>");
			$this->fail("Mismatching element names should throw exception");
		} catch (Exception $e) {
			$this->assertExceptionContains($e, "mismatch");
		}
	}

	function test_parse_soap_request() {
		$from_file = XmlToStdClassParser::parseFile("test-soap-get-stock-price-request.xml");

		$expected = new stdClass();
		$expected->Envelope = new stdClass();
		$expected->Envelope->Body = new stdClass();
		$expected->Envelope->Body->GetStockPrice = new stdClass();
		$expected->Envelope->Body->GetStockPrice->StockName = "IBM";

		$this->assertEquals($expected, $from_file);
	}

	function test_with_soap_client() {
		$expected = XmlToStdClassParser::parseFile("test-soap-convert-temp-response.xml");
		$soap_client = new SoapClient("http://www.webservicex.net/ConvertTemperature.asmx?WSDL");
		$request = new stdClass();
		$request->ConvertTemp = new stdClass();
		$request->ConvertTemp->Temperature = "100";
		$request->ConvertTemp->FromUnit = "degreeCelsius";
		$request->ConvertTemp->ToUnit = "kelvin";

		$actual = $soap_client->ConvertTemp($request->ConvertTemp);

		$this->assertEquals($expected->Envelope->Body->ConvertTempResponse, $actual);
	}

}

XmlToStdClassParserTest::runTests('XmlToStdClassParserTest');

?>

