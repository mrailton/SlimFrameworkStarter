<?php

declare(strict_types=1);

namespace App\Auth;

use App\Entity\User;
use App\Repository\UserRepository;

class AuthManager
{
    public const string SESSION_KEY = 'auth_user_id';

    private ?User $user = null;
    private ?int $resolvedUserId = null;

    public function __construct(private readonly UserRepository $userRepository) {}

    public function check(): bool
    {
        return $this->user() instanceof User;
    }

    public function attempt(string $email, string $password): bool
    {
        $user = $this->userRepository->findByEmail($email);
        if (!$user instanceof User) {
            return false;
        }

        if (!$user->verifyPassword($password)) {
            return false;
        }

        $this->login($user);
        return true;
    }

    public function login(User $user): void
    {
        $this->ensureSession();

        if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
            session_regenerate_id(true);
        }

        $_SESSION[self::SESSION_KEY] = $user->getId();
        $this->user = $user;
        $this->resolvedUserId = $user->getId();
    }

    public function logout(): void
    {
        $this->ensureSession();
        unset($_SESSION[self::SESSION_KEY]);
        $this->user = null;
        $this->resolvedUserId = null;

        if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
            session_regenerate_id(true);
        }
    }

    public function id(): ?int
    {
        $this->ensureSession();
        $id = $_SESSION[self::SESSION_KEY] ?? null;
        return is_numeric($id) ? (int) $id : null;
    }

    public function user(): ?User
    {
        $id = $this->id();
        if ($id === null) {
            $this->user = null;
            $this->resolvedUserId = null;
            return null;
        }

        if ($this->resolvedUserId === $id) {
            return $this->user;
        }

        $this->resolvedUserId = $id;
        return $this->user = $this->userRepository->findById($id);
    }

    private function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
    }
}
