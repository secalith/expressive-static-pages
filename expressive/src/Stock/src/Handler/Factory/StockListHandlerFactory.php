<?php

declare(strict_types=1);

namespace Stock\Handler\Factory;

use Product\Service\ProductService;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Stock\Handler\StockListHandler;
use Stock\Service\StockService;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Router\RouterInterface;
use Zend\Expressive\Template\TemplateRendererInterface;
use Zend\Paginator\Paginator;

class StockListHandlerFactory
{
    public function __invoke(ContainerInterface $container) : RequestHandlerInterface
    {
        $router   = $container->get(RouterInterface::class);
        $template = $container->has(TemplateRendererInterface::class)
            ? $container->get(TemplateRendererInterface::class)
            : null;

        $urlHelper = $container->get(UrlHelper::class);
        $productService = $container->get(ProductService::class);
        $stockService = $container->get(StockService::class);

        $tableGateway = $stockService->getStockTable()->getTableGateway();
        $sqlSelect = $tableGateway->getSql()->select();
        $sqlSelect->columns(array('stock_uid','product_qty','stock_status'));
        $sqlSelect->join('product', 'product.product_uid = stock.product_uid', array('name','price','description_short','unit','product_uid'), 'left');
        $sqlSelect->join('stock_status', 'stock_status.stock_uid = stock.stock_uid', array('status_code'), 'left');

        $paginator = new Paginator(
            new \Zend\Paginator\Adapter\DbSelect(
                $sqlSelect,
                $tableGateway->getAdapter(),
                $tableGateway->getResultSetPrototype()
            )
        );

        return new StockListHandler(
            $router,
            $template,
            get_class($container),
            $productService,
            $stockService,
            $urlHelper,
            $paginator
        );
    }
}
