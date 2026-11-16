<?php

declare(strict_types=1);

namespace Modules\AI\Exceptions;

use RuntimeException;

/** Richiesta al fornitore AI fallita (rete, HTTP, risposta vuota o non conforme allo schema). */
final class AiRequestException extends RuntimeException {}
