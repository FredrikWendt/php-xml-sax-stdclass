<?php

include_once('../MockClass.php');
include_once('Testable.php');

class MockClassTest extends Testable {

	function setUp() {
		$this->mock = MockClass::mock();	
	}

	function test_verifyZeroInteractions_on_mock_object_with_no_interactions() {
		MockClass::verifyZeroInteractions($this->mock);
	}

	function test_verifyZeroInteractions_on_mock_object_with_interactions() {
		$this->mock->interaction();
		try {
			MockClass::verifyZeroInteractions($this->mock);
			$this->fail("there were interactions - verifyZeroInteractions should fail");
		} catch (MockVerificationException $e) {
			$this->assertExceptionContains($e, '1 interaction');
		}
	}

	function test_stubbing_calling_unstubbed_method() {
		$result = $this->mock->call();
		$this->assertEquals(NULL, $result);
	}

	function test_stubbing_method_no_argument_simple_return() {
		MockClass::when($this->mock)->call()->return("abc");
		$result = $this->mock->call();
		$this->assertEquals("abc", $result);
	}

	function test_that_mocks_have_unique_numbers() {
		$mock2 = MockClass::mock();
		$this->assertNotEquals("$this->mock", "$mock2");
	}

	function xtest_stubbing_method_no_args_throw_exception() {
	}
	function xtest_stubbin_with_argument_matching_simple_return() {
	}
	function xtest_stubbin_with_argument_matching_throw_exception() {
	}
	function xtest_verify_no_args_method() {
	}
	function xtest_verify_no_args_method_never_happened() {
	}
	function xtest_verify_no_args_method_multiple_calls() {
	}
	function xtest_stubbing_several_answers_in_one_go() {
		when($mock->call())->return(1, 2, 3);
	}


}

MockClassTest::runTests('MockClassTest');

?>

