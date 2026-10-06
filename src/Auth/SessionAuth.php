<?php

declare(strict_types=1);

namespace App\Auth;

use App\Entity\User;
use App\Repository\UserRepository;

class SessionAuth implements AuthInterface
{
    public const SESSION_KEY = 'auth_user_id';

    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

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
    }

    public function logout(): void
    {
        $this->ensureSession();
        unset($_SESSION[self::SESSION_KEY]);
        if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
            session_regenerate_id(true);
        }
    }

    public function check(): bool
    {
        return $this->id() !== null && $this->user() instanceof User;
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
            return null;
        }

        return $this->userRepository->findById($id);
    }

    private function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
    }
}
