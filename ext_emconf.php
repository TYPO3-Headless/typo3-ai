<?php

/*
 * This file is part of the "TYPO3 AI" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 AI',
    'description' => 'TYPO3 AI is extension which utilizes ChatGPT to accelerate the translation process in TYPO3 CMS by leveraging Artificial Intelligence powerful natural language processing capabilities.',
    'state' => 'stable',
    'author' => 'Oskar Dydo',
    'author_email' => 'extensions@macopedia.pl',
    'author_company' => 'Macopedia Sp. z o.o.',
    'category' => 'be',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '11.4.0-11.5.99'
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
