<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Satu link tanda tangan digital kontrak untuk satu penandatangan (internal / customer).
 * Baris-baris dengan `batch` yang sama menandatangani snapshot PDF yang sama.
 *
 * @see \App\Services\Marketing\ContractSignatureService
 */
class ContractSignatureRequest extends Model
{
    public const ROLE_INTERNAL = 'internal';

    public const ROLE_CUSTOMER = 'customer';

    protected $fillable = [
        'contract_id',
        'batch',
        'signer_role',
        'signer_name',
        'signer_position',
        'signer_user_id',
        'signer_contact_id',
        'token',
        'html_path',
        'document_path',
        'document_hash',
        'expires_at',
        'signed_at',
        'signed_name',
        'signature_path',
        'signer_ip',
        'signer_user_agent',
        'final_document_path',
        'revoked_at',
        'created_by',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'signed_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    protected $hidden = [
        'token',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('revoked_at');
    }

    public function isSigned(): bool
    {
        return $this->signed_at !== null;
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function roleLabel(): string
    {
        return $this->signer_role === self::ROLE_INTERNAL ? 'Pihak Pink' : 'Pihak Customer';
    }

    public function publicUrl(): string
    {
        return route('contract-signatures.public.show', $this->token);
    }
}
