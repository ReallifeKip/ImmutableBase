<?php

declare (strict_types = 1);

namespace Tests\Regressions;

use Composer\Autoload\ClassLoader;
use PHPUnit\Framework\TestCase;
use ReallifeKip\ImmutableBase\CLI\Writer;
use ReallifeKip\ImmutableBase\CLI\writer\Markdown;
use ReallifeKip\ImmutableBase\ImmutableBase;
use Tests\Regressions\Objects\Writer\DocItem;
use Tests\Regressions\Objects\Writer\DocOrder;
use Tests\Regressions\Objects\Writer\DocPairFirst;
use Tests\Regressions\Objects\Writer\DocPairSecond;
use Tests\Regressions\Objects\Writer\Size;

/**
 * ib-writer output for the shapes it used to get wrong: see the fixtures in
 * Objects/Writer. Output is generated from the repository root, like the
 * other Writer tests, so the fixtures are picked up by the directory scan.
 */
class WriterOutputTest extends TestCase
{
    private string $dir;
    private array $originalState;

    protected function setUp(): void
    {
        Writer::$silent      = true;
        $this->dir           = sys_get_temp_dir() . '/ib_writer_regression_' . uniqid();
        $this->originalState = ImmutableBase::state();
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob("{$this->dir}/*"));
        rmdir($this->dir);
        $s = &ImmutableBase::state();
        $s = $this->originalState;
    }

    private function generate(string $type): string
    {
        Writer::generate($type, "{$this->dir}/doc.$type");

        return file_get_contents("{$this->dir}/doc.$type");
    }

    /** Extracts one `interface Name { ... }` / `class Name { ... }` block. */
    private static function block(string $content, string $opener): string
    {
        $start = strpos($content, $opener);
        self::assertNotFalse($start, "Missing block '$opener'");

        return substr($content, $start, strpos($content, '}', $start) - $start);
    }

    // ─── TypeScript ──────────────────────────────────────────

    public function testTypescriptEscapesStringEnumValues(): void
    {
        $ts = $this->generate('ts');
        $this->assertStringContainsString("Apostrophe = 'it\\'s',", $ts);
        $this->assertStringContainsString("Backslash = 'a\\\\b',", $ts);
        $this->assertStringContainsString("Newline = 'line\\nbreak',", $ts);
    }

    public function testTypescriptDeclaresEnumsReferencedOnlyThroughArrayOf(): void
    {
        $ts = $this->generate('ts');
        $this->assertStringContainsString('sizes: Tests.Regressions.Objects.Writer.Size[]', $ts);
        $this->assertStringContainsString("type Size = 'S' | 'M'", $ts);
    }

    public function testTypescriptCompiles(): void
    {
        $tsc = trim((string) shell_exec('command -v tsc 2>/dev/null'));
        if ($tsc === '') {
            $this->markTestSkipped('tsc is not installed');
        }
        $this->generate('ts');
        exec(escapeshellarg($tsc) . ' --noEmit --strict ' . escapeshellarg("{$this->dir}/doc.ts") . ' 2>&1', $out, $code);
        $this->assertSame(0, $code, implode("\n", $out));
    }

    // ─── Mermaid ─────────────────────────────────────────────

    public function testMermaidShowsPropertiesInheritedFromAbstractParent(): void
    {
        $this->assertStringContainsString('+int inheritedId', self::block($this->generate('mmd'), 'class DocOrder {'));
    }

    public function testMermaidRelationsCoverArrayOfAndUnionMembers(): void
    {
        $mmd = $this->generate('mmd');
        $this->assertStringContainsString('DocOrder --> "*" DocItem : lines', $mmd);
        $this->assertStringContainsString('DocOrder --> "*" DocItem : mixedLines', $mmd);
        $this->assertStringContainsString('DocOrder --> "0..1" DocEmail : contact', $mmd);
        $this->assertStringContainsString('DocOrder --> "0..1" DocItem : contact', $mmd);
        $this->assertStringContainsString('DocOrder --> "1" DocItem : main', $mmd);
    }

    public function testMermaidRendersClassExtendingEngineDirectly(): void
    {
        $this->assertStringContainsString('+string direct', self::block($this->generate('mmd'), 'class DocDirect {'));
    }

    // ─── Markdown ────────────────────────────────────────────

    public function testMarkdownShowsCallableLookingStringDefaultVerbatim(): void
    {
        $this->assertStringContainsString('| mode |  | string | trim | - |', $this->generate('md'));
    }

    public function testMarkdownShowsArrayOfElementTypes(): void
    {
        $md   = $this->generate('md');
        $item = DocItem::class;
        $size = Size::class;
        $this->assertStringContainsString("| lines | yes | [DocItem](#$item)[] |", $md);
        $this->assertStringContainsString("| sizes | yes | [Size](#$size)[] |", $md);
        $this->assertStringContainsString("| mixedLines | yes | (int, [DocItem](#$item))[] |", $md);
        $this->assertStringContainsString("# Size {#$size}", $md);
    }

    public function testMarkdownEnumListDoesNotLeakAcrossRuns(): void
    {
        $this->generate('md');
        $this->assertNotSame([], Markdown::$enums);
        Markdown::namespaceBlocksGenerate([], [], []);
        $this->assertSame([], Markdown::enumBlocksGenerate());
    }

    // ─── Discovery ───────────────────────────────────────────

    /**
     * PSR-4 can only load the class named after the file, so a file holding
     * several classes is only discoverable through classmap autoloading —
     * which is exactly where every class in it must be picked up.
     */
    public function testEveryClassInAClassmapLoadedFileIsDocumented(): void
    {
        $file = __DIR__ . '/Objects/Writer/DocPair.php';
        foreach (ClassLoader::getRegisteredLoaders() as $loader) {
            $loader->addClassMap([DocPairFirst::class => $file, DocPairSecond::class => $file]);
        }
        $md = $this->generate('md');
        $this->assertStringContainsString('# DocPairFirst {#', $md);
        $this->assertStringContainsString('# DocPairSecond {#', $md);
    }

    public function testOrderFixtureIsSane(): void
    {
        $this->assertInstanceOf(DocOrder::class, DocOrder::fromArray([
            'inheritedId' => 1, 'lines' => [], 'sizes' => ['S'], 'mixedLines' => [1],
            'contact'     => 'a@b', 'quote' => null, 'main' => ['sku' => 'x'],
        ]));
    }
}
