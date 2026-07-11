<?php

namespace Lartrix\Tests\Unit\Modules\Registry;

use Lartrix\Modules\Registry\RegistrySecurityAdvisory;
use PHPUnit\Framework\TestCase;

class RegistrySecurityAdvisoryTest extends TestCase
{
    public function testReturnsNoWarningsWhenSecurityFlagsAreFalse(): void
    {
        $warnings = (new RegistrySecurityAdvisory())->warnings([
            'writes_files' => false,
            'runs_commands' => false,
            'external_network' => false,
            'requires_secrets' => false,
        ]);

        self::assertSame([], $warnings);
    }

    public function testBuildsWarningsForHighRiskSecurityFlags(): void
    {
        $warnings = (new RegistrySecurityAdvisory())->warnings([
            'writes_files' => true,
            'runs_commands' => true,
            'external_network' => true,
            'requires_secrets' => true,
            'uses_eval' => true,
        ]);

        self::assertContains('writes_files: module declares it may write files.', $warnings);
        self::assertContains('runs_commands: module declares it may run commands.', $warnings);
        self::assertContains('external_network: module declares it may access external network.', $warnings);
        self::assertContains('requires_secrets: module declares it may require secrets.', $warnings);
        self::assertContains('uses_eval: module declares it may evaluate dynamic code.', $warnings);
    }

    public function testBlocksStrictSecurityWhenAnyRiskFlagIsTrue(): void
    {
        $advisory = new RegistrySecurityAdvisory();

        self::assertTrue($advisory->blocksStrict([
            'writes_files' => true,
            'runs_commands' => false,
            'external_network' => false,
            'requires_secrets' => false,
        ]));

        self::assertFalse($advisory->blocksStrict([
            'writes_files' => false,
            'runs_commands' => false,
            'external_network' => false,
            'requires_secrets' => false,
        ]));
    }
}
