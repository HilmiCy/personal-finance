<?php

/**
 * FinancialAnalytics Class
 * Specialized class for handling Machine Learning and Statistical calculations
 */
class FinancialAnalytics {

    /**
     * Simple Linear Regression using Least Squares Method
     * Formula: y = mx + c
     * 
     * @param array $data_points Array of values (Y-axis)
     * @return array [slope, intercept, prediction, trend]
     */
    public static function calculateLinearRegression($data_points) {
        $n = count($data_points);
        
        // Default return if not enough data
        if ($n < 2) {
            $avg = $n > 0 ? array_sum($data_points) / $n : 0;
            return [
                'slope' => 0,
                'intercept' => $avg,
                'prediction' => $avg,
                'trend' => 'stable'
            ];
        }

        // X is the time index (1, 2, 3...)
        // Y is the value (amount)
        $x = range(1, $n);
        $y = $data_points;

        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = 0;
        $sumXX = 0;

        for ($i = 0; $i < $n; $i++) {
            $sumXY += ($x[$i] * $y[$i]);
            $sumXX += ($x[$i] * $x[$i]);
        }

        // Calculate Slope (m)
        // m = (nΣxy - ΣxΣy) / (nΣx² - (Σx)²)
        $denominator = ($n * $sumXX - ($sumX * $sumX));
        
        if ($denominator == 0) {
            $slope = 0;
            $intercept = $sumY / $n;
        } else {
            $slope = ($n * $sumXY - ($sumX * $sumY)) / $denominator;
            
            // Calculate Intercept (c)
            // c = (Σy - mΣx) / n
            $intercept = ($sumY - ($slope * $sumX)) / $n;
        }

        // Predict next value (X = n + 1)
        $prediction = ($slope * ($n + 1)) + $intercept;

        // Determine Trend
        $trend = 'stable';
        if ($slope > 1000) $trend = 'up';
        if ($slope < -1000) $trend = 'down';

        return [
            'slope' => $slope,
            'intercept' => $intercept,
            'prediction' => max(0, $prediction),
            'trend' => $trend
        ];
    }
    
    /**
     * Get automated saving advice based on category and current trend
     */
    public static function getSavingAdvice($category_name, $amount) {
        $category_name = strtolower($category_name);
        
        $advice_map = [
            'makan' => 'Coba kurangi makan di luar atau beralih ke memasak sendiri di rumah. Anda bisa menghemat hingga 40%.',
            'belanja' => 'Bedakan antara kebutuhan dan keinginan. Gunakan aturan 24 jam sebelum memutuskan membeli barang non-pokok.',
            'transportasi' => 'Pertimbangkan penggunaan transportasi umum atau carpooling jika memungkinkan untuk menekan biaya bahan bakar.',
            'hiburan' => 'Cari alternatif hiburan gratis seperti taman kota atau langganan streaming bersama untuk membagi biaya.',
            'listrik' => 'Gunakan perangkat elektronik hemat energi dan pastikan mematikan lampu saat tidak digunakan.',
            'pulsa' => 'Evaluasi paket data Anda. Kadang beralih ke paket bulanan atau menggunakan Wi-Fi lebih hemat.',
            'kesehatan' => 'Biaya kesehatan adalah investasi, namun tetap pastikan Anda memiliki asuransi untuk proteksi biaya besar.',
            'pendidikan' => 'Alokasikan dana pendidikan secara rutin dalam instrumen investasi yang aman.'
        ];

        foreach ($advice_map as $key => $advice) {
            if (strpos($category_name, $key) !== false) {
                return $advice;
            }
        }

        return "Analisis kategori ini dan cari celah penghematan sebesar 10% setiap bulannya.";
    }
}
