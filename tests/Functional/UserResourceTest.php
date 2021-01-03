<?php

namespace App\Tests\Functional;

use App\Test\CustomApiTestCase;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;

class UserResourceTest extends CustomApiTestCase
{
    use ReloadDatabaseTrait;

    public function testCreateUser()
    {
        $client = self::createClient();

        $client->request('POST', '/api/users', [
            'json' => [
                'email' => 'cheeseplease@example.com',
                'username' => 'cheeseplease',
                'password' => '12345678'
            ]
        ]);
        $this->assertResponseStatusCodeSame(201);
        $this->logIn($client, 'cheeseplease@example.com', '12345678');
    }

    public function testUpdateUser()
    {
        $client = self::createClient();
        $user = $this->createUserAndLogIn($client, 'cheeseplease@example.com', '12345678');

        $client->request('PUT', '/api/users/' . $user->getId(), [
            'json' => [
                'username' => 'newUsername'
            ]
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'username' => 'newUsername'
        ]);
    }
}