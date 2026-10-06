<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Traits\TimestampableTrait;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
#[ORM\HasLifecycleCallbacks]
class User
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $password;

    public function __construct(
        #[ORM\Column(type: Types::STRING, length: 255)]
        private string $name,
        #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
        private string $email,
        string $password = '',
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null,
    ) {
        $this->setPassword($password);
        $this->setCreatedAt($createdAt ?? new DateTimeImmutable());
        $this->setUpdatedAt($updatedAt);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        if ($password !== '') {
            $info = password_get_info($password);

            if ($info['algo'] === null) {
                $this->password = password_hash($password, PASSWORD_DEFAULT);
            } else {
                $this->password = $password;
            }
        } else {
            $this->password = '';
        }

        return $this;
    }

    public function verifyPassword(string $password): bool
    {
        if ($this->password === '') {
            return false;
        }

        return password_verify($password, $this->password);
    }
}
