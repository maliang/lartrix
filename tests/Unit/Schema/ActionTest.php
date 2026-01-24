<?php

namespace Lartrix\Tests\Unit\Schema;

use PHPUnit\Framework\TestCase;
use Lartrix\Schema\Actions\SetAction;
use Lartrix\Schema\Actions\CallAction;
use Lartrix\Schema\Actions\FetchAction;
use Lartrix\Schema\Actions\IfAction;
use Lartrix\Schema\Actions\ScriptAction;

class ActionTest extends TestCase
{
    /** @test */
    public function set_action_has_correct_format(): void
    {
        $action = new SetAction('formData.name', 'value');
        $array = $action->toArray();

        $this->assertArrayHasKey('set', $array);
        $this->assertArrayHasKey('value', $array);
        $this->assertEquals('formData.name', $array['set']);
        $this->assertEquals('value', $array['value']);
    }

    /** @test */
    public function call_action_has_correct_format(): void
    {
        $action = new CallAction('handleSubmit', ['arg1', 'arg2']);
        $array = $action->toArray();

        $this->assertArrayHasKey('call', $array);
        $this->assertEquals('handleSubmit', $array['call']);
        $this->assertEquals(['arg1', 'arg2'], $array['args']);
    }

    /** @test */
    public function call_action_without_args(): void
    {
        $action = new CallAction('handleClick');
        $array = $action->toArray();

        $this->assertArrayHasKey('call', $array);
        $this->assertEquals('handleClick', $array['call']);
        $this->assertArrayNotHasKey('args', $array);
    }

    /** @test */
    public function fetch_action_has_correct_format(): void
    {
        $action = new FetchAction('/api/users', 'GET');
        $array = $action->toArray();

        $this->assertArrayHasKey('fetch', $array);
        $this->assertArrayHasKey('method', $array);
        $this->assertEquals('/api/users', $array['fetch']);
        $this->assertEquals('GET', $array['method']);
    }

    /** @test */
    public function fetch_action_with_body_and_callbacks(): void
    {
        $action = (new FetchAction('/api/users', 'POST'))
            ->body(['name' => '{{ formData.name }}'])
            ->then(new SetAction('users', '{{ $response.data }}'))
            ->catch(new CallAction('handleError'))
            ->finally(new SetAction('loading', false));

        $array = $action->toArray();

        $this->assertArrayHasKey('body', $array);
        $this->assertArrayHasKey('then', $array);
        $this->assertArrayHasKey('catch', $array);
        $this->assertArrayHasKey('finally', $array);
    }

    /** @test */
    public function if_action_has_correct_format(): void
    {
        $action = new IfAction(
            'isValid',
            new CallAction('submit'),
            new CallAction('showError')
        );
        $array = $action->toArray();

        $this->assertArrayHasKey('if', $array);
        $this->assertArrayHasKey('then', $array);
        $this->assertArrayHasKey('else', $array);
        $this->assertEquals('isValid', $array['if']);
    }

    /** @test */
    public function if_action_without_else(): void
    {
        $action = new IfAction('isValid', new CallAction('submit'));
        $array = $action->toArray();

        $this->assertArrayHasKey('if', $array);
        $this->assertArrayHasKey('then', $array);
        $this->assertArrayNotHasKey('else', $array);
    }

    /** @test */
    public function script_action_has_correct_format(): void
    {
        $action = new ScriptAction('console.log("Hello")');
        $array = $action->toArray();

        $this->assertArrayHasKey('script', $array);
        $this->assertEquals('console.log("Hello")', $array['script']);
    }

    /** @test */
    public function actions_implement_action_interface(): void
    {
        $actions = [
            new SetAction('key', 'value'),
            new CallAction('method'),
            new FetchAction('/api', 'GET'),
            new IfAction('condition', new CallAction('then')),
            new ScriptAction('code'),
        ];

        foreach ($actions as $action) {
            $this->assertIsArray($action->toArray());
        }
    }
}
