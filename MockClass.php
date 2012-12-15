<?php

function dump($data) {
	if (TRUE)
		print "$data\n";
}
	
class MockClass {
	
	private $interactions = array();
	private static $instance_counter = 1;

	public static function mock() {
		return new MockClass(MockClass::$instance_counter++);
	}
	public static function verifyZeroInteractions($mock) {
		$mock->__verifyZeroInteractions();
	}
	public static function when($mock) {
		return new MethodStubbingBuilder($mock);
	}

	function __construct($id) {
		dump("__construct($id)");
		$this->id = $id;
	}
	function __toString() {
		return "MockClass_". $this->id;
	}

	function __verifyZeroInteractions() {
		if (empty($this->interactions)) {
			return;
		}
		throw new MockVerificationException("There were at least 1 interaction with this mock");
	}

	function __call($method_name, $arguments) {
		dump("MockClass->__call($method_name, $arguments)");
		$interaction = new MockInteraction($this, $method_name, $arguments);
		array_push($this->interactions, $interaction);
		if (isset($this->responses)) 
		if (is_array($this->responses)) 
		return array_shift($this->responses);
	}
	function __addStubbedMethodResponse($method_name, $arguments, $responses) {
		$this->responses = $responses;
	}
}

class MethodStubbingAnswer {
	function __construct($mock, $method_name, $arguments, $responses) {
		dump("MethodStubbingAnswer");
		$this->mock = $mock;
		$this->method_name = $method_name;
		$this->arguments = $arguments;
		$this->responses = $responses;
		$this->mock->__addStubbedMethodResponse($method_name, $arguments, $responses);
	}
	function __call($method_name, $actual_arguments) {
		dump('__call($method_name)');
		$response = array_unshift($this->responses);
		return $response;
	}
	function __verify() {
		// check that $this->response have all been consumed
		if (empty($this->responses)) {
		
		}
	}
}
class MethodStubbingResponseBuilder {
	function __construct($mock, $method_name, $arguments) {
		print "MethodStubbingResponseBuilder\n";
		$this->mock = $mock;
		$this->method_name = $method_name;
		$this->expected_arguments = $arguments;
	}
	function __call($method_name, $responses){
		return new MethodStubbingAnswer($this->mock, $this->method_name, $this->expected_arguments, $responses);
	}
	function thenThrow(Exception $e) {
	}
}
class MethodStubbingBuilder {
	function __construct($mock) {
		print "MethodStubbingBuilder\n";
		$this->mock = $mock;
	}
	function __call($method_name, $arguments) {
		return new MethodStubbingResponseBuilder($this->mock, $method_name, $arguments);
	}
}
class MockInteraction {
}
class MockVerificationException extends Exception {
}

?>

