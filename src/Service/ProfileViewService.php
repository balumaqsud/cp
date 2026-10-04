<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Attribute;
use App\Entity\AttributeValue;
use App\Entity\CurriculumVitae;
use App\Entity\User;
use App\Enum\CvStatus;
use App\Repository\AttributeRepository;
use App\Repository\AttributeValueRepository;
use App\Repository\ProjectRepository;

final class ProfileViewService
{
    public function __construct(
        private readonly AttributeRepository $attributes,
        private readonly AttributeValueRepository $attributeValues,
        private readonly ProjectRepository $projects,
        private readonly CvService $cvService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(User $profile, User $viewer, bool $isPublicView, bool $canEdit, string $tab): array
    {
        $values = $this->attributeValues->findEntitiesIndexedByAttributeId($profile);
        $infoAttributes = [];
        foreach ($values as $row) {
            $attribute = $row->getAttribute();
            if ($attribute !== null && !$attribute->isBuiltIn()) {
                $infoAttributes[] = $attribute;
            }
        }

        $selectedInfoIds = array_map(
            static fn (Attribute $attribute): int => (int) $attribute->getId(),
            $infoAttributes,
        );

        $available = [];
        if (!$isPublicView) {
            foreach ($this->attributes->findLibrary() as $attribute) {
                if (!\in_array($attribute->getId(), $selectedInfoIds, true)) {
                    $available[] = $attribute;
                }
            }
        }

        $cvs = $this->cvService->listForProfile($profile, $viewer);
        if ($isPublicView) {
            $cvs = array_values(array_filter(
                $cvs,
                static fn (CurriculumVitae $cv): bool => $cv->getStatus() === CvStatus::Published->value,
            ));
        }

        return [
            'profileUser' => $profile,
            'tab' => $tab,
            'canEdit' => $canEdit,
            'isPublicView' => $isPublicView,
            'builtIns' => $this->attributes->findBuiltIns(),
            'infoAttributes' => $infoAttributes,
            'availableAttributes' => $available,
            'values' => $values,
            'projects' => $this->projects->findByOwner($profile),
            'cvs' => $cvs,
            'tagSuggestions' => $this->projects->findDistinctTags(),
        ];
    }

    /**
     * @return array{builtIns: list<Attribute>, values: array<int, AttributeValue>}
     */
    public function crmFields(User $profile): array
    {
        return [
            'builtIns' => $this->attributes->findBuiltIns(),
            'values' => $this->attributeValues->findEntitiesIndexedByAttributeId($profile),
        ];
    }
}
