<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;

final class RecentAttributeStore
{
    public const SESSION_KEY = 'recent_attribute_ids';
    private const LIMIT = 8;

    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * @return list<int>
     */
    public function ids(): array
    {
        $session = $this->requestStack->getSession();
        $raw = $session->get(self::SESSION_KEY, []);
        if (!\is_array($raw)) {
            return [];
        }

        $ids = [];
        foreach ($raw as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param list<int> $ids
     */
    public function remember(array $ids): void
    {
        $merged = [];
        foreach ([...$ids, ...$this->ids()] as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $merged[] = $id;
            }
        }

        $this->requestStack->getSession()->set(
            self::SESSION_KEY,
            array_slice(array_values(array_unique($merged)), 0, self::LIMIT),
        );
    }
}
