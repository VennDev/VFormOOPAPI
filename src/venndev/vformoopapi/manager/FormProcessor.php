<?php

declare(strict_types=1);

namespace venndev\vformoopapi\manager;

use Throwable;
use venndev\vformoopapi\attributes\IVAttributeForm;
use venndev\vformoopapi\attributes\VForm;
use venndev\vformoopapi\utils\ProcessDataInput;
use venndev\vformoopapi\utils\TypeContent;
use venndev\vformoopapi\utils\TypeForm;
use vennv\vapm\FiberManager;
use vennv\vapm\Promise;

trait FormProcessor
{

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getContent(): string
    {
        return $this->content;
    }

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
        $this->additionalAttribute[uniqid("methodAnonymous")] = [$attribute, $callable];
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
                        $this->data[TypeContent::TYPE] = $this->type = $attribute->type;
                        $this->data[TypeContent::TITLE] = $this->title === "" ? ProcessDataInput::processDataVResult($attribute->title) : $this->title;
                        $this->data[TypeContent::CONTENT] = $this->content === "" ? ProcessDataInput::processDataVResult($attribute->content) : $this->content;
                        if ($this->type === TypeForm::NORMAL_FORM) $this->data[TypeContent::BUTTONS] = [];
                        if ($this->type === TypeForm::MODAL_FORM) $this->data[TypeContent::BUTTON_1] = $this->data[TypeContent::BUTTON_2] = "";
                        if ($this->type === TypeForm::CUSTOM_FORM) $this->data[TypeContent::CONTENT] = [];
                    }
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