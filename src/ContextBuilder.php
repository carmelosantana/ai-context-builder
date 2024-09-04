<?php

declare(strict_types=1);

namespace CarmeloSantana\AiContextBuilder;

use Composer\Script\Event;

/**
 * Class ContextBuilder
 *
 * This class handles the generation of context files for AI chatbots. It reads files from the project directories,
 * including those specified by the user, and outputs them into a structured format within the .ai directory.
 */
class ContextBuilder
{
    /**
     * Generate context files by scanning project directories and processing vendor files.
     * It organizes files into specific categories (e.g., PHP, CSS, JS) and excludes hidden files.
     *
     * @param Event|array $args Composer script event, used to pass in arguments.
     */
    public static function generateContext(Event|array $args = []): void
    {
        if ($args instanceof Event) {
            $args = $args->getArguments();
        }

        // Assume the current working directory is the project root
        $dir = getcwd();

        $aiDir = $dir . '/.ai';

        if (!is_dir($aiDir)) {
            mkdir($aiDir, 0755, true); // Ensure secure permissions and recursive creation
        }

        $dirs = [
            $dir . '/src',
        ];

        $allFilesOutput = $aiDir . '/files-all.txt';
        $phpOutput = $aiDir . '/files-php.txt';
        $cssOutput = $aiDir . '/files-css.txt';
        $jsOutput = $aiDir . '/files-js.txt';

        // Include composer.json from the main project
        $additionalFiles = [
            $dir . '/composer.json',
        ];

        // Include additional files or directories provided by the user
        if (!empty($args) and isset($args[0])) {
            $additionalPathsFile = $args[0];

            // Check local working directory for the additional paths file
            if (!file_exists($additionalPathsFile)) {
                // Check the project root
                $additionalPathsFile = $dir . '/' . $args[0];

                if (!file_exists($additionalPathsFile)) {
                    throw new \Exception('Additional paths file not found.'); // Security: Avoid silent failures
                }
            }

            if (file_exists($additionalPathsFile)) {
                $additionalPaths = file($additionalPathsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $additionalFiles = array_merge($additionalFiles, $additionalPaths);
            }
        }

        // Scan and categorize files by type
        $paths = array_merge($dirs, $additionalFiles);
        $allFiles = self::getFilesByType($paths, []);
        $phpFiles = self::getFilesByType($paths, ['php']);
        $cssFiles = self::getFilesByType($paths, ['css']);
        $jsFiles = self::getFilesByType($paths, ['js']);

        // Write categorized files to the corresponding output files
        self::writeFilesToOutput($allFiles, $allFilesOutput);
        self::writeFilesToOutput($phpFiles, $phpOutput);
        self::writeFilesToOutput($cssFiles, $cssOutput);
        self::writeFilesToOutput($jsFiles, $jsOutput);

        // Process vendor files from the composer dependencies
        $composer = json_decode(file_get_contents($dir . '/composer.json'), true);
        $composerFiles = [];

        if (isset($composer['require'])) {
            foreach ($composer['require'] as $vendor => $package) {
                $composerFiles[] = self::processVendorFiles($vendor, $package, $dir, $aiDir);
            }
        }

        // Report on script completion
        self::reportCompletion([$allFilesOutput, $phpOutput, $cssOutput, $jsOutput], $composerFiles);
    }

    /**
     * Recursively scan directories for files by type.
     *
     * @param array $dirs Directories to scan.
     * @param array $extensions File extensions to filter by (e.g., ['php']). If empty, include all files.
     * @return array List of file paths.
     */
    private static function getFilesByType(array $dirs, array $extensions = []): array
    {
        $files = [];

        foreach ($dirs as $dir) {
            if (is_dir($dir)) {
                $items = scandir($dir);
            } else {
                $items = [$dir];
            }

            foreach ($items as $item) {
                if ($item[0] === '.') {
                    continue;
                }

                if (!is_file($item)) {
                    $path = $dir . '/' . $item;
                } else {
                    $path = $item;
                }

                if (is_dir($path)) {
                    $files = array_merge($files, self::getFilesByType([$path], $extensions));
                } elseif (is_file($path) and (empty($extensions) or in_array(pathinfo($path, PATHINFO_EXTENSION), $extensions, true))) {
                    $files[] = $path;
                }
            }
        }

        return $files;
    }

    /**
     * Write the contents of files to the output file.
     *
     * @param array $files List of file paths.
     * @param string $outputFile Output file path.
     */
    private static function writeFilesToOutput(array $files, string $outputFile): void
    {
        $output = '';

        // if empty files, skip
        if (empty($files)) {
            return;
        }

        foreach ($files as $file) {
            $relativePath = str_replace(__DIR__ . '/', '', $file);

            // Security: Limit the file size being read to prevent memory overloads
            $content = file_get_contents($file, false, null, 0, 500000); // 500KB limit

            $output .= "$relativePath\n";
            $output .= "```\n";
            $output .= $content;
            $output .= "\n```\n";
        }

        file_put_contents($outputFile, $output);
    }

    /**
     * Process vendor files from the composer dependencies and store only PHP files.
     *
     * @param string $vendor Vendor name.
     * @param string $package Package name.
     * @param string $dir Main project root directory.
     * @param string $aiDir .ai directory path.
     * @return string The path of the generated file.
     */
    private static function processVendorFiles(string $vendor, string $package, string $dir, string $aiDir): string
    {
        $outputFileVendor = $aiDir . '/composer-' . str_replace('/', '-', $vendor) . '-' . $package . '.txt';

        if (file_exists($outputFileVendor)) {
            return $outputFileVendor;
        }

        $package = explode(':', $package)[0];
        $vendorDir = $dir . '/vendor/' . $vendor . '/src/';

        if (!is_dir($vendorDir)) {
            return '';
        }

        // Process only PHP files
        $vendorFiles = self::getFilesByType([$vendorDir], ['php']);
        self::writeFilesToOutput($vendorFiles, $outputFileVendor);

        return $outputFileVendor;
    }

    /**
     * Report the completion of the script, including the number of files scanned and the details of files created.
     *
     * @param array $outputFiles List of output files generated by the script.
     * @param array $composerFiles List of composer output files generated by the script.
     */
    private static function reportCompletion(array $outputFiles, array $composerFiles): void
    {
        echo "\nScript completed successfully.\n";

        // Count total number of files scanned
        $totalFilesScanned = 0;
        foreach ($outputFiles as $outputFile) {
            if (!file_exists($outputFile)) {
                continue;
            }
            $totalFilesScanned += substr_count(file_get_contents($outputFile), "```\n");
        }
        echo "Total files scanned: $totalFilesScanned\n";

        // Display details of each file generated
        echo "Files created:\n";
        foreach (array_merge($outputFiles, $composerFiles) as $outputFile) {
            if ($outputFile and file_exists($outputFile)) {
                $fileSize = self::humanReadableFileSize(filesize($outputFile));
                echo "- " . basename($outputFile) . " (" . $fileSize . ")\n";
            }
        }
    }

    /**
     * Convert file size in bytes to a human-readable format.
     *
     * @param int $bytes File size in bytes.
     * @return string Human-readable file size.
     */
    private static function humanReadableFileSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' bytes';
    }
}
