<?php
use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\FADataProvider;
use Ksfraser\Amortizations\SelectorProvider;

class DBPrefixTest extends TestCase {
    public function testFADataProviderPrefix() {
        $pdo = $this->createMock(PDO::class);
        $provider = new FADataProvider($pdo, 'company1_');
        $reflection = new \ReflectionClass($provider);
        $property = $reflection->getProperty('dbPrefix');
        $property->setAccessible(true);
        $this->assertEquals('company1_', $property->getValue($provider));
    }

    public function testSelectorProviderPrefix() {
        $db = $this->createMock(PDO::class);
        $provider = new SelectorProvider($db, 'wp_');
        $reflection = new \ReflectionClass($provider);
        $property = $reflection->getProperty('dbPrefix');
        $property->setAccessible(true);
        $this->assertEquals('wp_', $property->getValue($provider));
    }
}
