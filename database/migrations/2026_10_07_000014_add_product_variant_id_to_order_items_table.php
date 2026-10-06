<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Records which product variant row each order line actually consumed.
 *
 * Stock bookkeeping for an order (releasing it on cancel, re-claiming it when
 * a cancelled order is reactivated) used to re-find the variant by matching the
 * denormalised (product_id, size, colour) snapshot. That match is ambiguous:
 * an admin can delete a variant and later create an identical (size, colour)
 * pair, and the historical order would then move stock on the wrong row.
 *
 * Storing the variant id gives every order line a stable identity. There is
 * deliberately no foreign key with ON DELETE SET NULL: when the variant row is
 * gone the stored id remains as a tombstone and stock operations simply skip
 * that line, instead of falling back to a snapshot match against an unrelated
 * row created later.
 *
 * Existing rows are backfilled from the snapshot (best effort), so orders that
 * predate this column keep behaving exactly as before.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('order_items', 'product_variant_id')) {
            return;
        }

        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('product_variant_id')->nullable()->after('product_id');
        });

        // Backfill from the snapshot. Driver-agnostic on purpose (no UPDATE ...
        // JOIN), and order volumes for a single shop make row-by-row fine here.
        $rows = DB::table('order_items')
            ->whereNull('product_variant_id')
            ->whereNotNull('product_id')
            ->get(['id', 'product_id', 'size', 'color']);

        foreach ($rows as $row) {
            $variantId = DB::table('product_variants')
                ->where('product_id', $row->product_id)
                ->where('size', $row->size)
                ->where('color', $row->color)
                ->value('id');

            if ($variantId !== null) {
                DB::table('order_items')->where('id', $row->id)->update(['product_variant_id' => $variantId]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('order_items', 'product_variant_id')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropColumn('product_variant_id');
            });
        }
    }
};
