<?php

declare(strict_types=1);

use CarmeloSantana\AiContextBuilder\ContextBuilder;

beforeEach(function () {
    $packageDir = __DIR__ . '/package';
    if (!is_dir($packageDir)) {
        mkdir($packageDir);
    }

    // go to working dir
    chdir($packageDir);

    // Setup: create a mock .ai directory and test files
    $this->aiDir = $packageDir . '/.ai';
    if (!is_dir($this->aiDir)) {
        mkdir($this->aiDir);
    }

    // Create a mock src directory with various files
    $this->srcDir = $packageDir . '/src';
    if (!is_dir($this->srcDir)) {
        mkdir($this->srcDir);
    }

    file_put_contents($this->srcDir . '/Test.php', '<?php echo "Hello, World!";');
    file_put_contents($this->srcDir . '/style.css', 'body { background-color: #fff; }');
    file_put_contents($this->srcDir . '/script.js', 'console.log("Hello, World!");');
    file_put_contents(__DIR__ . '/package/composer.json', '{}');

    // Create a file that includes ContextBuilder.php as an additional path
    $this->additionalPathsFile = $packageDir . '/additional-paths.txt';
    file_put_contents($this->additionalPathsFile, dirname(__DIR__) . '/src/ContextBuilder.php');
});

// Add a config flag to enable/disable deleting files after each test
$deleteFilesAfterTest = false;

afterEach(function () use ($deleteFilesAfterTest) {
    if ($deleteFilesAfterTest) {
        // Cleanup: remove the .ai directory and test files
        array_map('unlink', glob($this->srcDir . '/*.*'));
        rmdir($this->srcDir);

        array_map('unlink', glob($this->aiDir . '/*.*'));
        rmdir($this->aiDir);

        unlink(__DIR__ . '/package/composer.json');
        unlink($this->additionalPathsFile);
    }
});

it('generates all context files', function () {
    ContextBuilder::generateContext(['additional-paths.txt']);

    expect(file_exists($this->aiDir . '/files-all.txt'))->toBeTrue();
    expect(file_exists($this->aiDir . '/files-php.txt'))->toBeTrue();
    expect(file_exists($this->aiDir . '/files-css.txt'))->toBeTrue();
    expect(file_exists($this->aiDir . '/files-js.txt'))->toBeTrue();
});

it('includes content in generated files', function () {
    ContextBuilder::generateContext(['additional-paths.txt']);

    $allFilesContent = file_get_contents($this->aiDir . '/files-all.txt');
    $phpFilesContent = file_get_contents($this->aiDir . '/files-php.txt');
    $cssFilesContent = file_get_contents($this->aiDir . '/files-css.txt');
    $jsFilesContent = file_get_contents($this->aiDir . '/files-js.txt');

    expect($allFilesContent)->toContain('Hello, World!');
    expect($phpFilesContent)->toContain('<?php echo "Hello, World!";');
    expect($cssFilesContent)->toContain('body { background-color: #fff; }');
    expect($jsFilesContent)->toContain('console.log("Hello, World!");');
});
