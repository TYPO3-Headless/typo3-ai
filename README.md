# TYPO3 AI Translator using ChatGPT
#### We want to introduce AI in TYPO3 CMS


[![TYPO3 11](https://img.shields.io/badge/TYPO3-11-orange.svg)](https://get.typo3.org/version/11)
[![ci](https://github.com/TYPO3-Headless/typo3_ai/actions/workflows/ci.yml/badge.svg)](https://github.com/TYPO3-Headless/typo3_ai/actions/workflows)

ChatGPT (OpenAI) can greatly accelerate the translation process in TYPO3 CMS by leveraging Artificial Inteligence powerful natural language processing capabilities.

With ChatGPT, translation can be automated and improved with features such as language detection, translation suggestions, and content optimization.
This not only saves time but also increases translation accuracy and ensures that content is delivered to a global audience in a timely and effective manner.

- We support `ChatGPT v3.5`


## Installation
Type `composer require typo3headless/typo3_ai`

## Features
Create translation of page or content element just by one click:
1. Go to your content element in desired language (not default).
2. Click on new button `Translate by AI`.
3. You have new translaton of your element!
[![translation](https://github.com/TYPO3-Headless/typo3_ai/blob/main/Resources/Public/Image/example.png)](https://github.com/TYPO3-Headless/typo3_ai)


## Configuration
1. Create an account on website: https://platform.openai.com/
2. Go to https://platform.openai.com/account/org-settings and copy your org id.
3. Go to `Settings` and `Extension Configuration`, set it as `ChatGPT id`.
4. Create new secret key on site https://platform.openai.com/account/api-keys
5. Go to `Settings` and `Extension Configuration`, set it as `ChatGPT secret api key`.
[![configuration](https://github.com/TYPO3-Headless/typo3_ai/blob/main/Resources/Public/Image/configuration.png)](https://github.com/TYPO3-Headless/typo3_ai)

## Requirements
You need at least `TYPO3 v11` and `PHP 8.1`.
## Contact
`TYPO3 AI` is brought to you by [Macopedia](https://macopedia.com/) with a big support of the community.

[Contact us](https://macopedia.com/contact)
