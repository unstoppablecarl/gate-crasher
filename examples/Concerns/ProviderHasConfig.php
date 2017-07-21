<?php

namespace UnstoppableCarl\GateCrasherExamples\Concerns;

use Illuminate\Config\Repository;

trait ProviderHasConfig
{

    /**
     * Path to this providers default config file.
     * Should be set in provider using this trait.
     * @var string
     */
//    protected $configFilePath;

    /**
     * @var Repository
     */
    protected $configRepo;

    /**
     * Publish and merge provider config.
     * @param string|null $configFilePath Path to this providers default config file.
     * @return Repository
     */
    protected function registerConfig($configFilePath = null)
    {
        $filePath = $configFilePath;
        if (!$filePath && property_exists($this, 'configFilePath')) {
            $filePath = $this->configFilePath;
        }
        $fileName = basename($filePath);
        $key      = basename($fileName, '.php');

        $this->publishes([
            $filePath => config_path($fileName),
        ]);

        $this->mergeConfigFrom($filePath, $key);

        $config           = $this->app['config']->get($key);
        $this->configRepo = new Repository($config);

        return $this->configRepo;
    }

    /**
     * Getter for provider config.
     * @param string $key dot syntax key to get from config
     * @param null $default
     * @return mixed
     */
    protected function config($key, $default = null)
    {
        return $this->configRepo->get($key, $default);
    }

}