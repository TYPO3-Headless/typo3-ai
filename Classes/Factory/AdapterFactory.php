<?php

declare(strict_types=1);

namespace TYPO3Headless\Typo3Ai\Factory;

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
use TYPO3Headless\Typo3Ai\Credentials\CredentialsInterface;
use TYPO3Headless\Typo3Ai\ModelType\ModelTypeInterface;
use TYPO3Headless\Typo3Ai\TranslationAdapter\OpenAiPhpTranslationAdapter;
use TYPO3Headless\Typo3Ai\TranslationAdapter\TranslationAdapterInterface;

class AdapterFactory
{
    public function get(string $adapterType, CredentialsInterface $credentials, ModelTypeInterface $type): ?TranslationAdapterInterface
    {
        $adapter = GeneralUtility::makeInstance(OpenAiPhpTranslationAdapter::class, $credentials, $type);

        if ($adapter->isInitialized()) {
            return $adapter;
        }

        return null;
    }
}
