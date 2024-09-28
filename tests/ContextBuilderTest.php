<?php

declare(strict_types=1);

use CarmeloSantana\AiContextBuilder\ContextBuilder;

# https://www.php.net/manual/en/function.rmdir.php#110489
function delTree($dir)
{
    $files = array_diff(scandir($dir), ['.', '..']);

    foreach ($files as $file) {
        $file = $dir . '/' . $file;
        (is_dir($file)) ? delTree($file) : unlink($file);
    }

    return rmdir($dir);
}

beforeEach(function () {
    $packageDir = __DIR__ . '/package';
    if (!is_dir($packageDir)) {
        mkdir($packageDir);
    }

    // Go to working dir
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

    // Add various test files
    file_put_contents($this->srcDir . '/Test.php', '<?php echo "Hello, World!";');
    file_put_contents($this->srcDir . '/style.css', 'body { background-color: #fff; }');
    file_put_contents($this->srcDir . '/script.js', 'console.log("Hello, World!");');
    file_put_contents($packageDir . '/composer.json', '{}');

    // Hidden or unwanted files (e.g., .DS_Store)
    file_put_contents($this->srcDir . '/.DS_Store', 'This is a hidden system file');
    file_put_contents($this->srcDir . '/random.txt', 'This is a random text file.');
});

$delete = true;

afterEach(function () use ($delete) {
    if (!$delete) {
        return;
    }

    // Cleanup: remove the .ai directory
    delTree($this->aiDir);

    // Remove the package directory and its contents
    delTree(__DIR__ . '/package');
});

it('generates all context files', function () {
    // Generate the context files
    ContextBuilder::generateContext([]);

    // Ensure the expected context files are generated
    expect($this->aiDir . '/files-all.txt')->toBeFile();
    expect($this->aiDir . '/files-php.txt')->toBeFile();
    expect($this->aiDir . '/files-css.txt')->toBeFile();
    expect($this->aiDir . '/files-js.txt')->toBeFile();
});

it('includes content in generated files', function () {
    // Generate the context files
    ContextBuilder::generateContext([]);

    // Verify the content of the generated files
    $allFilesContent = file_get_contents($this->aiDir . '/files-all.txt');
    $phpFilesContent = file_get_contents($this->aiDir . '/files-php.txt');
    $cssFilesContent = file_get_contents($this->aiDir . '/files-css.txt');
    $jsFilesContent = file_get_contents($this->aiDir . '/files-js.txt');

    expect($allFilesContent)->toContain('Hello, World!');
    expect($phpFilesContent)->toContain('<?php echo "Hello, World!";');
    expect($cssFilesContent)->toContain('body { background-color: #fff; }');
    expect($jsFilesContent)->toContain('console.log("Hello, World!");');
});

it('excludes hidden and unsupported files', function () {
    // Generate the context files
    ContextBuilder::generateContext([]);

    // Verify that hidden files and unsupported files are not included
    $allFilesContent = file_get_contents($this->aiDir . '/files-all.txt');

    expect($allFilesContent)->not->toContain('.DS_Store');  // Hidden file should not be present
    expect($allFilesContent)->not->toContain('random.txt'); // Unsupported file should not be present
});

it('handles empty directories gracefully', function () {
    // Clear the src directory and run the generator
    array_map('unlink', glob($this->srcDir . '/*.*'));

    ContextBuilder::generateContext([]);

    // Check that files are generated, but should only include composer.json as src is empty
    $allFilesContent = file_get_contents($this->aiDir . '/files-all.txt');
    expect($allFilesContent)->toContain('composer.json');

    // Ensure PHP, CSS, and JS files are not generated as src directory is empty
    expect($this->aiDir . '/files-php.txt')->not->toBeFile();
    expect($this->aiDir . '/files-css.txt')->not->toBeFile();
    expect($this->aiDir . '/files-js.txt')->not->toBeFile();
});
