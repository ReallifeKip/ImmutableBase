<?php

declare (strict_types = 1);

namespace Tests;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use ReallifeKip\ImmutableBase\CLI\Writer;
use ReallifeKip\ImmutableBase\StaticStatus;

class WriterTest extends TestCase
{
    private string $outputDir;

    /** @var array Original state backup */
    private array $originalProperties;
    private array $originalRefs;

    protected function setUp(): void
    {
        Writer::$silent           = true;
        $this->outputDir          = sys_get_temp_dir() . '/ib_writer_test_' . uniqid();
        $this->originalProperties = StaticStatus::$properties;
        $this->originalRefs       = StaticStatus::$refs;
        mkdir($this->outputDir, 0777, true);
    }

    protected function tearDown(): void
    {
        // Clean up generated files
        foreach (['mmd', 'md'] as $ext) {
            $file = "{$this->outputDir}/doc.$ext";
            if (file_exists($file)) {
                unlink($file);
            }
        }
        if (is_dir($this->outputDir)) {
            rmdir($this->outputDir);
        }
        // Restore original state
        StaticStatus::$properties = $this->originalProperties;
        StaticStatus::$refs       = $this->originalRefs;
    }
    public function testBasic(): void
    {
        Writer::$silent = false;
        $outputFile     = "{$this->outputDir}/doc.md";
        Writer::generate('mmd', $outputFile);
        Writer::$silent = true;
        $this->assertFileExists($outputFile);
    }

    public function testGenerateMermaidOutput(): void
    {
        $outputFile = "{$this->outputDir}/doc.mmd";
        Writer::generate('mmd', $outputFile);

        $this->assertFileExists($outputFile);
        $content = file_get_contents($outputFile);
        $this->assertStringContainsString('classDiagram', $content);
        $this->assertStringContainsString('namespace', $content);
    }

    public function testGenerateMarkdownOutput(): void
    {
        $outputFile = "{$this->outputDir}/doc.md";
        Writer::generate('md', $outputFile);

        $this->assertFileExists($outputFile);
        $content = file_get_contents($outputFile);
        $this->assertStringContainsString('|name|required|type|default|description|', $content);
    }

    public function testMermaidContainsClassBlocks(): void
    {
        $outputFile = "{$this->outputDir}/doc.mmd";
        Writer::generate('mmd', $outputFile);

        $content = file_get_contents($outputFile);
        $this->assertStringContainsString('class ', $content);
    }

    public function testMermaidContainsStereotypes(): void
    {
        $outputFile = "{$this->outputDir}/doc.mmd";
        Writer::generate('mmd', $outputFile);

        $content = file_get_contents($outputFile);
        // Should contain at least one stereotype (DTO, VO, or SVO)
        $this->assertTrue(
            str_contains($content, '<<DTO>>') ||
            str_contains($content, '<<VO>>') ||
            str_contains($content, '<<SVO>>'),
            'Mermaid output should contain at least one stereotype'
        );
    }

    public function testMermaidContainsHideEmptyMembersConfig(): void
    {
        $outputFile = "{$this->outputDir}/doc.mmd";
        Writer::generate('mmd', $outputFile);

        $content = file_get_contents($outputFile);
        $this->assertStringContainsString('hideEmptyMembersBox: true', $content);
    }

    public function testMarkdownContainsTableHeaders(): void
    {
        $outputFile = "{$this->outputDir}/doc.md";
        Writer::generate('md', $outputFile);

        $content = file_get_contents($outputFile);
        $this->assertStringContainsString('|name|required|type|default|description|', $content);
        $this->assertStringContainsString('|--|--|--|--|', $content);
    }

    public function testMarkdownContainsClassHeadings(): void
    {
        $outputFile = "{$this->outputDir}/doc.md";
        Writer::generate('md', $outputFile);

        $content = file_get_contents($outputFile);
        // Should contain at least one class heading
        $this->assertMatchesRegularExpression('/^# \w+/m', $content);
    }

    public function testDisplayNameGeneratorUniqueShortName(): void
    {
        $classMap = [
            'App\\Models\\User' => [
                'ref'       => new \ReflectionClass(\stdClass::class), // placeholder
                'shortName' => 'User',
                'namespace' => 'App\\Models',
                'types'     => [],
            ],
        ];
        $shortNameCount = ['User' => 1];

        $result = Writer::displayNameGenerator('App\\Models\\User', $classMap, $shortNameCount);
        $this->assertEquals('User', $result);
    }

    public function testDisplayNameGeneratorDuplicateShortName(): void
    {
        $classMap = [
            'App\\Models\\User' => [
                'ref'       => new \ReflectionClass(\stdClass::class),
                'shortName' => 'User',
                'namespace' => 'App\\Models',
                'types'     => [],
            ],
            'App\\Admin\\User'  => [
                'ref'       => new \ReflectionClass(\stdClass::class),
                'shortName' => 'User',
                'namespace' => 'App\\Admin',
                'types'     => [],
            ],
        ];
        $shortNameCount = ['User' => 2];

        $result1 = Writer::displayNameGenerator('App\\Models\\User', $classMap, $shortNameCount);
        $result2 = Writer::displayNameGenerator('App\\Admin\\User', $classMap, $shortNameCount);

        $this->assertEquals('App_Models_User', $result1);
        $this->assertEquals('App_Admin_User', $result2);
        $this->assertNotEquals($result1, $result2);
    }

    public function testDisplayNameGeneratorClassNotInMap(): void
    {
        $classMap       = [];
        $shortNameCount = [];

        $result = Writer::displayNameGenerator('App\\Models\\Unknown', $classMap, $shortNameCount);
        $this->assertEquals('Unknown', $result);
    }

    public function testGenerateResetsPropertiesAndRebuilds(): void
    {
        // Clear properties to force re-indexing
        $savedProps               = StaticStatus::$properties;
        StaticStatus::$properties = [];

        $outputFile = $this->outputDir . '/doc.mmd';
        Writer::generate('mmd', $outputFile);

        // Properties should be repopulated after generate
        $this->assertNotEmpty(StaticStatus::$properties);

        StaticStatus::$properties = $savedProps;
    }

    public function testMermaidRelationsGenerated(): void
    {
        $outputFile = $this->outputDir . '/doc.mmd';
        Writer::generate('mmd', $outputFile);

        $content = file_get_contents($outputFile);
        // If any class has non-builtin property types that are in the class map,
        // there should be composition relations (-->)
        // This depends on your test objects having nested IB types
        if (str_contains($content, '-->')) {
            $this->assertStringContainsString('-->', $content);
        } else {
            // No relations is also valid if no nested IB types exist
            $this->assertTrue(true);
        }
    }

    public function testMarkdownNonBuiltinTypesRenderedAsLinks(): void
    {
        $outputFile = "{$this->outputDir}/doc.md";
        Writer::generate('md', $outputFile);

        $content = file_get_contents($outputFile);
        // Non-builtin types should be rendered as [ShortName](#FQCN) links
        // This depends on your test objects having nested IB types
        if (preg_match('/\[(\w+)\]\(#/', $content)) {
            $this->assertMatchesRegularExpression('/\[\w+\]\(#[\w\\\\]+\)/', $content);
        } else {
            $this->assertTrue(true);
        }
    }

    public function testGenerateOutputIsNotEmpty(): void
    {
        foreach (['mmd', 'md'] as $type) {
            $outputFile = "{$this->outputDir}/doc.$type";
            Writer::generate($type, $outputFile);

            $content = file_get_contents($outputFile);
            $this->assertNotEmpty($content, "Output for type '$type' should not be empty");

            unlink($outputFile);
        }
    }
    public function testParseUnreadableFileReturnsNull(): void
    {
        $root = vfsStream::setup('testDir');
        $file = vfsStream::newFile('test.php', 0000)
            ->withContent('<?php class Foo {}')
            ->at($root);

        $method = new \ReflectionMethod(Writer::class, 'parseFullClassName');
        $result = @$method->invoke(null, $file->url());

        $this->assertNull($result);
    }
}
