# Task 13: Stripe Payment Gateway Module

## Context
Payment gateway — customers pay invoices via Stripe.

## Objective
Add Stripe as payment option on client portal invoices. Auto-record payments.

## What to Build

### 1. Module Scaffold: `/var/www/html/modules/Stripe/`

### 2. Database
- `stripe_settings` — company_id, api_key (encrypted), webhook_secret (encrypted), test_mode (boolean)
- `stripe_payments` — id, company_id, document_id, stripe_payment_intent_id, stripe_charge_id, amount, currency, status, created_at

### 3. Features
- Settings page: enter Stripe API key and webhook secret, toggle test mode
- Client portal: "Pay with Card" button on invoice view
- Stripe Checkout Session: redirect customer to Stripe-hosted payment page
- Webhook handler: listen for payment_intent.succeeded → auto-record payment on invoice
- Payment confirmation page after successful payment
- Refund via Stripe API (from credit note or manual action)
- Support: credit card, debit card, ACH
- Payment history log

### 4. Integration
- Register as payment method in Akaunting's payment gateway system
- Show on invoice PDF: "Pay online at [link]"

## Verification
1. Configure Stripe test keys in settings
2. View invoice in client portal → "Pay with Card" button appears
3. Click pay → redirected to Stripe Checkout → complete test payment
4. Webhook fires → invoice marked as paid in Akaunting
5. Payment appears in transaction history

## Commit Message
`feat(modules): stripe payment gateway for invoice payments`
