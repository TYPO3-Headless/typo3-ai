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

use TYPO3\CMS\Core\Utility\GeneralUtility;

class TcaService
{
    public function getTranslatableColumns(string $table, array $withColumns = []): array
    {
        $columns = $withColumns;

        if (isset($GLOBALS['TCA'][$table]['columns'])) {
            foreach ($GLOBALS['TCA'][$table]['columns'] as $columnName => $columnConfig) {
                if ($this->canProcess($columnName, $columnConfig)) {
                    $columns[] = $columnName;
                }
            }

            return $columns;
        }

        return [];
    }

    protected function canProcess(string $key, array $columnDefinition): bool
    {
        $isValidType = !isset($columnDefinition['config']['renderType'])
            && !isset($columnDefinition['config']['valuePicker'])
            && in_array($columnDefinition['config']['type'], ['text', 'input'], true);

        $hasEvalField = isset($columnDefinition['config']['eval']);

        if ($isValidType === false) {
            return false;
        }

        if ($hasEvalField) {
            $eval = GeneralUtility::trimExplode(',', $columnDefinition['config']['eval']);

            $isValidEval = array_intersect($this->getInvalidEvalOptions(), $eval) === [];

            if ($isValidEval === false) {
                return false;
            }
        }

        $hasReadOnlyField = isset($columnDefinition['config']['readOnly']);

        if ($hasReadOnlyField && (bool)$columnDefinition['config']['readOnly'] === true) {
            return false;
        }

        return true;
    }

    public function getLanguageFieldForTable(string $tableName): string
    {
        return $GLOBALS['TCA'][$tableName]['ctrl']['languageField'] ?? '';
    }

    protected function getInvalidEvalOptions(): array
    {
        return ['int', 'datetime', 'password', 'datetime', 'saltedPassword', 'email', 'uniqueInSite'];
    }
}
