<?php
/**
 * SafeNode - Settings Helper
 * Helper simples para ler configurações da tabela safenode_settings com cache.
 */

class SafeNodeSettings
{
    private static $cache = [];

    /**
     * Obtém o valor de uma configuração.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        if (!function_exists('getSafeNodeDatabase')) {
            return $default;
        }

        $db = getSafeNodeDatabase();
        if (!$db) {
            return $default;
        }

        try {
            $stmt = $db->prepare("SELECT setting_value FROM safenode_settings WHERE setting_key = ? LIMIT 1");
            $stmt->execute([$key]);
            $value = $stmt->fetchColumn();

            if ($value === false || $value === null) {
                self::$cache[$key] = $default;
                return $default;
            }

            self::$cache[$key] = $value;
            return $value;
        } catch (PDOException $e) {
            error_log("SafeNodeSettings Error ({$key}): " . $e->getMessage());
            return $default;
        }
    }
}




