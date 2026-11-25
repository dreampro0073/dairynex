<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\Client;
use App\Services\DeliveryGenerationService;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use Throwable;

class GenerateNextWeekDeliveries extends Command
{
    protected $signature = 'deliveries:generate-next-week';

    protected $description = 'Generate scheduled deliveries for today and the next six days';

    public function handle(DeliveryGenerationService $generation): int
    {
        $today = today()->startOfDay();
        $created = 0;
        $failures = 0;

        $clients = Client::query()
            ->where('status', 1)
            ->with(['admins' => fn ($query) => $query->where('status', 1)->orderBy('id')])
            ->get();

        foreach ($clients as $client) {
            $admin = $client->admins->first(fn ($candidate) => $candidate->canSignIn());

            if (! $admin) {
                $this->line("Skipped client {$client->id}: no active client admin account.");

                continue;
            }

            for ($offset = 0; $offset < 7; $offset++) {
                $date = $today->copy()->addDays($offset)->toDateString();

                try {
                    $count = $generation->generate($admin, $date);
                    $created += $count;

                    $this->line("Client {$client->id} · {$date}: {$count} deliveries generated.");
                } catch (ValidationException $exception) {
                    $failures++;
                    $message = collect($exception->errors())->flatten()->first() ?? 'Invalid delivery setup.';
                    $this->warn("Client {$client->id} · {$date}: {$message}");
                } catch (Throwable $exception) {
                    $failures++;
                    report($exception);
                    $this->error("Client {$client->id} · {$date}: generation failed; see application logs.");
                }
            }
        }

        $this->info("Finished. {$created} deliveries generated; {$failures} date/client failures.");

        return $failures > 0 ? self::FAILURE : self::SUCCESS;
    }
}
