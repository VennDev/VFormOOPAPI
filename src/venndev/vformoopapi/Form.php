<?php

declare(strict_types=1);

namespace venndev\vformoopapi;

use Exception;
use Generator;
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
use vennv\vapm\CoroutineGen;
use vennv\vapm\Deferred;
use vennv\vapm\FiberManager;

class Form implements IForm
{
    use DataForm;
    use DataFormProcessor;
    use FormProcessor;

    private mixed $callableFormClose;
    private mixed $callableFormSubmit;

    /**
     * @throws Throwable
     */
    public function __construct(
        private readonly Player $player,
        private readonly mixed  $middleWare = null
    )
    {
        if (!VFormLoader::isInit()) throw new Exception("VFormLoader is not initialized");
        $this->callableFormClose = fn() => null;
        $this->callableFormSubmit = fn() => null;
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
                $doFunction = function(mixed $key, mixed $value, string $nameMethod) use ($player): Async {
                    return new Async(function() use ($player, $key, $value, $nameMethod): void {
                        if (isset($this->additionalAttribute[$nameMethod])) {
                            $additionalAttribute = $this->additionalAttribute[$nameMethod];
                            Async::await($additionalAttribute[1]($player, $value));
                        } elseif ($this->checkMethodExist($nameMethod)) {
                            Async::await($this->$nameMethod($player, $value));
                        }
                    });
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
                        FiberManager::wait();
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
                Async::await(fn() => $this->onCompletion($player, $data)); // Call the onCompletion method
            } catch (Throwable|Exception $e) {
                Server::getInstance()->getLogger()->error($e->getMessage());
            }
        });
    }

    /**
     * @throws Throwable
     */
    public function sendForm(): void
    {
        CoroutineGen::runBlocking(function (): Generator {
            try {
                $classReflection = new ReflectionClass($this);
                $this->methods = $classReflection->getMethods();
                $this->attributes = $classReflection->getAttributes();
                [$processAttributes, $processMethods, $processAdditionalAttribute] = yield from Deferred::awaitAll(
                    $this->processAttributes(), $this->processMethods(), $this->processAdditionalAttribute()
                );
                if ($processAttributes instanceof Throwable) {
                    Server::getInstance()->getLogger()->error(
                        $processAttributes->getMessage() . " in " . $processAttributes->getFile() . " on line " . $processAttributes->getLine()
                    );
                }
                if ($processMethods instanceof Throwable) {
                    Server::getInstance()->getLogger()->error(
                        $processMethods->getMessage() . " in " . $processMethods->getFile() . " on line " . $processMethods->getLine()
                    );
                }
                if ($processAdditionalAttribute instanceof Throwable) {
                    Server::getInstance()->getLogger()->error(
                        $processAdditionalAttribute->getMessage() . " in " . $processAdditionalAttribute->getFile() . " on line " . $processAdditionalAttribute->getLine()
                    );
                }
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

    public function setFormClose(mixed $callable): void
    {
        $this->callableFormClose = $callable;
    }

    public function setFormSubmit(mixed $callable): void
    {
        $this->callableFormSubmit = $callable;
    }

    // This method is called when the form is closed
    protected function onClose(Player $player): void
    {
        ($this->callableFormClose)($player);
    }

    // This method is called when the form is submitted
    protected function onCompletion(Player $player, mixed $data): void
    {
        ($this->callableFormSubmit)($player, $data);
    }

    public function jsonSerialize(): array
    {
        return $this->data;
    }

}