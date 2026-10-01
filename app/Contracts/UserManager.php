<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserManager
{
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function create(array $data): User;

    public function update(User $user, array $data): User;

    public function delete(User $user): void;

    public function changeStatus(User $user, UserStatus $status): User;

    public function resetPassword(User $user, string $newPassword, bool $mustChange = true): void;
}
