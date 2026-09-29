<?php

namespace App\Resources;

use App\Models\OrderPayment;

class OrderPaymentResource extends Resource
{
    public function __construct(private OrderPayment $payment) {}

    public function toArray(): array
    {
        return [
            'id'         => $this->payment->id,
            'provider'   => $this->payment->provider,
            'qr_id'      => $this->payment->qr_id,
            'external_id'=> $this->payment->external_id,
            'amount'     => (float)$this->payment->amount,
            'currency'   => $this->payment->currency,
            'status'     => $this->payment->status,
            'paid_at'    => $this->payment->paid_at,
            'created_at' => $this->payment->created_at,
        ];
    }
}