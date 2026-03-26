<?php

namespace Modules\Crm\Services;

use App\Models\Common\Contact;
use Modules\Crm\Models\CrmContact;

class CrmContactSyncService
{
    public function sync(CrmContact $crmContact): CrmContact
    {
        $data = [
            'company_id' => $crmContact->company_id,
            'type' => Contact::CUSTOMER_TYPE,
            'name' => $crmContact->name,
            'email' => $crmContact->email,
            'phone' => $crmContact->phone,
            'currency_code' => $crmContact->crmCompany?->currency ?: setting('default.currency', 'USD'),
            'enabled' => true,
        ];

        if ($crmContact->crmCompany) {
            $data['address'] = $crmContact->crmCompany->address;
        }

        $akauntingContact = null;

        if ($crmContact->akaunting_contact_id) {
            $akauntingContact = Contact::where('company_id', $crmContact->company_id)
                ->customer()
                ->find($crmContact->akaunting_contact_id);
        }

        if (! $akauntingContact && $crmContact->email) {
            $akauntingContact = Contact::where('company_id', $crmContact->company_id)
                ->customer()
                ->where('email', $crmContact->email)
                ->first();
        }

        if ($akauntingContact) {
            $akauntingContact->update($data);
        } else {
            $data['created_from'] = 'crm';
            $data['created_by'] = auth()->id();

            $akauntingContact = Contact::create($data);
        }

        if ($crmContact->akaunting_contact_id !== $akauntingContact->id) {
            $crmContact->forceFill([
                'akaunting_contact_id' => $akauntingContact->id,
            ])->save();
        }

        return $crmContact->refresh();
    }
}
