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

        $authenticatedUser = $this->createUserAndLogIn($client,'cheeseplease@example.com', '12345678');
        $otherUser = $this->createUser('otheruser@example.com', '12345678');

        $cheesyData = [
            'title' => 'Mystery cheese... kinda green',
            'description' => 'What mysteries does it hold?',
            'price' => 5000
        ];

        $client->request('POST', '/api/cheeses', [
            'json' => []
        ]);
        $this->assertResponseStatusCodeSame(400);

        $client->request('POST', '/api/cheeses', [
            'json' => $cheesyData
        ]);
        $this->assertResponseStatusCodeSame(201);

        $client->request('POST', '/api/cheeses', [
            'json' => $cheesyData + ['owner' => '/api/users/' . $otherUser->getId()]
        ]);
        $this->assertResponseStatusCodeSame(400, 'not passing the correct owner');

        $client->request('POST', '/api/cheeses', [
            'json' => $cheesyData + ['owner' => '/api/users/' . $authenticatedUser->getId()]
        ]);
        $this->assertResponseStatusCodeSame(201);
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
        $cheeseListing->setIsPublished(true);

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

    public function testGetCheeseListingCollection()
    {
        $client = self::createClient();
        $user = $this->createUser('cheeseplease@example.com', '12345678');

        $cheeseListing1 = new CheeseListing('cheese1');
        $cheeseListing1->setOwner($user);
        $cheeseListing1->setPrice(1000);
        $cheeseListing1->setTextDescription('cheese');

        $cheeseListing2 = new CheeseListing('cheese2');
        $cheeseListing2->setOwner($user);
        $cheeseListing2->setPrice(1000);
        $cheeseListing2->setTextDescription('cheese');
        $cheeseListing2->setIsPublished(true);

        $cheeseListing3 = new CheeseListing('cheese3');
        $cheeseListing3->setOwner($user);
        $cheeseListing3->setPrice(1000);
        $cheeseListing3->setTextDescription('cheese');
        $cheeseListing3->setIsPublished(true);

        $em = $this->getEntityManager();
        $em->persist($cheeseListing1);
        $em->persist($cheeseListing2);
        $em->persist($cheeseListing3);
        $em->flush();

        $client->request('GET', '/api/cheeses');
        $this->assertJsonContains(['hydra:totalItems' => 2]);
    }

    public function testGetCheeseListingItem()
    {
        $client = self::createClient();
        $user = $this->createUserAndLogIn($client,'cheeseplease@example.com', '12345678');

        $cheeseListing1 = new CheeseListing('cheese1');
        $cheeseListing1->setOwner($user);
        $cheeseListing1->setPrice(1000);
        $cheeseListing1->setTextDescription('cheese');
        $cheeseListing1->setIsPublished(false);

        $em = $this->getEntityManager();
        $em->persist($cheeseListing1);
        $em->flush();

        $client->request('GET', '/api/cheeses/' . $cheeseListing1->getId());
        $this->assertResponseStatusCodeSame(404);

        $client->request('GET', '/api/users/' . $user->getId());
        $data = $client->getResponse()->toArray();
        $this->assertEmpty($data['cheeseListings']);
    }
}