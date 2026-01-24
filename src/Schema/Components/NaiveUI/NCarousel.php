<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NCarousel - Naive UI 轮播图组件
 */
class NCarousel extends Component
{
    public function __construct()
    {
        parent::__construct('NCarousel');
    }

    public static function make(): static
    {
        return new static();
    }

    public function autoplay(bool $autoplay = true): static
    {
        return $this->props(['autoplay' => $autoplay]);
    }

    public function interval(int $interval): static
    {
        return $this->props(['interval' => $interval]);
    }

    public function loop(bool $loop = true): static
    {
        return $this->props(['loop' => $loop]);
    }

    public function direction(string $direction): static
    {
        return $this->props(['direction' => $direction]);
    }

    public function effect(string $effect): static
    {
        return $this->props(['effect' => $effect]);
    }

    public function showArrow(bool $show = true): static
    {
        return $this->props(['showArrow' => $show]);
    }

    public function showDots(bool $show = true): static
    {
        return $this->props(['showDots' => $show]);
    }

    public function dotType(string $type): static
    {
        return $this->props(['dotType' => $type]);
    }

    public function dotPlacement(string $placement): static
    {
        return $this->props(['dotPlacement' => $placement]);
    }

    public function slidesPerView(int|string $count): static
    {
        return $this->props(['slidesPerView' => $count]);
    }

    public function spaceBetween(int $space): static
    {
        return $this->props(['spaceBetween' => $space]);
    }

    public function centeredSlides(bool $centered = true): static
    {
        return $this->props(['centeredSlides' => $centered]);
    }

    public function draggable(bool $draggable = true): static
    {
        return $this->props(['draggable' => $draggable]);
    }

    public function touchable(bool $touchable = true): static
    {
        return $this->props(['touchable' => $touchable]);
    }

    public function mousewheel(bool $mousewheel = true): static
    {
        return $this->props(['mousewheel' => $mousewheel]);
    }

    public function keyboard(bool $keyboard = true): static
    {
        return $this->props(['keyboard' => $keyboard]);
    }

    public function transitionStyle(array $style): static
    {
        return $this->props(['transitionStyle' => $style]);
    }

    public function trigger(string $trigger): static
    {
        return $this->props(['trigger' => $trigger]);
    }
}
