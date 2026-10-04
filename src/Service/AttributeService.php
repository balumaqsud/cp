<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Attribute;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

final class AttributeService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Attribute $attribute): void
    {
        $attribute->setName(trim($attribute->getName()));
        if ($attribute->getId() === null) {
            $this->entityManager->persist($attribute);
        }

        $this->entityManager->flush();
    }

    /**
     * @param list<Attribute> $attributes
     * @return array{deleted: int, skippedBuiltIn: bool}
     */
    public function deleteSelected(array $attributes): array
    {
        $deleted = 0;
        $skippedBuiltIn = false;

        foreach ($attributes as $attribute) {
            if (!$attribute->isDeletable()) {
                $skippedBuiltIn = true;
                continue;
            }

            $this->entityManager->remove($attribute);
            ++$deleted;
        }

        try {
            $this->entityManager->flush();
        } catch (ForeignKeyConstraintViolationException $exception) {
            $this->entityManager->clear();
            throw $exception;
        }

        return ['deleted' => $deleted, 'skippedBuiltIn' => $skippedBuiltIn];
    }
}
