<?php

namespace Lartrix\Tests\Unit\Modules\Registry;

use Lartrix\Modules\Registry\RegistryInstalledPackageChecklist;
use PHPUnit\Framework\TestCase;

class RegistryInstalledPackageChecklistTest extends TestCase
{
    public function testBuildsLaravelReviewChecklistFromCopiedModule(): void
    {
        $modulePath = $this->makeModule([
            'composer.json' => '{"extra":{"laravel":{"providers":["Modules\\\\Cms\\\\Providers\\\\CmsServiceProvider"]}}}',
            'app/Providers/CmsServiceProvider.php' => '<?php',
            'database/migrations/2026_01_01_000000_create_posts_table.php' => '<?php',
            'database/seeders/CmsDatabaseSeeder.php' => '<?php',
        ]);

        $result = (new RegistryInstalledPackageChecklist())->build($modulePath, 'official.cms');

        self::assertTrue($result['has_composer']);
        self::assertSame(1, $result['provider_count']);
        self::assertSame(1, $result['migration_count']);
        self::assertSame(1, $result['seeder_count']);
        self::assertContains('Review composer.json and merge provider/autoload settings if needed.', $result['todos']);
        self::assertContains('Run migrations manually after review, for example: php artisan module:migrate OfficialCms', $result['commands']);
        self::assertContains('Run seeders manually after review, for example: php artisan module:seed OfficialCms', $result['commands']);
    }

    /**
     * @param array<string, string> $files
     */
    private function makeModule(array $files): string
    {
        $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lartrix-checklist-module-' . uniqid('', true);

        foreach ($files as $path => $content) {
            $fullPath = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
            $dir = dirname($fullPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            file_put_contents($fullPath, $content);
        }

        return $root;
    }
}
