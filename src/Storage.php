<?php
/**
 * JSON File Storage Engine
 * All data stored as flat JSON files. No MySQL required.
 */
class Storage
{
    private static array $cache = [];

    /**
     * Read entire JSON file, return as associative array
     */
    public static function read(string $name): array
    {
        if (isset(self::$cache[$name])) {
            return self::$cache[$name];
        }

        $file = self::path($name);
        if (!file_exists($file)) {
            return [];
        }

        $json = file_get_contents($file);
        if ($json === false || trim($json) === '') {
            return [];
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            return [];
        }

        self::$cache[$name] = $data;
        return $data;
    }

    /**
     * Write array to JSON file
     */
    public static function write(string $name, array $data): bool
    {
        self::$cache[$name] = $data;
        $file = self::path($name);
        $dir = dirname($file);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if ($json === false) {
            return false;
        }

        $result = file_put_contents($file, $json, LOCK_EX);
        return $result !== false;
    }

    /**
     * Get all items with optional key mapping and filtering
     */
    public static function all(string $name, ?callable $filter = null): array
    {
        $data = self::read($name);
        if ($filter === null) {
            return $data;
        }
        return array_filter($data, $filter);
    }

    /**
     * Get single item by ID
     */
    public static function get(string $name, string $id): ?array
    {
        $data = self::read($name);
        return $data[$id] ?? null;
    }

    /**
     * Insert or update an item
     */
    public static function save(string $name, string $id, array $item): bool
    {
        $data = self::read($name);
        $item['id'] = $id;
        $item['updated_at'] = date('c');
        if (!isset($item['created_at'])) {
            $existing = $data[$id] ?? null;
            $item['created_at'] = $existing['created_at'] ?? date('c');
        }
        $data[$id] = $item;
        return self::write($name, $data);
    }

    /**
     * Delete an item by ID
     */
    public static function delete(string $name, string $id): bool
    {
        $data = self::read($name);
        if (!isset($data[$id])) {
            return false;
        }
        unset($data[$id]);
        return self::write($name, $data);
    }

    /**
     * Generate a new unique ID
     */
    public static function newId(string $prefix = ''): string
    {
        return $prefix . bin2hex(random_bytes(8));
    }

    /**
     * Get file path for a data store
     */
    private static function path(string $name): string
    {
        return DATA_PATH . '/' . $name . '.json';
    }

    /**
     * Clear cache (useful for testing)
     */
    public static function clearCache(): void
    {
        self::$cache = [];
    }
}
