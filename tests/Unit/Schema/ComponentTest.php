<?php

namespace Lartrix\Tests\Unit\Schema;

use PHPUnit\Framework\TestCase;
use Lartrix\Schema\Components\NaiveUI\NButton;
use Lartrix\Schema\Components\NaiveUI\NInput;
use Lartrix\Schema\Components\NaiveUI\NForm;
use Lartrix\Schema\Components\NaiveUI\NFormItem;
use Lartrix\Schema\Components\NaiveUI\NCard;
use Lartrix\Schema\Actions\SetAction;
use Lartrix\Schema\Actions\CallAction;
use Lartrix\Schema\Actions\FetchAction;

class ComponentTest extends TestCase
{
    /** @test */
    public function it_outputs_correct_com_field(): void
    {
        $button = NButton::make();
        $array = $button->toArray();

        $this->assertEquals('NButton', $array['com']);
    }

    /** @test */
    public function it_can_set_props(): void
    {
        $button = NButton::make()
            ->type('primary')
            ->size('large');

        $array = $button->toArray();

        $this->assertEquals('primary', $array['props']['type']);
        $this->assertEquals('large', $array['props']['size']);
    }

    /** @test */
    public function it_can_set_children(): void
    {
        $button = NButton::make()->text('点击我');
        $array = $button->toArray();

        $this->assertEquals('点击我', $array['children']);
    }

    /** @test */
    public function it_can_nest_components(): void
    {
        $card = NCard::make()
            ->title('表单')
            ->children([
                NForm::make()->children([
                    NFormItem::make()->label('用户名')->children([
                        NInput::make()->placeholder('请输入用户名'),
                    ]),
                ]),
            ]);

        $array = $card->toArray();

        $this->assertEquals('NCard', $array['com']);
        $this->assertIsArray($array['children']);
        $this->assertEquals('NForm', $array['children'][0]['com']);
    }

    /** @test */
    public function it_can_bind_events(): void
    {
        $button = NButton::make()
            ->on('click', new CallAction('handleClick'));

        $array = $button->toArray();

        $this->assertArrayHasKey('events', $array);
        $this->assertArrayHasKey('click', $array['events']);
        $this->assertEquals('handleClick', $array['events']['click']['call']);
    }

    /** @test */
    public function it_can_set_directives(): void
    {
        $button = NButton::make()
            ->if('showButton')
            ->show('isVisible')
            ->for('item in items', 'item.id')
            ->model('formData.name')
            ->ref('buttonRef');

        $array = $button->toArray();

        $this->assertEquals('showButton', $array['if']);
        $this->assertEquals('isVisible', $array['show']);
        $this->assertEquals('item in items', $array['for']);
        $this->assertEquals('item.id', $array['key']);
        $this->assertEquals('formData.name', $array['model']);
        $this->assertEquals('buttonRef', $array['ref']);
    }

    /** @test */
    public function it_can_set_data_and_computed(): void
    {
        $form = NForm::make()
            ->data(['name' => '', 'email' => ''])
            ->computed(['isValid' => 'name && email']);

        $array = $form->toArray();

        $this->assertArrayHasKey('data', $array);
        $this->assertArrayHasKey('computed', $array);
        $this->assertEquals('', $array['data']['name']);
        $this->assertEquals('name && email', $array['computed']['isValid']);
    }

    /** @test */
    public function it_can_set_lifecycle_hooks(): void
    {
        $form = NForm::make()
            ->onMounted(new CallAction('loadData'))
            ->onUnmounted(new CallAction('cleanup'));

        $array = $form->toArray();

        $this->assertArrayHasKey('onMounted', $array);
        $this->assertArrayHasKey('onUnmounted', $array);
    }

    /** @test */
    public function it_can_set_api_config(): void
    {
        $form = NForm::make()
            ->initApi('/api/users')
            ->uiApi(['url' => '/api/submit', 'method' => 'POST']);

        $array = $form->toArray();

        $this->assertEquals('/api/users', $array['initApi']);
        $this->assertEquals('/api/submit', $array['uiApi']['url']);
    }

    /** @test */
    public function it_can_set_slots(): void
    {
        $card = NCard::make()
            ->slot('header', [NButton::make()->text('操作')])
            ->slot('footer', [NButton::make()->text('提交')], 'slotProps');

        $array = $card->toArray();

        $this->assertArrayHasKey('slots', $array);
        $this->assertArrayHasKey('header', $array['slots']);
        $this->assertArrayHasKey('footer', $array['slots']);
    }

    /** @test */
    public function it_outputs_valid_json(): void
    {
        $button = NButton::make()
            ->type('primary')
            ->text('提交');

        $json = $button->toJson();

        $this->assertJson($json);
        
        $decoded = json_decode($json, true);
        $this->assertEquals('NButton', $decoded['com']);
    }

    /** @test */
    public function naive_ui_components_have_correct_com_prefix(): void
    {
        $components = [
            NButton::make(),
            NInput::make(),
            NForm::make(),
            NFormItem::make(),
            NCard::make(),
        ];

        foreach ($components as $component) {
            $array = $component->toArray();
            $this->assertStringStartsWith('N', $array['com'], 
                'Naive UI 组件的 com 字段应以 "N" 开头');
        }
    }

    /** @test */
    public function children_normalization_returns_array(): void
    {
        // 单个子组件
        $card1 = NCard::make()->children(NButton::make());
        $array1 = $card1->toArray();
        $this->assertIsArray($array1['children']);

        // 多个子组件
        $card2 = NCard::make()->children([
            NButton::make(),
            NInput::make(),
        ]);
        $array2 = $card2->toArray();
        $this->assertIsArray($array2['children']);
        $this->assertCount(2, $array2['children']);
    }
}
