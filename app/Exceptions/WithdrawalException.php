<?php

namespace App\Exceptions;

use RuntimeException;

/** A withdrawal was refused. The message states the fact and the fix and is safe to show. */
class WithdrawalException extends RuntimeException {}
