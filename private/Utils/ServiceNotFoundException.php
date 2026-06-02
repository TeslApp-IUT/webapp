<?php

declare(strict_types=1);

namespace Teslapp\Utils;

use RuntimeException;

/**
 * Exception levée lorsqu'un service demandé n'existe pas dans le conteneur.
 */
class ServiceNotFoundException extends RuntimeException {}
