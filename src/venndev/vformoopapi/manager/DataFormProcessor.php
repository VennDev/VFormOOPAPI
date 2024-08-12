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
use venndev\vformoopapi\utils\ProcessDataInput;
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
            $text = ProcessDataInput::processDataVResult($attribute->text);
            $type = $attribute->type;
            $image = ProcessDataInput::processDataVResult($attribute->image);
            $label = ProcessDataInput::processDataVResult($attribute->label);
            $content = [TypeContent::TEXT => $text];
            if ($type !== null) {
                $content[TypeContent::IMAGE][ImageContent::TYPE] = $type;
                $content[TypeContent::IMAGE][ImageContent::DATA] = $image;
            }
            $this->data[TypeContent::BUTTONS][] = $content;
            $this->labelMap[] = $label ?? count($this->labelMap);
            return true;
        }
        return null;
    }

    private function processModalForm(object $attribute): bool|null
    {
        if ($this->type === TypeForm::MODAL_FORM && $attribute instanceof VButtonModal) {
            $text = ProcessDataInput::processDataVResult($attribute->text);
            if ($this->data[TypeContent::BUTTON_1] === "") {
                $this->data[TypeContent::BUTTON_1] = $text;
            } elseif ($this->data[TypeContent::BUTTON_2] === "") {
                $this->data[TypeContent::BUTTON_2] = $text;
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
                $text = ProcessDataInput::processDataVResult($attribute->text);
                $label = ProcessDataInput::processDataVResult($attribute->label);
                $addContent([
                    TypeContent::TYPE => TypeValueContent::LABEL,
                    TypeContent::TEXT => $text
                ]);
                $this->labelMap[] = $label ?? count($this->labelMap);
                $this->validationMethods[] = static fn($v) => $v === null;
                return true;
            } elseif ($attribute instanceof VToggle) {
                $text = ProcessDataInput::processDataVResult($attribute->text);
                $default = ProcessDataInput::processDataVResult($attribute->default);
                $label = ProcessDataInput::processDataVResult($attribute->label);
                $content = [
                    TypeContent::TYPE => TypeValueContent::TOGGLE,
                    TypeContent::TEXT => $text
                ];
                if ($attribute->default !== false) $content[TypeContent::DEFAULT] = $default;
                $addContent($content);
                $this->labelMap[] = $label ?? count($this->labelMap);
                $this->validationMethods[] = static fn($v) => is_bool($v);
                return true;
            } elseif ($attribute instanceof VInput) {
                $text = ProcessDataInput::processDataVResult($attribute->text);
                $placeholder = ProcessDataInput::processDataVResult($attribute->placeholder);
                $default = ProcessDataInput::processDataVResult($attribute->default);
                $label = ProcessDataInput::processDataVResult($attribute->label);
                $addContent([
                    TypeContent::TYPE => TypeValueContent::INPUT,
                    TypeContent::TEXT => $text,
                    TypeContent::PLACEHOLDER => $placeholder,
                    TypeContent::DEFAULT => $default
                ]);
                $this->labelMap[] = $label ?? count($this->labelMap);
                $this->validationMethods[] = static fn($v) => is_string($v);
                return true;
            } elseif ($attribute instanceof VDropDown) {
                $text = ProcessDataInput::processDataVResult($attribute->text);
                $options = ProcessDataInput::processDataVResult($attribute->options);
                $default = ProcessDataInput::processDataVResult($attribute->default);
                $label = ProcessDataInput::processDataVResult($attribute->label);
                $addContent([
                    TypeContent::TYPE => TypeValueContent::DROPDOWN,
                    TypeContent::TEXT => $text,
                    TypeContent::OPTIONS => $options,
                    TypeContent::DEFAULT => $default
                ]);
                $this->labelMap[] = $label ?? count($this->labelMap);
                $this->validationMethods[] = static fn($v) => $v === -1 || (is_int($v));
                return true;
            } elseif ($attribute instanceof VSlider) {
                $text = ProcessDataInput::processDataVResult($attribute->text);
                $min = ProcessDataInput::processDataVResult($attribute->min);
                $max = ProcessDataInput::processDataVResult($attribute->max);
                $step = ProcessDataInput::processDataVResult($attribute->step);
                $default = ProcessDataInput::processDataVResult($attribute->default);
                $label = ProcessDataInput::processDataVResult($attribute->label);
                $content = [
                    TypeContent::TYPE => TypeValueContent::SLIDER,
                    TypeContent::TEXT => $text,
                    TypeContent::MIN => $min,
                    TypeContent::MAX => $max
                ];
                if ($attribute->step !== -1) $content[TypeContent::STEP] = $step;
                if ($attribute->default !== -1) $content[TypeContent::DEFAULT] = $default;
                $addContent($content);
                $this->labelMap[] = $label ?? count($this->labelMap);
                $this->validationMethods[] = static fn($v) => (is_float($v) || is_int($v)) && $v >= $min && $v <= $max;
                return true;
            } elseif ($attribute instanceof VStepSlider) {
                $text = ProcessDataInput::processDataVResult($attribute->text);
                $steps = ProcessDataInput::processDataVResult($attribute->steps);
                $default = ProcessDataInput::processDataVResult($attribute->default);
                $label = ProcessDataInput::processDataVResult($attribute->label);
                $content = [
                    TypeContent::TYPE => TypeValueContent::STEP_SLIDER,
                    TypeContent::TEXT => $text,
                    TypeContent::STEPS => $steps
                ];
                if ($default !== -1) $content[TypeContent::DEFAULT] = $default;
                $addContent($content);
                $this->labelMap[] = $label ?? count($this->labelMap);
                $this->validationMethods[] = static fn($v) => $v === -1 || (is_int($v) && isset($steps[$v]));
                return true;
            }
        }
        return null;
    }

}