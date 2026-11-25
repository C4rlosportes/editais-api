<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('permite login de uma conta SME e retorna um token', function () {
    $user = User::factory()->create([
        'email' => 'sme@exemplo.test',
        'password' => 'SenhaForte123!',
    ]);

    $user->forceFill([
        'perfil_acesso' => 'SME',
    ])->save();

    $response = $this->postJson('/api/login', [
        'email' => 'sme@exemplo.test',
        'password' => 'SenhaForte123!',
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'token',
            'token_type',
            'user' => [
                'id',
                'name',
                'email',
                'perfil_acesso',
            ],
        ])
        ->assertJsonPath('token_type', 'Bearer')
        ->assertJsonPath('user.perfil_acesso', 'SME');

    expect($response->json('token'))->not->toBeEmpty();
});

it('rejeita credenciais inválidas', function () {
    User::factory()->create([
        'email' => 'sme@exemplo.test',
        'password' => 'SenhaForte123!',
    ]);

    $this->postJson('/api/login', [
        'email' => 'sme@exemplo.test',
        'password' => 'senha-incorreta',
    ])->assertUnauthorized();
});

it('não permite login de uma conta sem acesso definido', function () {
    User::factory()->create([
        'email' => 'conta@exemplo.test',
        'password' => 'SenhaForte123!',
    ]);

    $this->postJson('/api/login', [
        'email' => 'conta@exemplo.test',
        'password' => 'SenhaForte123!',
    ])->assertForbidden();
});

it('não permite consultar o usuário sem autenticação', function () {
    $this->getJson('/api/me')->assertUnauthorized();
});

it('retorna os dados da conta autenticada', function () {
    $user = User::factory()->create([
        'email' => 'sme@exemplo.test',
    ]);

    $user->forceFill([
        'perfil_acesso' => 'SME',
    ])->save();

    $token = $user->createToken('teste')->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/me')
        ->assertOk()
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonPath('user.perfil_acesso', 'SME');
});

it('invalida o token utilizado no logout', function () {
    $user = User::factory()->create([
        'email' => 'sme@exemplo.test',
    ]);

    $user->forceFill([
        'perfil_acesso' => 'SME',
    ])->save();

    $token = $user->createToken('teste')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/logout')
        ->assertOk();

    $this->withToken($token)
        ->getJson('/api/me')
        ->assertUnauthorized();
});
