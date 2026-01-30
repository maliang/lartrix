<?php

namespace Lartrix\Schema\Components\Business;

use Lartrix\Schema\Components\Component;
use Lartrix\Schema\Components\NaiveUI\Form;
use Lartrix\Schema\Components\NaiveUI\FormItem;
use Lartrix\Schema\Components\NaiveUI\Space;
use Lartrix\Schema\Components\NaiveUI\Button;
use Lartrix\Schema\JsonNodeInterface;

/**
 * OptForm - 简化表单组件
 * 
 * 自动处理字段绑定和数据初始化
 * 
 * 示例：
 * OptForm::make('formData')
 *     ->fields([
 *         ['用户名', 'name', NInput::make()->props(['placeholder' => '请输入'])],
 *         ['状态', 'status', NSwitch::make(), true],  // 第4个参数为默认值
 *     ])
 *     ->buttons([
 *         NButton::make()->text('取消')->on('click', [...]),
 *         NButton::make()->type('primary')->text('确定')->on('click', [...]),
 *     ])
 */
class OptForm implements JsonNodeInterface
{
    protected string $modelPath;
    protected array $fields = [];
    protected array $buttons = [];
    protected array $formProps = [];
    protected array $extraChildren = [];

    public function __construct(string $modelPath = 'formData')
    {
        $this->modelPath = $modelPath;
        $this->formProps = [
            'labelPlacement' => 'left',
            'labelWidth' => 80,
        ];
    }

    public static function make(string $modelPath = 'formData'): static
    {
        return new static($modelPath);
    }

    /**
     * 设置表单字段
     * 
     * 每个元素格式：[label, name, Component, ?default]
     * - label: 标签文字
     * - name: 字段名（自动绑定到 modelPath.name）
     * - Component: 表单控件
     * - default: 可选，默认值
     * 
     * 示例：
     * ->fields([
     *     ['用户名', 'name', NInput::make()],
     *     ['状态', 'status', NSwitch::make(), true],
     *     ['角色', 'roles', NSelect::make()->props(['multiple' => true]), []],
     * ])
     */
    public function fields(array $fields): static
    {
        $this->fields = $fields;
        return $this;
    }

    /**
     * 设置表单按钮
     */
    public function buttons(array $buttons): static
    {
        $this->buttons = $buttons;
        return $this;
    }

    /**
     * 设置表单属性
     */
    public function props(array $props): static
    {
        $this->formProps = array_merge($this->formProps, $props);
        return $this;
    }

    /**
     * 设置标签宽度
     */
    public function labelWidth(int|string $width): static
    {
        $this->formProps['labelWidth'] = $width;
        return $this;
    }

    /**
     * 设置标签位置
     */
    public function labelPlacement(string $placement): static
    {
        $this->formProps['labelPlacement'] = $placement;
        return $this;
    }

    /**
     * 添加额外的子组件
     */
    public function append(Component|array $children): static
    {
        if (is_array($children)) {
            $this->extraChildren = array_merge($this->extraChildren, $children);
        } else {
            $this->extraChildren[] = $children;
        }
        return $this;
    }

    /**
     * 获取表单数据的默认值
     * 
     * 用于初始化 CrudPage 的 data
     */
    public function getDefaultData(): array
    {
        $data = [];
        foreach ($this->fields as $field) {
            $name = $field[1];
            $default = $field[3] ?? '';
            $data[$name] = $default;
        }
        return $data;
    }

    /**
     * 获取模型路径
     */
    public function getModelPath(): string
    {
        return $this->modelPath;
    }

    /**
     * 转换为数组
     */
    public function toArray(): array
    {
        $formItems = [];

        foreach ($this->fields as $field) {
            $label = $field[0];
            $name = $field[1];
            $component = $field[2];
            
            // 自动绑定 model（如果组件没有预设数组格式的 model）
            // 数组格式的 model 用于特殊组件如 Tree 的 checkedKeys 绑定
            $componentArray = $component->toArray();
            if (!isset($componentArray['model']) || !is_array($componentArray['model'])) {
                $component->model("{$this->modelPath}.{$name}");
            }
            
            $formItem = FormItem::make()->label($label);
            
            // 检查是否有条件渲染
            if (isset($field[4]) && is_string($field[4])) {
                $formItem->if($field[4]);
            }
            
            $formItem->children([$component]);
            $formItems[] = $formItem;
        }

        // 添加额外子组件
        foreach ($this->extraChildren as $child) {
            if ($child instanceof JsonNodeInterface) {
                $formItems[] = $child;
            }
        }

        // 添加按钮
        if (!empty($this->buttons)) {
            $formItems[] = FormItem::make()->children([
                Space::make()->props(['justify' => 'end'])->children($this->buttons),
            ]);
        }

        return Form::make()
            ->props($this->formProps)
            ->children($formItems)
            ->toArray();
    }

    /**
     * 转换为 JSON
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}
