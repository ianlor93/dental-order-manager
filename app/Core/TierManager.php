<?php
namespace App\Core;

class TierManager {
    // Il CSV deve essere nella stessa cartella app/Core/ di OrderManager
    private static $csvPath = __DIR__ . '/config_tiers.csv';

    public static function getTierConfig($tierId) {
        if (!file_exists(self::$csvPath)) return null;

        if (($handle = fopen(self::$csvPath, "r")) !== FALSE) {
            $headers = fgetcsv($handle, 1000, ",");
            if (!$headers) { fclose($handle); return null; }
            $headers = array_map('trim', $headers);
            
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $data = array_map('trim', $data);
                if ($data[0] === trim($tierId)) {
                    fclose($handle);
                    if (count($headers) !== count($data)) continue;
                    return (object) array_combine($headers, $data);
                }
            }
            fclose($handle);
        }
        return ($tierId !== 'L0') ? self::getTierConfig('L0') : null;
    }
}