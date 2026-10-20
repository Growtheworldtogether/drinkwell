<?php

namespace App\Exceptions;

use RuntimeException;

/** A simulation command that the machine's state does not allow. The message is safe to show. */
class SimulationRefused extends RuntimeException {}
