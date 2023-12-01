<?php

namespace App\Tests;

use App\SalesDataAnalyzer;
use PHPUnit\Framework\TestCase;

class SalesDataAnalyzerTest extends TestCase
{
    public function testAnalyze()
    {
        $dataFileName = __DIR__ . '/../.tmp/salesData.txt';

        $startPmu = memory_get_peak_usage(false);

        if (!file_exists($dataFileName) || !self::isDataFileCheckSumOk($dataFileName)) {
            self::generateDataFile($dataFileName);

            assert(self::isDataFileCheckSumOk($dataFileName));
            assert(memory_get_peak_usage(false) - $startPmu < 64 * 1024);
        }

        $startTime = microtime(true);
        $result = (new SalesDataAnalyzer())->analyze($dataFileName);
        $endTime = microtime(true);
        $endPmu = memory_get_peak_usage(false);

        $this->assertEquals(
            [
                'topStoresByRevenue' => [
                    [
                        'storeId' => 251,
                        'revenue' => 979042.22,
                    ],
                    [
                        'storeId' => 250,
                        'revenue' => 978319.99,
                    ],
                    [
                        'storeId' => 240,
                        'revenue' => 978034.98,
                    ],
                ],
                'topStoresBySaleCount' => [
                    [
                        'storeId' => 251,
                        'count' => 6757,
                    ],
                    [
                        'storeId' => 240,
                        'count' => 6755,
                    ],
                    [
                        'storeId' => 250,
                        'count' => 6739,
                    ],
                ],
                'topStoresByAverageOrderAmount' => [
                    [
                        'storeId' => 252,
                        'averageOrderAmount' => 2797.96
                    ],
                    [
                        'storeId' => 250,
                        'averageOrderAmount' => 2755.83
                    ],
                    [
                        'storeId' => 253,
                        'averageOrderAmount' => 2717.83
                    ],
                ],
                'topProductsByRevenue' => [
                    [
                        'productId' => 507,
                        'revenue' => 1115706.41
                    ],
                    [
                        'productId' => 498,
                        'revenue' => 1022029.17
                    ],
                    [
                        'productId' => 457,
                        'revenue' => 1007579.88
                    ],
                ],
                'topProductsBySaleCount' => [
                    [
                        'productId' => 503,
                        'count' => 5132
                    ],
                    [
                        'productId' => 498,
                        'count' => 5087
                    ],
                    [
                        'productId' => 492,
                        'count' => 5076
                    ],
                ]
            ],
            $result
        );

        $megaSpinLimit = 500;
        $oneMegaSpinsTime = self::get1MSpinsTime();

        $this->assertLessThan(
            $megaSpinLimit,
            ($endTime - $startTime) / $oneMegaSpinsTime,
            sprintf(
                'Time consumption must be lower than %4.2fs',
                $megaSpinLimit * $oneMegaSpinsTime
            )
        );

        $megaByteLimit = 10;
        $this->assertLessThan(
            $megaByteLimit,
            ($endPmu - $startPmu) / (1024 * 1024),
            sprintf('Memory consumption must be lower than %dMB', $megaByteLimit)
        );
    }

    private static function isDataFileCheckSumOk(string $fileName): bool
    {
        return md5_file($fileName) === '4c298365f7c5c6c82058c736d0849249';
    }

    private static function generateDataFile(string $fileName)
    {
        $startMemoryUsage = memory_get_usage(false);
        mt_srand(0);

        $productPrices = [];
        for ($i = 0; $i < 1500; $i++) {
            $productPrices[$i] = self::getRandomIntWithBoundedGaussDist(150 * 100, 40 * 100, 1 * 100, 1000 * 100);
        }

        $fp = fopen($fileName, 'w');

        for ($i = 0; $i < 1_000_000; $i++) {
            $storeId = self::getRandomIntWithBoundedGaussDist(250, 60, 0, 500);
            $productId = self::getRandomIntWithBoundedGaussDist(500, 80, 0, count($productPrices) - 1);
            $price = $productPrices[$productId];
            $clientId = self::getRandomIntWithBoundedGaussDist($storeId + 250, 60, $storeId, $storeId + 500);
            $orderId = self::getRandomIntWithBoundedGaussDist($clientId + 50, 15, $clientId, $clientId + 100);

            $record = [
                'storeId' => $storeId,
                'productId' => $productId,
                'price' => $price,
                'clientId' => $clientId,
                'orderId' => $orderId,
            ];

            fwrite($fp, implode('|', array_map(
                fn($v, $k) => "$k:$v",
                $record,
                array_keys($record)
            )));

            fwrite($fp, "\n");
        }

        fclose($fp);
        $productPrices = null;

        assert(memory_get_usage(false) - $startMemoryUsage < 4 * 1024);
    }

    private static function get1MSpinsTime(): float
    {
        $startTime = microtime(true);

        for ($i = 0; $i < 1_000_000; $i++) {
            // noop, just spinning PHP interpreter
        }

        return microtime(true) - $startTime;
    }

    private static function getRandomIntWithBoundedGaussDist(
        int $mean,
        int $stdDev,
        int $min,
        int $max
    ): int {
        return (int)self::getRandomFloatWithBoundedGaussDist($mean, $stdDev, $min, $max);
    }

    private static function getRandomFloatWithBoundedGaussDist(
        float $mean,
        float $stdDev,
        float $min,
        float $max
    ): float {
        \assert($min < $max);

        \assert($min < $mean);

        \assert($mean < $max);

        $i = 0;
        while (true) {
            ++$i;
            $n = self::getRandomFloatWithGaussDist($mean, $stdDev);

            if ($min <= $n && $n <= $max) {
                return $n;
            }

            if ($i > 15) {
                throw new \RuntimeException(
                    \sprintf(
                        'Cannot generate a random float within [%f, %f] with mean=%f & std_dev=%f after %d iterations',
                        $min,
                        $max,
                        $mean,
                        $stdDev,
                        $i,
                    ),
                );
            }
        }
    }

    private static function getRandomFloatWithGaussDist(
        float $mean,
        float $stdDev
    ): float {
        $x = self::getRandomFloat();
        $y = self::getRandomFloat();
        $u = \sqrt(-2 * \log($x)) * \cos(2 * \pi() * $y);

        return $u * $stdDev + $mean;
    }

    private static function getRandomFloat(): float
    {
        return (float)\mt_rand() / (float)\mt_getrandmax();
    }
}
