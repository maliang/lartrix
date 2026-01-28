<?php

namespace Lartrix\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\NewAccessToken;

class AuthService extends BaseService
{
    /**
     * 获取用户模型类
     */
    protected function getUserModel(): string
    {
        return config('lartrix.models.user', \Lartrix\Models\AdminUser::class);
    }

    /**
     * 用户登录
     *
     * @param string $username 用户名
     * @param string $password 密码
     * @return array{user: Model, token: NewAccessToken}|null
     */
    public function login(string $username, string $password): ?array
    {
        $userModel = $this->getUserModel();

        // 查找用户
        $user = $userModel::where('username', $username)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return null;
        }

        // 检查用户状态
        if (!$user->isActive()) {
            return null;
        }

        // 更新最后登录信息
        $user->last_login_ip = request()->ip();
        $user->last_login_time = now();
        $user->save();

        // 是否撤销之前的 Token
        if (config('lartrix.sanctum.revoke_previous_tokens', false)) {
            $user->tokens()->delete();
        }

        // 生成新 Token
        $tokenPrefix = config('lartrix.sanctum.token_prefix', 'lartrix');
        $token = $user->createToken($tokenPrefix . '_' . time());

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * 用户登出（撤销当前 Token）
     *
     * @param Model $user
     * @return bool
     */
    public function logout(Model $user): bool
    {
        $currentToken = $user->currentAccessToken();
        
        if ($currentToken) {
            return $currentToken->delete();
        }

        return false;
    }

    /**
     * 刷新 Token
     *
     * @param Model $user
     * @return NewAccessToken
     */
    public function refresh(Model $user): NewAccessToken
    {
        // 撤销当前 Token
        $user->currentAccessToken()?->delete();

        // 生成新 Token
        $tokenPrefix = config('lartrix.sanctum.token_prefix', 'lartrix');
        return $user->createToken($tokenPrefix . '_' . time());
    }

    /**
     * 获取用户所有 Token
     *
     * @param Model $user
     * @return array
     */
    public function getTokens(Model $user): array
    {
        return $user->tokens()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($token) => [
                'id' => $token->id,
                'name' => $token->name,
                'last_used_at' => $token->last_used_at?->toDateTimeString(),
                'created_at' => $token->created_at->toDateTimeString(),
            ])
            ->toArray();
    }

    /**
     * 撤销指定 Token
     *
     * @param Model $user
     * @param int $tokenId
     * @return bool
     */
    public function revokeToken(Model $user, int $tokenId): bool
    {
        return $user->tokens()->where('id', $tokenId)->delete() > 0;
    }

    /**
     * 撤销用户所有 Token
     *
     * @param Model $user
     * @return int 撤销的 Token 数量
     */
    public function revokeAllTokens(Model $user): int
    {
        return $user->tokens()->delete();
    }
}
