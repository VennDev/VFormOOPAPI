<?php

declare(strict_types=1);

namespace venndev\vformoopapi;

use Exception;
use ReflectionClass;
use Throwable;
use pocketmine\Server;
use pocketmine\form\Form as IForm;
use pocketmine\player\Player;
use venndev\vformoopapi\manager\DataForm;
use venndev\vformoopapi\manager\DataFormProcessor;
use venndev\vformoopapi\manager\FormProcessor;
use venndev\vformoopapi\utils\TypeContent;
use venndev\vformoopapi\utils\TypeForm;
use venndev\vformoopapi\utils\TypeValueContent;
use vennv\vapm\Async;
use vennv\vapm\FiberManager;

class Form implements IForm
{
    use DataForm;
    use DataFormProcessor;
    use FormProcessor;

    /**
     * @throws Throwable
     */
    public function __construct(
        private readonly Player $player,
        private readonly mixed  $middleWare = null
    )
    {
        //TODO: Implement constructor
    }

    /**
     * @throws Throwable
     */
    public static function getInstance(Player $player): static
    {
        return new static($player);
    }

    private function checkMethodExist(string $method): bool
    {
        return method_exists($this, $method);
    }

    /**
     * @throws Throwable
     */
    public function handleResponse(Player $player, mixed $data): void
    {
        new Async(function () use ($player, $data): void {
            try {
                $data = Async::await($this->processData($data));
                if ($data === null) {
                    $this->onClose($player);
                    return;
                }
                $doFunction = function(mixed $key, mixed $value, string $nameMethod) use ($player) {
                    if (isset($this->additionalAttribute[$nameMethod])) {
                        $additionalAttribute = $this->additionalAttribute[$nameMethod];
                        $additionalAttribute[1]($player, $value);
                    } elseif ($this->checkMethodExist($nameMethod)) {
                        $this->$nameMethod($player, $value);
                    }
                };
                if (is_array($data) && $this->type === TypeForm::CUSTOM_FORM) {
                    foreach ($data as $key => $value) {
                        if (isset($this->callableMethods[$key]) && isset($this->data["content"][$key])) {
                            $content = $this->data[TypeContent::CONTENT][$key];
                            $nameMethod = $this->callableMethods[$key];
                            if ($content[TypeContent::TYPE] === TypeValueContent::DROPDOWN && isset($content[TypeContent::OPTIONS][$value])) $value = $content[TypeContent::OPTIONS][$value];
                            if ($content[TypeContent::TYPE] === TypeValueContent::STEP_SLIDER && isset($content[TypeContent::STEPS][$value])) $value = $content[TypeContent::STEPS][$value];
                            $doFunction($key, $value, $nameMethod);
                        }
                    }
                }
                if (is_int($data) || is_string($data)) {
                    $method = $this->callableMethods[$data] ?? null;
                    if ($method !== null) $doFunction($method, $data, $method);
                }
                if (is_bool($data) && count($this->callableMethods) > 1) {
                    $data === true ? $method = $this->callableMethods[0] ?? null : $method = $this->callableMethods[1] ?? null;
                    if ($method !== null) $doFunction($method, $data, $method);
                }
            } catch (Throwable|Exception $e) {
                Server::getInstance()->getLogger()->error($e->getMessage());
            }
        });
    }

    /**
     * @throws Throwable
     */
    public function sendForm(): Async
    {
        return new Async(function (): void {
            try {
                $classReflection = new ReflectionClass($this);
                $this->methods = $classReflection->getMethods();
                $this->attributes = $classReflection->getAttributes();

                Async::await($this->processAttributes());
                Async::await($this->processMethods());
                Async::await($this->processAdditionalAttribute());

                if (is_callable($this->middleWare)) ($this->middleWare)();

                $this->player->sendForm($this);
            } catch (Throwable $e) {
                Server::getInstance()->getLogger()->error($e->getMessage());
            }
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