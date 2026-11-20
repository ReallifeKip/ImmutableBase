<?php

declare (strict_types = 1);

namespace Tests\CLI;

use PHPUnit\Framework\TestCase;
use ReallifeKip\ImmutableBase\CLI\ClassDiscovery;

/**
 * Class-name discovery shared by ib-cacher and ib-writer: every declared
 * class is found under its real namespace, and nothing that only looks like
 * a declaration is reported.
 */
class ClassDiscoveryTest extends TestCase
{
    private array $files = [];

    protected function tearDown(): void
    {
        array_map('unlink', $this->files);
    }

    private function namesIn(string $source): array
    {
        $this->files[] = $path = tempnam(sys_get_temp_dir(), 'ib_discovery_');
        file_put_contents($path, $source);

        return ClassDiscovery::classNamesIn($path);
    }

    public function testFindsEveryDeclarationInAFile(): void
    {
        $this->assertSame(
            ['App\\A', 'App\\B', 'App\\C', 'App\\D'],
            $this->namesIn("<?php\nnamespace App;\nclass A {}\nfinal class B {}\nreadonly class C {}\n#[\\Attribute]\nabstract /* doc */ class D {}\n")
        );
    }

    public function testSkipsClassConstantReferences(): void
    {
        $this->assertSame(['App\\A'], $this->namesIn("<?php\nnamespace App;\necho Foo::class, \$x::class;\nclass A {}\n"));
    }

    public function testSkipsAnonymousClasses(): void
    {
        $source = "<?php\nnamespace App;\n"
            . "\$a = new class {};\n"
            . "\$b = new readonly class {};\n"
            . "\$c = new #[\\Attr] class {};\n"
            . "\$d = new class(1) extends Base implements Contract {};\n"
            . "\$e = new /* note */ class {};\n"
            . "class Real {}\n";
        $this->assertSame(['App\\Real'], $this->namesIn($source));
    }

    public function testBracedNamespace(): void
    {
        $this->assertSame(['App\\Models\\User'], $this->namesIn("<?php\nnamespace App\\Models {\n    class User {}\n}\n"));
    }

    public function testSeveralNamespacesInOneFile(): void
    {
        $source = "<?php\nnamespace App\\Models {\n    class User {}\n}\nnamespace App\\Dto {\n    readonly class Order {}\n    class Item {}\n}\nnamespace {\n    class Globalish {}\n}\n";
        $this->assertSame(['App\\Models\\User', 'App\\Dto\\Order', 'App\\Dto\\Item', 'Globalish'], $this->namesIn($source));
    }

    public function testSeveralSemicolonNamespacesInOneFile(): void
    {
        $this->assertSame(['First\\A', 'Second\\B'], $this->namesIn("<?php\nnamespace First;\nclass A {}\nnamespace Second;\nclass B {}\n"));
    }

    public function testFileWithoutNamespace(): void
    {
        $this->assertSame(['Plain'], $this->namesIn("<?php\nclass Plain {}\n"));
    }
}
