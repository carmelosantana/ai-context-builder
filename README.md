# AI Context Builder

A Composer package to simplify building context from your source code.

- [Install](#install)
- [Usage](#usage)
  - [Basic Usage](#basic-usage)
  - [Configuration](#configuration)
  - [Output](#output)
- [Funding](#funding)
- [Changelog](#changelog)
- [License](#license)

---

**Supports**

Easily create contextual intelligence from your project's source code when using chatbot for code assistance or autocompletion. This will dramatically improve the quality of the code or documentation generated.

**Features**

- Scan your composer project for PHP, CSS, and JS files.
- Automatically include composer dependencies in the context.
- Generate human-readable context files.
- Add additional paths or directories to the context.

## Install

Include `AiContextBuilder` in your project with [Composer](https://getcomposer.org/):

```bash
composer require --dev carmelosantana/ai-context-builder
```

Add the following configuration to your `composer.json` file:

```json
{
    "scripts": {
        "ai": "CarmeloSantana\\AiContextBuilder\\ContextBuilder::generateContext",
    },
}
```

**Requirements:**

- [PHP](https://www.php.net/manual/en/install.php) 8.1 or above
- [Composer](https://getcomposer.org/)

## Usage

### Basic Usage

The `AiContextBuilder` package is quite simple to use. After installation just run the following command:

```bash
composer ai
```

This will scan the `src` directory and any additional directories or files specified in the configuration. You will find the generated context files in the `.ai` directory at the root of your project.

### Configuration

You can pass additional paths or directories to be included in the context by providing a file with a list of paths:

```bash
composer ai path/to/your/paths.txt
```

The file should contain a list of paths separated by new lines. For example:

```txt
path/to/your/directory
path/to/your/file.php
```

### Output

Upon completion, `AiContextBuilder` will generate the following files:

- `files-all.txt`: Contains all scanned source files.
- `files-php.txt`: Contains only PHP files.
- `files-css.txt`: Contains only CSS files.
- `files-js.txt`: Contains only JavaScript files.
- `composer-*`: Contains files from each composer dependency.

Each file includes a summary of the scanned files and their content, formatted for easy integration with AI chatbots.

## Funding

If you find this project useful or use it in a commercial environment, please consider donating:

- Bitcoin ➭ `bc1qhxu9yf9g5jkazy6h4ux6c2apakfr90g2rkwu45`
- Ethereum ➭ `0x9f5D6dd018758891668BF2AC547D38515140460f`
- Patreon ➭ Subscribe via [patreon.com/carmelosantana](https://www.patreon.com/carmelosantana)
- PayPal ➭ [Donate via PayPal](https://www.paypal.com/donate/?business=PWVK9L8VGN4VA&no_recurring=0&item_name=Thank+you+for+supporting+the+AI+Context+Builder!&currency_code=USD)

## Changelog

- **1.0.0** - Sep 03, 2024
  - Initial release.

## License

The code is licensed under the [MIT License](https://opensource.org/licenses/MIT).
