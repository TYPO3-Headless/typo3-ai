<?php

declare(strict_types=1);

namespace TYPO3Headless\Typo3Ai\TranslationAdapter;

/*
 * This file is part of the Macopedia. package.
 *
 * (c) 2023 Macopedia <extensions@macopedia.pl>, macopedia.com
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use OpenAI\Client;
use TYPO3Headless\Typo3Ai\Credentials\CredentialsInterface;
use TYPO3Headless\Typo3Ai\ModelType\ModelTypeInterface;

class OpenAiPhpTranslationAdapter implements TranslationAdapterInterface
{
    public const NO_TRANSLATION = 'NO_TRANSLATION';
    public const TYPE = 'openai-php/client';

    protected bool $initialized = false;

    protected ModelTypeInterface|null $type = null;

    protected ?Client $client = null;

    protected array $defaultConfig = [
        'model' => '',
        'messages' => [],
    ];

    protected string $prompt = 'Translate text (keep html unchanged) after next semi-colon to %s %s or write \'' . self::NO_TRANSLATION . '\'; ';

    protected string $contextText = ' using following context in brackets as reference [%s]';

    public function __construct(CredentialsInterface $credentials, ModelTypeInterface $type)
    {
        $this->client = \OpenAI::client($credentials->getSecret(), $credentials->getId());
        $this->client->models()->list();

        $this->initialized = true;

        $this->defaultConfig['model'] = $type->getModel();
    }

    public function translate(string $textToTranslate, string $language, string $context = ''): ?string
    {
        $message = $this->defaultConfig;

        if ($this->client !== null
            && $textToTranslate !== ''
            && $language !== ''
            && !is_numeric($textToTranslate)
        ) {
            if ($context !== '') {
                $context = sprintf($this->contextText, $context);
            }

            $finalPrompt = sprintf($this->prompt, $language, $context) . $textToTranslate;

            $message['messages'][] =
                [
                    'role' => 'user',
                    'content' => $finalPrompt,
                ];

            $client = $this->client->chat()->create($message);

            if (!isset($client->choices[0])) {
                return null;
            }

            $message = $client->choices[0]->message->content;

            if (str_contains($message, self::NO_TRANSLATION)) {
                return null;
            }

            return $message;
        }

        return null;
    }

    public function isInitialized(): bool
    {
        return $this->initialized;
    }
}
