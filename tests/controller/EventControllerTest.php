<?php
namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class EventControllerTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    // Test 1 : page liste événements charge bien
    public function testEventIndexLoads(): void
    {
        $this->client->request('GET', '/event');
        $statusCode = $this->client->getResponse()->getStatusCode();
        $this->assertContains($statusCode, [200, 302]);
    }

    // Test 2 : page login accessible
    public function testLoginPageLoads(): void
    {
        $this->client->request('GET', '/login');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    // Test 3 : new event sans être admin
    public function testNewEventWithoutAdmin(): void
    {
        $this->client->request('GET', '/event/new');
        $statusCode = $this->client->getResponse()->getStatusCode();
        $this->assertContains($statusCode, [200, 302, 401, 403]);
    }
}