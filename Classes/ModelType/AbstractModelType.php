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

abstract class AbstractModelType implements ModelTypeInterface
{
    public const CONFIG_KEY = '';
    public const MODEL = '';

    public function getConfigKey(): string
    {
        return static::CONFIG_KEY;
    }

    public function getModel(): string
    {
        return static::MODEL;
    }
}
