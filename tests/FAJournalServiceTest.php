<?php
namespace Ksfraser\Amortizations\Tests;

use Ksfraser\Amortizations\FAJournalService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for FAJournalService
 * @covers Ksfraser\Amortizations\FAJournalService
 */
class FAJournalServiceTest extends TestCase {
    public function testPostPaymentToGLReturnsTrue() {
        $service = new FAJournalService($this->createMock(\PDO::class));
        $result = $service->postPaymentToGL(1, [], []);
        $this->assertTrue($result);
    }
}
