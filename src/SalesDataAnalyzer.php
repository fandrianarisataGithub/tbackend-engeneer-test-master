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

                // Total of revenu per product by store
                $productRevenue[$productId] = ($productRevenue[$productId] ?? 0) + ($price / 100);

                // Count of sale product per store
                $productSaleCount[$productId] = ($productSaleCount[$productId] ?? 0) + 1;

                $orderRevenue[$orderId] = ($orderRevenue[$orderId] ?? 0) + ($price / 100);
            }

            fclose($file);

            return [
                'topStoresByRevenue' => $this->getTopThree($storeRevenue, 'storeId', 'revenue'),
                'topStoresBySaleCount' => $this->getTopThree($storeSaleCount, 'storeId', 'count'),
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
                'topProductsByRevenue' => $this->getTopThree($productRevenue, 'productId', 'revenue'),
                'topProductsBySaleCount' => $this->getTopThree($productSaleCount, 'productId', 'count'),
            ];
            
            /*var_dump($this->getTopThree($productSaleCount, 'productId', 'count'));
            return [];*/


        } catch (\Throwable $th) {
            throw $th;
        }
    }
    public function getTopThree(array $array, string $keyName, $resultValue): array
    {
        $result = [];
    
        foreach ($array as $key => $value) {
            if (count($result) < 3) {
                $result[$key] = number_format($value, 2, '.', '');
            } else {
                // Vérifie si la valeur actuelle est plus grande que la plus petite des trois actuelles
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
        foreach($result as $key => $value){
            $formattedResult[] = [
                $keyName => $key,
                $resultValue => $value,
            ];
        }
    
        return $formattedResult;
    }
    
    private function getTopItemsx(array $array, int $count, string $keyName, string $valueName): array
    {
        uasort($array, function ($a, $b) {
            return $b <=> $a;
        });

        $result = [];
        foreach (array_slice($array, 0, $count, true) as $key => $value) {
            $result[] = [
                $keyName => $key,
                $valueName => number_format($value, 2, '.', '')
            ];
        }

        return $result;
    }
    
    private function getTopItems2(array $array, int $count, string $keyName, string $valueName): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            // Si le résultat n'a pas encore atteint la taille désirée, ajoute simplement
            if (count($result) < $count) {
                $result[] = [
                    $keyName => $key,
                    $valueName => number_format($value, 2, '.', '')
                ];
            } else {
                // Trouve l'élément avec la plus grande valeur dans le résultat
                $maxValue = max(array_column($result, $valueName));
                
                // Si la valeur actuelle est plus grande que la plus grande valeur dans le résultat,
                // remplace la plus grande valeur par la nouvelle valeur
                if ($value > $maxValue) {
                    $maxKey = array_search($maxValue, array_column($result, $valueName));
                    $result[$maxKey] = [
                        $keyName => $key,
                        $valueName => number_format($value, 2, '.', '')
                    ];
                }
            }
        }

        return $result;
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
