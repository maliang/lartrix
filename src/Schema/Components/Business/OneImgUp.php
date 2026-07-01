<?php

namespace Lartrix\Schema\Components\Business;

use Lartrix\Schema\Components\Custom\Html;
use Lartrix\Schema\Components\Custom\SvgIcon;
use Lartrix\Schema\Components\NaiveUI\Upload;
use Lartrix\Schema\Components\NaiveUI\Image;
use Lartrix\Schema\Actions\ActionInterface;
use Lartrix\Schema\JsonNodeInterface;

/**
 * OneImgUp - 单图上传组件（纯 PHP schema 组合，无需前端 Vue 组件）
 *
 * 特性：
 * - 可自定义尺寸（size/width/height），支持占满宽度（fullWidth）
 * - 默认「更换」模式(replace)：点击图片直接重新上传；鼠标悬停右上角显示小删除图标
 * - 「预览」模式(preview)：使用 naive-ui 原生 image-card 悬浮层（眼睛预览 + 垃圾桶删除），删除后可重新上传
 * - 未上传时中间显示加号，可改为文字(placeholderText)或完全自定义(placeholder)
 *
 * 用法：
 *   OneImgUp::make('formData.logo')
 *       ->action('/api/admin/upload/image')
 *       ->size(120)
 *       ->mode('replace');
 */
class OneImgUp implements JsonNodeInterface
{
    protected string $model;
    protected string $action = '';
    protected string $accept = '.jpg,.jpeg,.png,.gif,.webp,.ico';
    protected string $mode = 'replace';               // replace | preview
    protected string|int $width = 120;
    protected string|int $height = 120;
    protected bool $fullWidth = false;
    protected string $objectFit = 'contain';
    protected string $radius = '6px';

    protected ?string $placeholderText = null;
    protected string $plusIcon = 'carbon:add';
    protected ?int $plusIconSize = null;
    protected string $deleteIcon = 'carbon:close';
    protected ?JsonNodeInterface $placeholderNode = null;

    protected ?string $hoverStatePath = null;
    protected array $afterUpload = [];
    protected ?string $successMessage;
    protected ?string $errorMessage = null;

    public function __construct(string $model)
    {
        $this->model = $model;
        // 默认上传成功提示，可用 successMessage(null) 关闭
        $this->successMessage = function_exists('__t') ? __t('upload.ok') : '上传成功';
    }

    public static function make(string $model): static
    {
        return new static($model);
    }

    public function action(string $url): static { $this->action = $url; return $this; }
    public function accept(string $accept): static { $this->accept = $accept; return $this; }

    /** 模式：replace（点击更换，默认）| preview（原生 image-card 悬浮层，删除后重传） */
    public function mode(string $mode): static { $this->mode = $mode === 'preview' ? 'preview' : 'replace'; return $this; }

    public function width(string|int $w): static { $this->width = $w; return $this; }
    public function height(string|int $h): static { $this->height = $h; return $this; }

    /** 同时设置宽高，只传一个参数则为正方形 */
    public function size(string|int $w, string|int|null $h = null): static { $this->width = $w; $this->height = $h ?? $w; return $this; }

    /** 占满父容器宽度 */
    public function fullWidth(bool $full = true): static { $this->fullWidth = $full; return $this; }

    public function objectFit(string $fit): static { $this->objectFit = $fit; return $this; }
    public function radius(string $r): static { $this->radius = $r; return $this; }

    /** 空状态显示文字（替代默认加号） */
    public function placeholderText(string $text): static { $this->placeholderText = $text; return $this; }

    /** 自定义空状态加号图标 */
    public function plusIcon(string $icon): static { $this->plusIcon = $icon; return $this; }

    /** 自定义空状态加号图标大小（不设则按容器尺寸自动缩放） */
    public function plusIconSize(int $size): static { $this->plusIconSize = $size; return $this; }

    /** 自定义删除图标 */
    public function deleteIcon(string $icon): static { $this->deleteIcon = $icon; return $this; }

    /** 完全自定义空状态节点 */
    public function placeholder(JsonNodeInterface $node): static { $this->placeholderNode = $node; return $this; }

    /** 覆盖 hover 状态存储路径（默认自动派生自 model 同级容器） */
    public function hoverState(string $path): static { $this->hoverStatePath = $path; return $this; }

    /** 上传成功、写入 model 之后追加的动作（如刷新站点主题） */
    public function afterUpload(array $actions): static { $this->afterUpload = $actions; return $this; }

    public function successMessage(?string $msg): static { $this->successMessage = $msg; return $this; }
    public function errorMessage(?string $msg): static { $this->errorMessage = $msg; return $this; }

    protected function hoverPath(): string
    {
        if ($this->hoverStatePath !== null) {
            return $this->hoverStatePath;
        }
        // 存到 model 同级容器下，保证容器对象已存在，读取未定义属性得到 undefined 而非 ReferenceError
        $pos = strrpos($this->model, '.');
        if ($pos === false) {
            return '__oneimg_hover_' . $this->model;
        }
        return substr($this->model, 0, $pos) . '.__oneimg_hover_' . substr($this->model, $pos + 1);
    }

    protected function cssSize(string|int $v): string
    {
        return is_int($v) ? "{$v}px" : $v;
    }

    protected function widthCss(): string
    {
        return $this->fullWidth ? '100%' : $this->cssSize($this->width);
    }

    protected function heightCss(): string
    {
        return $this->cssSize($this->height);
    }

    protected function scopeClass(): string
    {
        return 'oneimgup-' . substr(md5($this->model . '|' . $this->mode), 0, 8);
    }

    protected function finishActions(): array
    {
        // Naive UI 的 onFinish 回调中 file 无 response 字段，上传返回数据只能从 XHR 事件读取
        $actions = [
            ['set' => $this->model, 'value' => '{{ JSON.parse($event.event.target.response)?.data?.url || "" }}'],
        ];
        foreach ($this->afterUpload as $a) {
            $actions[] = $a instanceof ActionInterface ? $a->toArray() : $a;
        }
        if ($this->successMessage !== null) {
            $actions[] = ['call' => '$methods.$message.success', 'args' => [$this->successMessage]];
        }
        return $actions;
    }

    protected function errorActions(): array
    {
        $msg = $this->errorMessage ?? (function_exists('__t') ? __t('upload.failed') : '上传失败');
        return [['call' => '$methods.$message.error', 'args' => [$msg]]];
    }

    protected function buildPlaceholder(): JsonNodeInterface
    {
        $w = $this->widthCss();
        $h = $this->heightCss();
        $fillCss = "width:{$w}; height:{$h}; display:flex; align-items:center; justify-content:center; "
            . "border:1px dashed #d9d9d9; border-radius:{$this->radius}; cursor:pointer; color:#999; "
            . "box-sizing:border-box; background:#fafafc;";

        if ($this->placeholderNode !== null) {
            $inner = $this->placeholderNode;
        } elseif ($this->placeholderText !== null) {
            $inner = Html::span()->children($this->placeholderText);
        } else {
            $inner = SvgIcon::make($this->plusIcon)->size($this->plusIconSizeValue())->color('#bbb');
        }

        return Html::div()
            ->if("!{$this->model}")
            ->css($fillCss)
            ->children([$inner]);
    }

    /**
     * 加号图标尺寸：优先取手动设置，否则按容器较小边的约 42% 自动缩放（限制在 28~72）。
     */
    protected function plusIconSizeValue(): int
    {
        if ($this->plusIconSize !== null) {
            return $this->plusIconSize;
        }
        $nums = [];
        foreach ([$this->width, $this->height] as $v) {
            if (is_int($v)) {
                $nums[] = $v;
            } elseif (is_string($v) && preg_match('/^(\d+(?:\.\d+)?)px$/', trim($v), $m)) {
                $nums[] = (float) $m[1];
            }
        }
        if ($nums === []) {
            return 44;
        }
        $size = (int) round(min($nums) * 0.42);
        return max(28, min(72, $size));
    }

    protected function buildDeleteButton(): JsonNodeInterface
    {
        $hover = $this->hoverPath();
        return Html::div()
            ->show("{$hover} && {$this->model}")
            ->css('position:absolute; top:4px; right:4px; z-index:3; width:22px; height:22px; display:flex; '
                . 'align-items:center; justify-content:center; background:rgba(0,0,0,0.55); color:#fff; '
                . 'border-radius:50%; cursor:pointer; pointer-events:auto;')
            ->on('click.stop', ['set' => $this->model, 'value' => ''])
            ->children([SvgIcon::make($this->deleteIcon)->size(15)->color('#fff')]);
    }

    public function toArray(): array
    {
        return $this->mode === 'preview' ? $this->buildPreview() : $this->buildReplace();
    }

    /**
     * 预览模式：直接使用 naive-ui 原生 image-card，自带悬浮层（眼睛预览 + 垃圾桶删除），
     * 删除后 "+" 触发器重新出现即可重新上传。通过作用域 CSS 覆盖其默认 96×96 尺寸。
     */
    protected function buildPreview(): array
    {
        $model = $this->model;
        $scope = $this->scopeClass();
        $w = $this->widthCss();
        $h = $this->heightCss();

        $gridCols = $this->fullWidth ? '1fr' : "repeat(auto-fill, {$w})";
        $css = ".{$scope} .n-upload-file-list--grid { grid-template-columns: {$gridCols} !important; }"
            . " .{$scope} .n-upload-trigger--image-card,"
            . " .{$scope} .n-upload-file--image-card-type { width: {$w} !important; height: {$h} !important; }";

        $defaultFileList = '{{ ' . $model . ' ? [{ id: ' . $model . ', name: "image", status: "finished", url: ' . $model . ' }] : [] }}';

        $upload = Upload::make()
            ->action($this->action)
            ->accept($this->accept)
            ->listType('image-card')
            ->max(1)
            ->showDownloadButton(false)
            ->props([
                'name' => 'file',
                'default-file-list' => $defaultFileList,
            ])
            ->on('finish', $this->finishActions())
            ->on('remove', [['set' => $model, 'value' => '']])
            ->on('error', $this->errorActions());

        // 自定义空状态（"+" 触发卡片内容）：文字或完全自定义节点；不设则用 naive-ui 默认的加号
        if ($this->placeholderNode !== null) {
            $upload->children([$this->placeholderNode]);
        } elseif ($this->placeholderText !== null) {
            $upload->children([Html::span()->children($this->placeholderText)]);
        }

        $display = $this->fullWidth ? 'block' : 'inline-block';

        return Html::div()
            ->class($scope)
            ->css("display:{$display};" . ($this->fullWidth ? ' width:100%;' : ''))
            ->children([
                Html::style()->children($css),
                $upload,
            ])
            ->toArray();
    }

    /**
     * 更换模式（默认）：点击图片/占位即上传替换；鼠标悬停右上角显示小删除图标。
     */
    protected function buildReplace(): array
    {
        $model = $this->model;
        $hover = $this->hoverPath();
        $w = $this->widthCss();
        $h = $this->heightCss();
        $scope = $this->scopeClass();
        $imgStyle = "display:block; border:1px solid #eee; border-radius:{$this->radius};";

        $children = [];

        // 占满宽度时，用作用域化 <style> 让 NUpload 触发器占满（inline style 无法命中 naive-ui 内部元素）
        if ($this->fullWidth) {
            $children[] = Html::style()->children(
                ".{$scope} .n-upload, .{$scope} .n-upload-trigger { display:block; width:100%; }"
            );
        }

        $children[] = Upload::make()
            ->action($this->action)
            ->accept($this->accept)
            ->showFileList(false)
            ->props(['name' => 'file', 'style' => 'width:100%; display:block;'])
            ->on('finish', $this->finishActions())
            ->on('error', $this->errorActions())
            ->children([
                Image::make()
                    ->if($model)
                    ->src("{{ {$model} }}")
                    ->width($w)->height($h)
                    ->objectFit($this->objectFit)
                    ->previewDisabled()
                    ->props(['style' => $imgStyle . ' cursor:pointer;']),
                $this->buildPlaceholder(),
            ]);

        $children[] = $this->buildDeleteButton();

        $display = $this->fullWidth ? 'block' : 'inline-block';

        return Html::div()
            ->class($scope)
            ->css("position:relative; display:{$display}; width:{$w}; height:{$h};")
            ->on('mouseenter', ['set' => $hover, 'value' => true])
            ->on('mouseleave', ['set' => $hover, 'value' => false])
            ->children($children)
            ->toArray();
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}
