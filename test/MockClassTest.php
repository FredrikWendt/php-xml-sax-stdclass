<?php

include_once('../MockClass.php');
include_once('Testable.php');

class MockClassTest extends Testable {

	function setUp() {
		$this->mock = MockClass::mock();	
	}

	function tearDown() {
		MockClass::$__PRINT_DEBUG = FALSE;
	}

	function test_that_mocks_have_unique_numbers() {
		$mock2 = MockClass::mock();
		$this->assertNotEquals("$this->mock", "$mock2");
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

	function test_stubbing_method_no_argument_two_responses() {
		MockClass::when($this->mock)->call()->return("abc", "cde", "efg");
		$this->assertEquals("abc", $result = $this->mock->call());
		$this->assertEquals("cde", $result = $this->mock->call());
		$this->assertEquals("efg", $result = $this->mock->call());
	}

	function test_stubbing_two_no_arg_methods() {
		MockClass::when($this->mock)->methodA()->return("a");
		MockClass::when($this->mock)->methodB()->return("b");
		$this->assertEquals("a", $result = $this->mock->methodA());
		$this->assertEquals("b", $result = $this->mock->methodB());
	}

	function test_stubbing_method_no_argument_simple_return_forever() {
		MockClass::when($this->mock)->call()->return("abc");
		$this->assertEquals("abc", $result = $this->mock->call());
		$this->assertEquals("abc", $result = $this->mock->call());
		$this->assertEquals("abc", $result = $this->mock->call());
	}

	function test_stubbing_method_no_args_throw_exception() {
		$exception = new Exception("asdf");
		$e;
		MockClass::when($this->mock)->call()->thenThrow($exception);
		try {
			$this->mock->call();
			$this->fail("should've thrown Exception");
		} catch (Exception $caught_exception) {
			$e = $caught_exception;
		}
		$this->assertEquals($exception, $e);
	}

	function test_stubbing_with_argument_matching_simple_return() {
		MockClass::when($this->mock)->method("a")->return("a");
		MockClass::when($this->mock)->method("b")->return("b");
		MockClass::when($this->mock)->method("c")->return("c");
		$this->assertEquals("a", $result = $this->mock->method("a"));
		$this->assertEquals("b", $result = $this->mock->method("b"));
		$this->assertEquals("c", $result = $this->mock->method("c"));
	}

	function xtest_stubbin_with_argument_matching_throw_exception() {
	}

	function test_stubbing_several_answers_in_one_go() {
		MockClass::when($this->mock)->someMethod()->return(1, 2, 3);

		$this->assertEquals(1, $result = $this->mock->someMethod());
		$this->assertEquals(2, $result = $this->mock->someMethod());
		$this->assertEquals(3, $result = $this->mock->someMethod());
		// last arg is repeated
		$this->assertEquals(3, $result = $this->mock->someMethod());
		$this->assertEquals(3, $result = $this->mock->someMethod());
	}

	function test_stubbing_mixing_several_results_and_args() {
		MockClass::when($this->mock)->someMethod("ints")->return(1, 2, 3);
		MockClass::when($this->mock)->someMethod("strings")->return("a", "b", "c");

		$this->assertEquals("a", $result = $this->mock->someMethod("strings"));
		$this->assertEquals("b", $result = $this->mock->someMethod("strings"));
		$this->assertEquals("c", $result = $this->mock->someMethod("strings"));

		$this->assertEquals(1, $result = $this->mock->someMethod("ints"));
		$this->assertEquals(2, $result = $this->mock->someMethod("ints"));
		$this->assertEquals(3, $result = $this->mock->someMethod("ints"));
	}

	function test_verify_no_args_method_never_happened() {
		MockClass::verify($this->mock, 0)->method();
	}

	function test_verify_args_method_never_happened() {
		MockClass::verify($this->mock, 0)->method("a", "b");
	}

	function test_verify_no_args_method_never_happened_sad_path() {
		$this->mock->aMethod();
		try {
			MockClass::verify($this->mock, 0)->aMethod();
			$this->fail("the call has been made - should throw exception");
		} catch (MockVerificationException $e) {
			$this->assertExceptionContains($e, "aMethod");
		}
	}

	function test_verify_args_method_never_happened_sad_path() {
		$this->mock->aMethod("a", "b");
		try {
			MockClass::verify($this->mock, 0)->aMethod("a", "b");
			$this->fail("the call has been made - should throw exception");
		} catch (MockVerificationException $e) {
			$this->assertExceptionContains($e, "aMethod");
		}
	}

	function test_verify_no_args_method() {
		$this->mock->add();
		MockClass::verify($this->mock)->add();
	}

	function test_verify_with_single_argument() {
		$this->mock->add("1");
		MockClass::verify($this->mock)->add("1");
	}

	function test_verify_with_different_single_argument() {
		$this->mock->add("1");
		try {
			MockClass::verify($this->mock)->add("2");
			$this->fail("arguments were different");
		} catch (MockVerificationException $e) {
		}
	}

	function test_verify_multiple_arguments() {
		$this->mock->add("1", "2", "3");
		MockClass::verify($this->mock)->add("1", "2", "3");
	}

	function test_verify_multiple_arguments_wrong_order() {
		$this->mock->add("1", "2", "3");
		try {
			MockClass::verify($this->mock)->method("3", "2", "1");
			$this->fail("arguments in wrong order");
		} catch (MockVerificationException $e) {
		}
	}

	function test_verify_args_method_additional_calls_was_made() {
		$this->mock->add("1");
		$this->mock->add("1");
		$this->mock->add("1");
		try {
			MockClass::verify($this->mock, 2)->add("1");
			$this->fail("add was called 3 times, not 2");
		} catch (MockVerificationException $e) {
			$this->assertExceptionContains($e, "2");
			$this->assertExceptionContains($e, "3");
		}
	}

}

MockClassTest::runTests('MockClassTest');

?>

