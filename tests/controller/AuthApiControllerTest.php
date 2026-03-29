<?php
namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthApiControllerTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        // Vide le cache entre les tests
        static::getContainer()->get('cache.app')->clear();
    }

    // Test 1 : register avec données valides → 201
    public function testRegisterSuccess(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email'    => 'test_' . uniqid() . '@example.com',
                'username' => 'testuser',
                'password' => 'password123'
            ])
        );

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertArrayHasKey('token', $data);
    }

    // Test 2 : register sans email → 400
    public function testRegisterMissingEmail(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['username' => 'testuser', 'password' => 'password123'])
        );

        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
    }

    // Test 3 : /me sans token → 401
    public function testMeWithoutToken(): void
    {
        $this->client->request('GET', '/api/auth/me');
        $this->assertSame(401, $this->client->getResponse()->getStatusCode());
    }

    // Test 4 : /me avec token valide → 200
    public function testMeWithValidToken(): void
{
    $uniqueEmail = 'metest_' . uniqid() . '@example.com';
    $user = new User();
    $user->setEmail($uniqueEmail);
    $user->setUsername('metest');
    $user->setRoles(['ROLE_USER']);
    $user->setPassword('hashed');

    $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
    $entityManager->persist($user);
    $entityManager->flush();

    $token = static::getContainer()
        ->get('lexik_jwt_authentication.jwt_manager')
        ->create($user);

    $this->client->request('GET', '/api/auth/me', [], [], [
        'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
    ]);

    $this->assertResponseIsSuccessful();
}
  // Test 5 : login mauvais mot de passe → 401
    public function testLoginWrongPassword(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'nobody@example.com', 'password' => 'wrong'])
        );

        $this->assertSame(401, $this->client->getResponse()->getStatusCode());
    }

    // Test 6 : page accueil accessible
    public function testHomeIsAccessible(): void
    {
        $this->client->request('GET', '/event');
        $statusCode = $this->client->getResponse()->getStatusCode();
        $this->assertContains($statusCode, [200, 302]);
    }
}