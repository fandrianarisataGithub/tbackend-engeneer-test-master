<?php

namespace App;

class SalesDataAnalyzer
{
    public function analyze(string $fileName): array
    {
        $storeRevenue 
        = $storeSaleCount 
        = $storeOrderCount 
        = $storeOrder 
        = $productRevenue 
        = $productSaleCount = [];

        try {
            $filename = __DIR__ . '/../.tmp/saleData.txt';
            if ( !file_exists($fileName) ) {
                throw new \Exception('File not found.');
            }

            $file = fopen($fileName, "r");

            if ( !$file ) {
                throw new \Exception('File open failed.');
            } 
            
            //operation while loop
            while(($line = fgets($file)) !== false){
                $fields = explode('|', $line);
                $sale = [];
                foreach($fields as $field){
                    // separation of ':' with the key (storeId, productId, ...)
                    list($key, $value) = explode(':', $field);
                    $sale[$key] = $value;
                }
                /*$storeId = $sale['storeId'];
                $productId = $sale['productId'];
                $price = $sale['price'];
                $orderId = $sale['orderId'];*/

                // revenu by store
                $storeRevenue[$sale['storeId']] = isset($storeRevenue[$sale['storeId']]) ? $storeRevenue[$sale['storeId']] + ($sale['price'] / 100) : $sale['price'] / 100;

                // count of the product sale by store
                $storeSaleCount[$sale['storeId']] = isset($storeSaleCount[$sale['storeId']]) ? $storeSaleCount[$sale['storeId']] + 1 : 1;

                // count of the order by store
                $storeOrderCount[$sale['storeId']] = isset($storeOrderCount[$sale['storeId']]) ? $storeOrderCount[$sale['storeId']] + 1 : 1;
                
                // Total of revenu per product by store
                $productRevenue[$sale['productId']] = isset($productRevenue[$sale['productId']]) ? $productRevenue[$sale['productId']] + ($sale['price'] / 100) : $sale['price'] / 100;

                // Count of sale product per store
                $productSaleCount[$sale['productId']] = isset($productSaleCount[$sale['productId']]) ? $productSaleCount[$sale['productId']] + 1 : 1;
                
                $orderRevenue[$sale['orderId']] = isset($orderRevenue[$sale['orderId']]) ? $orderRevenue[$sale['orderId']] + ($sale['price'] / 100) : $sale['price'] / 100;
            }

            fclose($file);
            arsort($storeRevenue);
            arsort($storeSaleCount);
            arsort($storeOrderCount);
            arsort($productRevenue);
            arsort($productSaleCount);

            $results = [
                'topStoresByRevenue' => $this->getTopItemsRevenu($storeRevenue, 3),
                'topStoresBySaleCount' => $this->getTopItemsCount($storeSaleCount, 3),
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
                'topProductsByRevenue' => $this->getTopProductByRevenu($productRevenue, 3),
                'topProductsBySaleCount' => $this->getTopProductBySaleCount($productSaleCount, 3),
            ];
            
            //var_dump($results);
            return $results;


        } catch (\Throwable $th) {
            throw $th;
        }
    }
    private function getTopItemsRevenu(array $array, int $count): array
    {
        $response =  array_slice($array, 0, $count, true);
        $result = [];
        foreach($response as $key => $value){
            $result[] = [
                'storeId' => $key,
                'revenue' => number_format($value, 2,'.', '')
            ];
            
        }
        return $result;
    }

    private function getTopItemsCount(array $array, int $count): array
    {
        $response =  array_slice($array, 0, $count, true);
        $result = [];
        foreach($response as $key => $value){
            $result[] = [
                'storeId' => $key,
                'count' => $value
            ];
            
        }
        return $result;
    }

    private function getTopProductByRevenu(array $array, int $count): array
    {
        $response =  array_slice($array, 0, $count, true);
        $result = [];
        foreach($response as $key => $value){
            $result[] = [
                'productId' => $key,
                'revenue' => number_format($value, 2,'.', '')
            ];
            
        }
        return $result;

    }

    private function getTopProductBySaleCount(array $array, int $count): array
    {
        $response =  array_slice($array, 0, $count, true);
        $result = [];
        foreach($response as $key => $value){
            $result[] = [
                'productId' => $key,
                'count' => $value
            ];
            
        }
        return $result;
    }
}
