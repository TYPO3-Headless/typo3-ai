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
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3Headless\Typo3Ai\Service\DatabaseService;
use TYPO3Headless\Typo3Ai\Service\TcaService;
use TYPO3Headless\Typo3Ai\Service\TranslationService;

class TranslationController extends ActionController
{
    public function __construct(
        protected DatabaseService $databaseService,
        protected TcaService $tcaService,
        protected FlashMessageService $flashMessageService,
        protected TranslationService $translationService,
        protected SiteFinder $siteFinder
    ) {}

    public function translateAction(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->isValidRequest($request) && $this->translationService->hasCurrentUserCorrectPermisions()) {
            foreach ($request->getQueryParams()['edit'] as $table => $config) {
                $languageField = $this->tcaService->getLanguageFieldForTable($table);

                if ($languageField === '') {
                    $this->addWarningMessage($this->getLocallangTranslation('warning.languageField'));
                    continue;
                }

                $columns = ['pid', 'uid', $languageField];
                $uid = (int)key($config);

                $element = $this->databaseService->getElementByUid(
                    $this->tcaService->getTranslatableColumns($table, $columns),
                    $table,
                    $uid
                );

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
                    $this->translationService->translateArrayToLanguage(
                        $element,
                        $this->getIsoCodeForLanguage($site, $languageUid),
                        true
                    );

                    if ($element === []) {
                        $this->addWarningMessage($this->getLocallangTranslation('warning.emptyAfterTranslation'));
                        return $this->returnToEditElement($request);
                    }

                    $this->databaseService->updateElement($table, $uid, $element);

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
