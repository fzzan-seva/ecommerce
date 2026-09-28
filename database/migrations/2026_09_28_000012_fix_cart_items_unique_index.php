<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fixes a stale UNIQUE (user_id, product_id) index on cart_items.
 *
 * Migration 2024_01_01_000010 tried to drop it, but the attempt failed silently
 * because MySQL/MariaDB refused to drop an index that the
 * `cart_items_product_id_foreign` foreign key was relying on:
 *
 *   ERROR 1553 Cannot drop index 'cart_items_user_id_product_id_unique':
 *          needed in a foreign key constraint
 *
 * The failure was swallowed by a try/catch, so the index survived.
 *
 * Consequence: a customer could only ever have ONE cart row per product, so
 * adding a second size/colour of the same product blew up with
 * "Duplicate entry '21-1' for key 'cart_items_user_id_product_id_unique'" (HTTP 500).
 *
 * Fix: give the foreign key its own dedicated index first, then drop the
 * composite unique key so one product can occupy several cart rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cart_items')) {
            return;
        }

        // The FK needs an index whose FIRST column is product_id.
        if (! $this->hasIndexStartingWith('product_id')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->index('product_id');
            });
        }

        $this->dropUniqueIfPresent('cart_items', ['user_id', 'product_id']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('cart_items')) {
            return;
        }

        // Collapse duplicate (user_id, product_id) rows before restoring the key,
        // otherwise the unique index cannot be created.
        DB::table('cart_items')
            ->select('user_id', 'product_id', DB::raw('MIN(id) as keep_id'), DB::raw('COUNT(*) as c'))
            ->groupBy('user_id', 'product_id')
            ->having('c', '>', 1)
            ->orderBy('keep_id')
            ->get()
            ->each(function ($row) {
                DB::table('cart_items')
                    ->where('user_id', $row->user_id)
                    ->where('product_id', $row->product_id)
                    ->where('id', '!=', $row->keep_id)
                    ->delete();
            });

        if (! $this->hasIndexStartingWith('product_id')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->index('product_id');
            });
        }

        Schema::table('cart_items', function (Blueprint $table) {
            $table->unique(['user_id', 'product_id']);
        });
    }

    private function hasIndexStartingWith(string $column): bool
    {
        foreach ($this->indexes() as $index) {
            if ($index['seq'] === 1 && $index['column'] === $column) {
                return true;
            }
        }

        return false;
    }

    private function dropUniqueIfPresent(string $table, array $columns): void
    {
        $name = $table . '_' . implode('_', $columns) . '_unique';

        try {
            Schema::table($table, function (Blueprint $blueprint) use ($columns) {
                $blueprint->dropUnique($columns);
            });
        } catch (\Throwable $e) {
            // Index already absent — nothing to do.
        }
    }

    /**
     * @return array<int, array{name: string, seq: int, column: string}>
     */
    private function indexes(): array
    {
        $rows = [];

        foreach (DB::select('show index from ' . $this->grammar()->wrapTable('cart_items')) as $row) {
            $data = (array) $row;

            $rows[] = [
                'name' => $data['Key_name'] ?? '',
                'seq' => (int) ($data['Seq_in_index'] ?? 0),
                'column' => $data['Column_name'] ?? '',
            ];
        }

        return $rows;
    }

    private function grammar()
    {
        return DB::connection()->getQueryGrammar();
    }
};
