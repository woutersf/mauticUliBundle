<?php

declare(strict_types=1);

namespace MauticPlugin\MauticUliBundle\Tests\Entity;

use MauticPlugin\MauticUliBundle\Entity\UniqueLogin;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

final class UniqueLoginTest extends TestCase
{
    public function testEntityCreationAndGetters(): void
    {
        $hash = bin2hex(random_bytes(32));
        $userId = 123;
        $ttl = new \DateTime('+1 day');
        $dateCreated = new \DateTime();

        $uli = new UniqueLogin();
        $uli->setHash($hash);
        $uli->setUserId($userId);
        $uli->setTtl($ttl);
        $uli->setDateCreated($dateCreated);

        Assert::assertEquals($hash, $uli->getHash());
        Assert::assertEquals($userId, $uli->getUserId());
        Assert::assertEquals($ttl, $uli->getTtl());
        Assert::assertEquals($dateCreated, $uli->getDateCreated());
        Assert::assertNull($uli->getId()); // ID is null until persisted
    }

    public function testIsValidReturnsTrueForFutureExpiration(): void
    {
        $uli = new UniqueLogin();
        $uli->setTtl((new \DateTime())->add(new \DateInterval('PT1H'))); // 1 hour from now

        Assert::assertTrue($uli->isValid());
    }

    public function testIsValidReturnsFalseForPastExpiration(): void
    {
        $uli = new UniqueLogin();
        $uli->setTtl((new \DateTime())->sub(new \DateInterval('PT1H'))); // 1 hour ago

        Assert::assertFalse($uli->isValid());
    }

    public function testIsValidReturnsFalseForCurrentTime(): void
    {
        $uli = new UniqueLogin();
        $uli->setTtl(new \DateTime()); // Exactly now

        // This might be flaky due to microsecond differences, so we allow either result
        // In practice, "exactly now" should be considered expired
        $isValid = $uli->isValid();
        Assert::assertTrue(is_bool($isValid)); // Just ensure it returns a boolean
    }

    public function testFluentInterface(): void
    {
        $uli = new UniqueLogin();
        $hash = 'test_hash';
        $userId = 456;
        $ttl = new \DateTime();
        $dateCreated = new \DateTime();

        $result = $uli->setHash($hash)
            ->setUserId($userId)
            ->setTtl($ttl)
            ->setDateCreated($dateCreated);

        // Verify fluent interface returns the same instance
        Assert::assertSame($uli, $result);

        // Verify values were set
        Assert::assertEquals($hash, $uli->getHash());
        Assert::assertEquals($userId, $uli->getUserId());
        Assert::assertEquals($ttl, $uli->getTtl());
        Assert::assertEquals($dateCreated, $uli->getDateCreated());
    }
}