<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateOAuthClient extends Command
{
    protected $signature = 'passport:client:custom
                            {--personal : Create a personal access client}
                            {--password : Create a password grant client}
                            {--name= : Client name}';

    protected $description = 'Create a custom OAuth client';

    public function handle(): void
    {
        $name = $this->option('name') ?:
            ($this->option('personal') ? 'Personal Access Client' : 'Password Grant Client');

        $client = DB::table('oauth_clients')->insertGetId([
            'user_id' => null,
            'name' => $name,
            'secret' => Str::random(40),
            'provider' => 'users',
            'redirect' => 'http://localhost',
            'personal_access_client' => $this->option('personal') ? 1 : 0,
            'password_client' => $this->option('password') ? 1 : 0,
            'revoked' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $secret = DB::table('oauth_clients')->where('id', $client)->value('secret');

        $this->info('OAuth client created successfully!');
        $this->line('Client ID: ' . $client);
        $this->line('Client Secret: ' . $secret);

        $this->newLine();
        $this->line('Add to .env:');
        if ($this->option('personal')) {
            $this->line('PASSPORT_PERSONAL_ACCESS_CLIENT_ID=' . $client);
            $this->line('PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET=' . $secret);
        } else {
            $this->line('PASSPORT_PASSWORD_GRANT_CLIENT_ID=' . $client);
            $this->line('PASSPORT_PASSWORD_GRANT_CLIENT_SECRET=' . $secret);
        }
    }
}
