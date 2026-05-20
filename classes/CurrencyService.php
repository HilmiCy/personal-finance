<?php

class CurrencyService {
    private static $cache_file = __DIR__ . '/../cache/exchange_rates.json';
    private static $cache_time = 900; // 15 minutes cache

    /**
     * Get exchange rates with IDR as base
     * Attempts multiple sources for robustness (fawazahmed0 API first, then exchangerate-api)
     */
    public static function getExchangeRates() {
        if (!file_exists(dirname(self::$cache_file))) {
            mkdir(dirname(self::$cache_file), 0777, true);
        }

        if (file_exists(self::$cache_file) && (time() - filemtime(self::$cache_file)) < self::$cache_time) {
            return json_decode(file_get_contents(self::$cache_file), true);
        }

        $rates = null;

        // --- SOURCE 1: fawazahmed0 API (CDN based, high availability, includes Crypto & XAU) ---
        $url1 = "https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/usd.json";
        try {
            $response = @file_get_contents($url1);
            if ($response) {
                $data = json_decode($response, true);
                if (isset($data['usd']) && isset($data['usd']['idr'])) {
                    $usd_to_idr = $data['usd']['idr'];
                    $rates = [];
                    foreach ($data['usd'] as $currency => $usd_to_target) {
                        $rates[strtoupper($currency)] = $usd_to_target / $usd_to_idr;
                    }
                }
            }
        } catch (Exception $e) {
            error_log("CurrencyService Source 1 Error: " . $e->getMessage());
        }

        // --- SOURCE 2: exchangerate-api.com (Fallback for Fiat) ---
        if (!$rates) {
            $url2 = "https://api.exchangerate-api.com/v4/latest/USD";
            try {
                $response = @file_get_contents($url2);
                if ($response) {
                    $data = json_decode($response, true);
                    if (isset($data['rates']) && isset($data['rates']['IDR'])) {
                        $usd_to_idr = $data['rates']['IDR'];
                        $rates = [];
                        foreach ($data['rates'] as $currency => $usd_to_target) {
                            $rates[strtoupper($currency)] = $usd_to_target / $usd_to_idr;
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("CurrencyService Source 2 Error: " . $e->getMessage());
            }
        }

        if ($rates) {
            // --- ADDITIONAL SOURCE: Yahoo Finance for IHSG (^JKSE) & DXY (DX-Y.NYB) ---
            try {
                $opts = [
                    "http" => [
                        "method" => "GET",
                        "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36\r\n"
                    ]
                ];
                $context = stream_context_create($opts);
                
                // Fetch IHSG
                $ihsg_url = "https://query1.finance.yahoo.com/v8/finance/chart/^JKSE";
                $ihsg_response = @file_get_contents($ihsg_url, false, $context);
                if ($ihsg_response) {
                    $ihsg_data = json_decode($ihsg_response, true);
                    if (isset($ihsg_data['chart']['result'][0]['meta']['regularMarketPrice'])) {
                        $rates['IHSG'] = $ihsg_data['chart']['result'][0]['meta']['regularMarketPrice'];
                        $rates['IHSG_PREV'] = $ihsg_data['chart']['result'][0]['meta']['previousClose'] ?? $rates['IHSG'];
                    }
                }

                // Fetch DXY
                $dxy_url = "https://query1.finance.yahoo.com/v8/finance/chart/DX-Y.NYB";
                $dxy_response = @file_get_contents($dxy_url, false, $context);
                if ($dxy_response) {
                    $dxy_data = json_decode($dxy_response, true);
                    if (isset($dxy_data['chart']['result'][0]['meta']['regularMarketPrice'])) {
                        $rates['DXY'] = $dxy_data['chart']['result'][0]['meta']['regularMarketPrice'];
                        $rates['DXY_PREV'] = $dxy_data['chart']['result'][0]['meta']['previousClose'] ?? $rates['DXY'];
                    }
                }
            } catch (Exception $e) {
                error_log("CurrencyService Market Data Error: " . $e->getMessage());
            }

            // --- ADDITIONAL SOURCE: CryptoCompare (Specific for TAO if missing) ---
            if (!isset($rates['TAO'])) {
                try {
                    $tao_url = "https://min-api.cryptocompare.com/data/price?fsym=TAO&tsyms=USD,IDR";
                    $tao_response = @file_get_contents($tao_url);
                    if ($tao_response) {
                        $tao_data = json_decode($tao_response, true);
                        if (isset($tao_data['IDR']) && $tao_data['IDR'] > 0) {
                            $rates['TAO'] = 1 / $tao_data['IDR'];
                        }
                    }
                } catch (Exception $e) {
                    error_log("CurrencyService TAO Error: " . $e->getMessage());
                }
            }

            file_put_contents(self::$cache_file, json_encode($rates));
            return $rates;
        }

        // Fallback rates if all APIs fail (approximate for May 2026)
        return [
            'IDR' => 1,
            'USD' => 0.000056,
            'EUR' => 0.000052,
            'JPY' => 0.0088,
            'SGD' => 0.000076,
            'BTC' => 0.0000000006, // Hypothetical BTC rate
            'ETH' => 0.00000001
        ];
    }

    /**
     * Convert amount to IDR
     */
    public static function convertToIDR($amount, $from_currency) {
        if ($from_currency === 'IDR') return $amount;
        
        $rates = self::getExchangeRates();
        if (isset($rates[$from_currency]) && $rates[$from_currency] > 0) {
            // Rate is 1 IDR = X Foreign
            // To get IDR: Amount / Rate
            return $amount / $rates[$from_currency];
        }
        
        return $amount; // Fallback to same amount
    }

    /**
     * Convert from IDR to Foreign
     */
    public static function convertFromIDR($amount, $to_currency) {
        if ($to_currency === 'IDR') return $amount;
        
        $rates = self::getExchangeRates();
        if (isset($rates[$to_currency])) {
            return $amount * $rates[$to_currency];
        }
        
        return $amount;
    }
}
