<?php

namespace app\models\dto\mapper;

use app\models\Author;
use app\models\dto\AuthorDto;

class AuthorDtoMapper {
    public static function mapFromAR(Author $author) {
        return new AuthorDto(
            $author->id,
            $author->name,
            $author->email,
            $author->msg,
            new \DateTimeImmutable($author->created_at),
            new \DateTimeImmutable($author->updated_at)
        );
    }

    /**
     * @param Author[] $authorRecords
     *
     * @return AuthorDto[]
     */
    public static function mapFromAuthorAR(array $ars) {
        $dtos = [];

        foreach ($ars as $ar) {
            if (!($ar instanceof Author)) {
                continue;
            }
            $dtos[] = self::mapFromAR($ar);
        }

        return $dtos;
    }
}