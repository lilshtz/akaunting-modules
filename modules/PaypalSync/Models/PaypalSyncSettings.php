<?php

namespace Modules\PaypalSync\Models;

use App\Abstracts\Model;

class PaypalSyncSettings extends Model
{
    protected $table = 'paypal_sync_settings';

    protected $fillable = [
        'company_id',
        'client_id',
        'client_secret',
        'mode',
        'bank_account_id',
        'last_sync',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'last_sync' => 'datetime',
    ];

    /**
     * Encrypt the client_id when setting.
     *
     * @param string|null $value
     * @return void
     */
    public function setClientIdAttribute($value)
    {
        $this->attributes['client_id'] = $value ? encrypt($value) : null;
    }

    /**
     * Decrypt the client_id when getting.
     *
     * @param string|null $value
     * @return string|null
     */
    public function getClientIdAttribute($value)
    {
        if (! $value) {
            return null;
        }

        try {
            return decrypt($value);
        } catch (\Throwable $e) {
            return $value;
        }
    }

    /**
     * Encrypt the client_secret when setting.
     *
     * @param string|null $value
     * @return void
     */
    public function setClientSecretAttribute($value)
    {
        $this->attributes['client_secret'] = $value ? encrypt($value) : null;
    }

    /**
     * Decrypt the client_secret when getting.
     *
     * @param string|null $value
     * @return string|null
     */
    public function getClientSecretAttribute($value)
    {
        if (! $value) {
            return null;
        }

        try {
            return decrypt($value);
        } catch (\Throwable $e) {
            return $value;
        }
    }

    /**
     * Get the company that owns the settings.
     */
    public function company()
    {
        return $this->belongsTo('App\Models\Common\Company');
    }

    /**
     * Get the bank account associated with the settings.
     */
    public function bankAccount()
    {
        return $this->belongsTo('App\Models\Banking\Account', 'bank_account_id');
    }
}
