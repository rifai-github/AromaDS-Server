<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\AutoFilterable;
use App\Http\Traits\HasBillingTaxIdentity;

class BillingGroup extends Model
{
    use AutoFilterable, HasBillingTaxIdentity;
}
