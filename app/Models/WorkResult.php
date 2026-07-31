<?php

namespace App\Models;

/**
 * Class WorkResult
 * Alias for WorkOrder model to maintain backward compatibility.
 */
class WorkResult extends WorkOrder
{
    protected $table = 'work_orders';
}
