<?php

declare(strict_types=1);

namespace MauticPlugin\MauticUliBundle\Tests\Command;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use MauticPlugin\MauticUliBundle\Command\GenerateUniqueLoginCommand;
use MauticPlugin\MauticUliBundle\Entity\UniqueLogin;
use PHPUnit\Framework\Assert;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class GenerateUniqueLoginCommandTest extends MauticMysqlTestCase
{
    private CommandTester $commandTester;
    private User $testUser;

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
        $this->testUser->setPassword('password'); // This will be hashed automatically
        $this->em->persist($this->testUser);
        $this->em->flush();

        // Initialize command tester
        $command = static::getContainer()->get(GenerateUniqueLoginCommand::class);
        $this->commandTester = new CommandTester($command);
    }

    public function testCommandGeneratesUniqueLoginSuccessfully(): void
    {
        // Execute command
        $result = $this->commandTester->execute([
            'user_id' => $this->testUser->getId()
        ]);

        // Assert command succeeded
        Assert::assertEquals(Command::SUCCESS, $result);

        // Assert output contains expected messages
        $output = $this->commandTester->getDisplay();
        Assert::assertStringContainsString('Unique login link generated successfully!', $output);
        Assert::assertStringContainsString('User:', $output);
        Assert::assertStringContainsString('testuser', $output);
        Assert::assertStringContainsString('Expires:', $output);
        Assert::assertStringContainsString('URL:', $output);
        Assert::assertStringContainsString('/s/unique_login?hash=', $output);

        // Verify ULI record was created in database
        $uliRepository = $this->em->getRepository(UniqueLogin::class);
        $uliRecords = $uliRepository->findBy(['userId' => $this->testUser->getId()]);

        Assert::assertCount(1, $uliRecords);

        $uliRecord = $uliRecords[0];
        Assert::assertEquals($this->testUser->getId(), $uliRecord->getUserId());
        Assert::assertNotEmpty($uliRecord->getHash());
        Assert::assertEquals(64, strlen($uliRecord->getHash())); // 32 bytes = 64 hex chars
        Assert::assertInstanceOf(\DateTime::class, $uliRecord->getTtl());
        Assert::assertInstanceOf(\DateTime::class, $uliRecord->getDateCreated());

        // Verify TTL is approximately 24 hours from now
        $expectedTtl = (new \DateTime())->add(new \DateInterval('P1D'));
        $actualTtl = $uliRecord->getTtl();
        $diff = abs($expectedTtl->getTimestamp() - $actualTtl->getTimestamp());
        Assert::assertLessThan(60, $diff); // Allow 1 minute difference
    }

    public function testCommandFailsForNonExistentUser(): void
    {
        $result = $this->commandTester->execute([
            'user_id' => 99999 // Non-existent user ID
        ]);

        Assert::assertEquals(Command::FAILURE, $result);

        $output = $this->commandTester->getDisplay();
        Assert::assertStringContainsString('User with ID 99999 not found', $output);
    }

    public function testCommandFailsForInvalidUserId(): void
    {
        $result = $this->commandTester->execute([
            'user_id' => 0
        ]);

        Assert::assertEquals(Command::FAILURE, $result);

        $output = $this->commandTester->getDisplay();
        Assert::assertStringContainsString('User ID must be a positive integer', $output);
    }

    public function testCommandGeneratesUniqueHashesForMultipleInvocations(): void
    {
        // Execute command twice
        $this->commandTester->execute(['user_id' => $this->testUser->getId()]);
        $this->commandTester->execute(['user_id' => $this->testUser->getId()]);

        // Verify two different ULI records were created
        $uliRepository = $this->em->getRepository(UniqueLogin::class);
        $uliRecords = $uliRepository->findBy(['userId' => $this->testUser->getId()]);

        Assert::assertCount(2, $uliRecords);
        Assert::assertNotEquals($uliRecords[0]->getHash(), $uliRecords[1]->getHash());
    }
}