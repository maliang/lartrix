<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NUpload - Naive UI 上传组件
 */
class NUpload extends Component
{
    public function __construct()
    {
        parent::__construct('NUpload');
    }

    public static function make(): static
    {
        return new static();
    }

    public function action(string $action): static
    {
        return $this->props(['action' => $action]);
    }

    public function accept(string $accept): static
    {
        return $this->props(['accept' => $accept]);
    }

    public function multiple(bool $multiple = true): static
    {
        return $this->props(['multiple' => $multiple]);
    }

    public function max(int $max): static
    {
        return $this->props(['max' => $max]);
    }

    public function disabled(bool|string $disabled = true): static
    {
        return $this->props(['disabled' => $disabled]);
    }

    public function listType(string $type): static
    {
        return $this->props(['list-type' => $type]);
    }

    public function showFileList(bool $show = true): static
    {
        return $this->props(['show-file-list' => $show]);
    }

    public function showPreviewButton(bool $show = true): static
    {
        return $this->props(['show-preview-button' => $show]);
    }

    public function showRemoveButton(bool $show = true): static
    {
        return $this->props(['show-remove-button' => $show]);
    }

    public function showDownloadButton(bool $show = true): static
    {
        return $this->props(['show-download-button' => $show]);
    }

    public function showRetryButton(bool $show = true): static
    {
        return $this->props(['show-retry-button' => $show]);
    }

    public function showCancelButton(bool $show = true): static
    {
        return $this->props(['show-cancel-button' => $show]);
    }

    public function directoryDnd(bool $dnd = true): static
    {
        return $this->props(['directory-dnd' => $dnd]);
    }

    public function headers(array|string $headers): static
    {
        return $this->props([
            'headers' => is_string($headers) ? "{{ $headers }}" : $headers
        ]);
    }

    public function data(array|string $data): static
    {
        return $this->props([
            'data' => is_string($data) ? "{{ $data }}" : $data
        ]);
    }

    public function fileList(string $list): static
    {
        return $this->props(['file-list' => "{{ $list }}"]);
    }
}
