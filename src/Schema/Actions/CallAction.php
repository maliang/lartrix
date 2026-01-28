<?php

namespace Lartrix\Schema\Actions;

/**
 * CallAction - 调用方法动作
 *
 * 对应 vschema 的 CallAction 类型
 *
 * 内置方法（自动补全 $methods. 前缀）：
 * - $message.success(content) - 成功消息
 * - $message.error(content) - 错误消息
 * - $message.warning(content) - 警告消息
 * - $message.info(content) - 信息消息
 * - $dialog.warning(options) - 警告对话框
 * - $dialog.error(options) - 错误对话框
 * - $dialog.success(options) - 成功对话框
 * - $notification.success(options) - 成功通知
 * - $notification.error(options) - 错误通知
 * - $notification.warning(options) - 警告通知
 * - $notification.info(options) - 信息通知
 * - $loadingBar.start() - 开始加载条
 * - $loadingBar.finish() - 结束加载条
 * - $loadingBar.error() - 加载条错误
 * - $nav.push(path) - 跳转页面
 * - $nav.replace(path) - 替换页面
 * - $nav.back() - 返回上一页
 * - $tab.close() - 关闭标签
 * - $tab.open(path, title?) - 新建标签页
 * - $tab.fix() - 固定标签页
 * - $window.open(url) - 打开新窗口
 * - $download(url, filename, options?) - 下载文件
 */
class CallAction implements ActionInterface
{
    /**
     * 需要自动补全 $methods. 前缀的内置方法前缀
     */
    protected static array $builtinPrefixes = [
        '$message.',
        '$dialog.',
        '$notification.',
        '$loadingBar.',
        '$nav.',
        '$tab.',
        '$window.',
        '$download',
    ];

    public function __construct(
        protected string $method,
        protected array $args = []
    ) {
        // 自动补全 $methods. 前缀
        $this->method = $this->normalizeMethod($method);
    }

    /**
     * 创建实例
     */
    public static function make(string $method, array $args = []): static
    {
        return new static($method, $args);
    }

    /**
     * 规范化方法名，自动补全 $methods. 前缀
     */
    protected function normalizeMethod(string $method): string
    {
        // 如果已经有 $methods. 前缀，直接返回
        if (str_starts_with($method, '$methods.')) {
            return $method;
        }

        // 检查是否是内置方法
        foreach (self::$builtinPrefixes as $prefix) {
            if (str_starts_with($method, $prefix)) {
                return '$methods.' . $method;
            }
        }

        return $method;
    }

    /**
     * 转换为数组
     */
    public function toArray(): array
    {
        $result = ['call' => $this->method];

        if (!empty($this->args)) {
            $result['args'] = $this->args;
        }

        return $result;
    }
}
