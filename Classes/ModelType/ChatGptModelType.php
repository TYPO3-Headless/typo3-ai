<?php

declare(strict_types=1);

namespace TYPO3Headless\Typo3Ai\ModelType;

/*
 * This file is part of the Macopedia. package.
 *
 * (c) 2023 Macopedia <extensions@macopedia.pl>, macopedia.com
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

class ChatGptModelType extends AbstractModelType
{
    public const CONFIG_KEY = 'chatgpt';
    public const MODEL = 'gpt-3.5-turbo';
}
