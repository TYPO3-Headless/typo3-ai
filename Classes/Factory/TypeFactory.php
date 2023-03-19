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
use TYPO3Headless\Typo3Ai\ModelType\ChatGptModelType;
use TYPO3Headless\Typo3Ai\ModelType\ModelTypeInterface;

class TypeFactory
{
    public function get(string $type = ChatGptModelType::CONFIG_KEY): ModelTypeInterface
    {
        return GeneralUtility::makeInstance(ChatGptModelType::class);
    }
}
