<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Moves payment proofs off the `public` disk and onto the private
 * `payment_proofs` disk.
 *
 * Payment proofs used to be written to storage/app/public/payment-proofs, which
 * `php artisan storage:link` mirrors into public/storage — so every customer's
 * bank transfer receipt was fetchable by anyone who knew the URL. New uploads
 * already go to the private disk; this command rescues receipts uploaded before
 * that change, so no order is left pointing at a 404.
 *
 * Idempotent: safe to run more than once.
 */
class RelocatePaymentProofs extends Command
{
    protected $signature = 'proofs:relocate {--dry-run : List what would move without touching anything}';

    protected $description = 'Move payment proofs from the public disk to the private payment_proofs disk';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $public = Storage::disk('public');
        $private = Storage::disk('payment_proofs');

        // Only follow rows the database actually points at, so unrelated files
        // in the public directory (product photos etc.) are never touched.
        $paths = Order::query()
            ->whereNotNull('payment_proof')
            ->pluck('payment_proof')
            ->filter(fn ($p) => str_starts_with($p, 'payment-proofs/'))
            ->unique();

        if ($paths->isEmpty()) {
            $this->info('No payment proofs on the public disk. Nothing to do.');

            return self::SUCCESS;
        }

        $moved = $skipped = $missing = 0;

        foreach ($paths as $path) {
            if ($private->exists($path)) {
                // Already relocated; drop the public copy if it is still there.
                if ($public->exists($path)) {
                    $dryRun ? $skipped++ : $public->delete($path);
                } else {
                    $skipped++;
                }

                continue;
            }

            if (! $public->exists($path)) {
                $this->warn("  missing, cannot move: {$path}");
                $missing++;

                continue;
            }

            if ($dryRun) {
                $this->line("  would move: {$path}");
                $moved++;

                continue;
            }

            $private->put($path, $public->get($path));
            $public->delete($path);
            $this->line("  moved: {$path}");
            $moved++;
        }

        $this->newLine();
        $this->info(sprintf(
            '%s: %d moved, %d already in place, %d missing.',
            $dryRun ? 'Dry run' : 'Done',
            $moved,
            $skipped,
            $missing,
        ));

        if ($missing > 0) {
            $this->warn('Some proofs could not be found. Those orders will show a missing receipt until re-uploaded.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
