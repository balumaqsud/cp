<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\SalesforceCrmDTO;
use App\Entity\User;
use App\Repository\AttributeRepository;
use App\Repository\AttributeValueRepository;

final class SalesforceCrmMapper
{
    public function __construct(
        private readonly AttributeRepository $attributes,
        private readonly AttributeValueRepository $attributeValues,
    ) {
    }

    /**
     * @return array{account: array<string, string>, contact: array<string, string>}
     */
    public function build(User $user, SalesforceCrmDTO $crm): array
    {
        $values = $this->attributeValues->findIndexedByAttributeId($user);
        $byName = [];
        foreach ($this->attributes->findBuiltIns() as $attribute) {
            $id = $attribute->getId();
            if ($id === null) {
                continue;
            }

            $byName[$attribute->getName()] = $values[$id] ?? null;
        }

        $location = $this->text($byName['Location'] ?? null);
        $firstName = $this->text($byName['First Name'] ?? null);
        $lastName = $this->text($byName['Last Name'] ?? null);
        if ($lastName === '') {
            $lastName = trim($user->getName());
        }

        $account = [
            'Name' => trim($crm->company),
        ];
        if ($location !== '') {
            $account['BillingCity'] = $location;
        }

        $description = $this->accountDescription($crm);
        if ($description !== '') {
            $account['Description'] = $description;
        }

        $contact = [
            'LastName' => $lastName,
            'Email' => $user->getEmail(),
        ];
        if ($firstName !== '') {
            $contact['FirstName'] = $firstName;
        }

        $phone = trim($crm->phone);
        if ($phone !== '') {
            $contact['Phone'] = $phone;
        }
        if ($location !== '') {
            $contact['MailingCity'] = $location;
        }

        $title = trim($crm->title);
        $role = $this->roleLabel($user);
        if ($title !== '') {
            $contact['Title'] = $title;
            $contact['Description'] = $role;
        } else {
            $contact['Title'] = $role;
        }

        return [
            'account' => $account,
            'contact' => $contact,
        ];
    }

    private function accountDescription(SalesforceCrmDTO $crm): string
    {
        $notes = trim($crm->notes);
        if (!$crm->newsletter) {
            return $notes;
        }

        if ($notes === '') {
            return 'Newsletter: yes';
        }

        return $notes."\nNewsletter: yes";
    }

    private function roleLabel(User $user): string
    {
        if ($user->isAdmin()) {
            return 'Admin';
        }

        if ($user->isRecruiter()) {
            return 'Recruiter';
        }

        return 'Candidate';
    }

    private function text(mixed $value): string
    {
        if (AttributeValueHelper::isEmpty($value) || !is_scalar($value)) {
            return '';
        }

        return trim((string) $value);
    }
}
