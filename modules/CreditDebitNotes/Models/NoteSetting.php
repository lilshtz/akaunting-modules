<?php

namespace Modules\CreditDebitNotes\Models;

use App\Abstracts\Model;

class NoteSetting extends Model
{
    protected $table = 'credit_debit_note_settings';

    protected $fillable = [
        'company_id',
        'cn_prefix',
        'cn_next_number',
        'dn_prefix',
        'dn_next_number',
    ];

    public function company()
    {
        return $this->belongsTo('App\Models\Common\Company');
    }

    public static function getForCompany(int $companyId): self
    {
        return static::firstOrCreate(
            ['company_id' => $companyId],
            [
                'cn_prefix' => 'CN-',
                'cn_next_number' => 1,
                'dn_prefix' => 'DN-',
                'dn_next_number' => 1,
            ]
        );
    }

    public function generateCreditNumber(): string
    {
        $number = $this->cn_prefix . str_pad($this->cn_next_number, 5, '0', STR_PAD_LEFT);
        $this->increment('cn_next_number');

        return $number;
    }

    public function generateDebitNumber(): string
    {
        $number = $this->dn_prefix . str_pad($this->dn_next_number, 5, '0', STR_PAD_LEFT);
        $this->increment('dn_next_number');

        return $number;
    }
}
