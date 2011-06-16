<?php
require_once __DIR__ . '/../src/DydraException.php';
require_once __DIR__ . '/../src/DydraClient.php';
require_once __DIR__ . '/../src/DydraRepository.php';

class DydraRepositoryTest extends PHPUnit_Framework_TestCase {
    
    const TEST_ACCOUNT_ID = 'testaccount';
    const TEST_AUTH_TOKEN = 'testtoken';
    const TEST_REPOSITORY_ID = 'foaf';
    
    private static $REPOSITORY = array(
        'repository_id' => 'testaccount/foaf',
        'name' => 'foaf',
        'summary' => '',
        'description' => '',
        'homepage' => 'http://dydra.com/testaccount/foaf',
        'triple_count' => 0,
        'byte_size' => 0
    );
    
    private static $TRIPLE = array(
        'subject' => 'http://example.org#jhacker',
        'predicate' => 'http://xmlns.com/foaf/0.1/nick',
        'object' => 'jhuckabee',
    );
    
    private static $CONTENTS = "<http://example.org#jhacker> <http://xmlns.com/foaf/0.1/nick> \"jhuckabee\" .";
    
    private static $QUERY = "SELECT * WHERE {
        ?a ?b ?c
    }";
    
    private static $RESPONSE = "OK";
    
    /**
     *  @expectedException PHPUnit_Framework_Error
     */
    function test___constructor_without_parameters() {
        $resource = new DydraRepository(); 
    }
    
    function test___constructor_with_parameters() {
        $client = new DydraClient(static::TEST_ACCOUNT_ID, static::TEST_AUTH_TOKEN);
        $repo = new DydraRepository($client, static::TEST_REPOSITORY_ID);
        
        $this->assertEquals(static::TEST_REPOSITORY_ID, $repo->getRepositoryId());
    }
    
    /**
     *  @expectedException DydraException
     */
    function test_getRepositoryList_bad_response_code() {
        $client = $this->getMock('DydraClient', array('executeRequest'), array(static::TEST_ACCOUNT_ID, static::TEST_AUTH_TOKEN));
        $client->expects($this->once())
               ->method('executeRequest')
               ->will($this->returnValue(
                   array(404, "", "")              // will raise exception
                 ));
        
        $repo = new DydraRepository($client, static::TEST_REPOSITORY_ID);
        
        $repo->getRepositoryList();
    }
    
    /**
     *  @expectedException DydraException
     */
    function test_getRepositoryList_bad_response_body() {
        $client = $this->getMock('DydraClient', array('executeRequest'), array(static::TEST_ACCOUNT_ID, static::TEST_AUTH_TOKEN));
        $client->expects($this->once())
               ->method('executeRequest')
               ->will($this->returnValue(
                   array(200, "{)][df)", "")       // will raise exception
                 ));
        
        $repo = new DydraRepository($client, static::TEST_REPOSITORY_ID);
        
        $repo->getRepositoryList();
    }
    
    function test_getRepositoryList_valid_response() {
        $client = $this->getMock('DydraClient', array('executeRequest'), array(static::TEST_ACCOUNT_ID, static::TEST_AUTH_TOKEN));
        $client->expects($this->once())
               ->method('executeRequest')
               ->will($this->returnValue(
                   array(200, json_encode(array(self::$REPOSITORY)), "") // will return object
                 ));
        
        $repo = new DydraRepository($client, static::TEST_REPOSITORY_ID);
        
        $list = $repo->getRepositoryList();
        
        /** Assert request */
        $this->assertEquals("GET", $client->getMethod());
        $this->assertEquals("/" . $client->getAccountId() . "/repositories", $client->getPath());
        $this->assertEquals(DydraClient::JSON, $client->getAccept());
        
        /** Assert response */
        $this->assertTrue(is_array($list));
        $this->assertTrue(count($list) > 0);
        $this->assertTrue(is_object($list[0]));
        $this->assertEquals(self::$REPOSITORY['repository_id'], $list[0]->repository_id);
        $this->assertEquals(self::$REPOSITORY['name'], $list[0]->name);
        $this->assertEquals(self::$REPOSITORY['summary'], $list[0]->summary);
        $this->assertEquals(self::$REPOSITORY['description'], $list[0]->description);
        $this->assertEquals(self::$REPOSITORY['homepage'], $list[0]->homepage);
        $this->assertEquals(self::$REPOSITORY['triple_count'], $list[0]->triple_count);
        $this->assertEquals(self::$REPOSITORY['byte_size'], $list[0]->byte_size);
    }
    
    /**
     *  @expectedException DydraException
     */
    function test_createRepository_bad_response_code() {
        $client = $this->getMock('DydraClient', array('executeRequest'), array(static::TEST_ACCOUNT_ID, static::TEST_AUTH_TOKEN));
        $client->expects($this->once())
               ->method('executeRequest')
               ->will($this->returnValue(
                   array(404, "", "")              // will raise exception
                 ));
        
        $repo = new DydraRepository($client, static::TEST_REPOSITORY_ID);
        
        $repo->createRepository(static::TEST_REPOSITORY_ID);
    }
    
    /**
     *  @expectedException DydraException
     */
    function test_createRepository_bad_response_body() {
        $client = $this->getMock('DydraClient', array('executeRequest'), array(static::TEST_ACCOUNT_ID, static::TEST_AUTH_TOKEN));
        $client->expects($this->once())
               ->method('executeRequest')
               ->will($this->returnValue(
                   array(200, "{)][df)", "")       // will raise exception
                 ));
        
        $repo = new DydraRepository($client, static::TEST_REPOSITORY_ID);
        
        $repo->createRepository(static::TEST_REPOSITORY_ID);
    }
    
    function test_createRepository_valid_response() {
        $client = $this->getMock('DydraClient', array('executeRequest'), array(static::TEST_ACCOUNT_ID, static::TEST_AUTH_TOKEN));
        $client->expects($this->once())
               ->method('executeRequest')
               ->will($this->returnValue(
                   array(200, json_encode(self::$REPOSITORY), "") // will return object
                 ));
        
        $repo = new DydraRepository($client, static::TEST_REPOSITORY_ID);
        
        $obj = $repo->createRepository(self::$REPOSITORY['name'], self::$REPOSITORY['summary'], self::$REPOSITORY['description'], self::$REPOSITORY['homepage']);
        
        /** Assert request */
        $this->assertEquals("POST", $client->getMethod());
        $this->assertEquals("/" . $client->getAccountId() . "/repositories", $client->getPath());
        $this->assertEquals(DydraClient::JSON, $client->getAccept());
        $this->assertTrue(array_key_exists('repository[name]', $client->getData()));
        $this->assertFalse(array_key_exists('repository[summary]', $client->getData()));
        $this->assertFalse(array_key_exists('repository[description]', $client->getData()));
        $this->assertTrue(array_key_exists('repository[homepage]', $client->getData()));
        
        /** Assert response */
        $this->assertTrue(is_object($obj));
        $this->assertEquals(self::$REPOSITORY['repository_id'], $obj->repository_id);
        $this->assertEquals(self::$REPOSITORY['name'], $obj->name);
        $this->assertEquals(self::$REPOSITORY['summary'], $obj->summary);
        $this->assertEquals(self::$REPOSITORY['description'], $obj->description);
        $this->assertEquals(self::$REPOSITORY['homepage'], $obj->homepage);
        $this->assertEquals(self::$REPOSITORY['triple_count'], $obj->triple_count);
        $this->assertEquals(self::$REPOSITORY['byte_size'], $obj->byte_size);
    }
    
    function test_deleteRepository() {
        $client = $this->getMock('DydraClient', array('executeRequest'), array(static::TEST_ACCOUNT_ID, static::TEST_AUTH_TOKEN));
        $client->expects($this->once())
               ->method('executeRequest')
               ->will($this->onConsecutiveCalls(
                   array(404, "", ""),       // will return false
                   array(200, "", "")        // will return true
                 ));
        
        $repo = new DydraRepository($client, static::TEST_REPOSITORY_ID);
        
        /** Assert response */
        $this->assertFalse($repo->deleteRepository(static::TEST_REPOSITORY_ID));
                
        /** Assert request */
        $this->assertEquals("DELETE", $client->getMethod());
        $this->assertEquals("/" . $client->getAccountId() . "/" . static::TEST_REPOSITORY_ID, $client->getPath());
        $this->assertEquals(DydraClient::JSON, $client->getAccept());        
    }
    
    /**
     *  @expectedException DydraException
     */
    function test_insert_with_empty_array() {
        $client = $this->getMock('DydraClient', array('executeRequest'), array(static::TEST_ACCOUNT_ID, static::TEST_AUTH_TOKEN));
        $client->expects($this->exactly(0))
               ->method('executeRequest')
               ->will($this->returnValue(
                   array(404, "", "")       // will return false
                 ));
        
        $repo = new DydraRepository($client, static::TEST_REPOSITORY_ID);
        
        $repo->insert(array());
    }
    
    /**
     *  @expectedException DydraException
     */
    function test_insert_with_invalid_array() {
        $client = $this->getMock('DydraClient', array('executeRequest'), array(static::TEST_ACCOUNT_ID, static::TEST_AUTH_TOKEN));
        $client->expects($this->exactly(0))
               ->method('executeRequest')
               ->will($this->returnValue(
                   array(404, "", "")       // will return false
                 ));
        
        $repo = new DydraRepository($client, static::TEST_REPOSITORY_ID);
        
        $repo->insert(array(array('bad' => 'value')));
    }
    
    function test_insert_with_single_triple() {
        $client = $this->getMock('DydraClient', array('executeRequest'), array(static::TEST_ACCOUNT_ID, static::TEST_AUTH_TOKEN));
        $client->expects($this->once())
               ->method('executeRequest')
               ->will($this->returnValue(
                   array(200, "", "")       // will return false
                 ));
        
        $repo = new DydraRepository($client, static::TEST_REPOSITORY_ID);
        
        /** Assert response */
        $this->assertTrue($repo->insert(self::$TRIPLE));

        /** Assert request */
        $this->assertEquals("POST", $client->getMethod());
        $this->assertEquals("/" . $client->getAccountId() . "/" . static::TEST_REPOSITORY_ID . "/statements", $client->getPath());
        $this->assertEquals(DydraClient::RDFXML, $client->getAccept());
        $this->assertContains("text/plain; charset=utf-8", $client->getAllHeaders());
        $this->assertArrayHasKey("Content-Type", $client->getAllHeaders());
        $this->assertEquals(sprintf("<%s> <%s> \"%s\" .\n", self::$TRIPLE['subject'], self::$TRIPLE['predicate'], self::$TRIPLE['object']), $client->getData());
    }
    
    /**
     *  @expectedException DydraException
     */
    function test_import_with_invalid_file() {
        $client = $this->getMock('DydraClient', array('executeRequest'), array(static::TEST_ACCOUNT_ID, static::TEST_AUTH_TOKEN));
        $client->expects($this->exactly(0))
               ->method('executeRequest')
               ->will($this->returnValue(
                   array(404, "", "")       // will return false
                 ));
        
        $repo = new DydraRepository($client, static::TEST_REPOSITORY_ID);
        
        $repo->import("/non/existent/path/" . uniqid() . ".nt");
    }
    
    function test_import_with_unreadable_file() {
        $client = $this->getMock('DydraClient', array('executeRequest'), array(static::TEST_ACCOUNT_ID, static::TEST_AUTH_TOKEN));
        $client->expects($this->exactly(0))
               ->method('executeRequest')
               ->will($this->returnValue(
                   array(404, "", "")       // will return false
                 ));
        
        $repo = new DydraRepository($client, static::TEST_REPOSITORY_ID);
        
        // create temporary file
        $tmpName = tempnam("/tmp", "hntest_");
        file_put_contents($tmpName, self::$CONTENTS);
        chmod($tmpName, 0200); // make write only
        
        try {
            $repo->import($tmpName);
        } catch(DydraException $e) {
            $this->assertTrue(true);
        }
        
        unlink($tmpName);
    }
    
    function test_import_bad_response() {
        $client = $this->getMock('DydraClient', array('executeRequest'), array(static::TEST_ACCOUNT_ID, static::TEST_AUTH_TOKEN));
        $client->expects($this->once())
               ->method('executeRequest')
               ->will($this->returnValue(
                   array(404, "", "")       // will return false
                 ));
        
        $repo = new DydraRepository($client, static::TEST_REPOSITORY_ID);
        
        // create temporary file
        $tmpName = tempnam("/tmp", "hntest_");
        file_put_contents($tmpName, self::$CONTENTS);
        
        /** Assert response */
        $this->assertFalse($repo->import($tmpName));

        /** Assert request */
        $this->assertEquals("POST", $client->getMethod());
        $this->assertEquals("/" . $client->getAccountId() . "/" . static::TEST_REPOSITORY_ID . "/statements", $client->getPath());
        $this->assertEquals(DydraClient::RDFXML, $client->getAccept());
        $this->assertArrayHasKey("file", $client->getData());
        $this->assertContains("@" . $tmpName . ";type=text/plain", $client->getData());

        // delete temporary file
        unlink($tmpName);
    }
    
    function test_import_good_response() {
        $client = $this->getMock('DydraClient', array('executeRequest'), array(static::TEST_ACCOUNT_ID, static::TEST_AUTH_TOKEN));
        $client->expects($this->once())
               ->method('executeRequest')
               ->will($this->returnValue(
                   array(201, "", "")       // will return true
                 ));
        
        $repo = new DydraRepository($client, static::TEST_REPOSITORY_ID);
        
        // create temporary file
        $tmpName = tempnam("/tmp", "hntest_");
        file_put_contents($tmpName, self::$CONTENTS);
        
        /** Assert response */
        $this->assertTrue($repo->import($tmpName));

        /** Assert request */
        $this->assertEquals("POST", $client->getMethod());
        $this->assertEquals("/" . $client->getAccountId() . "/" . static::TEST_REPOSITORY_ID . "/statements", $client->getPath());
        $this->assertEquals(DydraClient::RDFXML, $client->getAccept());
        $this->assertArrayHasKey("file", $client->getData());
        $this->assertContains("@" . $tmpName . ";type=text/plain", $client->getData());

        // delete temporary file
        unlink($tmpName);
    }
    
    /**
     *  @expectedException DydraException
     */
    function test_query_bad_string() {
        $client = $this->getMock('DydraClient', array('executeRequest'), array(static::TEST_ACCOUNT_ID, static::TEST_AUTH_TOKEN));
        $client->expects($this->exactly(0))
               ->method('executeRequest')
               ->will($this->returnValue(
                   array(201, "", "")       // will return true
                 ));
        
        $repo = new DydraRepository($client, static::TEST_REPOSITORY_ID);
        
        $repo->query(false);
    }
    
    function test_query_bad_response() {
        $client = $this->getMock('DydraClient', array('executeRequest'), array(static::TEST_ACCOUNT_ID, static::TEST_AUTH_TOKEN));
        $client->expects($this->once())
               ->method('executeRequest')
               ->will($this->returnValue(
                   array(404, "", "")       // will return null
                 ));
        
        $repo = new DydraRepository($client, static::TEST_REPOSITORY_ID);
        
        /** Assert response */
        $this->assertNull($repo->query(self::$QUERY));
        
        /** Assert request */
        $this->assertEquals("GET", $client->getMethod());
        $this->assertEquals("/" . $client->getAccountId() . "/" . static::TEST_REPOSITORY_ID . "/sparql", $client->getPath());
        $this->assertEquals(DydraClient::ANY, $client->getAccept());
        $this->assertContains(self::$QUERY, $client->getData());
        $this->assertArrayHasKey("query", $client->getData());
    }
    
    function test_query_good_response() {
        $client = $this->getMock('DydraClient', array('executeRequest'), array(static::TEST_ACCOUNT_ID, static::TEST_AUTH_TOKEN));
        $client->expects($this->once())
               ->method('executeRequest')
               ->will($this->returnValue(
                   array(200, self::$RESPONSE, "")       // will return "null"OK"
                 ));
        
        $repo = new DydraRepository($client, static::TEST_REPOSITORY_ID);
        
        /** Assert response */
        $this->assertEquals(self::$RESPONSE, $repo->query(self::$QUERY));
        
        /** Assert request */
        $this->assertEquals("GET", $client->getMethod());
        $this->assertEquals("/" . $client->getAccountId() . "/" . static::TEST_REPOSITORY_ID . "/sparql", $client->getPath());
        $this->assertEquals(DydraClient::ANY, $client->getAccept());
        $this->assertContains(self::$QUERY, $client->getData());
        $this->assertArrayHasKey("query", $client->getData());
    }
}