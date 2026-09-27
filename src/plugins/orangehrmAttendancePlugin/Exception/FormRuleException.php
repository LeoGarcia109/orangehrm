<?php

/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM is free software: you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with OrangeHRM.
 * If not, see <https://www.gnu.org/licenses/>.
 */

namespace OrangeHRM\Attendance\Exception;

/**
 * BR: a form rule broken, remembering which block broke it so the builder can
 * point at the card instead of leaving HR to hunt for it.
 */
class FormRuleException extends AttendanceServiceException
{
    private ?int $itemPosition = null;

    /**
     * @param int $position 1-based, as HR counts the cards
     */
    public static function at(int $position, string $message): self
    {
        $e = new self("Bloco {$position}: {$message}");
        $e->itemPosition = $position;
        return $e;
    }

    public static function form(string $message): self
    {
        return new self($message);
    }

    public function getItemPosition(): ?int
    {
        return $this->itemPosition;
    }
}
