<?php

declare(strict_types=1);

namespace venndev\vformoopapi\manager;

use Throwable;
use venndev\vformoopapi\attributes\IVAttributeForm;
use venndev\vformoopapi\attributes\VForm;
use venndev\vformoopapi\utils\TypeContent;
use venndev\vformoopapi\utils\TypeForm;
use venndev\vmskyblock\utils\MathUtil;
use vennv\vapm\FiberManager;
use vennv\vapm\Promise;

trait FormProcessor
{

    public function getMethods(): array
    {
        return $this->methods;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAdditionalAttribute(): array
    {
        return $this->additionalAttribute;
    }

    public function addContent(IVAttributeForm $attribute, callable $callable): void
    {
        $this->additionalAttribute[MathUtil::generateNameMethodUUID()] = [$attribute, $callable];
    }

    /**
     * @throws Throwable
     */
    private function processAttributes(): Promise
    {
        return new Promise(function ($resolve, $reject) {
            try {
                foreach ($this->attributes as $attribute) {
                    $attribute = $attribute->newInstance();
                    if ($attribute instanceof VForm) {
                        $this->type = $attribute->type;
                        $this->data[TypeContent::TYPE] = $this->type;
                        $this->data[TypeContent::TITLE] = $attribute->title;
                        $this->data[TypeContent::CONTENT] = $attribute->content;

                        if ($this->type === TypeForm::NORMAL_FORM) $this->data[TypeContent::BUTTONS] = [];
                        if ($this->type === TypeForm::MODAL_FORM) $this->data[TypeContent::BUTTON_1] = $this->data[TypeContent::BUTTON_2] = "";
                        if ($this->type === TypeForm::CUSTOM_FORM) $this->data[TypeContent::CONTENT] = [];
                    }

                    FiberManager::wait();
                }

                $resolve();
            } catch (Throwable $e) {
                $reject($e);
            }
        });
    }

    /**
     * @throws Throwable
     */
    private function processMethods(): Promise
    {
        return new Promise(function ($resolve, $reject) {
            try {
                foreach ($this->methods as $method) {
                    $label = null;
                    $isContentForm = false;
                    $attributes = $method->getAttributes();
                    $method = $method->getName();
                    foreach ($attributes as $attribute) {
                        $attribute = $attribute->newInstance();
                        $label = $attribute->label ?? null;
                        $isContentForm = $this->processNormalForm($attribute) ?? $this->processModalForm($attribute) ?? $this->processCustomForm($attribute);

                        FiberManager::wait();
                    }
                    if ($isContentForm) $label !== null ? $this->callableMethods[$label] = $method : $this->callableMethods[] = $method;

                    FiberManager::wait();
                }

                $resolve();
            } catch (Throwable $e) {
                $reject($e);
            }
        });
    }

    /**
     * @throws Throwable
     */
    private function processAdditionalAttribute(): Promise
    {
        return new Promise(function ($resolve, $reject) {
            try {
                foreach ($this->additionalAttribute as $nameCallable => $attribute) {
                    $attributeForm = $attribute[0];

                    $label = $attributeForm->label ?? null;
                    $isContentForm = $this->processNormalForm($attributeForm) ?? $this->processModalForm($attributeForm) ?? $this->processCustomForm($attributeForm);
                    if ($isContentForm) $label !== null ? $this->callableMethods[$label] = $nameCallable : $this->callableMethods[count($this->callableMethods)] = $nameCallable;

                    FiberManager::wait();
                }

                $resolve();
            } catch (Throwable $e) {
                $reject($e);
            }
        });
    }

}