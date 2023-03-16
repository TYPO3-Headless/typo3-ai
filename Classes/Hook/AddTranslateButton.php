<?php

declare(strict_types=1);

namespace TYPO3Headless\Typo3Ai\Hook;

/*
 * This file is part of the Macopedia. package.
 *
 * (c) 2023 Macopedia <extensions@macopedia.pl>, macopedia.com
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class AddTranslateButton
{
    public function __construct(
        protected UriBuilder $uriBuilder,
        protected IconFactory $iconFactory,
        protected ConnectionPool $connectionPool
    ) {
    }


    public function getButtons(array $params, ButtonBar $buttonBar): array
    {
        $buttons = $params['buttons'];

        if (isset($GLOBALS['TYPO3_REQUEST'])) {
            $route = $GLOBALS['TYPO3_REQUEST']->getAttribute('route');

            if ($route->getPath() !== '/record/edit') {
                return $buttons;
            }

            $editParameters = GeneralUtility::_GET('edit');

            if (empty($editParameters) || !is_array($editParameters)) {
                return $buttons;
            }

            $tableName = key($editParameters);
            $uid = key($editParameters[$tableName]);
            $language = $this->getLanguageIdForRecord($tableName, $uid);

            if ($language === 0) {
                return $buttons;
            }

            $actionUri = (string)$this->uriBuilder->buildUriFromRoute(
                'translate_ai',
                ['edit' => $editParameters, 'returnUrl' => (string)$GLOBALS['TYPO3_REQUEST']->getUri()]
            );

            $translateByAi = $buttonBar->makeLinkButton()
                ->setShowLabelText(true)
                ->setHref($actionUri)
                ->setTitle(
                    $this->getLanguageService()->sL(
                        'LLL:EXT:typo3_ai/Resources/Private/Language/locallang.xlf:translateAi'
                    )
                )
                ->setIcon($this->iconFactory->getIcon('actions-translate', Icon::SIZE_SMALL));

            $buttons[ButtonBar::BUTTON_POSITION_LEFT][20][] = $translateByAi;
        }

        return $buttons;
    }

    protected function getLanguageIdForRecord(string $tableName, int $uid): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()->removeAll();

        if (!isset($GLOBALS['TCA']['tt_content']['ctrl']['languageField'])) {
            return 0;
        }

        $languageField = $GLOBALS['TCA']['tt_content']['ctrl']['languageField'];

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

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
