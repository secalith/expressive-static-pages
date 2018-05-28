<?php

declare(strict_types=1);

namespace Common\Handler;

use RestableAdmin\Category\Model\CategoryModel;
use Common\Handler\ApplicationConfigAwareInterface;
use Common\Handler\ApplicationConfigAwareTrait;
use Common\Handler\ApplicationFormAwareInterface;
use Common\Handler\ApplicationFormAwareTrait;
use Common\Handler\DataAwareInterface;
use Common\Handler\DataAwareTrait;
use Common\Handler\ApplicationFieldsetSaveServiceAwareInterface;
use Common\Handler\ApplicationFieldsetSaveServiceAwareTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Router;
use Zend\Expressive\Template;

class CreateHandler implements RequestHandlerInterface,
    DataAwareInterface,
    ApplicationConfigAwareInterface,
    ApplicationFormAwareInterface,
    ApplicationFieldsetSaveServiceAwareInterface
{
    use ApplicationConfigAwareTrait;
    use ApplicationFieldsetSaveServiceAwareTrait;
    use ApplicationFormAwareTrait;
    use DataAwareTrait;

    private $containerName;

    private $router;

    private $template;

    private $urlHelper;

    public function __construct(
        Router\RouterInterface $router,
        Template\TemplateRendererInterface $template = null,
        string $containerName,
        UrlHelper $urlHelper = null
    ) {
        $this->router        = $router;
        $this->template      = $template;
        $this->containerName = $containerName;
        $this->urlHelper = $urlHelper;
    }

    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $handlerConfig = $this->getData('handler_config');

        $this->addData($this->getForms(),'forms');
        $messages = null;
        $rowsAffected = null;
        $results = null;

        if(strtoupper($request->getMethod())==="POST") {
            $postData = $request->getParsedBody();
            foreach($this->getData('forms') as $formIdentifier=>$formItem) {
                $formItem->setData($postData);
                if($formItem->isValid()) {

                    $messages['info'][] = 'Form is Valid.';

                    $formData = $formItem->getData();

                    if(array_key_exists('forms',$handlerConfig)) {
                        $formsConfig = $handlerConfig['forms'];
                        // find the config for the current form
                        foreach($formsConfig as $formConfig) {

                            if($formIdentifier===$formConfig['name']) {
                                if(array_key_exists('save',$formConfig)) {
                                    foreach($formConfig['save'] as $fieldsetConfig) {
                                        if(array_key_exists('service',$fieldsetConfig)) {
                                            foreach($fieldsetConfig['service'] as $serviceConfig) {
                                                if($this->hasFieldsetService($fieldsetConfig['fieldset_name'])) {
                                                    $fieldsetService = $this->getFieldsetService($fieldsetConfig['fieldset_name']);
                                                    $formDataArray = $formData->toArray();
//                                                    var_dump($formData);
//                                                    die();

                                                    $field_change = null;

                                                    if(isset($fieldsetConfig['entity_change'])) {
                                                        foreach($fieldsetConfig['entity_change'] as $entity_change ) {
                                                            if($entity_change['source']['type'] === 'result-insert') {
                                                                if(array_key_exists($entity_change['source']['source_name'],$results)) {
                                                                    $changeResources = $results[$entity_change['source']['source_name']];
                                                                    if(is_array($changeResources['item'])) {
                                                                        $newValue = $changeResources['item'][$entity_change['source']['source_field_name']];
                                                                    } elseif(is_object($changeResources['item'])) {
                                                                        $newValue = $changeResources['item']->{$entity_change['source']['source_field_name']};
                                                                    }
                                                                    $field_change[$fieldsetConfig['fieldset_name']][] = [
                                                                        'name' => $entity_change['field_name'],
                                                                        'value' => $newValue,
                                                                    ];
                                                                }
                                                            }
                                                        }
                                                    }

                                                    if(is_array($formData->get($fieldsetConfig['fieldset_name']))) {
                                                        // assume collection
                                                        foreach($formData->get($fieldsetConfig['fieldset_name']) as $collectionItem) {
                                                            if($field_change!==null && array_key_exists($fieldsetConfig['fieldset_name'],$field_change)) {
                                                                foreach($field_change[$fieldsetConfig['fieldset_name']] as $changeFieldItem) {
                                                                    $collectionItem->{$changeFieldItem['name']} = $changeFieldItem['value'];
                                                                }
                                                            }
                                                            $results[$fieldsetConfig['fieldset_name']] = $fieldsetService->saveItem($collectionItem);
                                                        }
                                                    } else {
                                                        // assume fieldset
                                                        $fieldsetItem = $formData->get($fieldsetConfig['fieldset_name']);
                                                        if($field_change!==null && array_key_exists($fieldsetConfig['fieldset_name'],$field_change)) {
                                                            foreach($field_change[$fieldsetConfig['fieldset_name']] as $changeFieldItem) {
                                                                $fieldsetItem->{$changeFieldItem['name']} = $changeFieldItem['value'];
                                                            }
                                                        }
                                                        $results[$fieldsetConfig['fieldset_name']] = $fieldsetService->saveItem($fieldsetItem);
                                                    }

//                                                    var_dump($results[$fieldsetConfig['fieldset_name']]);

                                                    $rowsAffected[$fieldsetConfig['fieldset_name']] = $results[$fieldsetConfig['fieldset_name']]['rows_affected'];
                                                }
                                            }
                                        }
                                    }
                                }

                                break;
                            }
                        }
                    } else {
                        echo 'dupa';
                    }

                } else {
                    $messages['error'][] = 'Form seems to be invalid.';
                    $messages['error'][] = 'Data has NOT been saved.';
//                    var_dump($formItem->getMessages());
                }
            }

            if($rowsAffected!=null) {
                $messages['success'][] = 'Item has been updated.';
                $messages['success'][] = var_export($rowsAffected,true);
            } else {
                $messages['info'][] = 'Data unchanged.';
                $messages['info'][] = 'Item has NOT been updated.';
            }
        }

        $this->addData($messages,'messages');

        return new HtmlResponse($this->template->render($this->getData('template'), $this->getData()));
    }
}
