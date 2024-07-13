<?php

declare(strict_types=1);

namespace venndev\vformoopapi;

use Exception;
use pocketmine\Server;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use Throwable;
use pocketmine\form\Form as IForm;
use pocketmine\player\Player;
use venndev\vformoopapi\attributes\VForm;
use venndev\vformoopapi\manager\FormManager;
use venndev\vformoopapi\utils\TypeContent;
use venndev\vformoopapi\utils\TypeForm;
use venndev\vformoopapi\utils\TypeValueContent;
use vennv\vapm\Async;
use vennv\vapm\FiberManager;
use vennv\vapm\Promise;

class Form implements IForm
{
    use FormManager;

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
     * @throws Throwable
     */
    public function __construct(
        private readonly Player $player,
        private readonly mixed  $middleWare = null
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
            try {
                $data = Async::await($this->processData($data));
                if ($data === null) {
                    $this->onClose($player);
                    return;
                }
                if (is_array($data) && $this->type === TypeForm::CUSTOM_FORM) {
                    foreach ($data as $key => $value) {
                        if (isset($this->callableMethods[$key]) && isset($this->data["content"][$key])) {
                            $content = $this->data[TypeContent::CONTENT][$key];
                            $nameMethod = $this->callableMethods[$key];
                            if ($content[TypeContent::TYPE] === TypeValueContent::DROPDOWN && isset($content[TypeContent::OPTIONS][$value])) $value = $content[TypeContent::OPTIONS][$value];
                            if ($content[TypeContent::TYPE] === TypeValueContent::STEP_SLIDER && isset($content[TypeContent::STEPS][$value])) $value = $content[TypeContent::STEPS][$value];
                            $this->$nameMethod($player, $value);
                        }

                        FiberManager::wait();
                    }
                }
                if (is_int($data) || is_string($data)) {
                    $method = $this->callableMethods[$data] ?? null;
                    if ($method !== null) $this->$method($player, $data);
                }
                if (is_bool($data) && count($this->callableMethods) > 1){
                    $data === true ? $method = $this->callableMethods[0] ?? null : $method = $this->callableMethods[1] ?? null;
                    if ($method !== null) $this->$method($player, $data);
                }
            } catch (Throwable|Exception $e) {
                Server::getInstance()->getLogger()->error($e->getMessage());
            }
        });
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
                        $this->data[TypeContent::TYPE] = $this->type;
                        $this->data[TypeContent::TITLE] = $attribute->title;
                        $this->data[TypeContent::CONTENT] = $attribute->content;

                        if ($this->type === TypeForm::NORMAL_FORM) $this->data[TypeContent::BUTTONS] = [];
                        if ($this->type === TypeForm::MODAL_FORM) $this->data[TypeContent::BUTTON_1] = $this->data[TypeContent::BUTTON_2] = "";
                        if ($this->type === TypeForm::CUSTOM_FORM) $this->data[TypeContent::CONTENT] = [];
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
        $this->data[TypeContent::CONTENT][$index] = array_merge($this->data[TypeContent::CONTENT][$index], $value);
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