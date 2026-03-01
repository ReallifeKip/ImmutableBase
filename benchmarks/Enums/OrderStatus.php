<?php

namespace Benchmarks\Enums;

enum OrderStatus: string {
    case PENDING    = 'pending';
    case PROCESSING = 'processing';
    case SHIPPED    = 'shipped';
    case DELIVERED  = 'delivered';
    case CANCELLED  = 'cancelled';
}
