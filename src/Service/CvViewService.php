<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Attribute;
use App\Entity\CurriculumVitae;
use App\Entity\User;
use App\Enum\CvStatus;
use App\Repository\AttributeRepository;
use App\Repository\AttributeValueRepository;
use App\Repository\CvLikeRepository;
use App\Repository\PositionRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CvViewService
{
    public function __construct(
        private readonly AttributeRepository $attributes,
        private readonly AttributeValueRepository $attributeValues,
        private readonly PositionRepository $positions,
        private readonly CvLikeRepository $likes,
        private readonly CvService $cvService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(CurriculumVitae $cv, User $viewer, bool $canEdit, bool $canPublish, bool $canLike): array
    {
        $owner = $cv->getUser() ?? throw new NotFoundHttpException();
        $position = $cv->getPosition() ?? throw new NotFoundHttpException();
        $this->positions->findOneWithTemplate((int) $position->getId());

        $builtIns = $this->attributes->findBuiltIns();
        $templateAttributes = [];
        $requiredIds = [];
        foreach ($position->getPositionAttributes() as $positionAttribute) {
            $attribute = $positionAttribute->getAttribute();
            if ($attribute === null) {
                continue;
            }
            if ($positionAttribute->isRequired() && $attribute->getId() !== null) {
                $requiredIds[] = $attribute->getId();
            }
            if (!$attribute->isBuiltIn()) {
                $templateAttributes[] = $attribute;
            }
        }

        $values = $this->attributeValues->findEntitiesIndexedByAttributeId($owner);
        $valueMap = [];
        foreach ($values as $attributeId => $row) {
            $valueMap[$attributeId] = $row->getValue();
        }

        $emptyIds = [];
        foreach ([...$builtIns, ...$templateAttributes] as $attribute) {
            $attributeId = $attribute->getId();
            if ($attributeId !== null && AttributeValueHelper::isEmpty($valueMap[$attributeId] ?? null)) {
                $emptyIds[] = $attributeId;
            }
        }

        return [
            'cv' => $cv,
            'owner' => $owner,
            'position' => $position,
            'builtIns' => $builtIns,
            'templateAttributes' => $templateAttributes,
            'requiredIds' => $requiredIds,
            'values' => $values,
            'emptyIds' => $emptyIds,
            'projects' => $this->cvService->relevantProjects($owner, $position),
            'complete' => $this->cvService->isComplete($cv),
            'canEdit' => $canEdit,
            'canPublish' => $canPublish,
            'canLike' => $canLike,
            'liked' => $this->likes->findOneByRecruiterAndCv($viewer, $cv) !== null,
            'likeCount' => $this->likes->countByCv($cv),
            'published' => $cv->getStatus() === CvStatus::Published->value,
        ];
    }

    /**
     * @return array<int, Attribute>
     */
    public function editableAttributes(CurriculumVitae $cv): array
    {
        $position = $cv->getPosition() ?? throw new NotFoundHttpException();
        $this->positions->findOneWithTemplate((int) $position->getId());

        $allowed = [];
        foreach ($this->attributes->findBuiltIns() as $attribute) {
            if ($attribute->getId() !== null) {
                $allowed[$attribute->getId()] = $attribute;
            }
        }

        foreach ($position->getPositionAttributes() as $positionAttribute) {
            $attribute = $positionAttribute->getAttribute();
            if ($attribute?->getId() !== null) {
                $allowed[$attribute->getId()] = $attribute;
            }
        }

        return $allowed;
    }
}
