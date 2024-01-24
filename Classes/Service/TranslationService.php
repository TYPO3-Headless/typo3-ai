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
use TYPO3Headless\Typo3Ai\Credentials\DefaultCredentials;
use TYPO3Headless\Typo3Ai\Factory\AdapterFactory;
use TYPO3Headless\Typo3Ai\Factory\TypeFactory;
use TYPO3Headless\Typo3Ai\ModelType\ChatGptModelType;
use TYPO3Headless\Typo3Ai\ModelType\ModelTypeInterface;
use TYPO3Headless\Typo3Ai\TranslationAdapter\OpenAiPhpTranslationAdapter;
use TYPO3Headless\Typo3Ai\TranslationAdapter\TranslationAdapterInterface;

class TranslationService
{
    protected ?TranslationAdapterInterface $adapter = null;

    protected array $adapterCache = [];

    public function __construct(
        protected ConnectionPool $connectionPool,
        protected ExtensionConfiguration $extensionConfiguration,
        protected AdapterFactory $adapterFactory,
        protected TypeFactory $typeFactory
    ) {}

    public function getLanguageIdForRecordFromDatabase(string $tableName, int $uid, string $languageColumn = ''): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()->removeAll();

        if ($languageColumn === '') {
            return 0;
        }

        $record = $queryBuilder
            ->select($languageColumn)
            ->from($tableName)
            ->where($queryBuilder->expr()->eq('uid', $uid))
            ->executeQuery()
            ->fetchAssociative();

        if (!is_array($record)) {
            return 0;
        }

        return $record[$languageColumn];
    }

    public function translateTextToLanguage(
        string $textToTranslate,
        string $languageToTranslate,
        string $context = '',
        string $type = ChatGptModelType::CONFIG_KEY,
        string $adapterType = OpenAiPhpTranslationAdapter::TYPE
    ): ?string {
        $modelType = $this->typeFactory->get($type);

        if (!isset($this->adapterCache[$adapterType])) {
            $adapter = $this->getTranslationAdapter($modelType, $adapterType);
            $this->adapterCache[$adapterType] = $adapter;
        }

        if (isset($this->adapterCache[$adapterType])) {
            return $this->adapterCache[$adapterType]->translate(
                $textToTranslate,
                $languageToTranslate,
                $context
            );
        }

        return null;
    }

    public function translateArrayToLanguage(
        array &$element,
        string $translateTo,
        bool $removeInvalidValues = false
    ): void {
        if ($translateTo === '' || $element === []) {
            $element = [];
            return;
        }

        foreach ($element as $columnName => $value) {
            if (is_string($value) && !is_numeric($value) && $value !== '') {
                $translation = $this->translateTextToLanguage($value, $translateTo);

                if ($translation !== null) {
                    $element[$columnName] = trim($translation);
                    continue;
                }
            }

            if ($removeInvalidValues === true) {
                unset($element[$columnName]);
            }
        }
    }

    protected function getTranslationAdapter(
        ModelTypeInterface $modelType,
        string $adapterType = OpenAiPhpTranslationAdapter::TYPE
    ): ?TranslationAdapterInterface {
        $credentials = null;
        $apiConfiguration = $this->getConfiguration();

        if (isset(
            $apiConfiguration[$modelType->getConfigKey()]['secret'],
            $apiConfiguration[$modelType->getConfigKey()]['id']
        )) {
            $credentials = GeneralUtility::makeInstance(
                DefaultCredentials::class,
                $apiConfiguration[$modelType->getConfigKey()]['id'],
                $apiConfiguration[$modelType->getConfigKey()]['secret'],
                $apiConfiguration[$modelType->getConfigKey()]
            );
        }

        if ($credentials !== null) {
            return $this->adapterFactory->get(
                $adapterType,
                $credentials,
                $modelType
            );
        }

        return null;
    }

    protected function getConfiguration()
    {
        return $this->extensionConfiguration->get('typo3_ai', 'api');
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
