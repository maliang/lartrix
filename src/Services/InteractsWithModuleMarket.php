<?php

namespace Lartrix\Services;

use Illuminate\Http\Client\PendingRequest;
use Lartrix\Modules\Registry\RegistryClient;

/** 为模块市场应用服务提供统一的 Registry 地址和认证请求。 */
trait InteractsWithModuleMarket
{
    /** 返回模块市场 API 根地址。 */
    protected function registryUrl(): string
    {
        return (new RegistryClient())->baseUrl();
    }

    /** 返回当前模块市场 Auth Key。 */
    protected function registryAuthKey(): string
    {
        return (new RegistryClient())->authKey();
    }

    /** 创建统一认证和超时的 Registry 请求。 */
    protected function registryRequest(): PendingRequest
    {
        return (new RegistryClient($this->registryUrl(), $this->registryAuthKey(), 60))->request();
    }
}
