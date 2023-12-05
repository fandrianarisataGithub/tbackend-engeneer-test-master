<?php

namespace App;

class SalesDataAnalyzer
{
    public function analyze(string $fileName): array
    {
        $storeRevenue = $storeSaleCount = $storeOrderCount = $productRevenue = $storeAverageOrderAmount = $productSaleCount = [];

        try {
            $filename = __DIR__ . '/../.tmp/saleData.txt';

            if ( !file_exists($fileName) ) {
                throw new \Exception('File not found.');
            }

            $file = fopen($fileName, "r");

            if ( !$file ) {
                throw new \Exception('File open failed.');
            } 
          
            while(($line = fgets($file)) !== false){
                $fields = explode('|', $line);
                $sale = [];
                foreach($fields as $field){
                    // separation of ':' with the key (storeId, productId, ...)
                    list($key, $value) = explode(':', $field);
                    $sale[$key] = $value;
                }
                
                $storeId = $sale['storeId'];
                $productId = $sale['productId'];
                $price = $sale['price'];
                $orderId = $sale['orderId'];
        
                // revenu by store
                $storeRevenue[$storeId] = ($storeRevenue[$storeId] ?? 0) + ($price / 100);

                // count of the product sale by store
                $storeSaleCount[$storeId] = ($storeSaleCount[$storeId] ?? 0) + 1;

                // count of the order by store
                $storeOrderCount[$storeId] = ($storeOrderCount[$storeId] ?? 0) + 1;

                //AverageOrderAmount by orderId by storeId
                $storeAverageOrderAmount[$storeId][$orderId] = ($storeAverageOrderAmount[$storeId][$orderId] ?? 0) + ($price / 100);

                // Total of revenu per product by store
                $productRevenue[$productId] = ($productRevenue[$productId] ?? 0) + ($price / 100);

                // Count of sale product per store
                $productSaleCount[$productId] = ($productSaleCount[$productId] ?? 0) + 1;
                
            }
            
            fclose($file);
            
            return [
                'topStoresByRevenue' => $this->getTop($storeRevenue, 'storeId', 'revenue', 3),
                'topStoresBySaleCount' => $this->getTop($storeSaleCount, 'storeId', 'count', 3),
                'topStoresByAverageOrderAmount' => $this->getTopStoresByAverageOrderAmount($storeAverageOrderAmount),
                'topProductsByRevenue' => $this->getTop($productRevenue, 'productId', 'revenue', 3),
                'topProductsBySaleCount' => $this->getTop($productSaleCount, 'productId', 'count', 3),
            ];

        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function getTop(array $array, string $keyName, $resultValue, $count): array
    {
        $result = [];
    
        foreach ($array as $key => $value) {
            if (count($result) < $count) {
                $result[$key] = number_format($value, 2, '.', '');
            } else {
                $minValue = min($result);
                if ($value > $minValue) {
                    $minKey = array_search($minValue, $result, true);
                    unset($result[$minKey]);
                    $result[$key] = number_format($value, 2, '.', '');
                }
            }
        }
    
        arsort($result);
        $formattedResult = [];
        
        foreach ($result as $key => $value){
            $formattedResult[] = [
                $keyName => $key,
                $resultValue => $value,
            ];
        }
    
        return $formattedResult;
    }

    public function getTopStoresByAverageOrderAmount(array $storeAverageOrderAmount): array
    {
        $result = [];

        foreach ($storeAverageOrderAmount as $storeId => $orderIdData) {
            $averageOrderAmount = 0;
            $totalOrderAmount = 0;
            $orderCount = 0;

            foreach ($orderIdData as $orderId => $orderAmount) {
                $totalOrderAmount += $orderAmount;
                $orderCount++;
            }

            if ($orderCount > 0) {
                $averageOrderAmount = $totalOrderAmount / $orderCount;
            }

            $result[] = [
                'storeId' => $storeId,
                'averageOrderAmount' => number_format($averageOrderAmount, 2, '.', ''),
            ];
        }

        usort($result, function ($a, $b) {
            return $b['averageOrderAmount'] <=> $a['averageOrderAmount'];
        });

        $result = array_slice($result, 0, 3);

        return $result;
    }   

}
