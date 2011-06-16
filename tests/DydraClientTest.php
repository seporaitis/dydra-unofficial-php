<?php
require_once __DIR__ . '/../src/DydraException.php';
require_once __DIR__ . '/../src/DydraClient.php';

class DydraClientExposed extends DydraClient {
    public function getURL() {
        return parent::getURL();
    }

    public function prepareHTTPHeaders() {
        return parent::prepareHTTPHeaders();
    }
}

class DydraClientTest extends PHPUnit_Framework_TestCase {

    const TEST_ACCOUNT_ID = 'testaccount';
    const TEST_AUTH_TOKEN = 'testtoken';

    /**
     *  @expectedException PHPUnit_Framework_Error_Warning
     */
    function test___constructor_with_no_parameters() {
        $client = new DydraClient();
    }

    function test___constructor_with_parameters() {
        $client = new DydraClient(static::TEST_ACCOUNT_ID, static::TEST_AUTH_TOKEN);
        $this->assertEquals(static::TEST_ACCOUNT_ID, $client->getAccountId());
    }

    function test_reset() {
        $client = new DydraClient(static::TEST_ACCOUNT_ID, static::TEST_AUTH_TOKEN);
        $client->setPath("/" . $client->getAccountId() . "/repositories")
        ->setMethod("POST")
        ->setData(array('subject' => 'test', 'object' => 'test'))
        ->setHeader("Content-Type", "application/xml")
        ->setAccept(DydraClient::NTRIPLES)
        ->reset();

        $this->assertEquals("GET", $client->getMethod());
        $this->assertNull($client->getPath());
        $this->assertNull($client->getData());
        $this->assertEmpty($client->getAllHeaders());
        $this->assertEquals(DydraClient::RDFXML, $client->getAccept());
    }

    function test_getURL_no_request() {
        $client = new DydraClientExposed(static::TEST_ACCOUNT_ID, static::TEST_AUTH_TOKEN);

        $this->assertEquals(DydraClient::HOST . "/?auth_token=" . static::TEST_AUTH_TOKEN, $client->getURL());
    }

    function test_getURL_get_request() {
        /** Check without query parameters */
        $client = new DydraClientExposed(static::TEST_ACCOUNT_ID, static::TEST_AUTH_TOKEN);
        $client->setPath("/" . $client->getAccountId() . "/repositories")
        ->setMethod("GET")
        ->setAccept("application/json");

        $this->assertEquals("GET", $client->getMethod());
        $this->assertNull($client->getData());
        $this->assertEquals("application/json", $client->getAccept());
        $this->assertEquals(DydraClient::HOST . "/" . $client->getAccountId() . "/repositories?auth_token=" . static::TEST_AUTH_TOKEN, $client->getURL());

        /** Check with query parameters as array */
        $client->reset()
        ->setPath("/" . $client->getAccountId() . "/repositories")
        ->setMethod("GET")
        ->setAccept("application/json")
        ->setData(array(
            'key1' => 'value1',
            'key2' => 'encoded value%',
        ));

        $this->assertEquals("GET", $client->getMethod());
        $this->assertTrue(count($client->getData()) > 0);
        $this->assertEquals("application/json", $client->getAccept());
        $this->assertEquals(DydraClient::HOST . "/" . $client->getAccountId() . "/repositories?key1=value1&key2=encoded+value%25&auth_token=" . static::TEST_AUTH_TOKEN, $client->getURL());

        /** Check with query parameters as string */
        $client->reset()
        ->setPath("/" . $client->getAccountId() . "/repositories")
        ->setMethod("GET")
        ->setAccept("application/json")
        ->setData("key1=value1&key2=" . urlencode("encoded value%"));

        $this->assertEquals("GET", $client->getMethod());
        $this->assertTrue(count($client->getData()) > 0);
        $this->assertEquals("application/json", $client->getAccept());
        $this->assertEquals(DydraClient::HOST . "/" . $client->getAccountId() . "/repositories?key1=value1&key2=encoded+value%25&auth_token=" . static::TEST_AUTH_TOKEN, $client->getURL());
    }

    function test_getURL_post_request() {
        /** Check without post data */
        $client = new DydraClientExposed(static::TEST_ACCOUNT_ID, static::TEST_AUTH_TOKEN);
        $client->setPath("/" . $client->getAccountId() . "/repositories")
        ->setMethod("POST")
        ->setAccept("application/json");

        $this->assertEquals("POST", $client->getMethod());
        $this->assertNull($client->getData());
        $this->assertEquals("application/json", $client->getAccept());
        $this->assertEquals(DydraClient::HOST . "/" . $client->getAccountId() . "/repositories?auth_token=" . static::TEST_AUTH_TOKEN, $client->getURL());

        /** Check with post data */
        $client->reset()
        ->setPath("/" . $client->getAccountId() . "/repositories")
        ->setMethod("POST")
        ->setAccept("application/json")
        ->setData(array(
            'name' => 'test',
        ));

        $this->assertEquals("POST", $client->getMethod());
        $this->assertTrue(count($client->getData()) > 0);
        $this->assertEquals("application/json", $client->getAccept());
        $this->assertEquals(DydraClient::HOST . "/" . $client->getAccountId() . "/repositories?auth_token=" . static::TEST_AUTH_TOKEN, $client->getURL());
    }

    function test_prepareHTTPHeaders() {
        $client = new DydraClientExposed(static::TEST_ACCOUNT_ID, static::TEST_AUTH_TOKEN);

        /** Test accept header */
        $this->assertContains("Accept: " . DydraClient::RDFXML, $client->prepareHTTPHeaders());
        $client->setHeader("Accept", "application/json");
        $this->assertContains("Accept: " . DydraClient::RDFXML, $client->prepareHTTPHeaders());
        $client->setAccept("application/json");
        $this->assertContains("Accept: application/json", $client->prepareHTTPHeaders());

        /** Test other headers */
        $client->reset()
        ->setHeader("Content-Type", "text/plain");
        $this->assertContains("Content-Type: text/plain", $client->prepareHTTPHeaders());
        $this->assertEquals(2, count($client->prepareHTTPHeaders()));
    }

    function test_setMethod() {
        $client = new DydraClientExposed(static::TEST_ACCOUNT_ID, static::TEST_AUTH_TOKEN);

        $client->setMethod("GET");
        $this->assertEquals("GET", $client->getMethod());
        $client->setMethod("POST");
        $this->assertEquals("POST", $client->getMethod());
        $client->setMethod("PUT");
        $this->assertEquals("PUT", $client->getMethod());
        $client->setMethod("DELETE");
        $this->assertEquals("DELETE", $client->getMethod());
    }

    /**
     *  @expectedException DydraException
     */
    function test_setMethod_exception() {
        $client = new DydraClientExposed(static::TEST_ACCOUNT_ID, static::TEST_AUTH_TOKEN);

        $client->setMethod("INVALID");
    }

    function test_setAccept() {
        $client = new DydraClientExposed(static::TEST_ACCOUNT_ID, static::TEST_AUTH_TOKEN);

        $this->assertContains("Accept: " . DydraClient::RDFXML, $client->prepareHTTPHeaders());
        $client->setAccept(DydraClient::NTRIPLES);
        $this->assertContains("Accept: " . DydraClient::NTRIPLES, $client->prepareHTTPHeaders());
        $client->setAccept(DydraClient::TURTLE);
        $this->assertContains("Accept: " . DydraClient::TURTLE, $client->prepareHTTPHeaders());
        $client->setAccept(DydraClient::N3);
        $this->assertContains("Accept: " . DydraClient::N3, $client->prepareHTTPHeaders());
        $client->setAccept(DydraClient::JSON);
        $this->assertContains("Accept: " . DydraClient::JSON, $client->prepareHTTPHeaders());
        $client->setAccept(DydraClient::RDFXML);
        $this->assertContains("Accept: " . DydraClient::RDFXML, $client->prepareHTTPHeaders());
    }

    /**
     *  @expectedException DydraException
     */
    function test_setAccept_exception() {
        $client = new DydraClientExposed(static::TEST_ACCOUNT_ID, static::TEST_AUTH_TOKEN);

        $client->setAccept("plain/invalid-type");
    }
}
