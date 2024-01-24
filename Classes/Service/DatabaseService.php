<?php

declare(strict_types=1);

namespace TYPO3Headless\Typo3Ai\Service;

/*
 * This file is part of the Macopedia. package.
 *
 * (c) 2023 Macopedia <extensions@macopedia.pl>, macopedia.com
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DatabaseService
{
    public function __construct(protected ConnectionPool $connectionPool) {}

    public function updateElement(string $table, int $uid, array $element): void
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);

        $queryBuilder
            ->update($table)
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid))
            );

        foreach ($element as $column => $value) {
            $queryBuilder->set($column, $value);
        }

        $queryBuilder->executeStatement();
    }

    public function getElementByUid(array $columns, string $table, int $uid): array
    {
        if ($columns === []) {
            return [];
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $element = $queryBuilder
            ->select(...$columns)
            ->from($table)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid)))
            ->executeQuery()
            ->fetchAssociative();

        if (is_array($element)) {
            return $element;
        }

        return [];
    }
}
