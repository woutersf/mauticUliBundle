<?php

declare(strict_types=1);

namespace MauticPlugin\MauticUliBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use MauticPlugin\MauticUliBundle\Entity\UniqueLogin;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Response;

final class UniqueLoginControllerTest extends MauticMysqlTestCase
{
    private User $testUser;
    private UniqueLogin $validUli;
    private UniqueLogin $expiredUli;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $role = new Role();
        $role->setName('Test Role');
        $role->setDescription('Test Role Description');
        $this->em->persist($role);

        $this->testUser = new User();
        $this->testUser->setUsername('testuser');
        $this->testUser->setEmail('test@example.com');
        $this->testUser->setFirstName('Test');
        $this->testUser->setLastName('User');
        $this->testUser->setRole($role);
        $this->testUser->setPassword('password');
        $this->em->persist($this->testUser);

        // Create valid ULI
        $this->validUli = new UniqueLogin();
        $this->validUli->setHash('valid_hash_' . bin2hex(random_bytes(16)));
        $this->validUli->setUserId($this->testUser->getId());
        $this->validUli->setTtl((new \DateTime())->add(new \DateInterval('P1D'))); // Valid for 24 hours
        $this->validUli->setDateCreated(new \DateTime());
        $this->em->persist($this->validUli);

        // Create expired ULI
        $this->expiredUli = new UniqueLogin();
        $this->expiredUli->setHash('expired_hash_' . bin2hex(random_bytes(16)));
        $this->expiredUli->setUserId($this->testUser->getId());
        $this->expiredUli->setTtl((new \DateTime())->sub(new \DateInterval('P1D'))); // Expired 24 hours ago
        $this->expiredUli->setDateCreated((new \DateTime())->sub(new \DateInterval('P2D')));
        $this->em->persist($this->expiredUli);

        $this->em->flush();
    }

    public function testValidHashRedirectsToDashboard(): void
    {
        $crawler = $this->client->request(
            'GET',
            '/s/unique_login?hash=' . $this->validUli->getHash()
        );

        // Should redirect to dashboard
        Assert::assertTrue($this->client->getResponse()->isRedirection());
        Assert::assertEquals(302, $this->client->getResponse()->getStatusCode());

        $location = $this->client->getResponse()->headers->get('Location');
        Assert::assertStringContainsString('/s/dashboard', $location);

        // Verify the hash was deleted from database
        $this->em->clear();
        $uliRepository = $this->em->getRepository(UniqueLogin::class);
        $deletedUli = $uliRepository->findByHash($this->validUli->getHash());
        Assert::assertNull($deletedUli);
    }

    public function testExpiredHashRedirectsToLogin(): void
    {
        $this->client->request(
            'GET',
            '/s/unique_login?hash=' . $this->expiredUli->getHash()
        );

        // Should redirect to login page
        Assert::assertTrue($this->client->getResponse()->isRedirection());
        Assert::assertEquals(302, $this->client->getResponse()->getStatusCode());

        $location = $this->client->getResponse()->headers->get('Location');
        Assert::assertStringContainsString('/s/login', $location);

        // Verify the hash still exists in database (wasn't deleted)
        $this->em->clear();
        $uliRepository = $this->em->getRepository(UniqueLogin::class);
        $stillExistsUli = $uliRepository->findByHash($this->expiredUli->getHash());
        Assert::assertNotNull($stillExistsUli);
    }

    public function testInvalidHashRedirectsToLogin(): void
    {
        $this->client->request(
            'GET',
            '/s/unique_login?hash=invalid_hash_12345'
        );

        // Should redirect to login page
        Assert::assertTrue($this->client->getResponse()->isRedirection());
        Assert::assertEquals(302, $this->client->getResponse()->getStatusCode());

        $location = $this->client->getResponse()->headers->get('Location');
        Assert::assertStringContainsString('/s/login', $location);
    }

    public function testMissingHashRedirectsToLogin(): void
    {
        $this->client->request('GET', '/s/unique_login');

        // Should redirect to login page
        Assert::assertTrue($this->client->getResponse()->isRedirection());
        Assert::assertEquals(302, $this->client->getResponse()->getStatusCode());

        $location = $this->client->getResponse()->headers->get('Location');
        Assert::assertStringContainsString('/s/login', $location);
    }

    public function testAlreadyLoggedInUserRedirectsToDashboard(): void
    {
        // First log in the user with a valid ULI
        $this->client->request(
            'GET',
            '/s/unique_login?hash=' . $this->validUli->getHash()
        );

        // Create another ULI for the same user
        $secondUli = new UniqueLogin();
        $secondUli->setHash('second_hash_' . bin2hex(random_bytes(16)));
        $secondUli->setUserId($this->testUser->getId());
        $secondUli->setTtl((new \DateTime())->add(new \DateInterval('P1D')));
        $secondUli->setDateCreated(new \DateTime());
        $this->em->persist($secondUli);
        $this->em->flush();

        // Try to use the second ULI while already logged in
        $this->client->request(
            'GET',
            '/s/unique_login?hash=' . $secondUli->getHash()
        );

        // Should redirect to dashboard
        Assert::assertTrue($this->client->getResponse()->isRedirection());
        $location = $this->client->getResponse()->headers->get('Location');
        Assert::assertStringContainsString('/s/dashboard', $location);
    }
}