<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $runtimeRole = $this->runtimeRole();

        DB::statement('ALTER TABLE invitations ENABLE ROW LEVEL SECURITY');

        // Reads and writes only see rows of the company set for this connection.
        // NULLIF turns an empty setting into NULL, which matches no rows, instead of raising a cast error.
        DB::statement(<<<'SQL'
            CREATE POLICY invitations_tenant_isolation ON invitations
            USING (company_id = NULLIF(current_setting('app.company_id', true), '')::uuid)
            WITH CHECK (company_id = NULLIF(current_setting('app.company_id', true), '')::uuid)
        SQL);

        // Narrow lookup for the public accept endpoint. Returns a company ID only for a valid token.
        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION invitation_company_for_token(p_token_hash text)
            RETURNS uuid
            LANGUAGE sql
            STABLE
            SECURITY DEFINER
            SET search_path = public
            AS $$
                SELECT i.company_id
                FROM invitations i
                WHERE i.token_hash = p_token_hash
                  AND i.accepted_at IS NULL
                  AND i.revoked_at IS NULL
                  AND i.expires_at > now()
                LIMIT 1
            $$
        SQL);

        DB::statement('REVOKE ALL ON FUNCTION invitation_company_for_token(text) FROM PUBLIC');
        DB::statement("GRANT EXECUTE ON FUNCTION invitation_company_for_token(text) TO \"{$runtimeRole}\"");
    }

    public function down(): void
    {
        DB::statement('DROP FUNCTION IF EXISTS invitation_company_for_token(text)');
        DB::statement('DROP POLICY IF EXISTS invitations_tenant_isolation ON invitations');
        DB::statement('ALTER TABLE invitations DISABLE ROW LEVEL SECURITY');
    }

    private function runtimeRole(): string
    {
        $role = config('database.connections.pgsql.username');

        if (! is_string($role) || preg_match('/^[a-z_][a-z0-9_]{0,62}$/', $role) !== 1) {
            throw new RuntimeException('Runtime database role name is not a safe identifier.');
        }

        return $role;
    }
};
