<?php
namespace Tests\Helpers;

use PHPUnit\Framework\TestCase;

/**
 * Mock Builder Helper
 *
 * Provides convenient methods for creating common mock objects.
 * Reduces boilerplate in test setup and improves readability.
 *
 * ### Usage
 *
 * ```php
 * // Create mock PDO
 * $pdo = MockBuilder::createPdoMock();
 *
 * // Create mock with return values
 * $pdo = MockBuilder::createPdoMock([
 *     'lastInsertId' => 123,
 *     'prepare' => $stmt
 * ]);
 *
 * // Create mock DataProvider
 * $provider = MockBuilder::createDataProviderMock([
 *     'getLoan' => $loanData,
 *     'insertSchedule' => true
 * ]);
 * ```
 *
 * @package   Ksfraser\Amortizations\Tests\Helpers
 * @author    KSF Development Team
 * @version   1.0.0
 * @since     2025-12-17
 */
class MockBuilder
{
    /**
     * @var TestCase
     */
    private static $testCase;

    /**
     * Set the test case for mock creation
     *
     * @param TestCase $testCase
     * @return void
     */
    public static function setTestCase(TestCase $testCase): void
    {
        self::$testCase = $testCase;
    }

    /**
     * Create a mock PDO connection
     *
     * @param array $returnValues Method return values
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    public static function createPdoMock(array $returnValues = [])
    {
        $defaults = [
            'lastInsertId' => 1,
            'prepare' => self::createPdoStatementMock([])
        ];

        $values = array_merge($defaults, $returnValues);

        $pdo = self::$testCase->createMock(\PDO::class);

        foreach ($values as $method => $value) {
            if (is_array($value)) {
                $pdo->method($method)->willReturnCallback(function () use ($value) {
                    return $value;
                });
            } else {
                $pdo->method($method)->willReturn($value);
            }
        }

        return $pdo;
    }

    /**
     * Create a mock PDO statement
     *
     * @param array $fetchData Data to return from fetch/fetchAll
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    public static function createPdoStatementMock(array $fetchData = [])
    {
        $stmt = self::$testCase->createMock(\PDOStatement::class);

        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetch')->willReturn(reset($fetchData) ?: null);
        $stmt->method('fetchAll')->willReturn($fetchData);
        $stmt->method('rowCount')->willReturn(count($fetchData));

        return $stmt;
    }

    /**
     * Create a mock DataProvider interface
     *
     * @param array $methodReturnValues Method names and return values
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    public static function createDataProviderMock(array $methodReturnValues = [])
    {
        $provider = self::$testCase->createMock(
            \Ksfraser\Amortizations\DataProviderInterface::class
        );

        foreach ($methodReturnValues as $method => $value) {
            if (is_callable($value)) {
                $provider->method($method)->willReturnCallback($value);
            } else {
                $provider->method($method)->willReturn($value);
            }
        }

        return $provider;
    }

    /**
     * Create a mock wpdb (WordPress database)
     *
     * @param array $returnValues Method return values
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    public static function createWpdbMock(array $returnValues = [])
    {
        $defaults = [
            'prefix' => 'wp_',
            'insert_id' => 1,
            'last_error' => '',
            'insert' => true,
            'update' => true,
            'delete' => true,
            'prepare' => 'SELECT * FROM table',
            'get_row' => ['id' => 1],
            'get_results' => [['id' => 1]],
            'get_var' => 1
        ];

        $values = array_merge($defaults, $returnValues);

        $wpdb = self::$testCase->getMockBuilder(stdClass::class)
            ->addMethods(array_keys($values))
            ->getMock();

        foreach ($values as $method => $value) {
            if (in_array($method, ['prefix', 'insert_id', 'last_error'])) {
                $wpdb->{$method} = $value;
            } else {
                $wpdb->method($method)->willReturn($value);
            }
        }

        return $wpdb;
    }

    /**
     * Create a mock LoanEvent
     *
     * @param array $properties Event properties
     * @return object
     */
    public static function createLoanEventMock(array $properties = []): object
    {
        $defaults = [
            'event_type' => 'extra_payment',
            'event_date' => date('Y-m-d'),
            'amount' => 500.00,
            'notes' => 'Test event'
        ];

        $event = new \stdClass();
        foreach (array_merge($defaults, $properties) as $key => $value) {
            $event->{$key} = $value;
        }

        return $event;
    }

    /**
     * Create a mock Calculator
     *
     * @param string $calculatorClass Calculator class to mock
     * @param array $methodReturnValues Method names and return values
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    public static function createCalculatorMock(string $calculatorClass, array $methodReturnValues = [])
    {
        $calculator = self::$testCase->createMock($calculatorClass);

        foreach ($methodReturnValues as $method => $value) {
            if (is_callable($value)) {
                $calculator->method($method)->willReturnCallback($value);
            } else {
                $calculator->method($method)->willReturn($value);
            }
        }

        return $calculator;
    }

    /**
     * Create a callable that returns different values on successive calls
     *
     * @param array $values Values to return on successive calls
     * @return callable
     */
    public static function createMultiCallReturn(array $values): callable
    {
        $index = 0;
        return function () use ($values, &$index) {
            if ($index < count($values)) {
                return $values[$index++];
            }
            return end($values);
        };
    }

    /**
     * Create a stub DataProvider that returns test data
     *
     * @param array $loanId Optional loan to return
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    public static function createDataProviderStub(array $loan = null)
    {
        $provider = self::$testCase->createMock(
            \Ksfraser\Amortizations\DataProviderInterface::class
        );

        $provider->method('getLoan')->willReturn($loan ?: [
            'id' => 1,
            'principal' => 30000,
            'interest_rate' => 4.5,
            'term_months' => 60
        ]);

        $provider->method('getScheduleRows')->willReturn([
            [
                'payment_date' => '2025-02-01',
                'payment_amount' => 554.73,
                'principal_portion' => 454.73,
                'interest_portion' => 100.00,
                'remaining_balance' => 29545.27
            ]
        ]);

        return $provider;
    }

    /**
     * Assert that a mock method was called with specific arguments
     *
     * @param \PHPUnit\Framework\MockObject\MockObject $mock Mock object
     * @param string $method Method name
     * @param array $args Expected arguments
     * @return void
     */
    public static function assertMethodCalled(
        $mock,
        string $method,
        array $args = []
    ): void {
        $constraint = empty($args)
            ? self::$testCase->once()
            : self::$testCase->once();

        $mock->expects($constraint)
            ->method($method)
            ->with(...$args);
    }

    /**
     * Create a spy that tracks calls but still executes the method
     *
     * @param object $object Object to spy on
     * @param string $method Method name
     * @return array [$spy, $calls] Spy mock and calls array
     */
    public static function createSpy(object $object, string $method): array
    {
        $calls = [];
        $spy = self::$testCase->getMockBuilder(get_class($object))
            ->onlyMethods([$method])
            ->getMock();

        $spy->method($method)->willReturnCallback(function (...$args) use (&$calls, $method, $object) {
            $calls[] = ['method' => $method, 'args' => $args];
            return call_user_func_array([$object, $method], $args);
        });

        return [$spy, $calls];
    }
}
