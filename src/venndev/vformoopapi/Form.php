<?php

declare(strict_types=1);

namespace venndev\vformoopapi;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use Throwable;
use pocketmine\form\Form as IForm;
use pocketmine\form\FormValidationException;
use pocketmine\player\Player;
use venndev\vformoopapi\attributes\custom\VDropDown;
use venndev\vformoopapi\attributes\custom\VInput;
use venndev\vformoopapi\attributes\custom\VLabel;
use venndev\vformoopapi\attributes\custom\VSlider;
use venndev\vformoopapi\attributes\custom\VStepSlider;
use venndev\vformoopapi\attributes\custom\VToggle;
use venndev\vformoopapi\attributes\normal\VButton as VButtonNormal;
use venndev\vformoopapi\attributes\modal\VButton as VButtonModal;
use venndev\vformoopapi\attributes\VForm;
use venndev\vformoopapi\utils\TypeContent;
use venndev\vformoopapi\utils\TypeForm;
use vennv\vapm\Async;
use vennv\vapm\FiberManager;
use vennv\vapm\Promise;

class Form implements IForm
{

    private string $type = TypeForm::NORMAL_FORM;
    private array $data = [];
    private array $labelMap = [];
    private array $validationMethods = [];

    /**
     * @var array<ReflectionMethod>
     */
    private array $methods;

    /**
     * @var array<ReflectionAttribute>
     */
    private array $attributes;

    private array $callableMethods = [];

    /**
     * @throws Throwable
     */
    public function __construct(
        private readonly Player $player,
        private readonly mixed $middleWare = null
    )
    {
        $this->sendForm();
    }

    /**
     * @throws Throwable
     */
    public static function send(Player $player): static
    {
        return new static($player);
    }

    public function getMethods(): array
    {
        return $this->methods;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @throws Throwable
     */
    public function handleResponse(Player $player, mixed $data): void
    {
        new Async(function () use ($player, $data) {
            $data = Async::await($this->processData($data));
            if ($data === null) {
                $this->onClose($player);
                return;
            }
            if (is_array($data) && $this->type === TypeForm::CUSTOM_FORM) {
                foreach ($data as $key => $value) {
                    if (isset($this->callableMethods[$key]) && isset($this->data["content"][$key])) {
                        $content = $this->data["content"][$key];
                        $nameMethod = $this->callableMethods[$key];
                        if ($content["type"] === TypeContent::DROPDOWN) $value = $content["options"][$value];
                        if ($content["type"] === TypeContent::STEP_SLIDER) $value = $content["steps"][$value];
                        $this->$nameMethod($player, $value);
                    }

                    FiberManager::wait();
                }
            }
            if (is_int($data) || is_string($data)) {
                $method = $this->callableMethods[$data] ?? null;
                if ($method !== null) $this->$method($player, $data);
            }
            if (is_bool($data)) {
                $data === true ? $method = $this->callableMethods[0] ?? null : $method = $this->callableMethods[1] ?? null;
                if ($method !== null) $this->$method($player, $data);
            }
        });
    }

    /**
     * @throws Throwable
     */
    private function processData(mixed $data): Async
    {
        return new Async(function () use ($data) {
            if ($data !== null) {
                if ($this->type === TypeForm::NORMAL_FORM) {
                    if (!is_int($data)) {
                        throw new FormValidationException("Expected an integer response, got " . gettype($data));
                    }
                    $count = count($this->data["buttons"]);
                    if ($data >= $count || $data < 0) {
                        throw new FormValidationException("Button $data does not exist");
                    }
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
        if ($this->type === TypeForm::NORMAL_FORM) {
            if ($attribute instanceof VButtonNormal) {
                $text = $attribute->text;
                $type = $attribute->type;
                $image = $attribute->image;
                $label = $attribute->label;

                $content = ["text" => $text];
                if ($type !== null) {
                    $content["image"]["type"] = $type;
                    $content["image"]["data"] = $image;
                }
                $this->data["buttons"][] = $content;
                $this->labelMap[] = $label ?? count($this->labelMap);
                return true;
            }
        }

        return null;
    }

    private function processModalForm(object $attribute): bool|null
    {
        if ($this->type === TypeForm::MODAL_FORM) {
            if ($attribute instanceof VButtonModal) {
                $text = $attribute->text;

                if ($this->data["button1"] === "") {
                    $this->data["button1"] = $text;
                } elseif ($this->data["button2"] === "") {
                    $this->data["button2"] = $text;
                }

                return true;
            }
        }

        return null;
    }

    private function processCustomForm(object $attribute): bool|null
    {
        if ($this->type === TypeForm::CUSTOM_FORM) {
            $addContent = fn(array $content) => $this->data["content"][] = $content;
            if ($attribute instanceof VLabel) {
                $addContent(["type" => TypeContent::LABEL, "text" => $attribute->text]);
                $this->labelMap[] = $attribute->label ?? count($this->labelMap);
                $this->validationMethods[] = static fn($v) => $v === null;
                return true;
            } elseif ($attribute instanceof VToggle) {
                $content = ["type" => TypeContent::TOGGLE, "text" => $attribute->text];
                if ($attribute->default !== false) $content["default"] = $attribute->default;
                $addContent($content);
                $this->labelMap[] = $attribute->label ?? count($this->labelMap);
                $this->validationMethods[] = static fn($v) => is_bool($v);
                return true;
            } elseif ($attribute instanceof VInput) {
                $addContent(["type" => TypeContent::INPUT, "text" => $attribute->text, "placeholder" => $attribute->placeholder, "default" => $attribute->default]);
                $this->labelMap[] = $attribute->label ?? count($this->labelMap);
                $this->validationMethods[] = static fn($v) => is_string($v);
                return true;
            } elseif ($attribute instanceof VDropDown) {
                $addContent(["type" => TypeContent::DROPDOWN, "text" => $attribute->text, "options" => $attribute->options, "default" => $attribute->default]);
                $this->labelMap[] = $attribute->label ?? count($this->labelMap);
                $this->validationMethods[] = static fn($v) => is_int($v) && isset($attribute->options[$v]);
                return true;
            } elseif ($attribute instanceof VSlider) {
                $content = ["type" => TypeContent::SLIDER, "text" => $attribute->text, "min" => $attribute->min, "max" => $attribute->max];
                if ($attribute->step !== -1) $content["step"] = $attribute->step;
                if ($attribute->default !== -1) $content["default"] = $attribute->default;
                $addContent($content);
                $this->labelMap[] = $attribute->label ?? count($this->labelMap);
                $this->validationMethods[] = static fn($v) => (is_float($v) || is_int($v)) && $v >= $attribute->min && $v <= $attribute->max;
                return true;
            } elseif ($attribute instanceof VStepSlider) {
                $content = ["type" => TypeContent::STEP_SLIDER, "text" => $attribute->text, "steps" => $attribute->steps];
                if ($attribute->default !== -1) $content["default"] = $attribute->default;
                $addContent($content);
                $this->labelMap[] = $attribute->label ?? count($this->labelMap);
                $this->validationMethods[] = static fn($v) => is_int($v) && isset($attribute->steps[$v]);
                return true;
            }
        }

        return null;
    }

    /**
     * @throws Throwable
     */
    private function sendForm(): void
    {
        Promise::c(function ($resolve, $reject): void {
            try {
                $classReflection = new ReflectionClass($this);
                $this->methods = $classReflection->getMethods();
                $this->attributes = $classReflection->getAttributes();

                foreach ($this->attributes as $attribute) {
                    $attribute = $attribute->newInstance();
                    if ($attribute instanceof VForm) {
                        $this->type = $attribute->type;
                        $this->data["type"] = $this->type;
                        $this->data["title"] = $attribute->title;
                        $this->data["content"] = $attribute->content;

                        if ($this->type === TypeForm::NORMAL_FORM) $this->data["buttons"] = [];
                        if ($this->type === TypeForm::MODAL_FORM) $this->data["button1"] = $this->data["button2"] = "";
                        if ($this->type === TypeForm::CUSTOM_FORM) $this->data["content"] = [];
                    }

                    FiberManager::wait();
                }

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

                if (is_callable($this->middleWare)) ($this->middleWare)();

                $resolve();
            } catch (Throwable $e) {
                $reject($e);
            }
        })->then(function (): void {
            $this->player->sendForm($this);
        })->catch(function (Throwable $e): void {
            throw $e;
        });
    }

    /**
     * Use this method to set the content of a form
     */
    protected function setIndexContent(int $index, mixed $value): void
    {
        $this->data["content"][$index] = array_merge($this->data["content"][$index], $value);
    }

    protected function onClose(Player $player): void
    {
        // Override this method to handle the form closing
    }

    public function jsonSerialize(): array
    {
        return $this->data;
    }

}