<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Exceptions;

use RuntimeException;

class NonRetryableEmailException extends RuntimeException {}
