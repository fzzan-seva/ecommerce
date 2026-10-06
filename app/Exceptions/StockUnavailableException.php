<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown from inside the checkout transaction when a variant's stock cannot be
 * claimed atomically. Rolling the transaction back releases the order, the
 * order items and any stock already taken for earlier lines.
 */
class StockUnavailableException extends Exception {}
