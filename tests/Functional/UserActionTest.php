<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Tests\TestCase;

class UserActionTest extends TestCase
{
    public function testListUsersHtml(): void
    {
        $request = $this->createRequest('GET', '/users');
        $response = $this->handleRequest($request);

        $this->assertSame(200, $response->getStatusCode());
        $body = (string)$response->getBody();
        $this->assertStringContainsString('User Management', $body);
        $this->assertStringContainsString('No users found in database', $body);
    }

    public function testCreateUserViaForm(): void
    {
        $request = $this->createFormRequest('POST', '/users', [
            'name' => 'Alice Doe',
            'email' => 'alice@example.com',
        ]);

        $response = $this->handleRequest($request);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/users', $response->getHeaderLine('Location'));

        /** @var UserRepository $userRepo */
        $userRepo = $this->container->get(UserRepository::class);
        $user = $userRepo->findByEmail('alice@example.com');

        $this->assertNotNull($user);
        $this->assertSame('Alice Doe', $user->getName());
    }

    public function testCreateUserValidationError(): void
    {
        $request = $this->createFormRequest('POST', '/users', [
            'name' => '',
            'email' => 'invalid-email',
        ]);

        $response = $this->handleRequest($request);

        $this->assertSame(422, $response->getStatusCode());
        $body = (string)$response->getBody();
        $this->assertStringContainsString('Name is required.', $body);
        $this->assertStringContainsString('A valid email is required.', $body);
    }

    public function testCreateUserDuplicateEmailError(): void
    {
        /** @var UserRepository $userRepo */
        $userRepo = $this->container->get(UserRepository::class);
        $userRepo->save(new User('Bob', 'bob@example.com'));

        $request = $this->createFormRequest('POST', '/users', [
            'name' => 'Bob Clone',
            'email' => 'bob@example.com',
        ]);

        $response = $this->handleRequest($request);

        $this->assertSame(422, $response->getStatusCode());
        $body = (string)$response->getBody();
        $this->assertStringContainsString('Email already exists.', $body);
    }

    public function testApiUserListAndCreate(): void
    {
        // 1. Create user via JSON API
        $createRequest = $this->createJsonRequest('POST', '/api/users', [
            'name' => 'Charlie Api',
            'email' => 'charlie@example.com',
        ]);

        $createResponse = $this->handleRequest($createRequest);
        $this->assertSame(201, $createResponse->getStatusCode());
        $this->assertStringContainsString('application/json', $createResponse->getHeaderLine('Content-Type'));

        $createdData = json_decode((string)$createResponse->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('Charlie Api', $createdData['name']);
        $this->assertSame('charlie@example.com', $createdData['email']);
        $this->assertNotNull($createdData['id']);

        // 2. Fetch users via JSON API
        $listRequest = $this->createRequest('GET', '/api/users', ['Accept' => 'application/json']);
        $listResponse = $this->handleRequest($listRequest);

        $this->assertSame(200, $listResponse->getStatusCode());
        $usersList = json_decode((string)$listResponse->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(1, $usersList);
        $this->assertSame('Charlie Api', $usersList[0]['name']);
    }
}
