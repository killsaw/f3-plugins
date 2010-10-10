<?php

require __DIR__.'/bootstrap.php';
require __DIR__.'/../url.php';

error_reporting(E_ALL|E_STRICT);

class UrlTest extends PHPUnit_Framework_TestCase
{
	protected $obj;
	
	const OKAY_URL = "http://test:pass@killsaw.com/test/unit.php?id=123&x=50#40";
	const BASE_URL = "http://test:pass@killsaw.com";
	
    public function setUp()
    {
    	$this->obj = new URL(self::OKAY_URL);
    }
    
    public function tearDown()
    {
    	// Do nothing.
    }
    
    public function testParsingAndGetters()
    {
    	$url = &$this->obj;
		
		// Test magic.
    	$this->assertEquals($url->scheme, "http");
    	$this->assertEquals($url->port, "");
    	$this->assertEquals($url->user, "test");
    	$this->assertEquals($url->pass, "pass");
    	$this->assertEquals($url->host, "killsaw.com");
    	$this->assertEquals($url->path, "/test/unit.php");
    	$this->assertEquals($url->fragment, "40");
    }
    
    public function testBadGet()
    {
    	PHPUnit_Framework_Error_Warning::$enabled = FALSE;
    	
    	$url = &$this->obj;
    	$prop_name = md5(time());
    	$this->setExpectedException('PHPUnit_Framework_Error');
		$fname = $url->__get($prop_name);
		
    	PHPUnit_Framework_Error_Warning::$enabled = TRUE;		
    }

    public function testBadSet()
    {
    	PHPUnit_Framework_Error_Warning::$enabled = FALSE;
    	
    	$url = &$this->obj;
    	$prop_name = md5(time());
    	$this->setExpectedException('PHPUnit_Framework_Error');
		$url->__set($prop_name, 'test');
		
    	PHPUnit_Framework_Error_Warning::$enabled = TRUE;		
    }

    public function testBadCall()
    {
    	PHPUnit_Framework_Error_Warning::$enabled = FALSE;
    	
    	$url = &$this->obj;
    	$meth_name = md5(time());
    	$this->setExpectedException('PHPUnit_Framework_Error');
		$url->$meth_name('test');
		
    	PHPUnit_Framework_Error_Warning::$enabled = TRUE;		
    }
    
    public function testToString()
    {
    	$url = &$this->obj;
    	
    	// Test basic string generation.
    	$this->assertEquals(self::OKAY_URL, $url->toString());
    	$this->assertEquals(self::OKAY_URL, $url->__toString());

		// And just the base.
		$this->assertEquals(self::BASE_URL,  $url->toString($just_base=true));
		
		// Test with no scheme, no path and set port.
		$url->scheme = '';
		$url->path = '';
		$url->port = 9080;
    	$this->assertEquals('http://test:pass@killsaw.com:9080/?id=123&x=50#40', 
    						 $url->toString());
    }
    
    public function testBaseURL()
    {
		$url = &$this->obj;
    	$this->assertEquals(self::BASE_URL, $url->getBaseURL());

    }
    
    public function testStaticEntrypoint()
    {
    	$url = URL::url();
    	$this->assertType('URL', $url);
    }
    
    public function testSetLogin()
    {
    	$url = &$this->obj;
    	$url->setLogin('testuser', 'testpass');
    	$this->assertEquals($url->user, 'testuser');
    	$this->assertEquals($url->pass, 'testpass');
    }
    
    public function testGetAndSetParam()
    {
    	$url = &$this->obj;
    	
    	$url->setParam('test', 'phpunit');
    	$this->assertEquals($url->getParam('test'), 'phpunit');
    	$this->assertNull($url->getParam(md5(time())));
    }
    
    public function testWebGet()
    {
    	$url = &$this->obj;
    	
    	$agent = 'Example Agent';
    	$referer = 'http://nsa.gov/hotlinks.html';
    	$cookies = array('username'=>'steven');
    	
		$reply = $url->get($cookies, $referer, $agent);
		$struct = unserialize($reply);
		
		$this->assertType('array', $struct);
		$this->assertEquals($struct['GET']['id'], '123');
		$this->assertEquals($struct['GET']['x'], '50');
		$this->assertEquals(count($struct['POST']), 0);
		$this->assertEquals($struct['Agent'], $agent);

		// Test with empty UA
		$reply = $url->get($cookies, $referer, $agent=null);
		$struct = unserialize($reply);
		$this->assertEquals($struct['Agent'], 'Mozilla/5.0 (Windows; U; MSIE 9.0; WIndows NT 9.0; en-US)');		
    }
    
    public function testLink()
    {
    	$url = &$this->obj;

    	// Test without name.
    	$link = $url->toLink();
    	$this->assertEquals($link, sprintf('<a href="%s">%s</a>', 
    									self::OKAY_URL, self::OKAY_URL));
    }
}