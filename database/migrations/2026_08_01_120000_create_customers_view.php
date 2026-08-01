<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * A read-only view unifying registered customers (users with the `customer` role) and
     * guest customers (orders with no `user_id`, grouped by `recipient_phone`) into a single
     * queryable list for the admin "Customers" screen. Guest rows get a negative synthetic id
     * (`-MIN(order.id)`) so they never collide with a registered user's positive id.
     */
    public function up(): void
    {
        // Quoted via the PDO driver so the backslashes in the FQCN are escaped correctly on
        // both MySQL (production) and SQLite (local/tests) — the two engines disagree on how a
        // literal backslash is written inside a SQL string, so a hardcoded literal can't
        // portably match Spatie's stored `model_type` value on both.
        $userMorphClass = DB::connection()->getPdo()->quote(User::class);

        // SQLite gives a computed expression column no type affinity inside a view, so a route
        // parameter bound as text (e.g. "-1") won't match the integer it actually stored —
        // casting makes the affinity explicit. MySQL already infers a concrete numeric type for
        // the expression, so it needs no cast (and doesn't support the SQLite `AS INTEGER` cast
        // syntax anyway).
        $guestId = DB::getDriverName() === 'sqlite' ? 'CAST(-MIN(o.id) AS INTEGER)' : '-MIN(o.id)';

        DB::statement(<<<SQL
            CREATE VIEW customers AS
            SELECT
                u.id AS id,
                u.id AS user_id,
                u.name AS name,
                u.phone AS phone,
                u.email AS email,
                'registered' AS type,
                u.created_at AS created_at,
                (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS orders_count,
                (SELECT COALESCE(SUM(o.total), 0) FROM orders o WHERE o.user_id = u.id AND o.status IN ('confirmed', 'processing', 'shipped', 'delivered')) AS lifetime_value
            FROM users u
            INNER JOIN model_has_roles mhr ON mhr.model_id = u.id AND mhr.model_type = {$userMorphClass}
            INNER JOIN roles r ON r.id = mhr.role_id AND r.name = 'customer'

            UNION ALL

            SELECT
                {$guestId} AS id,
                NULL AS user_id,
                MAX(o.recipient_name) AS name,
                o.recipient_phone AS phone,
                MAX(o.shipping_email) AS email,
                'guest' AS type,
                MIN(o.created_at) AS created_at,
                COUNT(*) AS orders_count,
                COALESCE(SUM(CASE WHEN o.status IN ('confirmed', 'processing', 'shipped', 'delivered') THEN o.total ELSE 0 END), 0) AS lifetime_value
            FROM orders o
            WHERE o.user_id IS NULL AND o.recipient_phone IS NOT NULL AND o.recipient_phone <> ''
            GROUP BY o.recipient_phone
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS customers');
    }
};
