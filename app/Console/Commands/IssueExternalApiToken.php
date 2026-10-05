<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Mint (or rotate) the shared bearer token used by wttsystem.dk and similar external
 * integrations when calling /api/external/*. Attach it to an admin "integration" user —
 * since the token-matched user is only used to authenticate the request, not to pick
 * which company's data to return (the truck plate owner does that).
 */
class IssueExternalApiToken extends Command
{
    protected $signature = 'external-api:issue-token
                            {user : User ID or email to attach the token to}
                            {--rotate : Replace the existing token instead of failing if one is set}';

    protected $description = 'Issue (or rotate) an external API bearer token for a user. Used by wttsystem.dk integration.';

    public function handle(): int
    {
        $arg = $this->argument('user');
        $user = is_numeric($arg) ? User::find($arg) : User::where('email', $arg)->first();
        if (!$user) {
            $this->error("User not found: {$arg}");
            return 1;
        }

        if ($user->external_api_token && !$this->option('rotate')) {
            $this->error("User {$user->email} already has a token. Pass --rotate to replace it.");
            return 1;
        }

        $token = Str::random(64);
        $user->update(['external_api_token' => $token]);

        $this->info("Token issued for {$user->email} (ID: {$user->id}).");
        $this->newLine();
        $this->line("Store this token in wttsystem.dk — it will not be shown again:");
        $this->line($token);
        return 0;
    }
}
