<?php

namespace Lartrix\Modules\Registry;

/** 使用配置的签名公钥验证发布包来源，签名缺失或无效时按策略拒绝安装。 */
class RegistryPackageSignatureVerifier
{
    /**
     * 校验数据或发布包的真实性与一致性。
     * @return array<string, mixed>
     */
    public function verify(string $payload, string $signature, string $key): array
    {
        if ($payload === '' || $signature === '' || $key === '') {
            return $this->result(false, 'signature_missing', 'Signature payload, signature, and key are required.');
        }

        if (!str_starts_with($signature, 'hmac-sha256:')) {
            return $this->result(false, 'signature_algorithm_unsupported', 'Only hmac-sha256 signatures are supported.');
        }

        $encoded = substr($signature, strlen('hmac-sha256:'));
        $provided = base64_decode($encoded, true);
        if ($provided === false) {
            return $this->result(false, 'signature_base64_invalid', 'Signature is not valid base64.');
        }

        $expected = hash_hmac('sha256', $payload, $key, true);
        if (!hash_equals($expected, $provided)) {
            return $this->result(false, 'signature_invalid', 'Signature does not match the payload.');
        }

        return $this->result(true, 'signature_verified', 'Signature matches the payload.');
    }

    /**
     * 执行 result 方法对应的具体职责。
     * @return array<string, mixed>
     */
    private function result(bool $verified, string $reason, string $message): array
    {
        return [
            'verified' => $verified,
            'reason' => $reason,
            'message' => $message,
        ];
    }
}
