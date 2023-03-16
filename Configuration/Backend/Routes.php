<?php

return [
    'translate_ai' => [
        'path' => '/translate_ai',
        'referrer' => 'required',
        'target' => \TYPO3Headless\Typo3Ai\Controller\TranslationController::class . '::translateAction',
    ],
];
