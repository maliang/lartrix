<?php

namespace Lartrix\Schema\Components\Custom;

use Lartrix\Schema\Components\Component;

/**
 * SvgIcon - trix SVG 图标组件
 */
class SvgIcon extends Component
{
    protected ?string $iconFontSize = null;
    protected ?string $iconColor = null;

    public function __construct()
    {
        parent::__construct('SvgIcon');
    }

    public static function make(string $icon): static
    {
        return (new static())->props(['icon' => $icon]);
    }

    public function icon(string $icon): static
    {
        return $this->props(['icon' => $icon]);
    }

    public function size(int|string $size): static
    {
        // 前端 SvgIcon 仅透传 class/style 给 iconify（图标为 1em），需用 font-size 控制尺寸
        $this->iconFontSize = is_int($size) ? "{$size}px" : (string) $size;
        return $this;
    }

    public function color(string $color): static
    {
        $this->iconColor = $color;
        return $this;
    }

    public function localIcon(string $name): static
    {
        return $this->props(['local-icon' => $name]);
    }

    public function toArray(): array
    {
        if ($this->iconFontSize !== null || $this->iconColor !== null) {
            $existing = $this->props['style'] ?? null;
            if (is_array($existing)) {
                if ($this->iconFontSize !== null) { $existing['fontSize'] = $this->iconFontSize; }
                if ($this->iconColor !== null) { $existing['color'] = $this->iconColor; }
                $this->props['style'] = $existing;
            } else {
                $parts = [];
                if ($this->iconFontSize !== null) { $parts[] = 'font-size: ' . $this->iconFontSize; }
                if ($this->iconColor !== null) { $parts[] = 'color: ' . $this->iconColor; }
                $extra = implode('; ', $parts);
                $this->props['style'] = (is_string($existing) && trim($existing) !== '')
                    ? rtrim(trim($existing), ';') . '; ' . $extra
                    : $extra;
            }
        }
        return parent::toArray();
    }
}
