<?php

namespace Lartrix\Controllers;

use Illuminate\Http\Request;
use Lartrix\Schema\Components\Html\Html;
use Lartrix\Schema\Components\NaiveUI\NCard;
use Lartrix\Schema\Components\NaiveUI\NFlex;
use Lartrix\Schema\Components\NaiveUI\NForm;
use Lartrix\Schema\Components\NaiveUI\NFormItem;
use Lartrix\Schema\Components\NaiveUI\NInput;
use Lartrix\Schema\Components\NaiveUI\NInputGroup;
use Lartrix\Schema\Components\NaiveUI\NButton;
use Lartrix\Schema\Components\NaiveUI\NCheckbox;
use Lartrix\Schema\Components\NaiveUI\NText;
use Lartrix\Schema\Components\NaiveUI\NDivider;
use Lartrix\Schema\Components\NaiveUI\NIcon;
use Lartrix\Schema\Components\Custom\SvgIcon;
use Lartrix\Schema\Components\Common\SystemLogo;
use Lartrix\Schema\Components\NaiveUI\NResult;
use Lartrix\Schema\Components\Custom\GlobalSearch;
use Lartrix\Schema\Components\Custom\HeaderNotification;
use Lartrix\Schema\Components\Custom\FullScreen;
use Lartrix\Schema\Components\Custom\LangSwitch;
use Lartrix\Schema\Components\Custom\ThemeSchemaSwitch;
use Lartrix\Schema\Components\Custom\ThemeButton;
use Lartrix\Schema\Components\Custom\UserAvatar;
use function Lartrix\Support\success;
use function Lartrix\Support\error;

class SystemController extends Controller
{
    /**
     * 获取设置模型类
     */
    protected function getSettingModel(): string
    {
        return config('lartrix.models.setting', \Lartrix\Models\Setting::class);
    }

    /**
     * 登录页面 UI Schema
     * 无需认证
     */
    public function loginPage(): array
    {
        // 从设置中获取登录页配置
        $settingModel = $this->getSettingModel();
        $loginConfig = $settingModel::getGroup('login');

        $appTitle = $loginConfig['app_title'] ?? config('app.name', 'Lartrix Admin');
        $appSubtitle = $loginConfig['app_subtitle'] ?? 'JSON 驱动的后台管理系统';
        $copyright = $loginConfig['copyright'] ?? '© ' . date('Y') . ' Lartrix Admin. All rights reserved.';

        // 构建登录页面 Schema
        $schema = Html::div()
            ->data($this->getLoginPageData())
            ->props([
                'style' => [
                    'minHeight' => '100vh',
                    'display' => 'flex',
                    'flexDirection' => 'column',
                    'justifyContent' => 'center',
                    'alignItems' => 'center',
                    'position' => 'relative',
                    'overflow' => 'hidden',
                    'background' => '#f8f9fc',
                ],
            ])
            ->children([
                // 动画 SVG 背景
                $this->buildAnimatedSvgBackground(),
                // 波浪动画样式
                $this->buildWaveStyles(),
                // 波浪容器
                $this->buildWaveContainer(),
                // 顶部渐变动画
                $this->buildTopGradient(),
                // 登录卡片
                $this->buildLoginCard($appTitle, $appSubtitle),
                // 版权信息
                NText::make()
                    ->props([
                        'style' => [
                            'marginTop' => '32px',
                            'color' => 'rgba(100, 100, 100, 0.8)',
                            'fontSize' => '13px',
                            'zIndex' => '10',
                        ],
                    ])
                    ->children([$copyright]),
            ]);

        return success($schema->toArray());
    }


    /**
     * 获取登录页面数据
     */
    protected function getLoginPageData(): array
    {
        return [
            'mode' => 'login',
            'form' => [
                'username' => 'admin',
                'password' => '123456',
            ],
            'resetForm' => [
                'phone' => '',
                'code' => '',
                'newPassword' => '',
                'confirmPassword' => '',
            ],
            'loading' => false,
            'rememberMe' => false,
            'countdown' => 0,
            'rules' => [
                'username' => [
                    ['required' => true, 'message' => '请输入用户名', 'trigger' => 'blur'],
                ],
                'password' => [
                    ['required' => true, 'message' => '请输入密码', 'trigger' => 'blur'],
                    ['min' => 6, 'message' => '密码长度不能少于6位', 'trigger' => 'blur'],
                ],
            ],
            'resetRules' => [
                'phone' => [
                    ['required' => true, 'message' => '请输入手机号', 'trigger' => 'blur'],
                    ['pattern' => '^1[3-9]\\d{9}$', 'message' => '请输入正确的手机号', 'trigger' => 'blur'],
                ],
                'code' => [
                    ['required' => true, 'message' => '请输入验证码', 'trigger' => 'blur'],
                    ['len' => 6, 'message' => '验证码为6位数字', 'trigger' => 'blur'],
                ],
                'newPassword' => [
                    ['required' => true, 'message' => '请输入新密码', 'trigger' => 'blur'],
                    ['min' => 6, 'message' => '密码长度不能少于6位', 'trigger' => 'blur'],
                ],
                'confirmPassword' => [
                    ['required' => true, 'message' => '请再次输入密码', 'trigger' => 'blur'],
                ],
            ],
        ];
    }

    /**
     * 构建动画 SVG 背景
     */
    protected function buildAnimatedSvgBackground(): Html
    {
        $svg = "<svg viewBox='0 0 1000 600' preserveAspectRatio='xMidYMid slice' style='position:absolute;top:0;left:0;width:100%;height:60%;pointer-events:none;filter:saturate(0.5);'>"
            . "<defs>"
            . "<linearGradient id='lg1' x1='0%' y1='0%' x2='100%' y2='100%'><stop offset='0%' style='stop-color:rgb(var(--primary-400-color));stop-opacity:0.2'/><stop offset='100%' style='stop-color:rgb(var(--primary-300-color));stop-opacity:0.1'/></linearGradient>"
            . "<linearGradient id='lg2' x1='100%' y1='0%' x2='0%' y2='100%'><stop offset='0%' style='stop-color:rgb(var(--primary-300-color));stop-opacity:0.15'/><stop offset='100%' style='stop-color:rgb(var(--primary-200-color));stop-opacity:0.08'/></linearGradient>"
            . "<linearGradient id='lg3' x1='0%' y1='100%' x2='100%' y2='0%'><stop offset='0%' style='stop-color:rgb(var(--primary-500-color));stop-opacity:0.12'/><stop offset='100%' style='stop-color:rgb(var(--primary-400-color));stop-opacity:0.06'/></linearGradient>"
            . "</defs>"
            . "<path stroke='url(#lg1)' stroke-width='1.5' fill='none'><animate attributeName='d' dur='20s' repeatCount='indefinite' values='M0,150 Q200,100 400,180 T800,120 T1000,160;M0,120 Q200,180 400,100 T800,180 T1000,130;M0,180 Q200,120 400,160 T800,100 T1000,150;M0,150 Q200,100 400,180 T800,120 T1000,160'/></path>"
            . "<path stroke='url(#lg2)' stroke-width='1' fill='none'><animate attributeName='d' dur='25s' repeatCount='indefinite' values='M0,250 Q250,200 500,280 T1000,220;M0,220 Q250,280 500,200 T1000,260;M0,280 Q250,220 500,260 T1000,200;M0,250 Q250,200 500,280 T1000,220'/></path>"
            . "<path stroke='url(#lg1)' stroke-width='0.8' fill='none' opacity='0.6'><animate attributeName='d' dur='18s' repeatCount='indefinite' values='M0,80 Q300,120 600,60 T1000,100;M0,100 Q300,60 600,120 T1000,70;M0,60 Q300,100 600,80 T1000,110;M0,80 Q300,120 600,60 T1000,100'/></path>"
            . "<path stroke='url(#lg3)' stroke-width='1.2' fill='none'><animate attributeName='d' dur='22s' repeatCount='indefinite' values='M0,320 Q180,280 360,340 T720,300 T1000,330;M0,300 Q180,340 360,280 T720,340 T1000,290;M0,340 Q180,300 360,320 T720,280 T1000,320;M0,320 Q180,280 360,340 T720,300 T1000,330'/></path>"
            . "<circle r='3' style='fill:rgb(var(--primary-400-color));fill-opacity:0.25'><animate attributeName='cx' dur='15s' repeatCount='indefinite' values='100;300;100'/><animate attributeName='cy' dur='12s' repeatCount='indefinite' values='150;200;150'/></circle>"
            . "<circle r='2' style='fill:rgb(var(--primary-300-color));fill-opacity:0.3'><animate attributeName='cx' dur='18s' repeatCount='indefinite' values='700;500;700'/><animate attributeName='cy' dur='14s' repeatCount='indefinite' values='100;180;100'/></circle>"
            . "<circle r='3.5' style='fill:rgb(var(--primary-500-color));fill-opacity:0.2'><animate attributeName='cx' dur='22s' repeatCount='indefinite' values='400;600;400'/><animate attributeName='cy' dur='16s' repeatCount='indefinite' values='250;180;250'/></circle>"
            . "</svg>";

        return Html::div()
            ->innerHTML($svg)
            ->css(['position' => 'absolute', 'inset' => '0', 'pointerEvents' => 'none']);
    }


    /**
     * 构建波浪动画样式
     */
    protected function buildWaveStyles(): Html
    {
        $css = ".wave-container { position: absolute; bottom: 0; left: 0; width: 100%; height: 85%; overflow: hidden; pointer-events: none; filter: saturate(0.6); } "
            . ".wave { position: absolute; left: 0; width: 200%; display: flex; animation: waveAnim 10s linear infinite; } "
            . ".wave svg { flex: 0 0 50%; height: 100%; display: block; } "
            . ".wave1 { animation-duration: 25s; bottom: 0; height: 100%; } "
            . ".wave2 { animation-duration: 20s; bottom: 0; height: 75%; } "
            . ".wave3 { animation-duration: 15s; bottom: 0; height: 50%; } "
            . ".wave4 { animation-duration: 10s; bottom: 0; height: 30%; } "
            . "@keyframes waveAnim { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }";

        return Html::style($css);
    }

    /**
     * 构建波浪容器
     */
    protected function buildWaveContainer(): Html
    {
        $waveSvg1 = "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1200 200' preserveAspectRatio='none'><path d='M0,100 C200,110 400,90 600,100 C800,110 1000,90 1200,100 L1200,200 L0,200 Z' style='fill:rgb(var(--primary-200-color))'/></svg>";
        $waveSvg2 = "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1200 200' preserveAspectRatio='none'><path d='M0,100 C200,130 400,70 600,100 C800,130 1000,70 1200,100 L1200,200 L0,200 Z' style='fill:rgb(var(--primary-300-color))'/></svg>";
        $waveSvg3 = "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1200 200' preserveAspectRatio='none'><path d='M0,100 C200,160 400,40 600,100 C800,160 1000,40 1200,100 L1200,200 L0,200 Z' style='fill:rgb(var(--primary-400-color))'/></svg>";
        $waveSvg4 = "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1200 200' preserveAspectRatio='none'><path d='M0,100 C200,195 400,5 600,100 C800,195 1000,5 1200,100 L1200,200 L0,200 Z' style='fill:rgb(var(--primary-500-color))'/></svg>";

        return Html::div()
            ->class('wave-container')
            ->children([
                Html::div()->class('wave wave1')->innerHTML($waveSvg1 . $waveSvg1),
                Html::div()->class('wave wave2')->innerHTML($waveSvg2 . $waveSvg2),
                Html::div()->class('wave wave3')->innerHTML($waveSvg3 . $waveSvg3),
                Html::div()->class('wave wave4')->innerHTML($waveSvg4 . $waveSvg4),
            ]);
    }

    /**
     * 构建顶部渐变动画
     */
    protected function buildTopGradient(): Html
    {
        $svg = "<svg viewBox='0 0 1000 300' preserveAspectRatio='none' style='position:absolute;top:0;left:0;width:100%;height:40%;transform:rotate(180deg)'>"
            . "<defs><linearGradient id='tg1' x1='0%' y1='0%' x2='100%' y2='0%'>"
            . "<stop offset='0%'><animate attributeName='stop-color' values='rgba(255,255,255,0.3);rgba(255,255,255,0.1);rgba(255,255,255,0.2);rgba(255,255,255,0.3)' dur='10s' repeatCount='indefinite'/></stop>"
            . "<stop offset='100%'><animate attributeName='stop-color' values='rgba(255,255,255,0.1);rgba(255,255,255,0.25);rgba(255,255,255,0.15);rgba(255,255,255,0.1)' dur='10s' repeatCount='indefinite'/></stop>"
            . "</linearGradient></defs>"
            . "<path fill='url(#tg1)'><animate attributeName='d' dur='14s' repeatCount='indefinite' values='M0,100 Q250,50 500,100 T1000,100 L1000,300 L0,300 Z;M0,80 Q250,130 500,80 T1000,80 L1000,300 L0,300 Z;M0,100 Q250,50 500,100 T1000,100 L1000,300 L0,300 Z'/></path>"
            . "</svg>";

        return Html::div()
            ->innerHTML($svg)
            ->css(['position' => 'absolute', 'inset' => '0', 'pointerEvents' => 'none']);
    }


    /**
     * 构建登录卡片
     */
    protected function buildLoginCard(string $appTitle, string $appSubtitle): NCard
    {
        return NCard::make()
            ->bordered(false)
            ->props([
                'style' => [
                    'width' => '400px',
                    'borderRadius' => '20px',
                    'boxShadow' => '0 25px 50px -12px rgba(0, 0, 0, 0.25)',
                    'background' => 'rgba(255, 255, 255, 0.95)',
                    'backdropFilter' => 'blur(20px)',
                    'WebkitBackdropFilter' => 'blur(20px)',
                    'zIndex' => '10',
                ],
                'contentStyle' => ['padding' => '40px'],
            ])
            ->children([
                // Logo 和标题
                $this->buildLogoHeader($appTitle, $appSubtitle),
                // 登录表单
                $this->buildLoginForm(),
                // 重置密码表单
                $this->buildResetPasswordForm(),
            ]);
    }

    /**
     * 构建 Logo 头部
     */
    protected function buildLogoHeader(string $appTitle, string $appSubtitle): NFlex
    {
        return NFlex::make()
            ->align('center')
            ->justify('center')
            ->props(['style' => ['marginBottom' => '32px', 'gap' => '12px']])
            ->children([
                Html::make('img')
                    ->props(['src' => '/admin/favicon.svg', 'style' => ['width' => '48px', 'height' => '48px']]),
                NFlex::make()
                    ->vertical()
                    ->props(['style' => ['gap' => '2px']])
                    ->children([
                        NText::make()
                            ->strong()
                            ->props(['style' => ['fontSize' => '24px', 'lineHeight' => '1.2']])
                            ->children([$appTitle]),
                        NText::make()
                            ->depth(3)
                            ->props(['style' => ['fontSize' => '12px']])
                            ->children([$appSubtitle]),
                    ]),
            ]);
    }

    /**
     * 构建登录表单
     */
    protected function buildLoginForm(): Html
    {
        return Html::div()
            ->if("mode === 'login'")
            ->children([
                NForm::make()
                    ->model('form')
                    ->rules('rules')
                    ->showLabel(false)
                    ->children([
                        // 用户名
                        NFormItem::make()
                            ->path('username')
                            ->children([
                                NInput::make()
                                    ->model('form.username')
                                    ->placeholder('用户名')
                                    ->size('large')
                                    ->clearable()
                                    ->slot('prefix', [
                                        NIcon::make()
                                            ->props(['style' => ['color' => '#999']])
                                            ->children([SvgIcon::make('carbon:user')]),
                                    ]),
                            ]),
                        // 密码
                        NFormItem::make()
                            ->path('password')
                            ->children([
                                NInput::make()
                                    ->model('form.password')
                                    ->type('password')
                                    ->placeholder('密码')
                                    ->size('large')
                                    ->showPasswordOn('click')
                                    ->clearable()
                                    ->slot('prefix', [
                                        NIcon::make()
                                            ->props(['style' => ['color' => '#999']])
                                            ->children([SvgIcon::make('carbon:password')]),
                                    ]),
                            ]),
                        // 记住我 & 忘记密码
                        NFlex::make()
                            ->justify('space-between')
                            ->align('center')
                            ->props(['style' => ['marginBottom' => '24px']])
                            ->children([
                                NCheckbox::make()
                                    ->props(['model:checked' => 'rememberMe'])
                                    ->children(['记住我']),
                                NButton::make()
                                    ->props(['text' => true, 'type' => 'primary'])
                                    ->on('click', ['set' => 'mode', 'value' => 'reset'])
                                    ->children(['忘记密码？']),
                            ]),
                        // 登录按钮
                        NButton::make()
                            ->type('primary')
                            ->props([
                                'block' => true,
                                'size' => 'large',
                                'loading' => '{{ loading }}',
                                'attrType' => 'submit',
                                'style' => ['height' => '44px', 'fontSize' => '16px'],
                            ])
                            ->on('click', ['script' => 'state.loading = true; try { await $methods.login(state.form.username, state.form.password); } finally { state.loading = false; }'])
                            ->text('登 录'),
                    ]),
                // 分割线
                NDivider::make()
                    ->props(['style' => ['margin' => '24px 0']])
                    ->children([
                        NText::make()
                            ->depth(3)
                            ->props(['style' => ['fontSize' => '12px']])
                            ->children(['其他登录方式']),
                    ]),
                // 社交登录按钮
                $this->buildSocialLoginButtons(),
            ]);
    }


    /**
     * 构建社交登录按钮
     */
    protected function buildSocialLoginButtons(): NFlex
    {
        return NFlex::make()
            ->justify('center')
            ->props(['style' => ['gap' => '24px']])
            ->children([
                NButton::make()
                    ->circle()
                    ->quaternary()
                    ->props(['style' => ['width' => '44px', 'height' => '44px']])
                    ->children([SvgIcon::make('carbon:logo-github')->props(['style' => ['fontSize' => '20px', 'color' => '#666']])]),
                NButton::make()
                    ->circle()
                    ->quaternary()
                    ->props(['style' => ['width' => '44px', 'height' => '44px']])
                    ->children([SvgIcon::make('carbon:logo-wechat')->props(['style' => ['fontSize' => '20px', 'color' => '#07c160']])]),
                NButton::make()
                    ->circle()
                    ->quaternary()
                    ->props(['style' => ['width' => '44px', 'height' => '44px']])
                    ->children([SvgIcon::make('carbon:email')->props(['style' => ['fontSize' => '20px', 'color' => '#1890ff']])]),
            ]);
    }

    /**
     * 构建重置密码表单
     */
    protected function buildResetPasswordForm(): Html
    {
        return Html::div()
            ->if("mode === 'reset'")
            ->children([
                // 返回按钮和标题
                NFlex::make()
                    ->align('center')
                    ->props(['style' => ['marginBottom' => '24px']])
                    ->children([
                        NButton::make()
                            ->props(['text' => true, 'style' => ['padding' => '0']])
                            ->on('click', [
                                ['set' => 'mode', 'value' => 'login'],
                                ['set' => 'resetForm', 'value' => "{{ ({ phone: '', code: '', newPassword: '', confirmPassword: '' }) }}"],
                                ['set' => 'countdown', 'value' => 0],
                            ])
                            ->children([SvgIcon::make('carbon:arrow-left')->props(['style' => ['fontSize' => '18px']])]),
                        NText::make()
                            ->strong()
                            ->props(['style' => ['fontSize' => '18px', 'marginLeft' => '12px']])
                            ->children(['重置密码']),
                    ]),
                // 重置密码表单
                NForm::make()
                    ->model('resetForm')
                    ->rules('resetRules')
                    ->showLabel(false)
                    ->children([
                        // 手机号
                        NFormItem::make()
                            ->path('phone')
                            ->children([
                                NInput::make()
                                    ->model('resetForm.phone')
                                    ->placeholder('手机号')
                                    ->size('large')
                                    ->clearable()
                                    ->maxlength(11)
                                    ->slot('prefix', [
                                        NIcon::make()
                                            ->props(['style' => ['color' => '#999']])
                                            ->children([SvgIcon::make('carbon:phone')]),
                                    ]),
                            ]),
                        // 验证码
                        NFormItem::make()
                            ->path('code')
                            ->children([
                                NInputGroup::make()
                                    ->children([
                                        NInput::make()
                                            ->model('resetForm.code')
                                            ->placeholder('验证码')
                                            ->size('large')
                                            ->clearable()
                                            ->maxlength(6)
                                            ->props(['style' => ['flex' => '1']])
                                            ->slot('prefix', [
                                                NIcon::make()
                                                    ->props(['style' => ['color' => '#999']])
                                                    ->children([SvgIcon::make('carbon:security')]),
                                            ]),
                                        NButton::make()
                                            ->type('primary')
                                            ->size('large')
                                            ->props([
                                                'disabled' => '{{ countdown > 0 || !resetForm.phone }}',
                                                'style' => ['width' => '120px'],
                                            ])
                                            ->on('click', [
                                                'script' => "if (!/^1[3-9]\\d{9}\$/.test(state.resetForm.phone)) { window.\$message?.warning('请输入正确的手机号'); return; } state.countdown = 60; const timer = setInterval(() => { state.countdown--; if (state.countdown <= 0) clearInterval(timer); }, 1000); window.\$message?.success('验证码已发送');",
                                            ])
                                            ->children(["{{ countdown > 0 ? countdown + 's' : '获取验证码' }}"]),
                                    ]),
                            ]),
                        // 新密码
                        NFormItem::make()
                            ->path('newPassword')
                            ->children([
                                NInput::make()
                                    ->model('resetForm.newPassword')
                                    ->type('password')
                                    ->placeholder('新密码')
                                    ->size('large')
                                    ->showPasswordOn('click')
                                    ->clearable()
                                    ->slot('prefix', [
                                        NIcon::make()
                                            ->props(['style' => ['color' => '#999']])
                                            ->children([SvgIcon::make('carbon:password')]),
                                    ]),
                            ]),
                        // 确认密码
                        NFormItem::make()
                            ->path('confirmPassword')
                            ->children([
                                NInput::make()
                                    ->model('resetForm.confirmPassword')
                                    ->type('password')
                                    ->placeholder('确认密码')
                                    ->size('large')
                                    ->showPasswordOn('click')
                                    ->clearable()
                                    ->slot('prefix', [
                                        NIcon::make()
                                            ->props(['style' => ['color' => '#999']])
                                            ->children([SvgIcon::make('carbon:checkmark')]),
                                    ]),
                            ]),
                        // 重置按钮
                        NButton::make()
                            ->type('primary')
                            ->props([
                                'block' => true,
                                'size' => 'large',
                                'style' => ['height' => '44px', 'fontSize' => '16px', 'marginTop' => '8px'],
                            ])
                            ->on('click', [
                                'if' => '!resetForm.phone || !resetForm.code || !resetForm.newPassword || !resetForm.confirmPassword',
                                'then' => ['script' => "window.\$message?.warning?.('请填写完整信息');"],
                                'else' => [
                                    'if' => 'resetForm.newPassword !== resetForm.confirmPassword',
                                    'then' => ['script' => "window.\$message?.error?.('两次输入的密码不一致');"],
                                    'else' => [
                                        ['script' => "window.\$message?.success?.('密码重置成功');"],
                                        ['set' => 'mode', 'value' => 'login'],
                                        ['set' => 'resetForm', 'value' => "{{ ({ phone: '', code: '', newPassword: '', confirmPassword: '' }) }}"],
                                        ['set' => 'countdown', 'value' => 0],
                                    ],
                                ],
                            ])
                            ->text('重置密码'),
                    ]),
            ]);
    }


    /**
     * 403 无权限页面 UI Schema
     */
    public function forbidden(): array
    {
        $schema = NFlex::make()
            ->vertical()
            ->justify('center')
            ->align('center')
            ->props(['class' => 'min-h-screen'])
            ->children([
                NResult::make()
                    ->status('403')
                    ->title('403')
                    ->description('抱歉，您没有权限访问此页面')
                    ->slot('footer', [
                        NFlex::make()
                            ->justify('center')
                            ->props(['class' => 'gap-4'])
                            ->children([
                                NButton::make()
                                    ->type('primary')
                                    ->on('click', ['call' => '$router.push', 'args' => ['/']])
                                    ->text('返回首页'),
                                NButton::make()
                                    ->on('click', ['call' => '$router.back'])
                                    ->text('返回上一页'),
                            ]),
                    ]),
            ]);

        return success($schema->toArray());
    }

    /**
     * 404 页面不存在 UI Schema
     */
    public function notFound(): array
    {
        $schema = NFlex::make()
            ->vertical()
            ->justify('center')
            ->align('center')
            ->props(['class' => 'min-h-screen'])
            ->children([
                NResult::make()
                    ->status('404')
                    ->title('404')
                    ->description('抱歉，您访问的页面不存在')
                    ->slot('footer', [
                        NFlex::make()
                            ->justify('center')
                            ->props(['class' => 'gap-4'])
                            ->children([
                                NButton::make()
                                    ->type('primary')
                                    ->on('click', ['call' => '$router.push', 'args' => ['/']])
                                    ->text('返回首页'),
                                NButton::make()
                                    ->on('click', ['call' => '$router.back'])
                                    ->text('返回上一页'),
                            ]),
                    ]),
            ]);

        return success($schema->toArray());
    }

    /**
     * 500 服务器错误页面 UI Schema
     */
    public function serverError(): array
    {
        $schema = NFlex::make()
            ->vertical()
            ->justify('center')
            ->align('center')
            ->props(['class' => 'min-h-screen'])
            ->children([
                NResult::make()
                    ->status('500')
                    ->title('500')
                    ->description('抱歉，服务器出现错误，请稍后再试')
                    ->slot('footer', [
                        NFlex::make()
                            ->justify('center')
                            ->props(['class' => 'gap-4'])
                            ->children([
                                NButton::make()
                                    ->type('primary')
                                    ->on('click', ['call' => '$router.push', 'args' => ['/']])
                                    ->text('返回首页'),
                                NButton::make()
                                    ->on('click', ['call' => 'location.reload'])
                                    ->text('刷新页面'),
                            ]),
                    ]),
            ]);

        return success($schema->toArray());
    }

    /**
     * 布局头部右侧组件 UI Schema
     */
    public function headerRight(): array
    {
        $schema = Html::div()
            ->props(['class' => 'h-full flex-y-center gap-4px'])
            ->children([
                // 全局搜索
                GlobalSearch::make(),
                // 通知中心
                // HeaderNotification::make()
                //     ->props([
                //         'badgeMode' => 'count',
                //         'pageSize' => 10,
                //         'enableWs' => false,
                //         'enableNotification' => true,
                //         'notificationDuration' => 4500,
                //         'fetchApi' => '/notifications',
                //         'readApi' => '/notifications/read',
                //         'readAllApi' => '/notifications/read-all',
                //         'tabs' => [
                //             ['key' => 'all', 'label' => '全部', 'icon' => 'ph:bell'],
                //             ['key' => 'system', 'label' => '系统', 'icon' => 'ph:gear', 'types' => ['system']],
                //             ['key' => 'order', 'label' => '订单', 'icon' => 'ph:shopping-cart', 'types' => ['order']],
                //             ['key' => 'message', 'label' => '消息', 'icon' => 'ph:chat-circle', 'types' => ['message']],
                //         ],
                //     ]),
                // 全屏切换
                FullScreen::make(),
                // 语言切换
                LangSwitch::make()
                    ->props([
                        'langOptions' => [
                            ['label' => '中文', 'value' => 'zh-CN'],
                            ['label' => 'English', 'value' => 'en-US'],
                        ],
                        'defaultLang' => 'zh-CN',
                        'submitUrl' => '',
                    ]),
                // 主题模式切换
                ThemeSchemaSwitch::make(),
                // 主题设置按钮
                ThemeButton::make(),
                // 用户头像菜单
                UserAvatar::make()
                    ->props([
                        'menuItems' => [
                            [
                                'key' => 'profile',
                                'label' => '个人中心',
                                'icon' => 'ph:user',
                                'action' => 'modal',
                                'modal' => [
                                    'title' => '个人中心',
                                    'width' => 600,
                                    'uiApi' => '/user/profile/ui',
                                ],
                            ],
                            [
                                'key' => 'settings',
                                'label' => '账号设置',
                                'icon' => 'ph:gear',
                                'action' => 'modal',
                                'modal' => [
                                    'title' => '账号设置',
                                    'width' => 500,
                                    'uiApi' => '/user/settings/ui',
                                    'submitApi' => '/user/settings',
                                ],
                            ],
                            [
                                'key' => 'password',
                                'label' => '修改密码',
                                'icon' => 'ph:lock-key',
                                'action' => 'modal',
                                'modal' => [
                                    'title' => '修改密码',
                                    'width' => 400,
                                    'uiApi' => '/user/password/ui',
                                    'submitApi' => '/user/password',
                                ],
                            ],
                            [
                                'key' => 'divider-1',
                                'divider' => true,
                            ],
                            [
                                'key' => 'logout',
                                'label' => 'common.logout',
                                'icon' => 'ph:sign-out',
                                'action' => 'logout',
                            ],
                        ],
                    ]),
            ]);

        return success($schema->toArray());
    }

    /**
     * 获取主题配置
     * 无需认证
     */
    public function getThemeConfig(): array
    {
        $settingModel = $this->getSettingModel();
        $themeConfig = $settingModel::getGroup('theme');

        // 如果没有配置，返回默认主题
        if (empty($themeConfig)) {
            $themeConfig = $this->getDefaultThemeConfig();
        }

        return success($themeConfig);
    }

    /**
     * 保存主题配置
     * 需要认证
     */
    public function saveThemeConfig(Request $request): array
    {
        $settingModel = $this->getSettingModel();

        $validated = $request->validate([
            'themeScheme' => 'nullable|string|in:light,dark,auto',
            'themeColor' => 'nullable|string|max:20',
            'otherColor' => 'nullable|array',
            'isInfoFollowPrimary' => 'nullable|boolean',
            'layout' => 'nullable|array',
            'page' => 'nullable|array',
            'header' => 'nullable|array',
            'tab' => 'nullable|array',
            'fixedHeaderAndTab' => 'nullable|boolean',
            'sider' => 'nullable|array',
            'footer' => 'nullable|array',
            'watermark' => 'nullable|array',
        ]);

        foreach ($validated as $key => $value) {
            if ($value !== null) {
                $settingModel::set("theme.{$key}", $value);
            }
        }

        return success('主题配置保存成功');
    }

    /**
     * 获取默认主题配置
     * 与 trix 前端的 themeSettings 保持一致
     */
    protected function getDefaultThemeConfig(): array
    {
        return [
            'appTitle' => config('lartrix.app_title', 'Lartrix Admin'),
            'logo' => config('lartrix.logo', '/favicon.svg'),
            'themeScheme' => 'light',
            'grayscale' => false,
            'colourWeakness' => false,
            'recommendColor' => false,
            'themeColor' => '#646cff',
            'themeRadius' => 6,
            'otherColor' => [
                'info' => '#2080f0',
                'success' => '#52c41a',
                'warning' => '#faad14',
                'error' => '#f5222d',
            ],
            'isInfoFollowPrimary' => true,
            'layout' => [
                'mode' => 'vertical',
                'scrollMode' => 'content',
            ],
            'page' => [
                'animate' => true,
                'animateMode' => 'fade-slide',
            ],
            'header' => [
                'height' => 56,
                'inverted' => false,
                'breadcrumb' => [
                    'visible' => true,
                    'showIcon' => true,
                ],
                'multilingual' => [
                    'visible' => true,
                ],
                'globalSearch' => [
                    'visible' => true,
                ],
            ],
            'tab' => [
                'visible' => true,
                'cache' => true,
                'height' => 44,
                'mode' => 'chrome',
                'closeTabByMiddleClick' => false,
            ],
            'fixedHeaderAndTab' => true,
            'sider' => [
                'inverted' => false,
                'width' => 220,
                'collapsedWidth' => 64,
                'mixWidth' => 90,
                'mixCollapsedWidth' => 64,
                'mixChildMenuWidth' => 200,
                'mixChildMenuBgColor' => '#ffffff',
                'autoSelectFirstMenu' => false,
            ],
            'footer' => [
                'visible' => true,
                'fixed' => false,
                'height' => 48,
                'right' => true,
            ],
            'watermark' => [
                'visible' => false,
                'text' => config('lartrix.app_title', 'Lartrix Admin'),
                'enableUserName' => false,
                'enableTime' => false,
                'timeFormat' => 'YYYY-MM-DD HH:mm',
            ],
            'tokens' => [
                'light' => [
                    'colors' => [
                        'container' => 'rgb(255, 255, 255)',
                        'layout' => 'rgb(247, 250, 252)',
                        'inverted' => 'rgb(0, 20, 40)',
                        'base-text' => 'rgb(31, 31, 31)',
                    ],
                    'boxShadow' => [
                        'header' => '0 1px 2px rgb(0, 21, 41, 0.08)',
                        'sider' => '2px 0 8px 0 rgb(29, 35, 41, 0.05)',
                        'tab' => '0 1px 2px rgb(0, 21, 41, 0.08)',
                    ],
                ],
                'dark' => [
                    'colors' => [
                        'container' => 'rgb(28, 28, 28)',
                        'layout' => 'rgb(18, 18, 18)',
                        'base-text' => 'rgb(224, 224, 224)',
                    ],
                ],
            ],
        ];
    }
}
