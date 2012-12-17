<?
/* 
 * Shamelessly mostly copied from http://stackoverflow.com/a/292305/153117
 */

/**
 * Provides a loggable entity with information on a test and how it executed
 */
class TestResult
{
    protected $_testableInstance = null;

    protected $_isSuccess = false;
    public function getSuccess()
    {
    	return $this->_isSuccess;
    }

    protected $_output = '';
    public function getOutput()
    {
    	return $_output;
    }
    public function setOutput( $value )
    {
    	$_output = $value;
    }

    protected $_test = null;
    public function getTest()
    {
    	return $this->_test;
    }

    public function getName()
    {
    	return $this->_test->getName();
    }
    public function getComment()
    {
    	return $this->ParseComment( $this->_test->getDocComment() );
    }

    private function ParseComment( $comment )
    {
    	$lines = explode( "\n", $comment );
    	for( $i = 0; $i < count( $lines ); $i ++ )
    	{
    		$lines[$i] = trim( $lines[ $i ] );
    	}
    	return implode( "\n", $lines );
    }

    protected $_exception = null;
    public function getException()
    {
    	return $this->_exception;
    }

    static public function CreateFailure( Testable $object, ReflectionMethod $test, Exception $exception )
    {
    	$result = new self();
    	$result->_isSuccess = false;
    	$result->testableInstance = $object;
    	$result->_test = $test;
    	$result->_exception = $exception;

    	return $result;
    }
    static public function CreateSuccess( Testable $object, ReflectionMethod $test )
    {
    	$result = new self();
    	$result->_isSuccess = true;
    	$result->testableInstance = $object;
    	$result->_test = $test;

    	return $result;
    }
}

/**
 * Provides a base class to derive tests from
 **/
abstract class Testable
{
    /**
     * Logs the result of a test. keeps track of results for later inspection, Overridable to log elsewhere.
     **/
    protected static function Log( TestResult $result )
    {
    	printf( "%s: %s was a %s %s\n"
    		,$result->getSuccess() ? 'PASS' : 'FAILURE '
    		,$result->getName()
    		,$result->getSuccess() ? 'success' : 'failure'
    		,$result->getSuccess() ? '' : sprintf( "\n%s (lines:%d-%d; file:%s)"
    			,$result->getException()->getMessage()
    			,$result->getTest()->getStartLine()
    			,$result->getTest()->getEndLine()
    			,$result->getTest()->getFileName()
    			)
    		);
    }

    static function runTests($cls) {
    	$class = new ReflectionClass( $cls );
    	foreach( $class->GetMethods() as $method ) {
		$that = new $cls();
    		$methodname = $method->getName();
    		if ( strlen( $methodname ) > 4 && substr( $methodname, 0, 4 ) == 'test' ) {
    			//ob_start();
			if (method_exists($that, "setUp")) {
				$that->setUp();
			}
    			try {
    				$that->$methodname();
    				$result = TestResult::CreateSuccess( $that, $method );
    			} catch( Exception $ex ) {
    				$result = TestResult::CreateFailure( $that, $method, $ex );
    			}
			if (method_exists($that, "tearDown")) {
				$that->tearDown();
			}
    			//$output = ob_get_clean();
    			//$result->setOutput( $output );
    			Testable::Log( $result );
    		}
    	}
    }
	function assertEquals($expected, $actual) {
		if (!($expected == $actual)) {
			throw new Exception("Expected '". print_r($expected, true) ."' but got '". print_r($actual, true) ."'");
		}
	}
	function assertNotEquals($expected, $actual) {
		if ($expected == $actual) {
			throw new Exception("Expected '". print_r($expected, true) ."' not equal to actual '". print_r($actual, true) ."'");
		}
	}
	function assertExceptionContains($exception, $string_to_contain) {
		$message = $exception->getMessage();
		if (stripos($message, $string_to_contain) === FALSE) {
			$this->fail("Expected exception message to contain '$string_to_contain', but did not: '". $message ."'");
		}
	}
	function fail($msg) {
		throw new Exception("Test failure: ". $msg);
	}
}

?>

