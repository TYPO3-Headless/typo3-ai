<?php

namespace TYPO3Headless\Typo3Ai\ModelType;

interface ModelTypeInterface
{
    public function getConfigKey(): string;

    public function getModel(): string;
}
