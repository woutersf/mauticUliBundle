<?php

declare(strict_types=1);

namespace MauticPlugin\MauticUliBundle\Tests\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use MauticPlugin\MauticUliBundle\Entity\UniqueLogin;
use MauticPlugin\MauticUliBundle\Entity\UniqueLoginRepository;
use PHPUnit\Framework\Assert;

final class UniqueLoginRepositoryTest extends MauticMysqlTestCase
{
    private UniqueLoginRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->em->getRepository(UniqueLogin::class);
    }

    public function testFindByHashReturnsCorrectEntity(): void
    {
        $hash = 'test_hash_' . bin2hex(random_bytes(16));

        $uli = new UniqueLogin();
        $uli->setHash($hash);
        $uli->setUserId(1);
        $uli->setTtl(new \DateTime('+1 day'));
        $uli->setDateCreated(new \DateTime());

        $this->em->persist($uli);
        $this->em->flush();

        $found = $this->repository->findByHash($hash);

        Assert::assertNotNull($found);
        Assert::assertEquals($hash, $found->getHash());
        Assert::assertEquals(1, $found->getUserId());
    }

    public function testFindByHashReturnsNullForNonExistentHash(): void
    {
        $found = $this->repository->findByHash('non_existent_hash');
        Assert::assertNull($found);
    }

    public function testFindValidByHashReturnsValidEntity(): void
    {
        $validHash = 'valid_hash_' . bin2hex(random_bytes(16));

        $validUli = new UniqueLogin();
        $validUli->setHash($validHash);
        $validUli->setUserId(1);
        $validUli->setTtl((new \DateTime())->add(new \DateInterval('P1D'))); // Valid for 1 day
        $validUli->setDateCreated(new \DateTime());

        $this->em->persist($validUli);
        $this->em->flush();

        $found = $this->repository->findValidByHash($validHash);

        Assert::assertNotNull($found);
        Assert::assertEquals($validHash, $found->getHash());
    }

    public function testFindValidByHashReturnsNullForExpiredEntity(): void
    {
        $expiredHash = 'expired_hash_' . bin2hex(random_bytes(16));

        $expiredUli = new UniqueLogin();
        $expiredUli->setHash($expiredHash);
        $expiredUli->setUserId(1);
        $expiredUli->setTtl((new \DateTime())->sub(new \DateInterval('P1D'))); // Expired 1 day ago
        $expiredUli->setDateCreated(new \DateTime());

        $this->em->persist($expiredUli);
        $this->em->flush();

        $found = $this->repository->findValidByHash($expiredHash);
        Assert::assertNull($found);
    }

    public function testDeleteExpiredTokensRemovesExpiredEntities(): void
    {
        // Create valid ULI
        $validUli = new UniqueLogin();
        $validUli->setHash('valid_' . bin2hex(random_bytes(16)));
        $validUli->setUserId(1);
        $validUli->setTtl((new \DateTime())->add(new \DateInterval('P1D')));
        $validUli->setDateCreated(new \DateTime());
        $this->em->persist($validUli);

        // Create expired ULI
        $expiredUli = new UniqueLogin();
        $expiredUli->setHash('expired_' . bin2hex(random_bytes(16)));
        $expiredUli->setUserId(2);
        $expiredUli->setTtl((new \DateTime())->sub(new \DateInterval('P1D')));
        $expiredUli->setDateCreated(new \DateTime());
        $this->em->persist($expiredUli);

        $this->em->flush();

        // Verify both entities exist
        Assert::assertCount(2, $this->repository->findAll());

        // Delete expired tokens
        $deletedCount = $this->repository->deleteExpiredTokens();

        Assert::assertEquals(1, $deletedCount);

        // Verify only valid entity remains
        $remaining = $this->repository->findAll();
        Assert::assertCount(1, $remaining);
        Assert::assertEquals($validUli->getHash(), $remaining[0]->getHash());
    }

    public function testDeleteByHashRemovesSpecificEntity(): void
    {
        $hash1 = 'hash1_' . bin2hex(random_bytes(16));
        $hash2 = 'hash2_' . bin2hex(random_bytes(16));

        // Create two ULI entities
        $uli1 = new UniqueLogin();
        $uli1->setHash($hash1);
        $uli1->setUserId(1);
        $uli1->setTtl(new \DateTime('+1 day'));
        $uli1->setDateCreated(new \DateTime());
        $this->em->persist($uli1);

        $uli2 = new UniqueLogin();
        $uli2->setHash($hash2);
        $uli2->setUserId(2);
        $uli2->setTtl(new \DateTime('+1 day'));
        $uli2->setDateCreated(new \DateTime());
        $this->em->persist($uli2);

        $this->em->flush();

        // Delete first entity by hash
        $deletedCount = $this->repository->deleteByHash($hash1);

        Assert::assertEquals(1, $deletedCount);

        // Verify only second entity remains
        $remaining = $this->repository->findAll();
        Assert::assertCount(1, $remaining);
        Assert::assertEquals($hash2, $remaining[0]->getHash());
    }

    public function testDeleteByHashReturnsZeroForNonExistentHash(): void
    {
        $deletedCount = $this->repository->deleteByHash('non_existent_hash');
        Assert::assertEquals(0, $deletedCount);
    }
}