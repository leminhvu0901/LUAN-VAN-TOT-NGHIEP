<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * reviews.user_id was always meant to cascadeOnDelete() (see
     * 2026_06_17_000000_create_missing_commerce_tables.php), but on
     * environments where the reviews table already existed before that
     * migration ran, the real constraint drifted to NO ACTION/RESTRICT —
     * blocking admin hard-delete of any customer who left a review.
     * This corrects the live constraint to match the code's own intent.
     */
    public function up(): void
    {
        // information_schema queries + raw ALTER TABLE ... FOREIGN KEY syntax are MySQL-specific;
        // tests run against sqlite, which already applies cascadeOnDelete() correctly on fresh create.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (!Schema::hasTable('reviews')) {
            return;
        }

        $constraintName = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'reviews')
            ->where('COLUMN_NAME', 'user_id')
            ->where('REFERENCED_TABLE_NAME', 'users')
            ->value('CONSTRAINT_NAME');

        if (!$constraintName) {
            return;
        }

        $deleteRule = DB::table('information_schema.REFERENTIAL_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('CONSTRAINT_NAME', $constraintName)
            ->value('DELETE_RULE');

        if ($deleteRule === 'CASCADE') {
            return;
        }

        DB::statement("ALTER TABLE `reviews` DROP FOREIGN KEY `{$constraintName}`");
        DB::statement('ALTER TABLE `reviews` ADD CONSTRAINT `fk_reviews_user_cascade` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE');
    }

    public function down(): void
    {
        // Fill-only migration: never revert a data-integrity fix.
    }
};
