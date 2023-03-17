<?php

declare(strict_types=1);

defined('TYPO3') or die('Access denied.');

call_user_func(
    static function ($extensionKey) {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Backend\Template\Components\ButtonBar']['getButtonsHook']['typo3_ai'] = \TYPO3Headless\Typo3Ai\Hook\AddTranslateButton::class . '->getButtons';
    },
    'typo3_ai'
);
