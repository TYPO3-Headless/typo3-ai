<?php

declare(strict_types=1);

namespace TYPO3Headless\Typo3Ai\Controller;

/*
 * This file is part of the Macopedia. package.
 *
 * (c) 2023 Macopedia <extensions@macopedia.pl>, macopedia.com
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3Headless\Typo3Ai\Service\TranslationService;

class TranslationController extends ActionController
{
    public function __construct(
        protected ConnectionPool $connectionPool,
        protected FlashMessageService $flashMessageService,
        protected TranslationService $translationService,
        protected SiteFinder $siteFinder
    ) {
    }

    public function translateAction(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->isValidRequest($request) && $this->translationService->hasCurrentUserCorrectPermisions()) {
            foreach ($request->getQueryParams()['edit'] as $table => $config) {
                $languageField = $this->translationService->getLanguageFieldForTable($table);

                if ($languageField === null) {
                    $this->addWarningMessage($this->getLocallangTranslation('warning.languageField'));
                    continue;
                }

                $columns = ['pid', 'uid', $languageField];
                $uid = key($config);

                foreach ($GLOBALS['TCA'][$table]['columns'] as $columnName => $columnConfig) {
                    if (
                        !isset($columnConfig['config']['renderType'])
                        && !isset($columnConfig['config']['valuePicker'])
                        && in_array($columnConfig['config']['type'], ['text', 'input'], true)
                    ) {
                        $eval = isset($columnConfig['config']['eval']) && $columnConfig['config']['eval'] === 'int';

                        if ($eval === false) {
                            $columns[] = $columnName;
                        }
                    }
                }

                $element = $this->getElementByUid($columns, $table, (int)$uid);

                if ($element === []) {
                    $this->addWarningMessage($this->getLocallangTranslation('warning.missingElement'));
                    return $this->returnToEditElement($request);
                }

                $site = $this->getSiteForElement($element, $table);

                $languageUid = $element[$languageField];

                if ($languageUid === 0) {
                    $this->addWarningMessage($this->getLocallangTranslation('warning.defaultLanguage'));
                    return $this->returnToEditElement($request);
                }

                try {
                    $this->translateElement($element, $this->getIsoCodeForLanguage($site, $languageUid));

                    if ($element === []) {
                        $this->addWarningMessage($this->getLocallangTranslation('warning.emptyAfterTranslation'));
                        return $this->returnToEditElement($request);
                    }

                    $this->updateElement($table, $uid, $element);

                    $affectedColumns = implode(', ', array_keys($element));

                    $successMessage = $this->getLocallangTranslation('success.translationCompleted.message');

                    $this->addSuccessMessage($successMessage . ' ' . $affectedColumns);
                } catch (\Exception $exception) {
                    $this->addErrorMessage($exception->getMessage());
                }
            }

            return $this->returnToEditElement($request);
        }

        return $this->returnToEditElement($request);
    }

    protected function translateElement(array &$element, string $translateTo): void
    {
        if ($translateTo === '' || $element === []) {
            $element = [];
            return;
        }

        foreach ($element as $columnName => $value) {
            if (is_string($value) && !is_numeric($value)) {
                $translation = $this->translationService->translate($value, $translateTo);

                if ($translation !== null) {
                    $element[$columnName] = trim($translation);
                    continue;
                }
            }

            unset($element[$columnName]);
        }
    }

    protected function updateElement(string $table, int $uid, array $element): void
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

    protected function getElementByUid(array $columns, string $table, int $uid): array
    {
        if ($columns === []) {
            return [];
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll()->add(
            GeneralUtility::makeInstance(DeletedRestriction::class)
        );

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

    protected function getSiteForElement(array $element, string $table): Site
    {
        if ($table === 'pages') {
            $pid = $element['uid'];
        } else {
            $pid = $element['pid'];
        }

        return $this->siteFinder->getSiteByPageId($pid);
    }

    protected function returnToEditElement(ServerRequestInterface $request): ResponseInterface
    {
        if (isset($request->getQueryParams()['returnUrl'])) {
            return new RedirectResponse($request->getQueryParams()['returnUrl']);
        }

        $errorMessage = $this->getLocallangTranslation('error.unknownReturnUrl.message');

        if ($errorMessage === '') {
            $errorMessage = 'TYPO3_AI: error occurred during execution.';
        }

        return new HtmlResponse('<span>' . $errorMessage . '</span>');
    }

    protected function isValidRequest(ServerRequestInterface $request): bool
    {
        return isset($request->getQueryParams()['edit']) && $request->getQueryParams()['edit'];
    }

    protected function getIsoCodeForLanguage(Site $site, int $languageUid): string
    {
        return $site->getLanguageById($languageUid)->getTwoLetterIsoCode();
    }

    protected function addFlashMessageWithSeverity(
        string $title,
        string $message,
        int $severity = FlashMessage::OK
    ): void {
        $query = $this->flashMessageService->getMessageQueueByIdentifier();

        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            $message,
            $title,
            $severity,
            true
        );

        $query->addMessage($flashMessage);
    }

    protected function addSuccessMessage(string $message, string $title = ''): void
    {
        if ($title === '') {
            $title = $this->getLocallangTranslation('success.title');
        }

        $this->addFlashMessageWithSeverity($title, $message);
    }

    protected function addInfoMessage(string $message, string $title = ''): void
    {
        if ($title === '') {
            $title = $this->getLocallangTranslation('info.title');
        }

        $this->addFlashMessageWithSeverity($title, $message, FlashMessage::INFO);
    }

    protected function addWarningMessage(string $message): void
    {
        $this->addFlashMessageWithSeverity('', $message, FlashMessage::WARNING);
    }

    protected function addErrorMessage(string $message, string $title = ''): void
    {
        if ($title === '') {
            $title = $this->getLocallangTranslation('error.title');
        }

        $this->addFlashMessageWithSeverity($title, $message, FlashMessage::ERROR);
    }

    protected function getLocallangTranslation(string $id): string
    {
        return $this->getLanguageService()->sL('LLL:EXT:typo3_ai/Resources/Private/Language/locallang.xlf:' . $id);
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
