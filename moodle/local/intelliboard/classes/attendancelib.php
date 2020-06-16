<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This plugin provides access to Moodle data. Attendance API
 *
 * @package    local_intelliboard
 * @copyright  2019 IntelliBoard, Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @website    http://intelliboard.net/
 */

use local_intelliboard\attendance_api;
use local_intelliboard\repositories\attendance_repository;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

class local_intelliboard_attendancelib extends external_api {
    /**
     * Returns description of params of attendance API
     *
     * @return external_function_parameters
     */
    public static function attendance_api_parameters() {
        return new external_function_parameters(
            [
                'params' => new external_single_structure(
                    [
                        'action' => new external_value(PARAM_TEXT, 'API action'),
                        'courseid' => new external_value(
                            PARAM_TEXT, 'Course ID', VALUE_OPTIONAL
                        ),
                        'activity_id' => new external_value(
                            PARAM_TEXT, 'Course Module ID', VALUE_OPTIONAL
                        ),
                        'userid' => new external_value(
                            PARAM_TEXT, 'User ID', VALUE_OPTIONAL
                        ),
                        'role' => new external_value(
                            PARAM_TEXT, 'Role (student, teacher)', VALUE_OPTIONAL
                        ),
                        'report_short_name' => new external_value(
                            PARAM_TEXT, 'Report name', VALUE_OPTIONAL
                        ),
                        'report_params' => new external_value(
                            PARAM_TEXT, 'Params of reports', VALUE_OPTIONAL
                        ),
                    ]
                )
            ]
        );
    }

    /**
     * Attendance API
     *
     * @param array $params Params.
     * @return array
     */
    public static function attendance_api($params) {
        if(self::method_available($params['action'])) {
            $methodname = $params['action'];
            $repository = new attendance_repository();

            return [
                'code' => 200,
                'content' => json_encode($repository->{$methodname}($params))
            ];
        } else {
            return [
                'code' => 400,
                'content' => json_encode(
                    ['response' => 'Method does not exists']
                ),
            ];
        }
    }

    public static function method_available($methodname) {
        $methods = [
            'get_courses', 'get_course', 'is_teacher', 'is_student', 'get_user',
            'has_role', 'is_course_participant', 'get_course_students', 'is_admin',
            'number_of_courses', 'report_data', 'get_course_activities',
            'get_activity', 'get_courses_categories'
        ];

        return in_array($methodname, $methods);
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 2.5
     */
    public static function attendance_api_returns() {
        return new external_single_structure(
            [
                'code' => new external_value(PARAM_INT),
                'content' => new external_value(PARAM_RAW),
            ]
        );
    }
}