<?php

namespace Lartrix\Controllers;

use Illuminate\Http\Request;
use Lartrix\Services\AuthService;
use Lartrix\Services\TranslationService;
use Lartrix\Schema\Components\Business\OptForm;
use Lartrix\Schema\Components\NaiveUI\Input;
use Lartrix\Schema\Components\NaiveUI\Select;
use Lartrix\Schema\Components\NaiveUI\Button;
use Lartrix\Schema\Actions\EmitAction;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    /**
     * 用户登录
     */
    public function login(Request $request): array
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $result = $this->authService->login(
            $request->input('username'),
            $request->input('password')
        );

        if (!$result) {
            error(__t('auth.failed'), null, 40001);
        }

        $token = $result['token'];

        return success(__t('auth.login_ok'), [
            'token' => $token->plainTextToken,
        ]);
    }

    /**
     * 用户登出
     */
    public function logout(Request $request): array
    {
        $this->authService->logout($request->user());
        return success(__t('auth.logout_ok'));
    }

    /**
     * 刷新 Token
     */
    public function refresh(Request $request): array
    {
        $token = $this->authService->refresh($request->user());

        return success(__t('auth.refresh_ok'), [
            'token' => $token->plainTextToken,
        ]);
    }

    /**
     * 获取当前用户信息
     */
    public function user(Request $request): array
    {
        $user = $request->user();

        return success([
            'id' => $user->id,
            'username' => $user->username,
            'nickname' => $user->nickname,
            'avatar' => $user->avatar,
            'email' => $user->email,
            'phone' => $user->phone,
            'status' => $user->status,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getActivePermissions()->pluck('name'),
            'locale' => $user->locale ?: config('lartrix.locale', 'zh-CN'),
        ]);
    }

    /**
     * 获取用户所有 Token
     */
    public function tokens(Request $request): array
    {
        $tokens = $this->authService->getTokens($request->user());
        return success($tokens);
    }

    /**
     * 撤销指定 Token
     */
    public function revokeToken(Request $request, int $id): array
    {
        $result = $this->authService->revokeToken($request->user(), $id);

        if (!$result) {
            error(__t('auth.token_not_found'), null, 40004);
        }

        return success(__t('auth.revoke_ok'));
    }

    /**
     * 个人资料弹窗 UI Schema
     * GET /user/profile/ui
     */
    public function profileUi(Request $request): array
    {
        $user = $request->user();

        $form = OptForm::make('formData')
            ->fields([
                ['用户名', 'username', Input::make()->props(['disabled' => true, 'placeholder' => '用户名'])],
                ['昵称', 'nickname', Input::make()->props(['placeholder' => '请输入昵称']), $user->nickname ?? ''],
                ['邮箱', 'email', Input::make()->props(['placeholder' => '请输入邮箱']), $user->email ?? ''],
                ['手机号', 'phone', Input::make()->props(['placeholder' => '请输入手机号']), $user->phone ?? ''],
                ['头像', 'avatar', Input::make()->props(['placeholder' => '头像 URL']), $user->avatar ?? ''],
            ])
            ->buttons([
                Button::make()->on('click', EmitAction::make('close'))->text(__t('button.cancel')),
                Button::make()->type('primary')->on('click', EmitAction::make('submit'))->text(__t('button.save')),
            ]);

        return success($form->toArray());
    }

    /**
     * 保存个人资料
     * PUT /user/profile
     */
    public function profile(Request $request): array
    {
        $user = $request->user();

        $validated = $request->validate([
            'nickname' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|string|max:255',
        ]);

        $user->fill($validated);
        $user->save();

        return success(__t('common.save_ok'));
    }

    /**
     * 账号设置弹窗 UI Schema
     * GET /user/settings/ui
     */
    public function settingsUi(Request $request): array
    {
        $user = $request->user();
        $translationService = app(TranslationService::class);
        $locale = $user->locale ?: config('lartrix.locale', 'zh-CN');

        $form = OptForm::make('formData')
            ->fields([
                ['语言', 'locale', Select::make()->props([
                    'placeholder' => '请选择语言',
                    'options' => $translationService->getLanguageOptions(),
                ]), $locale],
            ])
            ->buttons([
                Button::make()->on('click', EmitAction::make('close'))->text(__t('button.cancel')),
                Button::make()->type('primary')->on('click', EmitAction::make('submit'))->text(__t('button.save')),
            ]);

        return success($form->toArray());
    }

    /**
     * 保存账号设置
     * PUT /user/settings
     */
    public function settings(Request $request): array
    {
        $user = $request->user();

        $validated = $request->validate([
            'locale' => 'nullable|string|max:20',
        ]);

        if (!empty($validated['locale'])) {
            $service = app(TranslationService::class);
            $locale = $service->normalizeLocale($validated['locale']);
            if ($locale === null) {
                error(__t('system.locale_invalid'), null, 40022);
            }
            $user->locale = $locale;
            $user->save();
            app()->setLocale($locale);
        }

        return success(__t('common.save_ok'));
    }

    /**
     * 修改密码弹窗 UI Schema
     * GET /user/password/ui
     */
    public function passwordUi(): array
    {
        $form = OptForm::make('formData')
            ->fields([
                ['当前密码', 'current_password', Input::make()->props(['type' => 'password', 'showPasswordOn' => 'click', 'placeholder' => '请输入当前密码'])],
                ['新密码', 'new_password', Input::make()->props(['type' => 'password', 'showPasswordOn' => 'click', 'placeholder' => '请输入新密码（至少 6 位）'])],
                ['确认新密码', 'new_password_confirmation', Input::make()->props(['type' => 'password', 'showPasswordOn' => 'click', 'placeholder' => '请再次输入新密码'])],
            ])
            ->buttons([
                Button::make()->on('click', EmitAction::make('close'))->text(__t('button.cancel')),
                Button::make()->type('primary')->on('click', EmitAction::make('submit'))->text(__t('button.save')),
            ]);

        return success($form->toArray());
    }

    /**
     * 修改当前用户密码
     * PUT /user/password
     */
    public function password(Request $request): array
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            error(__t('auth.password_incorrect'), null, 40022);
        }

        $user->password = Hash::make($validated['new_password']);
        $user->save();

        return success(__t('auth.password_changed'));
    }

    /**
     * 获取后台配置（API 接口）
     * 注意：只返回前端必需的公开信息
     */
    public function config(): array    {
        $theme = \Lartrix\Models\Setting::fetchThemeConfig(config('lartrix.theme', []));
        return success([
            'apiPrefix' => '/' . ltrim(config('lartrix.api_prefix', 'api/admin'), '/'),
            'appTitle' => $theme['appTitle'] ?? 'Lartrix Admin',
            'logo' => $theme['logo'] ?? '',
            'locale' => config('lartrix.locale', 'zh-CN'),
            'fallbackLocale' => config('lartrix.fallback_locale', 'en-US'),
            'languages' => app(TranslationService::class)->getLanguageOptions(),
            'translationsUrl' => '/translations',
            'realtime' => $this->getRealtimeConfig(),
        ]);
    }

    protected function getRealtimeConfig(): array
    {
        return [
            'enabled' => (bool) config('lartrix.realtime.enabled', true),
            'enableNotification' => (bool) config('lartrix.realtime.enable_notification', true),
            'driver' => config('lartrix.realtime.driver', 'polling'),
            'polling' => [
                'api' => config('lartrix.realtime.polling.api', '/notifications/poll'),
                'interval' => (int) config('lartrix.realtime.polling.interval', 15000),
            ],
            'websocket' => [
                'url' => config('lartrix.realtime.websocket.url', ''),
            ],
            'behaviors' => config('lartrix.realtime.behaviors', []),
        ];
    }
}
