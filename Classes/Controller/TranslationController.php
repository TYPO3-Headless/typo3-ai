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

use TYPO3Headless\Typo3Ai\Service\TranslationService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class TranslationController extends ActionController
{

    public function __construct(protected ConnectionPool $connectionPool)
    {
    }

    public function translateAction(ServerRequestInterface $request): ResponseInterface
    {
        if ($request->getQueryParams()['edit'] && $this->getBackendUser()->isAdmin()) {
            $translationService = GeneralUtility::makeInstance(TranslationService::class);
            $columns = ['sys_language_uid', 'pid'];

            foreach ($request->getQueryParams()['edit'] as $table => $config) {
                $uid = key($config);

                foreach ($GLOBALS['TCA'][$table]['columns'] as $columnName => $columnConfig) {
                    if (!isset($columnConfig['config']['renderType'])
                        && !isset($columnConfig['config']['valuePicker'])
                        && in_array($columnConfig['config']['type'], ['text', 'input'])) {
                        $eval = isset($columnConfig['config']['eval']) && $columnConfig['config']['eval'] === 'int';

                        if ($eval === false) {
                            $columns[] = $columnName;
                        }
                    }
                }

                $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);

                if ($columns !== []) {
                    $queryBuilder->getRestrictions()->removeAll()->add(
                        GeneralUtility::makeInstance(DeletedRestriction::class)
                    );

                    $element = $queryBuilder
                        ->select(...$columns)
                        ->from($table)
                        ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid)))
                        ->executeQuery()
                        ->fetchAssociative();

                    if ($table === 'pages') {
                        $pid = $element['uid'];
                    } else {
                        $pid = $element['pid'];
                    }

                    $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($pid);

                    $languageUid = $element['sys_language_uid'];
                    if ($languageUid !== 0) {
                        try {
                            $siteLanguage = $site->getLanguageById($languageUid);

                            $translateTo = $siteLanguage->getTwoLetterIsoCode();

                            foreach ($element as $columnName => $value) {
                                if (!is_numeric($value) && !empty($value)) {
                                    $translation = $translationService->translate($value, $translateTo);

                                    if ($translation !== null) {
                                        $element[$columnName] = trim($translation);
                                        continue;
                                    }
                                }

                                unset($element[$columnName]);
                            }

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
                        } catch (\Exception $exception) {
                        }
                    }
                }
            }
        }

        return new RedirectResponse($request->getQueryParams()['returnUrl']);
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Returns the current BE user.
     *
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
