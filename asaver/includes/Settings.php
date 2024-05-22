<?php

class Settings
{
    private static $settingsFile = __DIR__ . '/../settings.json';
    private static $instances = [];

    protected function __construct()
    {
    }

    protected function __clone()
    {
    }

    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize a singleton.");
    }

    public static function getInstance(): Settings
    {
        $cls = static::class;
        if (!isset(self::$instances[$cls])) {
            self::$instances[$cls] = new static();
        }

        return self::$instances[$cls];
    }

    /**
     * Finally, any singleton should define some business logic, which can be
     * executed on its instance.
     */
    public function getSettings()
    {
        $data = file_get_contents(self::$settingsFile);
        $data = json_decode($data, true);
        return $data;
    }
}