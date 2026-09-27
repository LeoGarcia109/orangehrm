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

namespace OrangeHRM\Attendance\Service\Form;

/**
 * BR: the vocabulary of the forms module, in one place.
 */
final class FormTypes
{
    public const KIND_QUIZ = 'QUIZ';
    public const KIND_SURVEY = 'SURVEY';

    public const TYPE_CONTENT = 'CONTENT';
    public const TYPE_SINGLE = 'SINGLE';
    public const TYPE_MULTIPLE = 'MULTIPLE';
    public const TYPE_SHORT_TEXT = 'SHORT_TEXT';
    public const TYPE_LONG_TEXT = 'LONG_TEXT';
    public const TYPE_SCALE = 'SCALE';
    public const TYPE_YES_NO = 'YES_NO';

    public const ITEM_TYPES = [
        self::TYPE_CONTENT,
        self::TYPE_SINGLE,
        self::TYPE_MULTIPLE,
        self::TYPE_SHORT_TEXT,
        self::TYPE_LONG_TEXT,
        self::TYPE_SCALE,
        self::TYPE_YES_NO,
    ];

    /** Everything that asks for an answer -- all but CONTENT. */
    public const QUESTION_TYPES = [
        self::TYPE_SINGLE,
        self::TYPE_MULTIPLE,
        self::TYPE_SHORT_TEXT,
        self::TYPE_LONG_TEXT,
        self::TYPE_SCALE,
        self::TYPE_YES_NO,
    ];

    public const CHOICE_TYPES = [self::TYPE_SINGLE, self::TYPE_MULTIPLE];
    public const TEXT_TYPES = [self::TYPE_SHORT_TEXT, self::TYPE_LONG_TEXT];

    /** What counts towards a quiz score. A scale is an opinion, never right or wrong. */
    public const SCORED_TYPES = [
        self::TYPE_SINGLE,
        self::TYPE_MULTIPLE,
        self::TYPE_YES_NO,
        self::TYPE_SHORT_TEXT,
        self::TYPE_LONG_TEXT,
    ];

    public const SCOPE_NETWORK = 'NETWORK';
    public const SCOPE_SUBUNIT = 'SUBUNIT';
    public const SCOPE_EMPLOYEE = 'EMPLOYEE';

    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_PUBLISHED = 'PUBLISHED';
    public const STATUS_CLOSED = 'CLOSED';

    public const SUBMISSION_GRADED = 'GRADED';
    public const SUBMISSION_PENDING_REVIEW = 'PENDING_REVIEW';
    public const SUBMISSION_RECORDED = 'RECORDED';

    public const TITLE_MAX = 150;
    public const DESCRIPTION_MAX = 5000;
    public const OPTION_MAX = 255;
    public const SHORT_TEXT_MAX = 255;
    public const LONG_TEXT_MAX = 5000;
    public const SCALE_MIN = 1;
    public const SCALE_MAX = 5;
    public const ANONYMOUS_MIN_RESPONSES = 3;
    public const IMAGE_MAX_BYTES = 2097152;
    public const IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
}
