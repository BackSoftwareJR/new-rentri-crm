<?php

namespace Tests\Feature\Sprint16;

use App\Domain\Operatore\OperatoreProfiloService;
use App\Models\User;
use Tests\TestCase;

class OperatoreProfiloServiceTest extends TestCase
{
    public function test_update_profilo_changes_name(): void
    {
        $user = User::where('email', 'operatore@example.com')->firstOrFail();

        $updated = app(OperatoreProfiloService::class)->updateProfilo($user, 'Operatore Demo');

        $this->assertSame('Operatore Demo', $updated->name);
        $this->assertSame('operatore@example.com', $updated->email);
    }

    public function test_update_profilo_rejects_empty_name(): void
    {
        $user = User::where('email', 'operatore@example.com')->firstOrFail();

        $this->expectException(\InvalidArgumentException::class);

        app(OperatoreProfiloService::class)->updateProfilo($user, '   ');
    }
}
