<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\LocalVersionService;
use App\Tests\Utils\FilemtimeStub;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(LocalVersionService::class)]
final class LocalVersionServiceTest extends TestCase
{
    private string $tempDir;

    #[\Override]
    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir().'/local_version_service_test_'.uniqid();
        mkdir($this->tempDir);
    }

    #[\Override]
    protected function tearDown(): void
    {
        FilemtimeStub::$forceFailure = false;

        $files = glob($this->tempDir.'/*');
        if (false !== $files) {
            foreach ($files as $file) {
                unlink($file);
            }
        }
        rmdir($this->tempDir);
    }

    #[Test]
    public function getVersionReturnsTrimmedFileContent(): void
    {
        file_put_contents($this->tempDir.'/version', "1.2.12\n");
        $service = new LocalVersionService($this->tempDir);

        $this->assertSame('1.2.12', $service->getVersion());
    }

    #[Test]
    public function getVersionReturnsFallbackWhenFileMissing(): void
    {
        $service = new LocalVersionService($this->tempDir);

        $this->assertSame('unknown', $service->getVersion());
    }

    #[Test]
    public function getUpdatedAtReturnsFileMtime(): void
    {
        file_put_contents($this->tempDir.'/version', "1.2.12\n");
        $expectedMtime = filemtime($this->tempDir.'/version');
        $service = new LocalVersionService($this->tempDir);

        $this->assertSame($expectedMtime, $service->getUpdatedAt()?->getTimestamp());
    }

    #[Test]
    public function getUpdatedAtReturnsNullWhenFileMissing(): void
    {
        $service = new LocalVersionService($this->tempDir);

        $this->assertNull($service->getUpdatedAt());
    }

    #[Test]
    public function getUpdatedAtReturnsNullWhenFilemtimeFails(): void
    {
        file_put_contents($this->tempDir.'/version', "1.2.12\n");
        FilemtimeStub::$forceFailure = true;
        $service = new LocalVersionService($this->tempDir);

        $this->assertNull($service->getUpdatedAt());
    }
}
