<?php

declare(strict_types=1);

namespace TYPO3Headless\Typo3Ai\Credentials;

/*
 * This file is part of the Macopedia. package.
 *
 * (c) 2023 Macopedia <extensions@macopedia.pl>, macopedia.com
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

class DefaultCredentials implements CredentialsInterface
{
    public function __construct(protected string $id, protected string $secret, protected array $data) {}

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getSecret(): string
    {
        return $this->secret;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }
}
