<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class EmailInvitation extends Model
{
    protected $table = 'account_verify_tokens';

    protected $fillable = [
        'user_id',
        'token',
        'invitation',
        'expires_at'
    ];


    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }


    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();

    }
}
