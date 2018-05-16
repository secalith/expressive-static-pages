<?php

declare(strict_types=1);

namespace Stock\Handler;

use Common\Handler\DataAwareInterface;
use Common\Handler\DataAwareTrait;
use Product\Service\ProductService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Stock\Form\StockProductForm;
use Stock\Service\StockService;
use Stock\Service\StockServiceAwareInterface;
use Stock\Service\StockServiceAwareTrait;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Router;
use Zend\Expressive\Template;

class StockWriteHandler implements RequestHandlerInterface, StockServiceAwareInterface, DataAwareInterface
{

    use StockServiceAwareTrait;
    use DataAwareTrait;

    private $containerName;

    private $router;

    private $template;

    private $productService;

    private $urlHelper;

    private $currentProductUid;

    public function __construct(
        Router\RouterInterface $router,
        Template\TemplateRendererInterface $template = null,
        string $containerName,
        ProductService $productService = null,
        StockService $stockService = null,
        UrlHelper $urlHelper = null
    ) {
        $this->router        = $router;
        $this->template      = $template;
        $this->containerName = $containerName;
        $this->productService = $productService;
        $this->setStockService($stockService);
        $this->urlHelper = $urlHelper;
    }

    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $this->addData('restable-stock-layout::restable-stock','layout');
        $messages = null;

        $this->currentProductUid = $request->getAttribute('product_uid');

        $requestedProductUid = $request->getAttribute('product_uid');

        $this->addData('restable-stock-layout::restable-stock','layout');
        $this->addData($this->productService->getItem($requestedProductUid),'product_data');
        $this->addData($this->getStockService()->getItem($requestedProductUid),'stock_data');
        $this->addData($this->getWriteForm(),'form');

        $data['form'] = $this->getWriteForm();

        if(strtoupper($request->getMethod())==="POST") {
            $postData = $request->getParsedBody();

            $this->getData('form')->setData($postData);

            if ($this->getData('form')->isValid()) {

                $formData = $this->getData('form')->getData();

                $rowsAffected = $this->getStockService()->addStockProduct($formData);

                if($rowsAffected['rows_affected']['product']!==0||$rowsAffected['rows_affected']['stock']) {
                    $messages['success'][] = 'Item has been updated.';
                } else {
                    $messages['info'][] = 'Data unchanged. Item has NOT been updated.';
                }
            } else {
                $messages['error'][] = 'Form seems to be invalid. Item has NOT been updated.';
            }

        } else {
            $model = new \Stock\Model\StockWriteModel([
                'fieldset_product'=>$this->getData('product_data')->toArray(),
                'fieldset_stock'=>$this->getData('stock_data')->toArray(),
            ]);

            $this->getData('form')->setData($model->toArray());
        }

        $this->addData($messages,'messages');

        return new HtmlResponse($this->template->render('stock::stock-product-write', $this->getData()));
    }

    /**
     * @return StockBarcodeForm
     */
    private function getWriteForm()
    {
        $form = new StockProductForm();
        $form->setAttribute(
            'action',
            $this->urlHelper->generate(
                'stock.product.write.post',
                ['product_uid'=>$this->currentProductUid]
            )
        );

        return $form;
    }
}
