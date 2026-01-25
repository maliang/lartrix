<?php

namespace Lartrix\Schema\Components\NaiveUI;

use Lartrix\Schema\Components\Component;

/**
 * NUploadFileList - Naive UI 上传文件列表组件
 */
class UploadFileList extends Component
{
    public function __construct()
    {
        parent::__construct('NUploadFileList');
    }

    public static function make(): static
    {
        return new static();
    }
}
