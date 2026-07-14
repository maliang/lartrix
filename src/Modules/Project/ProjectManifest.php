<?php

namespace Lartrix\Modules\Project;

use InvalidArgumentException;
use JsonException;

/** 经过统一校验的只读项目清单。 */
final class ProjectManifest
{
    /** 禁止外部绕过校验直接构造。 */
    private function __construct(private readonly array $data)
    {
    }

    /** 从 JSON 文件加载并校验项目清单。 */
    public static function load(string $path): self
    {
        if (!is_file($path)) {
            throw new InvalidArgumentException("Project manifest not found: {$path}");
        }
        try {
            $data = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidArgumentException("Project manifest is not valid JSON: {$path}", previous: $e);
        }
        if (!is_array($data)) {
            throw new InvalidArgumentException('Project manifest root must be an object.');
        }
        $errors = ProjectManifestValidator::validate($data);
        if ($errors !== []) {
            throw new InvalidArgumentException('Invalid project manifest: ' . json_encode($errors, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        return new self($data);
    }

    /** 返回项目清单原始标准数据。 */
    public function toArray(): array
    {
        return $this->data;
    }

    /** 返回项目生态 ID。 */
    public function id(): string
    {
        return $this->data['id'];
    }

    /** 返回项目版本。 */
    public function version(): string
    {
        return $this->data['version'];
    }
}
