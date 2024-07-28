<?php

declare(strict_types=1);

namespace venndev\vformoopapi\manager;

use Throwable;
use pocketmine\form\FormValidationException;
use venndev\vformoopapi\attributes\custom\VDropDown;
use venndev\vformoopapi\attributes\custom\VInput;
use venndev\vformoopapi\attributes\custom\VLabel;
use venndev\vformoopapi\attributes\custom\VSlider;
use venndev\vformoopapi\attributes\custom\VStepSlider;
use venndev\vformoopapi\attributes\custom\VToggle;
use venndev\vformoopapi\attributes\modal\VButton as VButtonModal;
use venndev\vformoopapi\attributes\normal\VButton as VButtonNormal;
use venndev\vformoopapi\utils\ImageContent;
use venndev\vformoopapi\utils\TypeContent;
use venndev\vformoopapi\utils\TypeForm;
use venndev\vformoopapi\utils\TypeValueContent;
use vennv\vapm\Async;
use vennv\vapm\FiberManager;

trait DataFormProcessor
{

    /**
     * @throws Throwable
     */
    private function processData(mixed $data): Async
    {
        return new Async(function () use ($data): mixed {
            if ($data !== null) {
                if ($this->type === TypeForm::NORMAL_FORM) {
                    if (!is_int($data)) throw new FormValidationException("Expected an integer response, got " . gettype($data));
                    $count = count($this->data[TypeContent::BUTTONS]);
                    if ($data >= $count || $data < 0) throw new FormValidationException("Button $data does not exist");
                    $data = $this->labelMap[$data] ?? null;
                }
                if ($this->type === TypeForm::MODAL_FORM && !is_bool($data)) throw new FormValidationException("Expected a boolean response, got " . gettype($data));
                if ($this->type === TypeForm::CUSTOM_FORM) {
                    if ($data !== null && !is_array($data)) throw new FormValidationException("Expected an array response, got " . gettype($data));
                    if (is_array($data)) {
                        if (count($data) !== count($this->validationMethods)) throw new FormValidationException("Expected an array response with the size " . count($this->validationMethods) . ", got " . count($data));
                        $newData = [];
                        foreach ($data as $i => $v) {
                            $validationMethod = $this->validationMethods[$i] ?? null;
                            if ($validationMethod === null) throw new FormValidationException("Invalid element " . $i);
                            if (!$validationMethod($v)) throw new FormValidationException("Invalid type given for element " . $this->labelMap[$i]);
                            $newData[$this->labelMap[$i]] = $v;
                            FiberManager::wait();
                        }
                        $data = $newData;
                    }
                }
            }
            return $data;
        });
    }

    private function processNormalForm(object $attribute): bool|null
    {
        if ($this->type === TypeForm::NORMAL_FORM && $attribute instanceof VButtonNormal) {
            $content = [TypeContent::TEXT => $attribute->text];
            if ($attribute->type !== null) {
                $content[TypeContent::IMAGE][ImageContent::TYPE] = $attribute->type;
                $content[TypeContent::IMAGE][ImageContent::DATA] = $attribute->image;
            }
            $this->data[TypeContent::BUTTONS][] = $content;
            $this->labelMap[] = $attribute->label ?? count($this->labelMap);
            return true;
        }

        return null;
    }

    private function processModalForm(object $attribute): bool|null
    {
        if ($this->type === TypeForm::MODAL_FORM && $attribute instanceof VButtonModal) {
            if ($this->data[TypeContent::BUTTON_1] === "") {
                $this->data[TypeContent::BUTTON_1] = $attribute->text;
            } elseif ($this->data[TypeContent::BUTTON_2] === "") {
                $this->data[TypeContent::BUTTON_2] = $attribute->text;
            }
            return true;
        }

        return null;
    }

    private function processCustomForm(object $attribute): bool|null
    {
        if ($this->type === TypeForm::CUSTOM_FORM) {
            $addContent = fn(array $content) => $this->data["content"][] = $content;
            if ($attribute instanceof VLabel) {
                $addContent([
                    TypeContent::TYPE => TypeValueContent::LABEL,
                    TypeContent::TEXT => $attribute->text
                ]);
                $this->labelMap[] = $attribute->label ?? count($this->labelMap);
                $this->validationMethods[] = static fn($v) => $v === null;
                return true;
            } elseif ($attribute instanceof VToggle) {
                $content = [
                    TypeContent::TYPE => TypeValueContent::TOGGLE,
                    TypeContent::TEXT => $attribute->text
                ];
                if ($attribute->default !== false) $content[TypeContent::DEFAULT] = $attribute->default;
                $addContent($content);
                $this->labelMap[] = $attribute->label ?? count($this->labelMap);
                $this->validationMethods[] = static fn($v) => is_bool($v);
                return true;
            } elseif ($attribute instanceof VInput) {
                $addContent([
                    TypeContent::TYPE => TypeValueContent::INPUT,
                    TypeContent::TEXT => $attribute->text,
                    TypeContent::PLACEHOLDER => $attribute->placeholder,
                    TypeContent::DEFAULT => $attribute->default
                ]);
                $this->labelMap[] = $attribute->label ?? count($this->labelMap);
                $this->validationMethods[] = static fn($v) => is_string($v);
                return true;
            } elseif ($attribute instanceof VDropDown) {
                $addContent([
                    TypeContent::TYPE => TypeValueContent::DROPDOWN,
                    TypeContent::TEXT => $attribute->text,
                    TypeContent::OPTIONS => $attribute->options,
                    TypeContent::DEFAULT => $attribute->default
                ]);
                $this->labelMap[] = $attribute->label ?? count($this->labelMap);
                $this->validationMethods[] = static fn($v) => $v === -1 || (is_int($v));
                return true;
            } elseif ($attribute instanceof VSlider) {
                $content = [
                    TypeContent::TYPE => TypeValueContent::SLIDER,
                    TypeContent::TEXT => $attribute->text,
                    TypeContent::MIN => $attribute->min,
                    TypeContent::MAX => $attribute->max
                ];
                if ($attribute->step !== -1) $content[TypeContent::STEP] = $attribute->step;
                if ($attribute->default !== -1) $content[TypeContent::DEFAULT] = $attribute->default;
                $addContent($content);
                $this->labelMap[] = $attribute->label ?? count($this->labelMap);
                $this->validationMethods[] = static fn($v) => (is_float($v) || is_int($v)) && $v >= $attribute->min && $v <= $attribute->max;
                return true;
            } elseif ($attribute instanceof VStepSlider) {
                $content = [
                    TypeContent::TYPE => TypeValueContent::STEP_SLIDER,
                    TypeContent::TEXT => $attribute->text,
                    TypeContent::STEPS => $attribute->steps
                ];
                if ($attribute->default !== -1) $content[TypeContent::DEFAULT] = $attribute->default;
                $addContent($content);
                $this->labelMap[] = $attribute->label ?? count($this->labelMap);
                $this->validationMethods[] = static fn($v) => $v === -1 || (is_int($v) && isset($attribute->steps[$v]));
                return true;
            }
        }
        return null;
    }

}