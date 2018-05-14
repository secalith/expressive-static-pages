<?php

declare(strict_types=1);

namespace Stock;

use Zend\Hydrator\ObjectProperty;

/**
 * The configuration provider for the App module
 *
 * @see https://docs.zendframework.com/zend-component-installer/
 */
class ConfigProvider
{
    /**
     * Returns the configuration array
     *
     * To add a bit of a structure, each section is defined in a separate
     * method which returns an array with its configuration.
     *
     */
    public function __invoke() : array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'templates'    => $this->getTemplates(),
            'app'    => $this->getApplicationConfig(),
        ];
    }

    /**
     * Returns the container dependencies
     */
    public function getDependencies() : array
    {
        return [
            'factories'  => [
                Handler\ScanBarcodeInHandler::class => Handler\ScanBarcodeInHandlerFactory::class,
                Handler\ProductListHandler::class => Handler\ProductListHandlerFactory::class,
                Handler\SearchBarcodeHandler::class => Handler\SearchBarcodeHandlerFactory::class,
                Service\StockService::class => Service\StockServiceFactory::class,
            ],
        ];
    }

    /**
     * Returns the templates configuration
     */
    public function getTemplates() : array
    {
        return [
            'paths' => [
                'stock'    => [__DIR__ . '/../templates/stock'],
                'stock-form'    => [__DIR__ . '/../templates/stock-form'],
            ],
        ];
    }

    public function getApplicationConfig()
    {
        return [
            'application' => [
                [
                    'application_id' => '2',
                    'application_name' => 'restablestock',
                ]
            ],
            'table_service' => [
                'Stock\TableService' => [
                    'gateway' => [
                        'name' => 'Stock\TableGateway',
                    ],
                ],
            ],
            'gateway' => [
                'Stock\TableGateway' => [
                    'name' => 'Stock\TableGateway',
                    'table' => [
                        'name' => 'form_request_demo',
                        'object' => Model\StockBarcodeTable::class,
                    ],
                    'adapter' => [
                        'name' => 'Application\Db\LocalAdapter',
                    ],
                    'model' => [
                        "object" => Model\StockBarcodeModel::class,
                    ],
                    'hydrator' => [
                        "object" => ObjectProperty::class,
                    ],
                ],
            ],
            'route' => [
                'stock.product.list' => [
                    'cache_response' => [
                        'enabled' => false,
                    ],
                    'module' => [
                        'stock' => [
                            'view_template_model' => [
                                'layout' => 'restablesite-layout::restable-site',
                                'template' => 'staticpages::page-homepage-2018',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

}
