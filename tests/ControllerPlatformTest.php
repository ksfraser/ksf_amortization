<?php
use PHPUnit\Framework\TestCase;
use Ksfraser\Amortizations\AmortizationModel;
use Ksfraser\Amortizations\FA\FADataProvider;
use Ksfraser\Amortizations\WordPress\WPDataProvider;
use Ksfraser\Amortizations\SuiteCRM\SuiteCRMDataProvider;

class ControllerPlatformTest extends TestCase
{
    public function testFAProviderInstantiation()
    {
        $db = $this->getMockBuilder('PDO')->disableOriginalConstructor()->getMock();
        $provider = new FADataProvider($db);
        $model = new AmortizationModel($provider);
        $this->assertInstanceOf(AmortizationModel::class, $model);
    }

    public function testWPProviderInstantiation()
    {
        $wpdb = $this->getMockBuilder('stdClass')->getMock();
        $provider = new WPDataProvider($wpdb);
        $model = new AmortizationModel($provider);
        $this->assertInstanceOf(AmortizationModel::class, $model);
    }

    public function testSuiteCRMProviderInstantiation()
    {
        $provider = new SuiteCRMDataProvider();
        $model = new AmortizationModel($provider);
        $this->assertInstanceOf(AmortizationModel::class, $model);
    }
}
