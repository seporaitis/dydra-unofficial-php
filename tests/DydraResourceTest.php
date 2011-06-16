<?php
require_once __DIR__ . '/../src/DydraException.php';
require_once __DIR__ . '/../src/DydraClient.php';
require_once __DIR__ . '/../src/DydraResource.php';

class DydraResourceExposed extends DydraResource {
    public function getClient() {
        return parent::getClient();
    }
}

class DydraResourceTest extends PHPUnit_Framework_TestCase {
    
    const TEST_ACCOUNT_ID = 'testaccount';
    const TEST_AUTH_TOKEN = 'testtoken';
    
    /**
     *  @expectedException PHPUnit_Framework_Error
     */
    function test___constructor_without_parameters() {
        $resource = new DydraResource(); 
    }
    
    function test___constructor_with_parameters() {
        $client = new DydraClient(static::TEST_ACCOUNT_ID, static::TEST_AUTH_TOKEN);
        $resource = new DydraResourceExposed($client);
        
        $this->assertNotNull($resource->getClient());
        $this->assertEquals($client->getAccountId(), $resource->getClient()->getAccountId());
    }
    
}