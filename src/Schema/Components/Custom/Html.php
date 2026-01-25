<?php

namespace Lartrix\Schema\Components\Custom;

use Lartrix\Schema\Components\Component;

/**
 * Html - 通用 HTML 元素组件
 * 
 * 用于输出浏览器默认标签，如 div、span、style、a 等
 */
class Html extends Component
{
    public function __construct(string $tag = 'div')
    {
        parent::__construct($tag);
    }

    /**
     * 创建指定标签的 HTML 元素
     */
    public static function make(string $tag = 'div'): static
    {
        return new static($tag);
    }

    /**
     * 快捷方法：创建 div
     */
    public static function div(): static
    {
        return new static('div');
    }

    /**
     * 快捷方法：创建 span
     */
    public static function span(): static
    {
        return new static('span');
    }

    /**
     * 快捷方法：创建 style
     */
    public static function style(string $css = ''): static
    {
        $instance = new static('style');
        if ($css) {
            $instance->children($css);
        }
        return $instance;
    }

    /**
     * 快捷方法：创建 a 链接
     */
    public static function a(string $href = ''): static
    {
        $instance = new static('a');
        if ($href) {
            $instance->props(['href' => $href]);
        }
        return $instance;
    }


    /**
     * 设置 class
     */
    public function class(string $class): static
    {
        return $this->props(['class' => $class]);
    }

    /**
     * 设置 style 属性
     */
    public function css(array|string $style): static
    {
        return $this->props(['style' => $style]);
    }

    /**
     * 设置 innerHTML
     */
    public function innerHTML(string $html): static
    {
        return $this->props(['innerHTML' => $html]);
    }

    /**
     * 设置 id
     */
    public function id(string $id): static
    {
        return $this->props(['id' => $id]);
    }
}
