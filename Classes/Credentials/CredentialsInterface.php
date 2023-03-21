<?php

namespace TYPO3Headless\Typo3Ai\Credentials;

interface CredentialsInterface
{
    /**
     * @return string
     */
    public function getId(): string;

    /**
     * @return string
     */
    public function getSecret(): string;
}
