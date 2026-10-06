<?php

declare(strict_types=1);

namespace App\Auth;

use App\Entity\User;

interface AuthInterface
{
    /**
     * Attempt to authenticate a user using email and password.
     */
    public function attempt(string $email, string $password): bool;

    /**
     * Log in a user directly.
     */
    public function login(User $user): void;

    /**
     * Log out the currently authenticated user.
     */
    public function logout(): void;

    /**
     * Check if a user is currently authenticated.
     */
    public function check(): bool;

    /**
     * Get the currently authenticated user.
     */
    public function user(): ?User;

    /**
     * Get the ID of the currently authenticated user.
     */
    public function id(): ?int;
}
