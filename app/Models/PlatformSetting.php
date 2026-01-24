<?php

namespace App\Models;

/**
 * @deprecated Use App\Domain\Platform\Models\PlatformSetting instead
 * @see \App\Domain\Platform\Models\PlatformSetting
 *
 * This class is kept for backward compatibility.
 * All logic has been moved to the Domain layer.
 */
class PlatformSetting extends \App\Domain\Platform\Models\PlatformSetting
{
    // Backward compatibility - extends new Domain model
}
