<?php

namespace App\Modules\Tenancy\Services;

use App\Models\User;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Tenancy\Context\TenantDatabaseContext;
use App\Modules\Tenancy\Enums\CompanyRole;
use App\Modules\Tenancy\Enums\MembershipStatus;
use App\Modules\Tenancy\Models\Company;
use App\Modules\Tenancy\Models\Invitation;
use App\Modules\Tenancy\Models\Membership;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class InvitationService
{
    public const VALID_DAYS = 7;

    private const GENERIC_INVALID = 'This invitation is not valid or has expired.';

    public function __construct(
        private readonly AuditLogger $audit,
        private readonly TenantDatabaseContext $database,
    ) {}

    /**
     * Returns the raw token exactly once. Only its SHA-256 hash is stored.
     */
    public function create(Company $company, string $email, CompanyRole $role, int $actorId): string
    {
        $email = Str::lower($email);

        $alreadyActive = Membership::query()
            ->where('company_id', $company->id)
            ->where('status', MembershipStatus::Active)
            ->whereHas('user', fn ($query) => $query->where('email', $email))
            ->exists();

        if ($alreadyActive) {
            throw ValidationException::withMessages(['email' => 'This person is already an active member.']);
        }

        $openInvitation = Invitation::query()
            ->where('company_id', $company->id)
            ->where('email', $email)
            ->whereNull('accepted_at')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->exists();

        if ($openInvitation) {
            throw ValidationException::withMessages(['email' => 'An open invitation already exists for this email.']);
        }

        $rawToken = Str::random(64);

        DB::transaction(function () use ($company, $email, $role, $actorId, $rawToken): void {
            Invitation::query()->create([
                'company_id' => $company->id,
                'email' => $email,
                'role' => $role,
                'token_hash' => hash('sha256', $rawToken),
                'expires_at' => now()->addDays(self::VALID_DAYS),
                'created_by' => $actorId,
            ]);

            $this->audit->record(
                action: 'invitation.created',
                result: 'success',
                targetType: 'invitation',
                actorId: $actorId,
                companyId: $company->id,
                metadata: ['role' => $role->value],
            );
        });

        return $rawToken;
    }

    /**
     * Accepts an invitation. The public endpoint has no company context yet, so the company is
     * resolved first by a narrow lookup on the token hash. Then RLS is set for that company only.
     */
    public function accept(string $rawToken, ?User $signedIn, ?string $name, ?string $password): Membership
    {
        $tokenHash = hash('sha256', $rawToken);

        $companyId = DB::connection('pgsql')
            ->selectOne('SELECT invitation_company_for_token(?) AS company_id', [$tokenHash])
            ?->company_id;

        if ($companyId === null) {
            throw $this->invalid();
        }

        $this->database->apply((string) $companyId);

        try {
            return $this->acceptWithin($tokenHash, $signedIn, $name, $password);
        } finally {
            $this->database->clear();
        }
    }

    private function acceptWithin(string $tokenHash, ?User $signedIn, ?string $name, ?string $password): Membership
    {
        return DB::transaction(function () use ($tokenHash, $signedIn, $name, $password): Membership {
            $invitation = Invitation::query()
                ->where('token_hash', $tokenHash)
                ->whereNull('accepted_at')
                ->whereNull('revoked_at')
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->first();

            if ($invitation === null) {
                throw $this->invalid();
            }

            $user = $signedIn ?? $this->newAccountFor($invitation->email, $name, $password);

            // The invitation is bound to one email address. Any other account is refused.
            if (Str::lower($user->email) !== $invitation->email) {
                throw $this->invalid();
            }

            $membership = Membership::query()->updateOrCreate(
                ['company_id' => $invitation->company_id, 'user_id' => $user->id],
                ['role' => $invitation->role, 'status' => MembershipStatus::Active],
            );

            $invitation->update(['accepted_at' => now()]);

            $this->audit->record(
                action: 'invitation.accepted',
                result: 'success',
                targetType: 'invitation',
                targetId: $invitation->id,
                actorId: $user->id,
                companyId: $invitation->company_id,
                metadata: ['role' => $invitation->role->value],
            );

            return $membership;
        });
    }

    private function newAccountFor(string $email, ?string $name, ?string $password): User
    {
        if (User::query()->where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'token' => 'Log in with this email address, then accept the invitation.',
            ]);
        }

        if ($name === null || $password === null) {
            throw ValidationException::withMessages([
                'name' => 'Name and password are required to create a new account.',
            ]);
        }

        return User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);
    }

    private function invalid(): ValidationException
    {
        return ValidationException::withMessages(['token' => self::GENERIC_INVALID]);
    }
}
