<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\AccessScope;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccessScopeTest extends TestCase
{
    #[Test]
    public function ForUserShouldCreateSingleUserScope(): void
    {
        $scope = AccessScope::forUser(7);

        $this->assertSame([7], $scope->userIds());
        $this->assertNull($scope->groupId());
        $this->assertFalse($scope->isGroup());
    }

    #[Test]
    public function ForUsersShouldNormalizeAndDeduplicateUserIds(): void
    {
        $scope = AccessScope::forUsers([1, 2, 2, 0, -1, 3]);

        $this->assertSame([1, 2, 3], $scope->userIds());
        $this->assertNull($scope->groupId());
        $this->assertFalse($scope->isGroup());
    }

    #[Test]
    public function ForGroupShouldCreateGroupScopeWithMembers(): void
    {
        $scope = AccessScope::forGroup(12, [9, 10, 10]);

        $this->assertSame([9, 10], $scope->userIds());
        $this->assertSame(12, $scope->groupId());
        $this->assertTrue($scope->isGroup());
    }

    #[Test]
    public function ForUsersShouldThrowWhenNoValidUserIdExists(): void
    {
        $this->expectException(InvalidArgumentException::class);

        AccessScope::forUsers([0, -1]);
    }

    #[Test]
    public function ForGroupShouldThrowWhenGroupIdIsInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        AccessScope::forGroup(0, [1, 2]);
    }

    #[Test]
    public function ForGroupShouldThrowWhenNoValidMemberExists(): void
    {
        $this->expectException(InvalidArgumentException::class);

        AccessScope::forGroup(5, [0, -1]);
    }
}
