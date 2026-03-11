<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

final class AccessScope
{
    /**
     * @param  array<int>  $userIds
     */
    private function __construct(
        private readonly array $userIds,
        private readonly ?int $groupId = null
    ) {
    }

    public static function forUser(int $userId): self
    {
        return self::forUsers([$userId]);
    }

    /**
     * @param  array<int>  $userIds
     */
    public static function forUsers(array $userIds): self
    {
        $normalized = self::normalizeUserIds($userIds);

        if ($normalized === []) {
            throw new InvalidArgumentException('Access scope requires at least one valid user ID.');
        }

        return new self($normalized);
    }

    /**
     * @param  array<int>  $memberUserIds
     */
    public static function forGroup(int $groupId, array $memberUserIds): self
    {
        if ($groupId <= 0) {
            throw new InvalidArgumentException('Group ID must be a positive integer.');
        }

        $normalized = self::normalizeUserIds($memberUserIds);

        if ($normalized === []) {
            throw new InvalidArgumentException('Group access scope requires at least one valid member user ID.');
        }

        return new self($normalized, $groupId);
    }

    /**
     * @return array<int>
     */
    public function userIds(): array
    {
        return $this->userIds;
    }

    public function groupId(): ?int
    {
        return $this->groupId;
    }

    public function isGroup(): bool
    {
        return $this->groupId !== null;
    }

    /**
     * @param  array<int>  $userIds
     * @return array<int>
     */
    private static function normalizeUserIds(array $userIds): array
    {
        $normalized = array_values(array_unique(array_map('intval', $userIds)));

        return array_values(array_filter($normalized, static fn (int $id): bool => $id > 0));
    }
}
