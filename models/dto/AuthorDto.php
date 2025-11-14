<?php

namespace app\models\dto;

class AuthorDto
{
    public function __construct(
        readonly public int $id,
        readonly public string $name,
        readonly public string $email,
        readonly public string $msg,
        readonly public \DateTimeInterface $createdAt,
        readonly public \DateTimeInterface $updated_at,
    ) {
    }
}
