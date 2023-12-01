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
            
            //operation while loop
            $for250Price = [];
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
        
                // revenu by store
                $storeRevenue[$storeId] = ($storeRevenue[$storeId] ?? 0) + ($price / 100);

                // count of the product sale by store
                $storeSaleCount[$storeId] = ($storeSaleCount[$storeId] ?? 0) + 1;

                // count of the order by store
                $storeOrderCount[$storeId] = ($storeOrderCount[$storeId] ?? 0) + 1;

                //storeAverageOrderAmount


                // Total of revenu per product by store
                $productRevenue[$productId] = ($productRevenue[$productId] ?? 0) + ($price / 100);

                // Count of sale product per store
                $productSaleCount[$productId] = ($productSaleCount[$productId] ?? 0) + 1;
                
            }
            
            fclose($file);
            
           return [
                'topStoresByRevenue' => $this->getTopThree($storeRevenue, 'storeId', 'revenue'),
                'topStoresBySaleCount' => $this->getTopThree($storeSaleCount, 'storeId', 'count'),
                //'topStoresByAverageOrderAmount' => $this->getTopStoresByAverageOrderAmount($storeRevenue, $storeOrderCount),
                'topProductsByRevenue' => $this->getTopThree($productRevenue, 'productId', 'revenue'),
                'topProductsBySaleCount' => $this->getTopThree($productSaleCount, 'productId', 'count'),
            ];


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
    public function getTopStoresByAverageOrderAmount(array $storeRevenue, array $storeOrderCount): array
    {
        $result = [];

        foreach ($storeRevenue as $storeId => $revenue) {
            $orderCount = $storeOrderCount[$storeId] ?? 0;

            // Calculer la moyenne du montant de la commande
            $averageOrderAmount = $orderCount > 0 ? $revenue / $orderCount : 0;

            $result[] = [
                'storeId' => $storeId,
                'averageOrderAmount' => number_format($averageOrderAmount, 2, '.', ''),
            ];
        }

        // Trier par ordre décroissant de la moyenne du montant de la commande
        usort($result, function ($a, $b) {
            return $b['averageOrderAmount'] <=> $a['averageOrderAmount'];
        });

        // Garder seulement les trois premiers éléments
        $result = array_slice($result, 0, 3);

        return $result;
    }


}
