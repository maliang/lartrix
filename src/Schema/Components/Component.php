<?php

namespace Lartrix\Schema\Components;

use Lartrix\Schema\JsonNodeInterface;
use Lartrix\Schema\Actions\ActionInterface;

/**
 * Component 基类
 * 
 * 对应 vschema 的 JsonNode 类型
 */
abstract class Component implements JsonNodeInterface
{
    // === Component Rendering ===
    protected ?string $com = null;
    protected array $props = [];
    protected array $children = [];
    protected array $events = [];
    protected array $slots = [];

    // === Directives ===
    protected ?string $if = null;
    protected ?string $show = null;
    protected ?string $for = null;
    protected ?string $key = null;
    protected string|array|null $model = null;  // 支持字符串或对象格式
    protected ?string $ref = null;

    // === Data and Logic ===
    protected array $data = [];
    protected array $computed = [];
    protected array $watch = [];
    protected array $methods = [];

    // === Lifecycle Hooks ===
    protected array $onMounted = [];
    protected array $onUnmounted = [];
    protected array $onUpdated = [];

    // === API Configuration ===
    protected string|array|null $initApi = null;
    protected string|array|null $uiApi = null;

    public function __construct(?string $com = null)
    {
        $this->com = $com;
    }

    // === Component Rendering Methods ===

    /**
     * 设置属性
     */
    public function props(array $props): static
    {
        $this->props = array_merge($this->props, $props);
        return $this;
    }

    /**
     * 设置子组件
     */
    public function children(array|string|JsonNodeInterface $children): static
    {
        if ($children instanceof JsonNodeInterface) {
            $this->children = [$children];
        } elseif (is_array($children)) {
            $this->children = $children;
        } else {
            $this->children = [$children];
        }
        return $this;
    }

    /**
     * 绑定事件
     */
    public function on(string $event, ActionInterface|array $handler): static
    {
        $this->events[$event] = $handler;
        return $this;
    }

    /**
     * 设置插槽
     */
    public function slot(string $name, array $content, ?string $slotProps = null): static
    {
        $this->slots[$name] = $slotProps
            ? ['content' => $content, 'slotProps' => $slotProps]
            : $content;
        return $this;
    }

    // === Directives Methods ===

    /**
     * 条件渲染 v-if
     */
    public function if(string $expression): static
    {
        $this->if = $expression;
        return $this;
    }

    /**
     * 显示/隐藏 v-show
     */
    public function show(string $expression): static
    {
        $this->show = $expression;
        return $this;
    }

    /**
     * 列表渲染 v-for
     */
    public function for(string $expression, ?string $key = null): static
    {
        $this->for = $expression;
        if ($key) {
            $this->key = $key;
        }
        return $this;
    }

    /**
     * 双向绑定 v-model
     * 
     * 支持两种格式：
     * 1. model('path') - 简单 v-model，支持修饰符如 'username.trim'
     * 2. model(['columns' => 'tableColumns', 'visible' => 'showModal']) - 带参数的 v-model:xxx
     * 
     * @param string|array $model 绑定路径或参数映射数组
     */
    public function model(string|array $model): static
    {
        $this->model = $model;
        return $this;
    }

    /**
     * 引用 ref
     */
    public function ref(string $name): static
    {
        $this->ref = $name;
        return $this;
    }

    // === Data and Logic Methods ===

    /**
     * 设置响应式数据
     */
    public function data(array $data): static
    {
        $this->data = array_merge($this->data, $data);
        return $this;
    }

    /**
     * 设置计算属性
     */
    public function computed(array $computed): static
    {
        $this->computed = array_merge($this->computed, $computed);
        return $this;
    }

    /**
     * 设置方法
     */
    public function methods(array $methods): static
    {
        $this->methods = array_merge($this->methods, $methods);
        return $this;
    }

    /**
     * 设置监听器
     */
    public function watch(string $path, ActionInterface|array $handler, bool $immediate = false, bool $deep = false): static
    {
        $config = ['handler' => $handler];
        if ($immediate) {
            $config['immediate'] = true;
        }
        if ($deep) {
            $config['deep'] = true;
        }
        $this->watch[$path] = $config;
        return $this;
    }

    // === Lifecycle Hooks Methods ===

    /**
     * 挂载后回调
     */
    public function onMounted(ActionInterface|array $actions): static
    {
        $this->onMounted = is_array($actions) ? $actions : [$actions];
        return $this;
    }

    /**
     * 卸载后回调
     */
    public function onUnmounted(ActionInterface|array $actions): static
    {
        $this->onUnmounted = is_array($actions) ? $actions : [$actions];
        return $this;
    }

    /**
     * 更新后回调
     */
    public function onUpdated(ActionInterface|array $actions): static
    {
        $this->onUpdated = is_array($actions) ? $actions : [$actions];
        return $this;
    }

    // === API Configuration Methods ===

    /**
     * 初始化 API
     */
    public function initApi(string|array $config): static
    {
        $this->initApi = $config;
        return $this;
    }

    /**
     * UI API
     */
    public function uiApi(string|array $config): static
    {
        $this->uiApi = $config;
        return $this;
    }

    // === Output Methods ===

    /**
     * 转换为数组
     */
    public function toArray(): array
    {
        $result = [];

        // Component Rendering
        if ($this->com) {
            $result['com'] = $this->com;
        }
        if (!empty($this->props)) {
            $result['props'] = $this->props;
        }
        if (!empty($this->children)) {
            $result['children'] = $this->normalizeChildren($this->children);
        }
        if (!empty($this->events)) {
            $result['events'] = $this->normalizeEvents($this->events);
        }
        if (!empty($this->slots)) {
            $result['slots'] = $this->normalizeSlots($this->slots);
        }

        // Directives
        if ($this->if) {
            $result['if'] = $this->if;
        }
        if ($this->show) {
            $result['show'] = $this->show;
        }
        if ($this->for) {
            $result['for'] = $this->for;
        }
        if ($this->key) {
            $result['key'] = $this->key;
        }
        if ($this->model) {
            $result['model'] = $this->model;
        }
        if ($this->ref) {
            $result['ref'] = $this->ref;
        }

        // Data and Logic
        if (!empty($this->data)) {
            $result['data'] = $this->data;
        }
        if (!empty($this->computed)) {
            $result['computed'] = $this->computed;
        }
        if (!empty($this->watch)) {
            $result['watch'] = $this->normalizeWatch($this->watch);
        }
        if (!empty($this->methods)) {
            $result['methods'] = $this->normalizeMethods($this->methods);
        }

        // Lifecycle Hooks
        if (!empty($this->onMounted)) {
            $result['onMounted'] = $this->normalizeActions($this->onMounted);
        }
        if (!empty($this->onUnmounted)) {
            $result['onUnmounted'] = $this->normalizeActions($this->onUnmounted);
        }
        if (!empty($this->onUpdated)) {
            $result['onUpdated'] = $this->normalizeActions($this->onUpdated);
        }

        // API Configuration
        if ($this->initApi) {
            $result['initApi'] = $this->initApi;
        }
        if ($this->uiApi) {
            $result['uiApi'] = $this->uiApi;
        }

        return $result;
    }

    /**
     * 转换为 JSON 字符串
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    // === Helper Methods ===

    /**
     * 规范化子组件
     */
    protected function normalizeChildren(array $children): array|string
    {
        // 如果只有一个字符串子元素，直接返回字符串
        if (count($children) === 1 && is_string($children[0])) {
            return $children[0];
        }

        return array_map(
            fn($c) => $c instanceof JsonNodeInterface ? $c->toArray() : $c,
            $children
        );
    }

    /**
     * 规范化事件
     * 
     * 支持以下格式：
     * 1. 单个 ActionInterface 对象（包括批量 SetAction）
     * 2. ActionInterface 数组（索引数组）
     * 3. 普通数组格式的 Action（如 ['call' => 'methodName']、['script' => '...']）
     * 4. 索引数组，元素可以是 ActionInterface 或普通数组格式
     */
    protected function normalizeEvents(array $events): array
    {
        return array_map(function ($h) {
            // 单个 Action 对象
            if ($h instanceof ActionInterface) {
                // 批量 SetAction 返回的是数组的数组，直接返回（作为 Action 数组）
                if ($h instanceof \Lartrix\Schema\Actions\SetAction && $h->isBatch()) {
                    return $h->toArray();
                }
                return $h->toArray();
            }
            
            // 数组类型
            if (\is_array($h)) {
                // 检查是否是普通数组格式的 Action（关联数组）
                // 支持的 Action 类型：set, call, emit, fetch, script, if, copy, ws
                if (isset($h['call']) || isset($h['set']) || isset($h['fetch']) || isset($h['emit']) 
                    || isset($h['script']) || isset($h['if']) || isset($h['copy']) || isset($h['ws'])) {
                    return $h;
                }
                
                // 索引数组，遍历处理每个元素
                $result = [];
                foreach ($h as $a) {
                    if ($a instanceof ActionInterface) {
                        // 批量 SetAction 需要展开
                        if ($a instanceof \Lartrix\Schema\Actions\SetAction && $a->isBatch()) {
                            foreach ($a->toArray() as $item) {
                                $result[] = $item;
                            }
                        } else {
                            $result[] = $a->toArray();
                        }
                    } else {
                        $result[] = $a;
                    }
                }
                return $result;
            }
            
            return $h;
        }, $events);
    }

    /**
     * 规范化插槽
     */
    protected function normalizeSlots(array $slots): array
    {
        return array_map(function ($s) {
            if (isset($s['content'])) {
                return [
                    'content' => array_map(
                        fn($n) => $n instanceof JsonNodeInterface ? $n->toArray() : $n,
                        $s['content']
                    ),
                    'slotProps' => $s['slotProps'] ?? null,
                ];
            }
            return array_map(
                fn($n) => $n instanceof JsonNodeInterface ? $n->toArray() : $n,
                $s
            );
        }, $slots);
    }

    /**
     * 规范化监听器
     */
    protected function normalizeWatch(array $watch): array
    {
        return array_map(function ($c) {
            if (isset($c['handler'])) {
                $c['handler'] = $this->normalizeActions(
                    is_array($c['handler']) ? $c['handler'] : [$c['handler']]
                );
            }
            return $c;
        }, $watch);
    }

    /**
     * 规范化方法
     */
    protected function normalizeMethods(array $methods): array
    {
        return array_map(
            fn($a) => $this->normalizeActions(is_array($a) ? $a : [$a]),
            $methods
        );
    }

    /**
     * 规范化动作
     */
    protected function normalizeActions(array $actions): array
    {
        $result = [];
        foreach ($actions as $a) {
            if ($a instanceof ActionInterface) {
                // 批量 SetAction 需要展开
                if ($a instanceof \Lartrix\Schema\Actions\SetAction && $a->isBatch()) {
                    foreach ($a->toArray() as $item) {
                        $result[] = $item;
                    }
                } else {
                    $result[] = $a->toArray();
                }
            } else {
                $result[] = $a;
            }
        }
        return $result;
    }
}
