<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FrontendDeployTrigger
{
    /**
     * Ask GitHub Actions to rebuild+redeploy the frontend.
     *
     * Runs synchronously and swallows failures — the queue on this host isn't
     * reliably drained (see DEPLOY.md), so a queued job could sit unprocessed
     * indefinitely. This is a single fast HTTP call, and a missed deploy
     * trigger should never fail a product save.
     */
    public function triggerNewProductDeploy(): void
    {
        $token = config('services.github_deploy.token');
        $repo = config('services.github_deploy.repo');

        if (! $token || ! $repo) {
            Log::info('Skipping frontend deploy trigger: GITHUB_DEPLOY_TOKEN or GITHUB_DEPLOY_REPO not configured.');

            return;
        }

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(5)
                ->post("https://api.github.com/repos/{$repo}/dispatches", [
                    'event_type' => 'product_created',
                ]);

            if ($response->failed()) {
                Log::warning('Frontend deploy trigger failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Frontend deploy trigger threw an exception', [
                'message' => $e->getMessage(),
            ]);
        }
    }
}
