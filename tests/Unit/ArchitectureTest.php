<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Dependencies point inward: Infrastructure → Application → Domain. Domain and Application
 * are plain PHP, so they must not import the framework or an outer layer.
 */
class ArchitectureTest extends TestCase
{
    private const FRAMEWORK = ['Illuminate\\', 'Laravel\\', 'Symfony\\', 'Database\\', 'App\\'];

    /**
     * @param  list<string>  $outerLayers
     */
    #[DataProvider('layers')]
    public function test_layer_does_not_depend_on_outer_layers_or_the_framework(string $layer, array $outerLayers): void
    {
        $files = $this->files($layer);
        $this->assertNotEmpty($files);

        $violations = [];
        foreach ($files as $file) {
            preg_match_all('/^use\s+([^;\s]+)/m', file_get_contents($file), $matches);

            foreach ($matches[1] as $import) {
                // Src\<Context>\<Layer>\...
                $importedLayer = explode('\\', $import)[2] ?? null;

                if (str_starts_with($import, 'Src\\') ? in_array($importedLayer, $outerLayers, true) : $this->isFramework($import)) {
                    $violations[] = "{$file} uses {$import}";
                }
            }
        }

        $this->assertSame([], $violations);
    }

    /**
     * @return array<string, array{string, list<string>}>
     */
    public static function layers(): array
    {
        return [
            'Domain' => ['Domain', ['Application', 'Infrastructure']],
            'Application' => ['Application', ['Infrastructure']],
        ];
    }

    private function isFramework(string $import): bool
    {
        foreach (self::FRAMEWORK as $prefix) {
            if (str_starts_with($import, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function files(string $layer): array
    {
        $files = [];
        foreach (glob(dirname(__DIR__, 2)."/src/*/{$layer}", GLOB_ONLYDIR) as $dir) {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $file) {
                if ($file->getExtension() === 'php') {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }
}
