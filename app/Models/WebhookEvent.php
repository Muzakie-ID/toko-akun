<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Catatan event webhook yang sudah diproses, untuk mengabaikan kiriman ganda (X-Event-Id).
 */
#[Fillable(['event_id', 'type', 'merchant_ref'])]
class WebhookEvent extends Model {}
