<?php

namespace Modules\Stripe\Models;

use App\Abstracts\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StripeSettings extends Model
{
    use HasFactory;

    protected $table = 'stripe_settings';

    protected $fillable = [
        'company_id',
        'api_key',
        'webhook_secret',
        'test_mode',
        'enabled',
    ];

    protected $casts = [
        'test_mode' => 'boolean',
        'enabled' => 'boolean',
    ];

    /**
     * Encrypt the API key before storing.
     *
     * @param  string|null  $value
     * @return void
     */
    public function setApiKeyAttribute($value)
    {
        $this->attributes['api_key'] = $value ? encrypt($value) : null;
    }

    /**
     * Decrypt the API key when accessing.
     *
     * @param  string|null  $value
     * @return string|null
     */
    public function getApiKeyAttribute($value)
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
     * Encrypt the webhook secret before storing.
     *
     * @param  string|null  $value
     * @return void
     */
    public function setWebhookSecretAttribute($value)
    {
        $this->attributes['webhook_secret'] = $value ? encrypt($value) : null;
    }

    /**
     * Decrypt the webhook secret when accessing.
     *
     * @param  string|null  $value
     * @return string|null
     */
    public function getWebhookSecretAttribute($value)
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
}
