<div>
    <div>
        <h2>{{ trans('stripe::general.pay_with_card') }}</h2>
        <div class="well well-sm mt-2 blockquote">
            {{ trans('stripe::general.description') }}
        </div>
    </div>
    <br>

    <div class="buttons">
        <div class="pull-right">
            <a href="{{ $checkout_url }}"
               id="button-stripe-pay"
               class="relative flex items-center justify-center bg-green hover:bg-green-700 text-white px-6 py-1.5 text-base rounded-lg"
            >
                <span class="material-icons-outlined text-white text-lg mr-2">credit_card</span>
                {{ trans('stripe::general.pay_with_card') }}
                &mdash;
                @money($invoice->amount_due, $invoice->currency_code, true)
            </a>
        </div>
    </div>
</div>
