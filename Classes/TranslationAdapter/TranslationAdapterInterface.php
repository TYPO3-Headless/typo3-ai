<?php

namespace TYPO3Headless\Typo3Ai\TranslationAdapter;

interface TranslationAdapterInterface
{
    public function isInitialized(): bool;

    public function translate(string $textToTranslate, string $language, string $context = ''): ?string;
}
