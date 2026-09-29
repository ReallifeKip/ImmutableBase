<?php

declare (strict_types = 1);

namespace Tests\Regressions;

use PHPUnit\Framework\TestCase;

/**
 * bin/ib-writer must be scriptable (CI, composer scripts): given --format it
 * runs without prompting and reports failures through its exit code.
 */
class WriterCliTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/ib_writer_cli_' . uniqid();
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob("{$this->dir}/*"));
        rmdir($this->dir);
    }

    /** @return array{int, string} Exit code and combined output. */
    private static function invoke(string ...$args): array
    {
        $bin = \dirname(__DIR__, 2) . '/bin/ib-writer';
        $cmd = implode(' ', array_map('escapeshellarg', [PHP_BINARY, $bin, ...$args])) . ' 2>&1 </dev/null';
        exec($cmd, $output, $code);

        return [$code, implode("\n", $output)];
    }

    public function testGeneratesWithoutPrompting(): void
    {
        foreach (['mmd', 'md', 'ts'] as $format) {
            [$code, $out] = self::invoke("--format=$format", "--output={$this->dir}");
            $this->assertSame(0, $code, $out);
            $this->assertFileExists("{$this->dir}/doc.$format");
        }
    }

    public function testShortOptions(): void
    {
        [$code, $out] = self::invoke('-fts', "-o{$this->dir}");
        $this->assertSame(0, $code, $out);
        $this->assertFileExists("{$this->dir}/doc.ts");
    }

    public function testRejectsUnknownFormat(): void
    {
        [$code, $out] = self::invoke('--format=pdf', "--output={$this->dir}");
        $this->assertSame(1, $code);
        $this->assertStringContainsString('pdf', $out);
    }

    public function testRejectsMissingOutputDirectory(): void
    {
        [$code, $out] = self::invoke('--format=md', "--output={$this->dir}/missing");
        $this->assertSame(1, $code);
        $this->assertStringContainsString('not found', $out);
    }

    public function testOutputWithoutFormatIsRejected(): void
    {
        [$code, $out] = self::invoke("--output={$this->dir}");
        $this->assertSame(1, $code);
        $this->assertStringContainsString('--format', $out);
    }
}
