<?php

declare(strict_types=1);

namespace RestableAdmin\Contact\Form;

use Zend\Form\Form as Form;
use Zend\Hydrator\ClassMethods;
use Zend\InputFilter\InputFilter;

class ContactWriteForm extends Form
{
    public function __construct($name = 'form_create', $options = array())
    {
        parent::__construct($name,$options);

        $this
            ->setAttribute('method', 'post')
            ->setHydrator(new ClassMethods(true))
            ->setInputFilter($this->addInputFilter())
        ;

        $this->addElements($options);

        $this->addInputFilter();

    }

    protected function addElements($options=null)
    {

        $this->add([
            'type' => 'Zend\Form\Element\Hidden',
            'name' => 'application_id',
        ], ['priority'=>10]);

        $this->add(array(
            'name' => 'form_create',
            'type' => \RestableAdmin\Contact\Form\Fieldset\ContactWriteFieldset::class,
            'options' => array(
                'use_as_base_fieldset' => true
            )
        ));

        $this->add([
            'type' => 'Zend\Form\Element\Csrf',
            'name' => 'csrf',
        ], ['priority'=>60]);

        $this->add([
            'name' => 'submit',
            'attributes' => [
                'type' => 'submit',
                'value' => 'Submit',
                'class' => 'btn btn-success ',
            ],
        ], ['priority'=>-100]);
    }

    private function addInputFilter()
    {

        $inputFilter = new InputFilter();
        $this->setInputFilter($inputFilter);

        $inputFilter->add([
            'name'     => 'application_id',
            'required' => false,
            'filters' => [
                ['name' => 'StringTrim'],
                ['name' => 'StripTags'],
                ['name' => 'StripNewlines'],
            ],
            'validators' => [
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'min' => 1,
                        'max' => 64,
                        'encoding' => 'UTF-8',
                    ],
                ],
            ],
        ]);

        return $inputFilter;
    }
}
