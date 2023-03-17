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

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class TranslationService
{
    public const NO_TRANSLATION = 'NO_TRANSLATION';

    protected string $apiKey = '';

    protected string $apiId = '';

    protected string $mainTranslator = 'chatgpt';

    protected $client;

    public function __construct(protected ConnectionPool $connectionPool)
    {
        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
        $apiConfiguration = $extensionConfiguration->get('typo3_ai', 'api');

        if (isset($apiConfiguration[$this->mainTranslator]['secret'])) {
            $this->apiKey = $apiConfiguration[$this->mainTranslator]['secret'];
        }

        if (isset($apiConfiguration[$this->mainTranslator]['id'])) {
            $this->apiId = $apiConfiguration[$this->mainTranslator]['id'];
        }

        if ($this->apiKey !== '' && $this->apiId !== '') {
            $this->client = \OpenAI::client($this->apiKey, $this->apiId);
        }
    }

    public function translate(string $textToTranslate, string $languageToTranslate, string $context = ''): ?string
    {
        if ($this->client !== null
            && $textToTranslate !== ''
            && $languageToTranslate !== ''
            && !is_numeric($textToTranslate)
        ) {
            if ($context !== '') {
                $context = ' using following context in brackets as reference [' . $context . ']';
            }

            $client = $this->client->chat()->create(
                [
                    'model' => 'gpt-3.5-turbo',
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => 'Translate text (keep html unchanged) after next semi-colon to ' .
                                $languageToTranslate . ' ' . $context . ' or write \'' . self::NO_TRANSLATION . '\'; ' . $textToTranslate,
                        ],
                    ],
                ]
            );

            if (!isset($client->choices[0])) {
                return null;
            }

            $message = $client->choices[0]->message->content;

            if (str_contains($message, self::NO_TRANSLATION)) {
                return null;
            }

            return $message;
        }

        return null;
    }

    public function getLanguageIdForRecordFromDatabase(string $tableName, int $uid): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()->removeAll();

        $languageField = $this->getLanguageFieldForTable($tableName);

        if (!isset($languageField)) {
            return 0;
        }

        $record = $queryBuilder
            ->select($languageField)
            ->from($tableName)
            ->where($queryBuilder->expr()->eq('uid', $uid))
            ->executeQuery()
            ->fetchAssociative();

        if (!is_array($record)) {
            return 0;
        }

        return $record[$languageField];
    }

    public function getLanguageFieldForTable(string $tableName): ?string
    {
        return $GLOBALS['TCA'][$tableName]['ctrl']['languageField'] ?? null;
    }

    public function hasCurrentUserCorrectPermisions(): bool
    {
        return $this->getBackendUser()->isAdmin();
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
