<?php

namespace App\Tests\Functional;

use App\Entity\CheeseListing;
use App\Test\CustomApiTestCase;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;

class CheeseListingResourceTest extends CustomApiTestCase
{
    use ReloadDatabaseTrait;

    public function testCreateCheeseListing()
    {
        $client = self::createClient();

        $this->createUserAndLogIn($client,'cheeseplease@example.com', '12345678');

        $client->request('POST', '/api/cheeses', [
            'json' => []
        ]);
        $this->assertResponseStatusCodeSame(400);
    }

    public function testUpdateCheeseListing()
    {
        $client = self::createClient();

        $user1 = $this->createUser('cheeseplease1@example.com', '12345678');
        $user2 = $this->createUser('cheeseplease2@example.com', '12345678');
        $this->createUser('admin@example.com', '12345678', 'ROLE_ADMIN');

        $cheeseListing = new CheeseListing('Block of cheddar');
        $cheeseListing->setOwner($user1);
        $cheeseListing->setPrice(1000);
        $cheeseListing->setTextDescription('mmmmm');

        $em = $this->getEntityManager();
        $em->persist($cheeseListing);
        $em->flush();

        $this->logIn($client, 'cheeseplease1@example.com', '12345678');
        $client->request('PUT', '/api/cheeses/' . $cheeseListing->getId(), [
            'json' => ['title' => 'updated']
        ]);
        $this->assertResponseStatusCodeSame(200);

        $this->logIn($client, 'cheeseplease2@example.com', '12345678');
        $client->request('PUT', '/api/cheeses/' . $cheeseListing->getId(), [
            'json' => ['title' => 'updated']
        ]);
        $this->assertResponseStatusCodeSame(403, 'only author can updated');
        // var_dump($client->getResponse()->getContent(false));

        $this->logIn($client, 'cheeseplease2@example.com', '12345678');
        $client->request('PUT', '/api/cheeses/' . $cheeseListing->getId(), [
            'json' => [
                'title' => 'updated',
                'owner' => '/api/users/' . $user2->getId()
            ]
        ]);
        $this->assertResponseStatusCodeSame(403, 'only author can updated');

        $this->logIn($client, 'admin@example.com', '12345678');
        $client->request('PUT', '/api/cheeses/' . $cheeseListing->getId(), [
            'json' => [
                'title' => 'updated',
                'owner' => '/api/users/' . $user2->getId()
            ]
        ]);
        $this->assertResponseStatusCodeSame(200);
    }
}