<?php

use App\Models\Processo;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('upload stores document path on processo', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $processo = Processo::query()->create([
        'title' => 'Processo com documento',
        'description' => 'Descrição',
        'status' => 'pending',
        'responsible_user_id' => $user->id,
        'category' => 'Cat',
    ]);

    $file = UploadedFile::fake()->create('doc.pdf', 200, 'application/pdf');

    $response = $this->post("/api/processo/{$processo->id}/document", [
        'document' => $file,
        ], [
        'Accept' => 'application/json',
    ]);

    $response->assertOk();

    $processo->refresh();

    expect($processo->document_path)->not->toBeNull();

    Storage::disk('public')->assertExists($processo->document_path);
});

test('upload rejects invalid file types', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $processo = Processo::query()->create([
        'title' => 'Processo com documento',
        'description' => 'Descrição',
        'status' => 'pending',
        'responsible_user_id' => $user->id,
        'category' => 'Cat',
    ]);

    $file = UploadedFile::fake()->create('doc.exe', 10, 'application/octet-stream');

    $response = $this->post("/api/processo/{$processo->id}/document", ['document' => $file,], [
        'Accept' => 'application/json',]);

    $response->assertStatus(422);

});
