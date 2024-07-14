<?php

namespace venndev\vformoopapi\manager;

use ReflectionAttribute;
use ReflectionMethod;
use venndev\vformoopapi\attributes\IVAttributeForm;
use venndev\vformoopapi\utils\TypeForm;

/**
 * Trait DataForm
 * @package venndev\vformoopapi\manager
 *
 * This is a trait that contains the properties of the DataForm class
 * You need use it before use classes like FormProcessor, DataFormProcessor, etc
 */
trait DataForm
{

    private string $type = TypeForm::NORMAL_FORM;
    private array $data = [];
    private array $labelMap = [];
    private array $validationMethods = [];

    private array $callableMethods = [];

    /**
     * @var array<ReflectionMethod>
     */
    private array $methods;

    /**
     * @var array<ReflectionAttribute>
     */
    private array $attributes;

    /**
     * @var array<IVAttributeForm, callable>
     */
    private array $additionalAttribute = [];

}