XML To stdClass SAX Parser
==========================

What does it do?
----------------
It takes XML input, like this:
	<one><two><three>four</three></two></one>

and returns an stdClass instance looking like this (according to print_r):
	stdClass Object
	(
	    [one] => stdClass Object
	        (
	            [two] => stdClass Object
	                (
	                    [three] => four
	                )
	        )
	)

which is similar to doing this:
	$result = new stdClass();
	$result->one = new stdClass();
	$result->one->two = new stdClass();
	$result->one->two->three = "four";


How would you use it?
---------------------
I use it with a SoapClientMock class to mock away a real SoapClient, 
while still being able to test interactions with it. Like this:

	class SomeClassTest {

	  function test_some_interaction() {
	    $database_layer = Phockito::mock("DatabaseLayerInterface");
	    $soap_client = new SoapClientMock();
	    $testee = new SomeClass($soap_client);
	
	
	    // arrange
	    $person_id = "1";
	    $number = "not_valid";
	    Phockito::when($database_layer->getCellPhoneNumber($person_id))->return($number);
	    $expectedSoapRequest = XMLToStdClassParser::parseFile('test/sms-request.xml')->Envelope->Body;
	    SoapClientMock::when($soap_client->sendSMS($expectedSoapRequest))->throw($exception);
	
	    // act
	    try {
	      $testee->sendGreeting($person_id);
	      $this->fail("should not accept invalid number format");
	    } catch (Exception $e) {
	      // assert
	      $this->assertExceptionMesageContains($e, "invalid number");
	      $this->assertExceptionMesageContains($e, $number);
	
	      SoapClientMock::verify($soap_client)->sendSMS($expectedSoapRequest);
	      Phockito::verify($database_layer)->contactDetailsNeedsReview($person_id);
	    }
	  }
	}


Why write this?
---------------
I couldn't find a good way to compare input and output to/from SoapClient and SoapServer.


Is it complete?
---------------
Not really - it doesn't handle attributes nor namespaces (except strip namespace prefix
from tag names/elements).


Thanks
------
To Phockito, https://github.com/hafriedlander/phockito
To "Kris", http://stackoverflow.com/a/292305/153117
To Adam A. Flynn, http://www.criticaldevelopment.net/xml/parser_php5.phps
