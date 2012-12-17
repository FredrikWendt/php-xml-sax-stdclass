<?php

function dump($data) { if (MockClass::$__PRINT_DEBUG) print "$data\n"; }
	
class MockClass {
	static $__PRINT_DEBUG = FALSE;
	
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
	public static function verify($mock, $times = 1) {
		return new MethodVerificationBuilder($mock, $times);
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
		if (isset($this->stubbed_method_handlers->$method_name)) {
			$handler = $this->stubbed_method_handlers->$method_name;
			$interaction->setHandler($handler);
			return $handler->handleCall($arguments);
		}
		dump("MockClass->__call(...) - NO handler");
	}
	function __addStubbedMethodResponses($method_name, $expected_arguments, $responses) {
		if (!isset($this->stubbed_method_handlers->$method_name)) {
			$handler = new MethodStubHandler($this, $method_name);
			$this->stubbed_method_handlers->$method_name = $handler;
		}
		$handler = $this->stubbed_method_handlers->$method_name;
		$handler->addStubbedResponses($expected_arguments, $responses);
	}
	function __addStubbedMethodExceptions($method_name, $expected_arguments, $exceptions) {
		if (!isset($this->stubbed_method_handlers->$method_name)) {
			$handler = new MethodStubHandler($this, $method_name);
			$this->stubbed_method_handlers->$method_name = $handler;
		}
		$handler = $this->stubbed_method_handlers->$method_name;
		$handler->addStubbedExceptions($expected_arguments, $exceptions);
	}
	function __verifyMethodInvokation($method_name, $expected_arguments, $expected_times) {
		$last_match = null;
		$actual_times = 0;
		foreach ($this->interactions as $interaction) {
			if ($interaction->methodNameMatches($method_name)) {
				$method_match = $interaction;
				if ($interaction->argumentsMatch($expected_arguments)) {
					if ($interaction->isVerified()) {
						continue;
					}
					$actual_times += 1;
					$interaction->markVerified();
				}
			}
		}
		if ($actual_times !== $expected_times) {
			$msg = "Method $method_name with arguments ... was invoked $actual_times, ".
				"expected $expected_times";
			throw new MockVerificationException($msg);
		}
	}
}
class MethodVerificationBuilder {
	function __construct($mock, $times) {
		$this->mock = $mock;
		$this->times = $times;
	}
	function __call($method_name, $expected_arguments) {
		$this->mock->__verifyMethodInvokation($method_name, $expected_arguments, $this->times);
	}
}
class ArgumentMatcher {
	function __construct($args, $response) {
		$this->expected_arguments = $args;
		$this->response = $response;
		$this->consumed = FALSE;
	}
	function matches($actual_arguments) {

		return $this->expected_arguments == $actual_arguments;
	}
	function isConsumed() {
		return $this->consumed;
	}
	function getResponse() {
		$this->consumed = TRUE;
		return $this->response;
	}
}
class ArgumentMatcherWithException extends ArgumentMatcher {
	function getResponse() {
		$exception = parent::getResponse();
		throw $exception;
	}
}
		

class MethodStubHandler {
	function __construct() {
		$this->argument_matchers = array();
	}
	function addStubbedResponses($expected_arguments, $responses) {
		if (!is_array($responses)) {
			array_push($this->argument_matchers, new ArgumentMatcher($expected_arguments, $responses));
		} else {
			while (!empty($responses)) {
				$matcher = new ArgumentMatcher($expected_arguments, array_shift($responses));
				array_push($this->argument_matchers, $matcher);
			}
		}
	}
	function addStubbedExceptions($expected_arguments, $exceptions) {
		if (!is_array($exceptions)) {
			array_push($this->argument_matchers, new ArgumentMatcherWithException($expected_arguments, $exceptions));
		} else {
			while (!empty($exceptions)) {
				$matcher = new ArgumentMatcherWithException($expected_arguments, array_shift($exceptions));
				array_push($this->argument_matchers, $matcher);
			}
		}
	}
	function handleCall($actual_arguments) {
		$match = null;
		foreach ($this->argument_matchers as $matcher) {
			if ($matcher->matches($actual_arguments)) {
				$match = $matcher;
				if (!$matcher->isConsumed()) {
					return $matcher->getResponse();
				}
			}
		}
		if (isset($match)) {
			return $matcher->getResponse();
		}
	}
}

class MethodStubbingResponseBuilder {
	function __construct($mock, $method_name, $arguments) {
		dump("MethodStubbingResponseBuilder");
		$this->mock = $mock;
		$this->method_name = $method_name;
		$this->expected_arguments = $arguments;
	}
	function __call($method_name, $responses){
		if ("throw" === $method_name) {
			$this->mock->__addStubbedMethodExceptions($this->method_name, $this->expected_arguments, $responses);
		} else {
			$this->mock->__addStubbedMethodResponses($this->method_name, $this->expected_arguments, $responses);
		}
	}
}
class MethodStubbingBuilder {
	function __construct($mock) {
		dump("MethodStubbingBuilder");
		$this->mock = $mock;
	}
	function __call($method_name, $arguments) {
		return new MethodStubbingResponseBuilder($this->mock, $method_name, $arguments);
	}
}
class MockInteraction {
	function __construct($mock, $method_name, $actual_arguments) {
		$this->mock = $mock;
		$this->method_name = $method_name;
		$this->actual_arguments = $actual_arguments;
		$this->verified = FALSE;
	}
	function setHandler($handler) {
		$this->handler = $handler;
	}
	function methodNameMatches($method_name) {
		return $this->method_name === $method_name;
	}
	function argumentsMatch($expected_arguments) {
		return $this->actual_arguments === $expected_arguments;
	}
	function isVerified() {
		return $this->verified;
	}
	function markVerified() {
		$this->verified = true;
	}
}
class MockVerificationException extends Exception {
}

?>

