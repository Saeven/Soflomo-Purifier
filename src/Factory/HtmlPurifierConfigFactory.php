<?php
/**
 * @license See the file LICENSE for copying permission.
 */

namespace Soflomo\Purifier\Factory;

use HTMLPurifier_Config;
use RuntimeException;
use Zend\ServiceManager\ServiceLocatorInterface;

class HtmlPurifierConfigFactory
{
    public function __invoke(ServiceLocatorInterface $serviceLocator)
    {
        $configService = $serviceLocator->get('config');
        $moduleConfig  = isset($configService['soflomo_purifier']) ? $configService['soflomo_purifier'] : [];

        if ($moduleConfig['standalone']) {
            if (! file_exists($moduleConfig['standalone_path'])) {
                throw new RuntimeException('Could not find standalone purifier file');
            }

            include_once $moduleConfig['standalone_path'];
        }

        $config      = isset($moduleConfig['config']) ? $moduleConfig['config'] : [ ];
        $definitions = isset($moduleConfig['definitions']) ? $moduleConfig['definitions'] : [ ];

        if (isset($config['definitions'])) {
            $definitions  = $config['definitions'];
            unset($config['definitions']);
        }

        $purifierConfig = self::createConfig($config, $definitions);

        return $purifierConfig;
    }

    /**
     * @param HTMLPurifier_Config $purifierConfig
     * @param array               $definitions
     *
     * @throws \HTMLPurifier_Exception
     *
     * @return HTMLPurifier_Config
     */
    public static function createConfig(array $config, array $definitions = [])
    {
        $purifierConfig = HTMLPurifier_Config::create($config);

        foreach ($definitions as $type => $methods) {
            $definition = $purifierConfig->getDefinition($type, true, true);

            if (! $definition) {
                // definition is cached, skip iteration
                continue;
            }

            foreach ($methods as $method => $invocations) {
                $invocations = self::convertSingleInvocationToArray($invocations);
                foreach ($invocations as $args) {
                    call_user_func_array([ $definition, $method ], $args);
                }
            }
        }

        return $purifierConfig;
    }


    /**
     * @param array $invocations
     *
     * @return array[]
     */
    private static function convertSingleInvocationToArray(array $invocations)
    {
        if (count($invocations) === 3) {
            $invocations = [ $invocations ];
        }

        return $invocations;
    }
}
