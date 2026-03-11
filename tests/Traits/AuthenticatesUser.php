<?php

declare(strict_types=1);

namespace Tests\Traits;

use App\Models\User;

trait AuthenticatesUser
{
    protected User $user;

    protected function setUpAuthenticatesUser(): void
    {
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }
}
