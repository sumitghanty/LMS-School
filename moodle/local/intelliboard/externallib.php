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
 * This plugin provides access to Moodle data in form of analytics and reports in real time.
 *
 *
 * @package    local_intelliboard
 * @copyright  2017 IntelliBoard, Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @website    https://intelliboard.net/
 */

use local_intelliboard\helpers\DBHelper;
use local_intelliboard\reports\entities\reportColumnFilter;
use local_intelliboard\reports\entities\reportColumnOrder;

require_once($CFG->libdir . "/externallib.php");

class local_intelliboard_external extends external_api {

    public $params = array();
    public $debug = false;
    public $prfx = 0;
    public $users = [];
    public $courses = [];
    public $categories = [];
    public $cohorts = [];
    public $columns_set = [];
    public $groupAdditionalColumns = '';
    private $selectedExtraColumns = [];

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function database_query_parameters() {
        return new external_function_parameters(
            array('params' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'function' => new external_value(PARAM_ALPHANUMEXT, 'Main Function name', VALUE_REQUIRED),
                            'timestart' => new external_value(PARAM_INT, 'Time start param', VALUE_OPTIONAL, 0),
                            'timefinish' => new external_value(PARAM_INT, 'Time finish param', VALUE_OPTIONAL, 0),
                            'start' => new external_value(PARAM_INT, 'Pagination start', VALUE_OPTIONAL, 0),
                            'length' => new external_value(PARAM_INT, 'Pagination length', VALUE_OPTIONAL, 0),
                            'columns' => new external_value(PARAM_RAW, 'Profile columns', VALUE_OPTIONAL, 0),
                            'completion' => new external_value(PARAM_SEQUENCE, 'Completion status param', VALUE_OPTIONAL, 0),
                            'extra_columns' => new external_value(PARAM_SEQUENCE, 'Extra Columns', VALUE_OPTIONAL, 0),
                            'extra_columns_filter' => new external_value(PARAM_SEQUENCE, 'Extra Columns Filter', VALUE_OPTIONAL, 0),
                            'filter_columns' => new external_value(PARAM_SEQUENCE, 'Filter columns param', VALUE_OPTIONAL, 0),
                            'filter_profile' => new external_value(PARAM_INT, 'Filter profile column param', VALUE_OPTIONAL, 0),
                            'order_column' => new external_value(PARAM_INT, 'Order column param', VALUE_OPTIONAL, 0),
                            'order_dir' => new external_value(PARAM_ALPHA, 'Order direction param', VALUE_OPTIONAL, ''),
                            'filter' => new external_value(PARAM_RAW, 'Filter var', VALUE_OPTIONAL, ''),
                            'custom' => new external_value(PARAM_RAW, 'Custom var', VALUE_OPTIONAL, ''),
                            'custom2' => new external_value(PARAM_RAW, 'Custom2 var', VALUE_OPTIONAL, ''),
                            'custom3' => new external_value(PARAM_RAW, 'Custom3 var', VALUE_OPTIONAL, ''),
                            'custom4' => new external_value(PARAM_RAW, 'Completion state flag', VALUE_OPTIONAL, ''),
                            'teacher_roles' => new external_value(PARAM_SEQUENCE, 'Teacher roles', VALUE_OPTIONAL, 0),
                            'learner_roles' => new external_value(PARAM_SEQUENCE, 'Learner roles', VALUE_OPTIONAL, 0),
                            'users' => new external_value(PARAM_SEQUENCE, 'Users SEQUENCE', VALUE_OPTIONAL, 0),
                            'userid' => new external_value(PARAM_INT, 'Instuctor ID', VALUE_OPTIONAL, 0),
                            'vendor_user_id' => new external_value(PARAM_INT, 'Vendor User ID', VALUE_OPTIONAL, 0),
                            'externalid' => new external_value(PARAM_INT, 'External user ID', VALUE_OPTIONAL, 0),
                            'sizemode' => new external_value(PARAM_INT, 'Size mode', VALUE_OPTIONAL, 0),
                            'debug' => new external_value(PARAM_INT, 'Debug mode', VALUE_OPTIONAL, 0),
                            'request' => new external_value(PARAM_INT, 'Request mode', VALUE_OPTIONAL, 0),
                            'userfilter' => new external_value(PARAM_INT, 'User Filter mode', VALUE_OPTIONAL, 0),
                            'courseid' => new external_value(PARAM_SEQUENCE, 'Course IDs SEQUENCE', VALUE_OPTIONAL, 0),
                            'cohortid' => new external_value(PARAM_SEQUENCE, 'Cohort IDs SEQUENCE', VALUE_OPTIONAL, 0),
                            'filter_user_deleted' => new external_value(PARAM_INT, 'filter_user_deleted', VALUE_OPTIONAL, 0),
                            'filter_user_suspended' => new external_value(PARAM_INT, 'filter_user_suspended', VALUE_OPTIONAL, 0),
                            'filter_user_guest' => new external_value(PARAM_INT, 'filter_user_guest', VALUE_OPTIONAL, 0),
                            'filter_user_active' => new external_value(PARAM_INT, 'filter_user_active', VALUE_OPTIONAL, 0),
                            'filter_user_active_time' => new external_value(PARAM_INT, 'filter_user_active_time', VALUE_OPTIONAL, 0),
                            'filter_user_active_starttime' => new external_value(PARAM_INT, 'filter_user_active_starttime', VALUE_OPTIONAL, 0),
                            'filter_course_visible' => new external_value(PARAM_INT, 'filter_course_visible', VALUE_OPTIONAL, 0),
                            'filter_enrolmethod_status' => new external_value(PARAM_INT, 'filter_enrolmethod_status', VALUE_OPTIONAL, 0),
                            'filter_enrol_status' => new external_value(PARAM_INT, 'filter_enrol_status', VALUE_OPTIONAL, 0),
                            'filter_enrolled_users' => new external_value(PARAM_INT, 'filter_enrolled_users', VALUE_OPTIONAL, 0),
                            'filter_module_visible' => new external_value(PARAM_INT, 'filter_module_visible', VALUE_OPTIONAL, 0),
                            'scales' => new external_value(PARAM_INT, 'custom scales', VALUE_OPTIONAL, 0),
                            'scale_raw' => new external_value(PARAM_INT, 'custom scale_raw', VALUE_OPTIONAL, 0),
                            'scale_real' => new external_value(PARAM_INT, 'custom scale_real', VALUE_OPTIONAL, 0),
                            'scale_total' => new external_value(PARAM_FLOAT, 'custom scale_total', VALUE_OPTIONAL, 0),
                            'scale_value' => new external_value(PARAM_FLOAT, 'custom scale_value', VALUE_OPTIONAL, 0),
                            'scale_percentage' => new external_value(PARAM_INT, 'custom scale_percentage', VALUE_OPTIONAL, 0)
                        )
                    )
                )
            )
        );
    }

    public static function database_query($params) {
        global $CFG, $DB;

        require_once($CFG->dirroot .'/local/intelliboard/locallib.php');
        require_once($CFG->dirroot.'/local/intelliboard/classes/external_functions.php');

        $params = self::validate_parameters(self::database_query_parameters(), array('params' => $params));

        self::validate_context(context_system::instance());

        $params = (object)reset($params['params']);

        $params->userid = isset($params->userid) ? $params->userid : 0;
        $params->vendor_user_id = isset($params->vendor_user_id) ? $params->vendor_user_id : 0;
        $params->courseid = isset($params->courseid) ? $params->courseid : 0;
        $params->cohortid = isset($params->cohortid) ? $params->cohortid : 0;
        $params->externalid = isset($params->externalid) ? $params->externalid : 0;
        $params->users = isset($params->users) ? $params->users : 0;
        $params->start = isset($params->start) ? $params->start : 0;
        $params->length = isset($params->length) ? $params->length : 50;
        $params->filter = isset($params->filter) ? clean_raw($params->filter) : '';
        $params->custom = isset($params->custom) ? clean_raw($params->custom, false) : '';
        $params->custom2 = isset($params->custom2) ? clean_raw($params->custom2) : '';
        $params->custom3 = isset($params->custom3) ? clean_raw($params->custom3) : '';
        $params->custom4 = isset($params->custom4) ? clean_raw($params->custom4) : '';
        $params->columns = isset($params->columns) ? $params->columns : '';
        $params->extra_columns = (isset($params->extra_columns)) ? $params->extra_columns : '';
        $params->extra_columns_filter = (isset($params->extra_columns_filter)) ? $params->extra_columns_filter : '';
        $params->completion = (isset($params->completion)) ? $params->completion : "1,2";
        $params->filter_columns = (isset($params->filter_columns)) ? $params->filter_columns : "0,1";
        $params->filter_profile = (isset($params->filter_profile)) ? $params->filter_profile : 0;
        $params->timestart = (isset($params->timestart)) ? $params->timestart : 0;
        $params->timefinish = (isset($params->timefinish)) ? $params->timefinish : 0;
        $params->sizemode = (isset($params->sizemode)) ? $params->sizemode : 0;
        $params->debug = (isset($params->debug)) ? (int)$params->debug : 0;
        $params->request = (isset($params->request)) ? (int)$params->request : 0;
        $params->userfilter = (isset($params->userfilter)) ? (int)$params->userfilter : 0;
        $params->filter_user_deleted = (isset($params->filter_user_deleted)) ? $params->filter_user_deleted : 0;
        $params->filter_user_suspended = (isset($params->filter_user_suspended)) ? $params->filter_user_suspended : 0;
        $params->filter_user_guest = (isset($params->filter_user_guest)) ? $params->filter_user_guest : 0;
        $params->filter_user_active = (isset($params->filter_user_active)) ? $params->filter_user_active : 0;
        $params->filter_user_active_time = (isset($params->filter_user_active_time)) ? $params->filter_user_active_time : 0;
        $params->filter_user_active_starttime = (isset($params->filter_user_active_starttime)) ? $params->filter_user_active_starttime : 0;
        $params->filter_course_visible = (isset($params->filter_course_visible)) ? $params->filter_course_visible : 0;
        $params->filter_enrolmethod_status = (isset($params->filter_enrolmethod_status)) ? $params->filter_enrolmethod_status : 0;
        $params->filter_enrol_status = (isset($params->filter_enrol_status)) ? $params->filter_enrol_status : 0;
        $params->filter_enrolled_users = (isset($params->filter_enrolled_users)) ? $params->filter_enrolled_users : 0;
        $params->filter_module_visible = (isset($params->filter_module_visible)) ? $params->filter_module_visible : 0;
        $params->teacher_roles = (isset($params->teacher_roles) and $params->teacher_roles) ? $params->teacher_roles : 3;
        $params->learner_roles = (isset($params->learner_roles) and $params->learner_roles) ? $params->learner_roles : 5;
        $params->scales = (isset($params->scales)) ? $params->scales : 0;
        $params->scale_raw = (isset($params->scale_raw)) ? $params->scale_raw : 0;
        $params->scale_real = (isset($params->scale_real)) ? $params->scale_real : 0;
        $params->scale_total = (isset($params->scale_total)) ? $params->scale_total : 0;
        $params->scale_value = (isset($params->scale_value)) ? $params->scale_value : 0;
        $params->scale_percentage = (isset($params->scale_percentage)) ? $params->scale_percentage : 0;

        if ($params->debug) {
            $CFG->debug = (E_ALL | E_STRICT);
            $CFG->debugdisplay = 1;
        }

        if ($params->request) {
            if ($params->function != 'kill_db_queries') {
                $connection_record = new stdClass();
                if ($CFG->dbtype == 'pgsql') {
                    $connection_record->connection_id = $DB->get_field_sql("SELECT pid FROM pg_stat_activity WHERE state = 'active' ");
                } else {
                    $connection_record->connection_id = $DB->get_field_sql('SELECT CONNECTION_ID()');
                }
                $connection_record->timecreated = time();
                $connection_record->id = $DB->insert_record('local_intelliboard_dbconn', $connection_record, true);
            }
        }



        $transaction = $DB->start_delegated_transaction();
        $obj = new local_intelliboard_external();
        $external = new local_external_functions();

        $obj->columns_set = $external->get_columns();

        if (isset($params->debug) and $params->debug === 4){
            return json_encode([$external->get_functions()]);
        }else if (isset($params->debug) and $params->debug === 5){
            ob_start();
            var_dump($external->get_functions());
            $functions_dump = ob_get_clean();

            ob_start();
            var_dump($params);
            $params_dump = ob_get_clean();

            return json_encode(['functions' => $functions_dump, 'params' => $params_dump]);
        }else if ($function = $external->get_function($params)) {
            $data = $obj->{$function}($params);
        } else {
            $data = json_encode(['function_not_available']);
        }


        $transaction->allow_commit();

        if ($params->request and isset($connection_record)) {
            $DB->delete_records('local_intelliboard_dbconn', array('id' => $connection_record->id));
        }

        return json_encode($data);
    }
    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function database_query_returns() {
        return new external_value(PARAM_RAW, 'Moodle DB records');
    }

    private function get_limit_sql($params)
    {
        global $CFG;

        if ($CFG->dbtype == 'pgsql') {
            return (isset($params->start) and $params->length != 0 and $params->length != -1) ? " LIMIT $params->length OFFSET $params->start" : "";
        } else {
            return (isset($params->start) and $params->length != 0 and $params->length != -1) ? " LIMIT $params->start, $params->length" : "";
        }
    }

    private function get_order_sql($params, $columns)
    {
        if (isset($params->order_column) and isset($columns[$params->order_column]) and $params->order_dir) {
            $columnorder = new reportColumnOrder($columns[$params->order_column], $params->order_dir);

            return $columnorder->getOrderSQL();
        }

        return "";
    }

    private function get_filter_course_sql($params, $prefix)
    {
        return ($params->filter_course_visible) ? "" : " AND {$prefix}visible = 1";
    }
    private function get_filter_enrol_sql($params, $prefix = '', $field = '')
    {
        if (!$field) {
            $field = "{$prefix}status";
        }

        return ($params->filter_enrol_status) ? "" : " AND {$field} = 0";
    }

    private function get_filter_module_sql($params, $prefix)
    {
        return ($params->filter_module_visible) ? "" : " AND {$prefix}visible = 1";
    }
    private function get_filter_enrolled_users_sql($params, $column)
    {
        $sql = $this->get_filter_course_sql($params, 'c.');
        $sql .= $this->get_filter_enrol_sql($params, 'e.');
        $sql .= $this->get_filter_enrol_sql($params, 'ue.');

        return ($params->filter_enrolled_users) ? " AND {$column} IN (SELECT DISTINCT userid FROM {user_enrolments} ue, {enrol} e, {course} c WHERE ue.enrolid = e.id AND c.id = e.courseid $sql)" : "";
    }
    private function get_filter_user_sql($params, $prefix, $enrols = false)
    {
        $filter = ($params->filter_user_deleted) ? "" : " AND {$prefix}deleted = 0";
        $filter .= ($params->filter_user_suspended) ? "" : " AND {$prefix}suspended = 0";
        $filter .= ($params->filter_user_guest) ? "" : " AND {$prefix}username <> 'guest'";
        if ($params->filter_user_active and $params->filter_user_active_time and $params->filter_user_active_starttime) {
          $filter .= " AND {$prefix}lastaccess > " . intval($params->filter_user_active_starttime);
        } else {
          $filter .= ($params->filter_user_active) ? " AND {$prefix}lastaccess > 0" : "";
        }
        return $filter;
    }

    private function get_filter_columns($params, $fields = [])
    {
        global $CFG;

        $data = [];

        if ($CFG->dbtype == 'pgsql') {
            if (isset($params->extra_columns) and $params->extra_columns) {
                $course_field = isset($fields[1]) ? $fields[1] : "c";
                $activity_field = isset($fields[2]) ? $fields[2] : "cm";
                $columns = explode(",", $params->extra_columns);

                foreach($columns as $index){
                    $index = clean_param($index, PARAM_INT);

                    if (isset($this->columns_set[$index])) {
                        $column = $this->columns_set[$index];
                        $column = str_replace(['course_alias_', 'cm_alias_'], [$course_field, $activity_field], $column);
                        $data[] = $column;
                    }
                }
            }

            if (!empty($params->columns) && ((isset($fields[0]) && $fields[0] != null) || !isset($fields[0]))) {
                $columns = explode(",", $params->columns);
                $field = isset($fields[0]) ? $fields[0] : "u.id";
                $alias = explode('.',$field);
                foreach($columns as $column){
                    $data[] = "{$alias[0]}.{$column}";
                }
            }
        } else {
            if (isset($params->extra_columns) and $params->extra_columns) {
                $columns = explode(",", $params->extra_columns);
                foreach($columns as $index){
                    $index = clean_param($index, PARAM_INT);
                    if (isset($this->columns_set[$index])) {
                        $data[] = "column$index"; // {$column} defined in each report
                    }
                }
            }

            if (!empty($params->columns)) {
                $columns = explode(",", $params->columns);
                foreach($columns as $column){
                    $data[] = "field$column"; // {$column} defined in each report
                }
            }
        }
        return $data;
    }
    private function get_columns($params, $fields = [])
    {
        global $CFG;

        $data = "";
        if (isset($params->extra_columns) and $params->extra_columns) {
            $extra_columns = explode(",", $params->extra_columns);
            foreach ($extra_columns as $index) {
              $index = clean_param($index, PARAM_INT);

              $course_field = isset($fields[1]) ? $fields[1] : "c";
              $activity_field = isset($fields[2]) ? $fields[2] : "cm";

              if (isset($this->columns_set[$index])) {
                $column = $this->columns_set[$index];
                $column = str_replace(['course_alias_', 'cm_alias_'], [$course_field, $activity_field], $column);
                $data .= ", $column AS column$index";
                $this->selectedExtraColumns[$index] = $CFG->dbtype == 'pgsql' ? $column : "column{$index}";
              }
            }
        }
        if (!empty($params->columns)) {
            $field = isset($fields[0]) ? $fields[0] : "u.id";
            $columns = explode(",", $params->columns);
            foreach ($columns as $column) {
                if ($column == clean_param($column,PARAM_SEQUENCE)) {
                    $this->prfx = $this->prfx + 1;
                    $key = "column$column" . $this->prfx;
                    $this->params[$key] = $column;
                    $this->groupAdditionalColumns .= ", field$column";
                    $data .= ", (SELECT CONCAT(d.data,'[[[field_type]]]',f.datatype) FROM {user_info_data} d, {user_info_field} f WHERE f.id = :$key AND d.fieldid = f.id AND d.userid = $field) AS field$column";
                } else {
                    $alias = explode('.',$field);
                    $column = clean_param($column, PARAM_ALPHANUM);
                    $data .= ", ".$alias[0].".".$column." AS field".$column;
                }
            }
        }
        return $data;
    }
    private function get_params_key($column, $value){
        $this->prfx = $this->prfx + 1;
        $key = clean_param($column, PARAM_ALPHANUMEXT).$this->prfx;
        $this->params[$key] = $value;
        return $key;
    }
    private function get_completion($params, $prefix = "", $sep = true)
    {
        if (!empty($params->completion)) {
            return $this->get_filter_in_sql($params->completion, $prefix."completionstate", $sep);
        } else {
            $prefix = ($sep) ? " AND ".$prefix : $prefix;
            return $prefix . "completionstate IN(1,2)";
        }
    }

    /**
     * @param $params
     * @param $columns
     * @param bool $hasgrouping
     * @return string
     * @throws coding_exception
     * @throws dml_exception
     * @throws Exception
     */
    private function get_filter_sql($params, $columns, $hasgrouping = true)
    {
        global $DB, $CFG;

        $texttypecast = DBHelper::get_typecast("text");

        $filter = "";

        //Filter by report columns
        if ($params->filter and !empty($columns)) {
            $sql_arr = array();
            $filter_columns = explode(",", $params->filter_columns);

            foreach($columns as $i => $column) {
                if ($column and in_array($i, $filter_columns)) {
                    $this->prfx = $this->prfx + 1;
                    $columnfilter = new reportColumnFilter($column, $params->filter, $this->prfx);
                    $sql_arr[] = $columnfilter->getFilterSQL();
                    $this->params[$columnfilter->getFilterKey()] = $columnfilter->getFilterValue();
                }
            }
            $filter .= ($sql_arr) ? implode(" OR ", $sql_arr) : "";
        }

        // filter by extra columns
        if ($params->filter && $params->extra_columns_filter) {
            $extracolumnsfilter = [];
            $selectedextracolumns = array_keys($this->selectedExtraColumns);

            foreach (explode(',', $params->extra_columns_filter) as $col) {
                if (in_array($col, $selectedextracolumns)) {
                    $filtercol = $this->selectedExtraColumns[$col];

                    if (in_array($col, [9, 10])) { // search by course or activity tags
                        $filtertags = explode(',', $params->filter);

                        foreach ($filtertags as $filtertag) {
                            $this->prfx = $this->prfx + 1;
                            $key = 'extracol' . $this->prfx;
                            $extracolumnsfilter[] = $DB->sql_like("{$filtercol}{$texttypecast}", ":{$key}", false, false);
                            $this->params[$key] = "%" . trim($filtertag) . "%";
                        }
                    } else {
                        $this->prfx = $this->prfx + 1;
                        $key = 'extracol' . $this->prfx;
                        $extracolumnsfilter[] = $DB->sql_like("{$filtercol}{$texttypecast}", ":{$key}", false, false);
                        $this->params[$key] = "%{$params->filter}%";
                    }
                }
            }

            $filter .= ($filter ? " OR " : "") . implode(" OR ", $extracolumnsfilter);
        }

        //Filter by User profile fields
        if ($params->filter_profile){
            $params->custom3 = clean_param($params->custom3, PARAM_SEQUENCE);

            if ($params->custom3 and !empty($params->columns)) {
                $cols = explode(",", $params->columns);
                $fields = $DB->get_records_sql("SELECT id, fieldid, data FROM {user_info_data} WHERE id IN ($params->custom3)");
                $fields_filter = array();

                foreach($fields as $i => $field){
                    if (in_array($field->fieldid, $cols)){
                        $this->prfx = $this->prfx + 1;
                        $field->fieldid = (int)$field->fieldid; //fieldid -> int

                        if ($CFG->dbtype == 'pgsql') {
                            $key = "(SELECT data FROM {user_info_data} WHERE fieldid = $field->fieldid AND userid = u.id)";
                        } else {
                            $key = "field$field->fieldid";
                        }

                        $unickey = "field{$this->prfx}_{$field->fieldid}_{$i}";
                        $fields_filter[] = $DB->sql_like($key . $texttypecast, ":$unickey", false, false);
                        $this->params[$unickey] = "%{$field->data}%";
                    }
                }

                $filter = ($fields_filter and $filter) ? "($filter) AND " : $filter;
                $filter .= ($fields_filter) ? " (" . implode(" OR ", $fields_filter) .") " : "";
            }
        }

        if ($filter) {
            if ($CFG->dbtype == 'pgsql' && !$hasgrouping) {
                return " AND ({$filter})";
            }

            return " HAVING {$filter}";
        }

        return '';
    }

    private function get_filterdate_sql($params, $column)
    {
        if($params->timestart and $params->timefinish){
            $this->prfx = $this->prfx + 1;
            $timestart = 'tmstart'.$this->prfx;
            $timefinish = 'tmfinish'.$this->prfx;
            $this->params[$timestart] = $params->timestart;
            $this->params[$timefinish] = $params->timefinish;

            return " AND $column BETWEEN :$timestart AND :$timefinish ";
        }
        return "";
    }
    private function get_filter_in_sql($sequence, $column, $sep = true, $equal = true)
    {
        global $DB;

        if($sequence){
            $items = (is_array($sequence)) ? $sequence : explode(",", clean_param($sequence, PARAM_SEQUENCE));
            if(!empty($items)){
                $this->prfx = $this->prfx + 1;
                $key = clean_param($column.$this->prfx, PARAM_ALPHANUM);
                list($sql, $params) = $DB->get_in_or_equal($items, SQL_PARAMS_NAMED, $key, $equal);
                $this->params = array_merge($this->params, $params);
                return ($sep) ? " AND $column $sql ": " $column $sql ";
            }
        }
        return '';
    }
    private function get_report_data($query, $params, $wrap = true)
    {
        global $DB;

        if (isset($params->debug) and $params->debug === 2){
            return array($query, $this->params);
        }

        if(isset($params->start) and $params->length != 0 and $params->length != -1){
            $data = $DB->get_records_sql($query, $this->params, $params->start, $params->length);
        }else{
            $data = $DB->get_records_sql($query, $this->params);
        }
        return ($wrap) ? array("data" => $data) : $data;
    }
    private function get_modules_sql($filter, $result = false, $name = 'activity')
    {
        global $DB;

        $list = clean_param($filter, PARAM_SEQUENCE);
        $sql_mods = $this->get_filter_in_sql($list, "m.id");
        $sql_columns = "";
        $modules = $DB->get_records_sql("SELECT m.id, m.name FROM {modules} m WHERE m.visible = 1 $sql_mods", $this->params);
        if ($result) {
          return $modules;
        }
        foreach($modules as $module){
            $sql_columns .= " WHEN m.name='{$module->name}' THEN (SELECT name FROM {".$module->name."} WHERE id = cm.instance)";
        }
        return ($sql_columns) ? ", CASE $sql_columns ELSE 'NONE' END AS $name" : "'' AS $name";
    }
    private function get_suspended_sql($params, $courseid = 'c.id', $userid = 'u.id', $coursefilter = true)
    {
      if (!$params->userfilter) {
        return '';
      }
      $sql = '';
      $sql_user = $this->get_filter_user_sql($params, "u.");
      $sql_course = $this->get_filter_course_sql($params, "c.");
      $sql_enrol = $this->get_filter_enrol_sql($params, "ue.");
      $sql_enrol .= $this->get_filter_enrol_sql($params, "e.");
      $sql_role = $this->get_filter_in_sql($params->learner_roles, "ra.roleid");

      if ($params->userfilter == 1) {
        $sql_filter = ($coursefilter) ? $this->get_filter_in_sql($params->courseid, "e.courseid") : '';

        $sql = "SELECT ue.userid, e.courseid FROM {user_enrolments} ue, {enrol} e WHERE e.id = ue.enrolid $sql_enrol $sql_filter GROUP BY ue.userid, e.courseid";
      } elseif ($params->userfilter == 2) {
        $sql_filter = ($coursefilter) ? $this->get_filter_in_sql($params->courseid, "e.courseid") : '';

        $sql = "SELECT ue.userid, e.courseid FROM {user_enrolments} ue, {enrol} e, {user} u, {course} c WHERE u.id = ue.userid AND c.id = e.courseid AND e.id = ue.enrolid $sql_course $sql_user $sql_enrol $sql_filter GROUP BY ue.userid, e.courseid";
      } elseif ($params->userfilter == 3) {
        $sql_filter = ($coursefilter) ? $this->get_filter_in_sql($params->courseid, "e.courseid") : '';

        $sql = "SELECT ue.userid, e.courseid FROM {user_enrolments} ue, {enrol} e, {context} ctx, {role_assignments} ra, {user} u, {course} c WHERE ctx.contextlevel = 50 AND ctx.instanceid = c.id AND ra.userid = u.id AND ctx.id = ra.contextid AND u.id = ue.userid AND c.id = e.courseid AND e.id = ue.enrolid $sql_role $sql_course $sql_user $sql_enrol $sql_filter GROUP BY ue.userid, e.courseid";
      } elseif ($params->userfilter == 4) {
        $sql_filter = ($coursefilter) ? $this->get_filter_in_sql($params->courseid, "ctx.instanceid") : '';

        $sql = "SELECT ra.userid, ctx.instanceid AS courseid FROM {context} ctx, {role_assignments} ra, {user} u, {course} c WHERE ctx.contextlevel = 50 AND ctx.instanceid = c.id AND ra.userid = u.id AND ctx.id = ra.contextid AND c.id = ctx.instanceid $sql_role $sql_course $sql_user $sql_filter GROUP BY ra.userid, ctx.instanceid";
      } elseif ($params->userfilter == 5) {
        $sql_filter = ($coursefilter) ? $this->get_filter_in_sql($params->courseid, "ctx.instanceid") : '';

        $sql = "SELECT ra.userid, ctx.instanceid AS courseid FROM {context} ctx, {role_assignments} ra WHERE ctx.contextlevel = 50 AND ctx.id = ra.contextid $sql_role $sql_filter GROUP BY ra.userid, ctx.instanceid";
      }
      return ($sql) ? " JOIN ($sql) ucf ON ucf.userid = $userid AND ucf.courseid = $courseid" : "";
    }

    public function report1($params)
    {
        global $CFG;
        $columns = array_merge(
          array(
            "u.firstname",
            "u.lastname",
            "u.username",
            "u.email",
            "c.fullname",
            "c.shortname",
            "enrols",
            "l.visits",
            "l.timespend",
            "grade",
            "cc.timecompleted",
            "enrolled",
            "ul.timeaccess",
            "ue.timeend",
            "cc.timecompleted",
            "u.idnumber",
            "u.phone1",
            "u.phone2",
            "u.institution",
            "u.department",
            "u.address",
            "u.city",
            "u.country",
            "teacher",
            "cohortname"),
            $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_join_filter = ""; $sql_mode = 0;
        $grade_single = intelliboard_grade_sql(false, $params);

        $sql_join = "";
        if(isset($params->custom) and  strrpos($params->custom, ',') !== false){
            $sql_filter .= $this->get_filter_in_sql($params->custom, "u.id");
            $sql_filter_column = "ue.timecreated";
        }elseif(isset($params->custom) and $params->custom == 3){
            $sql_filter .= " AND (cc.timecompleted IS NULL OR (1 ".$this->get_filterdate_sql($params, "cc.timecompleted")."))";
            $sql_mode = 2;
        }elseif(isset($params->custom) and $params->custom == 2 and !$params->sizemode){
            $sql_filter_column = "l.timepoint";
            $sql_mode = 1;
        }elseif(isset($params->custom) and $params->custom == 1){
            $sql_filter_column = "cc.timecompleted";
        }else{
            $sql_filter_column = "ue.timecreated";
        }
        if($sql_mode == 2){
            // do nothing, it is for obix
        }elseif($sql_mode){
            $sql_join_filter .= $this->get_filterdate_sql($params, "$sql_filter_column");
        }else{
            $sql_filter .= $this->get_filterdate_sql($params, "$sql_filter_column");
        }
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "ue.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "e.");
        $sql_filter .= $this->vendor_filter('ue.userid', 'c.id', $params);


        if($params->cohortid){
            if ($CFG->dbtype == 'pgsql') {
                $cohorts = "string_agg( DISTINCT coh.name, ', ')";
            } else {
                $cohorts = "GROUP_CONCAT( DISTINCT coh.name)";
            }
            $sql_columns .= ", coo.cohortname AS cohortname";
            $sql_cohort = $this->get_filter_in_sql($params->cohortid, "ch.cohortid");
            $sql_join = " LEFT JOIN (SELECT ch.userid, $cohorts as cohortname FROM {cohort} coh, {cohort_members} ch WHERE coh.id = ch.cohortid $sql_cohort GROUP BY ch.userid) coo ON coo.userid = u.id";
            $sql_filter .= " AND coo.cohortname IS NOT NULL";
        } else {
          $sql_columns .= ", '' as cohortname";
        }

        if($params->sizemode){
            $sql_columns .= ", '0' AS timespend, '0' AS visits";
        }elseif($sql_mode == 1){
            $sql_columns .= ", l.timespend, l.visits";
            $sql_join .= " LEFT JOIN (SELECT t.id,t.userid,t.courseid, SUM(l.timespend) AS timespend, SUM(l.visits) AS visits FROM
                                {local_intelliboard_tracking} t,
                                {local_intelliboard_logs} l
                            WHERE l.trackid = t.id $sql_join_filter GROUP BY t.courseid, t.userid) l ON l.courseid = c.id AND l.userid = u.id";
        }else{
            $sql_columns .= ", l.timespend, l.visits";
            $sql_join .= " LEFT JOIN (SELECT t.userid,t.courseid, SUM(t.timespend) AS timespend, SUM(t.visits) AS visits FROM
                                {local_intelliboard_tracking} t GROUP BY t.courseid, t.userid) l ON l.courseid = c.id AND l.userid = u.id";
        }
        $sql_teacher_roles = $this->get_filter_in_sql($params->teacher_roles, "ra.roleid");
        if ($CFG->dbtype == 'pgsql') {
            $group_concat = "string_agg( DISTINCT CONCAT(u.firstname,' ',u.lastname), ', ')";
        } else {
            $group_concat = "GROUP_CONCAT(DISTINCT CONCAT(u.firstname,' ',u.lastname) SEPARATOR ', ')";
        }

        if($params->custom4 == 1) {
            $sql_filter .= ' AND cc.timecompleted IS NOT NULL';
        } elseif($params->custom4 == 2) {
            $sql_filter .= ' AND cc.timecompleted IS NULL';
        }

        if(!empty($params->custom2)){
            $sql_roles_filter = $this->get_filter_in_sql($params->custom2, "ra.roleid");
            $sql_roles_filter .= $this->get_filter_in_sql($params->courseid, "ctx.instanceid");
            $sql_join .= " JOIN (SELECT DISTINCT ra.userid, ctx.instanceid
                    FROM {role_assignments} AS ra
                        JOIN {role} r ON r.id=ra.roleid
                        JOIN {context} AS ctx ON ctx.id = ra.contextid
                    WHERE ctx.contextlevel = 50 $sql_roles_filter) rl ON rl.userid=u.id AND rl.instanceid=c.id";
        }

        return $this->get_report_data("
            SELECT ue.id,
                (CASE WHEN ue.timestart > 0 THEN ue.timestart ELSE ue.timecreated END) AS enrolled,
                ue.timeend,
                ul.timeaccess,
                $grade_single AS grade,
                c.enablecompletion,
                cc.timecompleted AS complete,
                u.id AS uid,
                u.email,
                u.idnumber,
                u.phone1,
                u.phone2,
                u.institution,
                u.department,
                u.address,
                u.city,
                u.country,
                u.firstname,
                u.lastname,
                u.username,
                e.enrol AS enrols,
                c.id AS cid,
                c.fullname AS course,
                c.shortname,
                c.timemodified AS start_date,
                (SELECT $group_concat
                    FROM {role_assignments} AS ra
                        JOIN {user} u ON ra.userid = u.id
                        JOIN {context} AS ctx ON ctx.id = ra.contextid
                    WHERE ctx.instanceid = c.id AND ctx.contextlevel = 50 $sql_teacher_roles
                ) AS teacher
                $sql_columns
            FROM {user_enrolments} ue
                JOIN {enrol} e ON e.id = ue.enrolid
                JOIN {user} u ON u.id = ue.userid
                JOIN {course} c ON c.id = e.courseid
                LEFT JOIN {user_lastaccess} ul ON ul.courseid = c.id AND ul.userid = u.id
                LEFT JOIN {course_completions} cc ON cc.course = e.courseid AND cc.userid = ue.userid
                LEFT JOIN {grade_items} gi ON gi.itemtype = 'course' AND gi.courseid = e.courseid
                LEFT JOIN {grade_grades} g ON g.userid = u.id AND g.itemid = gi.id AND g.finalgrade IS NOT NULL
                $sql_join
            WHERE ue.id > 0 $sql_filter $sql_having $sql_order", $params);
    }
    public function report2($params)
    {
        $columns = array_merge(array(
            "category",
            "c.fullname",
            "c.shortname",
            "c.idnumber",
            "c.visible",
            "learners",
            "modules",
            "completed",
            "visits",
            "timespend",
            "grade",
            "c.timecreated",
        ), $this->get_filter_columns($params, [null]));
        $sql_columns = $this->get_columns($params, [null]);
        $sql_filter = $this->get_teacher_sql($params, ["c.id" => "courses", "ue.userid" => "users"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "ue.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "e.");
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $grade_avg = intelliboard_grade_sql(true, $params);
        $sql_vendor_filter = $this->vendor_filter('ue.userid', 'c.id', $params);
        $sql_vendor_filter1 = $this->vendor_filter('userid', 'courseid', $params);
        $sql_filter_tracking = '';
        $sql_compl = "";
        if ($params->custom == 1) {
            $sql_compl = $this->get_filterdate_sql($params, "cc.timecompleted");
        } elseif ($params->custom == 2) {
            $sql_filter .= $this->get_filterdate_sql($params, "ue.timecreated");
        } elseif ($params->custom == 3) {
            $sql_filter_tracking = $this->get_filterdate_sql($params, "firstaccess");
        } else {
            $sql_filter .= $this->get_filterdate_sql($params, "c.timecreated");
        }
        return $this->get_report_data("
            SELECT c.id,
                c.fullname AS course,
                c.shortname,
                c.timecreated AS created,
                c.idnumber,
                c.enablecompletion,
                c.visible,
                $grade_avg AS grade,
                MAX(l.timespend) AS timespend,
                MAX(l.visits) AS visits,
                COUNT(DISTINCT cc.userid) AS completed,
                COUNT(DISTINCT ue.userid) AS learners,
                (SELECT COUNT(id) FROM {course_modules} WHERE visible = 1 AND course = c.id) AS modules,
                (SELECT name FROM {course_categories} WHERE id = c.category) AS category
                $sql_columns
            FROM {course} c
                LEFT JOIN {enrol} e ON e.courseid = c.id
                LEFT JOIN {user_enrolments} ue ON ue.enrolid=e.id
                LEFT JOIN {course_completions} cc ON cc.timecompleted > 0 AND cc.course = c.id AND cc.userid = ue.userid $sql_compl
                LEFT JOIN {grade_items} gi ON gi.itemtype = 'course' AND gi.courseid = c.id
                LEFT JOIN {grade_grades} g ON g.userid = ue.userid AND g.itemid = gi.id AND g.finalgrade IS NOT NULL
                LEFT JOIN (SELECT courseid, SUM(timespend) AS timespend, SUM(visits) AS visits
                             FROM {local_intelliboard_tracking}
                            WHERE id > 0 {$sql_filter_tracking} {$sql_vendor_filter1}
                         GROUP BY courseid
                          ) l ON l.courseid = c.id
            WHERE c.id > 0 $sql_filter {$sql_vendor_filter} GROUP BY c.id $sql_having $sql_order", $params);
    }
    public function report3($params)
    {
        $columns = array_merge(
            array(
                "activity", "m.name", "completed", "l.visits", "l.timespend",
                "grade", "cm.added", "l.firstaccess", "c.fullname"
            ),
            $this->get_filter_columns($params, [null])
        );

        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_filter = $this->get_teacher_sql($params, ["c.id" => "courses"]);
        $sql_filter .= $this->get_filter_module_sql($params, "cm.");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "cm.course");
        $sql_filter .= $this->get_filter_in_sql($params->custom, "m.id");
        $sql_columns = $this->get_modules_sql($params->custom);
        $sql_columns .= $this->get_columns($params, [null]);
        $sql_order = $this->get_order_sql($params, $columns);
        $grade_avg = intelliboard_grade_sql(true, $params);
        $completion = $this->get_completion($params, "");

        if ($params->custom3 == 1) {
            $sql_filter .= $this->get_filterdate_sql($params, 'l.firstaccess');
        } else {
            $sql_filter .= $this->get_filterdate_sql($params, 'cm.added');
        }

        return $this->get_report_data(
            "SELECT cm.id,
                    m.name AS moduletype,
                    cm.added,
                    cm.completion,
                    c.fullname,
                    l.timespend AS timespend,
                    l.visits AS visits,
                    l.firstaccess AS firstaccess,
                    (SELECT COUNT(id) FROM {course_modules_completion} WHERE coursemoduleid = cm.id $completion) AS completed,
                    (SELECT $grade_avg FROM {grade_items} gi, {grade_grades} g WHERE gi.itemtype = 'mod' AND gi.itemmodule = m.name AND gi.iteminstance = cm.instance AND g.itemid = gi.id AND g.finalgrade IS NOT NULL) AS grade
                    {$sql_columns}
               FROM {course_modules} cm
               JOIN {modules} m ON m.id = cm.module
               JOIN {course} c ON c.id = cm.course
          LEFT JOIN (SELECT param,
                            SUM(timespend) AS timespend,
                            SUM(visits) AS visits,
                            MIN(firstaccess) AS firstaccess
                       FROM {local_intelliboard_tracking}
                      WHERE page='module'
                   GROUP BY param
                    ) l ON l.param = cm.id
              WHERE cm.id > 0 $sql_filter $sql_having $sql_order",
            $params
        );
    }
    public function report4($params)
    {
        global $CFG;
        $columns = array_merge(array("u.firstname","u.lastname","u.email","registered","courses","completed_activities","completed_courses","visits","timespend","grade", "u.lastaccess", "cohortname"), $this->get_filter_columns($params));

        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "ctx.instanceid" => "courses"]);
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_in_sql($params->custom2, "ra.roleid");
        $sql_filter .= $this->vendor_filter('u.id', 'ctx.instanceid', $params);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_columns = $this->get_columns($params, ["u.id"]);
        $grade_avg = intelliboard_grade_sql(true, $params);
        $completion = $this->get_completion($params, "");

        $sql_join = "";


        $sql_raw = true;
        $sql_join_filter = "";
        if(isset($params->custom) and $params->custom == 1){
            $sql_join_filter .= $this->get_filterdate_sql($params, "l.timepoint");
            $sql_raw = false;
        }else{
            $sql_filter .= $this->get_filterdate_sql($params, "u.timecreated");
        }

        $sql_filter_course = ($params->filter_course_visible) ? "" : " JOIN {course} c ON t.courseid=c.id AND c.visible = 1";
        if($params->sizemode and $sql_raw){
            $sql_columns .= ", '0' as timespend, '0' as visits";
            $sql_join = "";
        }else{
            if($sql_raw){
                $sql_columns .= ", MAX(lit.timespend) AS timespend, MAX(lit.visits) AS visits";
                $sql_join .= " LEFT JOIN (SELECT t.userid, SUM(t.timespend) as timespend, SUM(t.visits) as visits FROM
                            {local_intelliboard_tracking} t
                            $sql_filter_course
                        WHERE t.courseid > 0 GROUP BY t.userid) as lit ON lit.userid = u.id";
            }else{
                $sql_columns .= ", MAX(lit.timespend) AS timespend, MAX(lit.visits) AS visits";
                $sql_join .= " LEFT JOIN (SELECT t.userid, SUM(l.timespend) as timespend, SUM(l.visits) as visits
                                            FROM {local_intelliboard_logs} l
                                            JOIN {local_intelliboard_tracking} t ON t.id = l.trackid
                                                 $sql_filter_course
                                           WHERE l.trackid = t.id AND t.courseid > 0
                                                 $sql_join_filter
                                        GROUP BY t.userid
                                          ) as lit ON lit.userid = u.id";
            }
        }

        if($params->cohortid){
            if ($CFG->dbtype == 'pgsql') {
                $cohorts = "string_agg( DISTINCT coh.name, ', ')";
            } else {
                $cohorts = "GROUP_CONCAT( DISTINCT coh.name)";
            }
            $sql_columns .= ", coo.cohortname AS cohortname";
            $sql_cohort = $this->get_filter_in_sql($params->cohortid, "ch.cohortid");
            $sql_join .= " LEFT JOIN (SELECT ch.userid, $cohorts as cohortname FROM {cohort} coh, {cohort_members} ch WHERE coh.id = ch.cohortid $sql_cohort GROUP BY ch.userid) coo ON coo.userid = u.id";
            $sql_filter .= " AND coo.cohortname IS NOT NULL";
        } else {
          $sql_columns .= ", '' as cohortname";
        }


        return $this->get_report_data(
            "SELECT u.id,
                    u.firstname,
                    u.lastname,
                    u.email,
                    u.lastaccess,
                    u.timecreated as registered,
                    $grade_avg as grade,
                    COUNT(DISTINCT ctx.instanceid) as courses,
                    COUNT(DISTINCT cc.id) as completed_courses,
                    MAX(ca.completed_activities) AS completed_activities
                    $sql_columns
               FROM {user} u
          LEFT JOIN {role_assignments} ra ON ra.userid = u.id
          LEFT JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel=50
          LEFT JOIN (SELECT COUNT(id) AS completed_activities, userid
                       FROM {course_modules_completion}
                      WHERE id > 0 $completion
                   GROUP BY userid
                    ) ca ON ca.userid = u.id
          LEFT JOIN {course_completions} cc ON cc.course = ctx.instanceid AND
                                               cc.userid = u.id AND cc.timecompleted > 0
          LEFT JOIN {grade_items} gi ON gi.courseid = ctx.instanceid AND
                                        gi.itemtype = 'course'
          LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.userid = u.id AND
                                        g.finalgrade IS NOT NULL
                    $sql_join
              WHERE u.id > 0 $sql_filter
           GROUP BY u.id $sql_having $sql_order",
            $params
        );
    }
    public function report5($params)
    {
        $columns = array_merge(array(
          "u.firstname",
          "u.lastname",
          "u.email",
          "t.page",
          "t.param",
          "c.fullname",
          "visits",
          "timespend",
          "t.firstaccess",
          "t.lastaccess",
          "t.useragent",
          "t.useros",
          "t.userlang",
          "t.userip",
          "u.timecreated"), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql(
            $params, ["u.id" => "users", "c.id" => "courses"]
        );
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "t.courseid");
        $sql_join = "";

        if($params->custom == 3) {
            $sql_columns .= ", l.timespend as timespend, l.visits as visits, l.lastaccess";
            $sql_filter_join = $this->get_filterdate_sql($params, "timepoint");
            $sql_join = "JOIN (SELECT trackid, SUM(timespend) as timespend, SUM(visits) as visits, MAX(timepoint) as lastaccess FROM {local_intelliboard_logs} WHERE id > 0 $sql_filter_join GROUP BY trackid) l ON l.trackid = t.id";
        } elseif($params->custom == 2) {
          $sql_columns .= ", t.timespend as timespend, t.visits as visits, t.lastaccess";
          $sql_filter .= $this->get_filterdate_sql($params, "t.lastaccess");
        } elseif($params->custom == 1) {
          $sql_columns .= ", t.timespend as timespend, t.visits as visits, t.lastaccess";
          $sql_filter .= $this->get_filterdate_sql($params, "t.firstaccess");
        } else {
            $sql_columns .= ", t.timespend as timespend, t.visits as visits, t.lastaccess";
            $sql_filter .= $this->get_filterdate_sql($params, "u.timecreated");
        }
        $sql_join .= $this->get_suspended_sql($params);

        return $this->get_report_data(
            "SELECT t.id,
                    t.userid,
                    t.courseid,
                    t.page,
                    t.param,
                    t.firstaccess,
                    t.useragent,
                    t.useros,
                    t.userlang,
                    t.userip,
                    c.fullname,
                    u.firstname,
                    u.lastname,
                    u.email,
                    u.timecreated
                    $sql_columns
               FROM {local_intelliboard_tracking} t
               JOIN {user} u ON u.id = t.userid
          LEFT JOIN {course} c ON c.id = t.courseid
          $sql_join
              WHERE t.id > 0 $sql_filter $sql_having $sql_order",
            $params
        );
    }
    public function report6($params)
    {
        global $CFG;
        $columns = array_merge(array(
            "u.firstname",
            "u.lastname",
            "email",
            "c.fullname",
            "started",
            "grade",
            "grade",
            "completed",
            "grade",
            "complete",
            "visits",
            "timespend",
            "teacher",
            "cohortname",
            "e.enrol"
        ), $this->get_filter_columns($params));

        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "e.courseid");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "ue.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "e.");
        $sql_filter .= $this->get_filterdate_sql($params, "ue.timecreated");
        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $grade_avg = intelliboard_grade_sql(false, $params, 'g.', 0, 'gi.',true);
        $grade_avg_real = intelliboard_grade_sql(false, $params, 'g.', 0, 'gi.');
        $sql_columns = $this->get_columns($params, ["u.id"]);
        $completion = $this->get_completion($params, "cmc.");
        $sql_teacher_roles = $this->get_filter_in_sql($params->teacher_roles, "ra.roleid");
        if ($CFG->dbtype == 'pgsql') {
            $group_concat = "string_agg( DISTINCT CONCAT(u.firstname,' ',u.lastname), ', ')";
        } else {
            $group_concat = "GROUP_CONCAT(DISTINCT CONCAT(u.firstname,' ',u.lastname) SEPARATOR ', ')";
        }

        $sql_join = "";
        if($params->cohortid) {
            if ($CFG->dbtype == 'pgsql') {
                $cohorts = "string_agg( DISTINCT coh.name, ', ')";
            } else {
                $cohorts = "GROUP_CONCAT( DISTINCT coh.name)";
            }
            $sql_columns .= ", coo.cohortname AS cohortname";
            $sql_cohort = $this->get_filter_in_sql($params->cohortid, "ch.cohortid");
            $sql_join = " JOIN (SELECT ch.userid, $cohorts as cohortname FROM {cohort} coh, {cohort_members} ch WHERE coh.id = ch.cohortid $sql_cohort GROUP BY ch.userid) coo ON coo.userid = u.id";
        } else {
          $sql_columns .= ", '' as cohortname";
        }

        if($params->sizemode){
            $sql_columns .= ", '0' as timespend, '0' as visits";
        }else{
            $sql_lit = $this->get_filter_in_sql($params->courseid, "courseid");
            $sql_columns .= ", lit.timespend AS timespend, lit.visits AS visits";
            $sql_join .= " LEFT JOIN (SELECT userid, courseid, SUM(timespend) as timespend, SUM(visits) as visits
                        FROM {local_intelliboard_tracking} WHERE id > 0 $sql_lit GROUP BY courseid, userid) lit ON lit.courseid = c.id AND lit.userid = u.id";
        }

        return $this->get_report_data("
            SELECT ue.id AS id,
                cri.gradepass AS gradepass,
                ue.timecreated as started,
                $grade_avg AS grade,
                $grade_avg_real AS grade_real,
                cc.timecompleted as complete,
                c.id as cid,
                c.fullname,
                e.enrol,
                u.email,
                u.id AS userid,
                u.firstname,
                u.lastname,
                c.enablecompletion,
                (SELECT COUNT(DISTINCT cmc.id) FROM {course_modules_completion} cmc, {course_modules} cm WHERE cm.visible = 1 AND cmc.coursemoduleid = cm.id $completion AND cm.completion > 0 AND cm.course = c.id AND cmc.userid = u.id) AS completed,
                (SELECT $grade_avg FROM {grade_items} gi, {grade_grades} g WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL AND gi.courseid=c.id LIMIT 1) AS average,
                (SELECT $group_concat
                  FROM {role_assignments} AS ra
                    JOIN {user} u ON ra.userid = u.id
                    JOIN {context} AS ctx ON ctx.id = ra.contextid
                  WHERE ctx.instanceid = c.id AND ctx.contextlevel = 50 $sql_teacher_roles
                ) AS teacher
                $sql_columns
            FROM {user_enrolments} ue
                LEFT JOIN {user} u ON u.id = ue.userid
                LEFT JOIN {enrol} e ON e.id = ue.enrolid
                LEFT JOIN {course} c ON c.id = e.courseid
                LEFT JOIN {course_completions} cc ON cc.course = e.courseid AND cc.userid = ue.userid
                LEFT JOIN {course_completion_criteria} cri ON cri.course = e.courseid AND cri.criteriatype = 6
                LEFT JOIN {grade_items} gi ON gi.courseid = c.id AND gi.itemtype = 'course'
                LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.userid =u.id
                $sql_join
            WHERE ue.id > 0 $sql_filter $sql_having $sql_order", $params);
    }
    public function report7($params)
    {
        $columns = array_merge(array("u.firstname","u.lastname","email", "course", "visits", "participations", "assignments", "grade"), $this->get_filter_columns($params));

        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "e.courseid");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filterdate_sql($params, "ue.timecreated");
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_columns = $this->get_columns($params, ["u.id"]);
        $grade_single = intelliboard_grade_sql(false, $params);
        $completion1 = $this->get_completion($params, "cmc.");
        $completion2 = $this->get_completion($params, "cmc.");
        $sql_vendor_filter = $this->vendor_filter('ue.userid', 'c.id', $params);

        if($params->sizemode){
            $sql_columns .= ", '0' as grade";
            $sql_join = "";
        }else{
            $sql_columns .= ", MIN(gc.grade) AS grade";
            $sql_join = "
                    LEFT JOIN (SELECT gi.courseid, g.userid, $grade_single AS grade
                    FROM {grade_items} gi, {grade_grades} g WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL
                    GROUP BY gi.courseid, g.userid, g.finalgrade, g.rawgrademax) as gc ON gc.courseid = c.id AND gc.userid = u.id";
        }

        return $this->get_report_data(
            "SELECT MIN(ue.id) AS id,
                    u.id AS userid,
                    u.email,
                    ((MAX(cmca.cmcnuma) / MAX(cma.cmnuma))*100 ) AS assignments,
                    ((MAX(cmc.cmcnums) / MAX(cmx.cmnumx))*100 ) AS participations,
                    ((MAX(lit.vsts) / MAX(cm.cmnums))*100 ) AS visits,
                    MAX(cma.cmnuma) AS assigns,
                    c.fullname AS course,
                    u.firstname,
                    u.lastname
                    $sql_columns
               FROM {user_enrolments} ue
          LEFT JOIN {user} u ON u.id = ue.userid
          LEFT JOIN {enrol} e ON e.id = ue.enrolid
          LEFT JOIN {course} c ON c.id = e.courseid
          LEFT JOIN (SELECT COUNT(DISTINCT id) AS vsts, courseid, userid
                       FROM {local_intelliboard_tracking}
                      WHERE page = 'module'
                   GROUP BY courseid, userid
                    ) lit ON lit.courseid = c.id AND lit.userid = u.id
          LEFT JOIN (SELECT course, count(id) as cmnums FROM {course_modules} WHERE visible = 1 GROUP BY course) as cm ON cm.course = c.id
          LEFT JOIN (SELECT course, count(id) as cmnumx FROM {course_modules} WHERE visible = 1 and completion > 0 GROUP BY course) cmx ON cmx.course = c.id
          LEFT JOIN (SELECT cm.course, count(cm.id) as cmnuma
                       FROM {course_modules} cm
                       JOIN {modules} m ON m.id = cm.module
                      WHERE cm.visible = 1 and m.name = 'assign'
                   GROUP BY cm.course
                    ) cma ON cma.course = c.id
          LEFT JOIN (SELECT cm.course, cmc.userid, count(cmc.id) as cmcnums FROM {course_modules} cm, {course_modules_completion} cmc WHERE cmc.coursemoduleid = cm.id AND cm.visible  =  1 $completion1 GROUP BY cm.course, cmc.userid) cmc ON cmc.course = c.id AND cmc.userid = u.id
          LEFT JOIN (SELECT cm.course, cmc.userid, count(cmc.id) as cmcnuma FROM {course_modules} cm, {course_modules_completion} cmc WHERE cmc.coursemoduleid = cm.id AND cm.module = 1 AND cm.visible  =  1 $completion2 GROUP BY cm.course, cmc.userid) as cmca ON cmca.course = c.id AND cmca.userid = u.id
                    $sql_join
              WHERE ue.id > 0 $sql_filter {$sql_vendor_filter}
           GROUP BY u.id,c.id $sql_having $sql_order",
              $params
          );
    }
    public function report8($params)
    {
        $columns = array_merge(array(
            "teacher",
            "courses",
            "learners",
            "activelearners",
            "completedlearners",
            "grade"
        ), $this->get_filter_columns($params));

        $sql_filter = $this->get_teacher_sql(
            $params, ["u.id" => "users", "ctx.instanceid" => "courses"]
        );
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_columns = $this->get_columns($params, ["u.id"]);
        $grade_avg = intelliboard_grade_sql(true, $params);

        $sql1 = $this->get_filter_in_sql($params->learner_roles, "ra.roleid");
        $sql2 = $this->get_filter_in_sql($params->learner_roles, "ra.roleid");
        $sql3 = $this->get_filter_in_sql($params->teacher_roles, "ra.roleid");

        $this->params['tx1'] = strtotime('-30 days');
        $this->params['tx2'] = time();

        $coursevisibilityfilter = '';
        if(!$params->filter_course_visible) {
            $coursevisibilityfilter = " JOIN {course} c ON ctx.instanceid = c.id AND
                                                      c.visible = 1";
        }

        return $this->get_report_data(
            "SELECT u.id,
                    CONCAT(u.firstname, ' ', u.lastname) teacher,
                    COUNT(DISTINCT ctx.instanceid) as courses,
                    SUM(l.learners) as learners,
                    SUM(l1.activelearners) as activelearners,
                    SUM(cc.completed) as completedlearners,
                    AVG(g.grade) as grade
                    $sql_columns
               FROM {user} u
          LEFT JOIN {role_assignments} AS ra ON ra.userid = u.id
          LEFT JOIN {context} AS ctx ON ctx.id = ra.contextid
                    {$coursevisibilityfilter}
          LEFT JOIN (SELECT ctx.instanceid, count(distinct ra.userid) as learners
                       FROM {role_assignments} ra, {context} ctx
                      WHERE ctx.id = ra.contextid AND ctx.contextlevel = 50 $sql1
                   GROUP BY ctx.instanceid
                    ) AS l ON l.instanceid = ctx.instanceid
          LEFT JOIN (SELECT ctx.instanceid, count(distinct ra.userid) as activelearners
                       FROM {role_assignments} ra, {user} u, {context} ctx
                      WHERE ctx.id = ra.contextid AND ctx.contextlevel = 50 AND
                            u.id = ra.userid AND u.lastaccess BETWEEN :tx1 AND:tx2 AND
                            u.deleted = 0 AND u.suspended = 0 $sql2
                   GROUP BY ctx.instanceid
                    ) AS l1 ON l1.instanceid = ctx.instanceid
          LEFT JOIN (SELECT course, count(id) as completed
                       FROM {course_completions}
                      WHERE timecompleted > 0
                   GROUP BY course
                    ) cc ON cc.course = ctx.instanceid
          LEFT JOIN (SELECT gi.courseid, $grade_avg AS grade
                       FROM {grade_items} gi, {grade_grades} g
                      WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND
                            g.finalgrade IS NOT NULL GROUP BY gi.courseid
                    ) g ON g.courseid = ctx.instanceid
              WHERE ctx.contextlevel = 50 $sql3 $sql_filter
           GROUP BY u.id $sql_having $sql_order", $params);
    }
    public function report9($params)
    {
        $columns = array_merge(
            array(
                "c.fullname",
                "q.name",
                "questions",
                "attempts",
                "q.timeopen",
                "duration",
                "grade",
                "q.timemodified"),
            $this->get_filter_columns($params, [null])
        );

        $sql_columns = $this->get_columns($params, [null]);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["q.course" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "q.course");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->vendor_filter('qa.userid', 'c.id', $params);

        return $this->get_report_data("
            SELECT q.id,
                q.name,
                q.course,
                c.fullname,
                MAX(ql.questions) AS questions,
                q.timemodified,
                q.timeopen,
                q.timeclose,
                avg((qa.sumgrades/q.sumgrades)*100) AS grade,
                count(distinct(qa.id)) AS attempts,
                sum(qa.timefinish - qa.timestart) AS duration
                {$sql_columns}
            FROM {quiz} q
                JOIN {course} c ON c.id = q.course
                LEFT JOIN {quiz_attempts} qa ON qa.quiz = q.id
                LEFT JOIN (SELECT quizid, count(*) questions FROM {quiz_slots} GROUP BY quizid) ql ON ql.quizid = q.id
                LEFT JOIN {modules} m ON m.name = 'quiz'
                LEFT JOIN {course_modules} cm ON cm.module = m.id AND cm.course = c.id AND cm.instance = q.id
            WHERE q.id > 0 $sql_filter
            GROUP BY q.id, c.id $sql_having $sql_order", $params);
    }
    public function report10($params)
    {
        global $CFG;
        $columns = array_merge(array(
            "fullname", "name","firstname","lastname", "email", "qa.state", "qa.timestart", "qa.timefinish",
            "duration", "grade", "tags", "", "cohortname"
        ), $this->get_filter_columns($params));

        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "q.course" => "courses"]);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "q.course");
        $sql_filter .= $this->get_filter_in_sql($params->learner_roles, "ra.roleid");
        $sql_filter .= $this->get_filterdate_sql($params, "qa.timestart");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->vendor_filter('qa.userid', 'c.id', $params);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_columns = $this->get_columns($params, ["u.id"]);

        $sql_join = "";
        if($params->cohortid){
            if ($CFG->dbtype == 'pgsql') {
                $cohorts = "string_agg( DISTINCT coh.name, ', ')";
            } else {
                $cohorts = "GROUP_CONCAT( DISTINCT coh.name)";
            }
            $sql_columns .= ", coo.cohortname AS cohortname";
            $sql_cohort = $this->get_filter_in_sql($params->cohortid, "ch.cohortid");
            $sql_join = " LEFT JOIN (SELECT ch.userid, $cohorts as cohortname FROM {cohort} coh, {cohort_members} ch WHERE coh.id = ch.cohortid $sql_cohort GROUP BY ch.userid) coo ON coo.userid = u.id";
            $sql_filter .= " AND coo.cohortname IS NOT NULL";
        } else {
          $sql_columns .= ", '' as cohortname";
        }


        if ($CFG->dbtype == 'pgsql') {
            $group_concat = "string_agg(t.rawname, ', ')";
        } else {
            $group_concat = "GROUP_CONCAT(DISTINCT t.rawname SEPARATOR ', ')";
        }
        $sql_join .= $this->get_suspended_sql($params);

        return $this->get_report_data("
            SELECT qa.id,
                MIN(q.name) AS name,
                MIN(u.email) AS email,
                MIN(q.course) AS course,
                MIN(c.fullname) as fullname,
                qa.timestart,
                qa.timefinish,
                qa.state,
                (qa.timefinish - qa.timestart) as duration,
                (qa.sumgrades/MAX(q.sumgrades)*100) as grade,
                MIN(u.firstname) AS firstname,
                MIN(u.lastname) AS lastname,
                $group_concat as tags
                $sql_columns
            FROM {quiz_attempts} qa
                LEFT JOIN {quiz} q ON q.id = qa.quiz
                LEFT JOIN {user} u ON u.id = qa.userid
                LEFT JOIN {course} c ON c.id = q.course
                LEFT JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = 50
                LEFT JOIN {role_assignments} ra ON ra.contextid = ctx.id AND ra.userid = u.id

                JOIN {modules} m ON m.name='quiz'
                JOIN {course_modules} cm ON cm.course=c.id AND cm.module=m.id AND cm.instance=q.id
                LEFT JOIN {tag_instance} ti ON ti.itemtype='course_modules' AND ti.itemid=cm.id
                LEFT JOIN {tag} t ON t.id=ti.tagid
                $sql_join
            WHERE qa.id > 0 $sql_filter
            GROUP BY qa.id $sql_having $sql_order", $params);
    }
    public function report11($params)
    {
        global $CFG;
        $columns = array_merge(array(
            "u.firstname",
            "u.lastname",
            "course",
            "u.email",
            "enrolled",
            "complete",
            "grade",
            "complete",
            "cohortname"
        ), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filterdate_sql($params, "ue.timecreated");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "ue.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "e.");
        $grade_single = intelliboard_grade_sql(false, $params);

        $sql_join = "";
        if($params->cohortid){
            if ($CFG->dbtype == 'pgsql') {
                $cohorts = "string_agg( DISTINCT coh.name, ', ')";
            } else {
                $cohorts = "GROUP_CONCAT( DISTINCT coh.name)";
            }
            $sql_columns .= ", coo.cohortname AS cohortname";
            $sql_cohort = $this->get_filter_in_sql($params->cohortid, "ch.cohortid");
            $sql_join = " LEFT JOIN (SELECT ch.userid, $cohorts as cohortname FROM {cohort} coh, {cohort_members} ch WHERE coh.id = ch.cohortid $sql_cohort GROUP BY ch.userid) coo ON coo.userid = u.id";
            $sql_filter .= " AND coo.cohortname IS NOT NULL";
        } else {
          $sql_columns .= ", '' as cohortname";
        }

        if ($params->custom2) {
            $sql_filter .= " AND ra.id IS null";
            $sql_join .= " JOIN {context} AS ctx ON ctx.contextlevel = 50 AND ctx.instanceid = e.courseid
                LEFT JOIN {role_assignments} ra ON ra.contextid = ctx.id and ra.userid = ue.userid";
        }

        return $this->get_report_data("
            SELECT ue.id,
                ue.timecreated as enrolled,
                cc.timecompleted as complete,
                $grade_single AS grade,
                u.id as uid,
                u.email,
                u.firstname,
                u.lastname,
                c.id as cid,
                c.enablecompletion,
                c.fullname as course
                $sql_columns
            FROM {user_enrolments} ue
                JOIN {enrol} e ON e.id = ue.enrolid
                JOIN {user} u ON u.id = ue.userid
                JOIN {course} c ON c.id = e.courseid
                LEFT JOIN {course_completions} cc ON cc.course = e.courseid AND cc.userid = u.id
                LEFT JOIN {grade_items} gi ON gi.courseid = e.courseid AND gi.itemtype = 'course'
                LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.userid = u.id
                $sql_join
            WHERE ue.id > 0 $sql_filter $sql_having $sql_order", $params);
    }
    public function report12($params)
    {
        $columns = array_merge(
            [
                "c.fullname",
                "learners",
                "completed",
                "visits",
                "timespend",
                "grade"
            ], $this->get_filter_columns($params, [null])
        );
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["c.id" => "courses"]);
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->vendor_filter(null, 'c.id', $params);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $grade_avg = intelliboard_grade_sql(true, $params);

        $sql_inner_filter1 = $this->vendor_filter('ra.userid', 'ctx.instanceid', $params);
        $sql_inner_filter1 .= $this->get_filter_in_sql($params->courseid, "ctx.instanceid");
        $sql_inner_filter1 .= $this->get_filterdate_sql($params, "ra.timemodified");
        $sql_inner_filter1 .= $this->get_filter_in_sql($params->learner_roles, "ra.roleid");

        $sql_inner_filter2 = $this->vendor_filter('g.userid', 'gi.courseid', $params);
        $sql_inner_filter2 .= $this->get_filter_in_sql($params->courseid, "gi.courseid");
        $sql_inner_filter2 .= $this->get_filterdate_sql($params, "g.timemodified");

        $sql_inner_filter3 = $this->vendor_filter('cc.userid', 'cc.course', $params);
        $sql_inner_filter3 .= $this->get_filter_in_sql($params->courseid, "cc.course");
        $sql_inner_filter3 .= $this->get_filterdate_sql($params, "cc.timecompleted");

        $sql_inner_filter4 = $this->vendor_filter('lit.userid', 'lit.courseid', $params);
        $sql_inner_filter4 .= $this->get_filter_in_sql($params->courseid, "lit.courseid");
        $sql_inner_filter4 .= $this->get_filter_in_sql($params->learner_roles, "ra.roleid");

        if($params->sizemode){
            $sql_columns = ", '0' as timespend, '0' as visits";
            $sql_join = "";
        }else{
            $date_filter = $this->get_filterdate_sql($params, "lil.timepoint");
            $sql_columns = ", lit.timespend AS timespend, lit.visits AS visits";
            $sql_join = "LEFT JOIN (SELECT lit.courseid,
                                           SUM(lil.timespend) AS timespend, SUM(lil.visits) AS visits
                                      FROM {local_intelliboard_tracking} lit
                                        JOIN {local_intelliboard_logs} lil ON lil.trackid=lit.id
                                        JOIN {context} ctx ON ctx.instanceid = lit.courseid AND ctx.contextlevel = 50
                                        JOIN {role_assignments} ra ON ra.contextid = ctx.id  AND ra.userid = lit.userid
                                     WHERE lit.courseid>0 {$date_filter} {$sql_inner_filter4}
                                  GROUP BY lit.courseid
                                   ) lit ON lit.courseid = c.id ";
        }
        $sql_columns .= $this->get_columns($params, [null]);
        $sql_join .= $this->get_suspended_sql($params, 'c.id', 'ra.userid');

        return $this->get_report_data(
            "SELECT c.id,
                    c.fullname,
                    l.learners,
                    cc.completed,
                    g.grade
                    {$sql_columns}
               FROM {course} c
                    LEFT JOIN (SELECT
                                  ctx.instanceid,
                                  COUNT(DISTINCT ra.userid) AS learners
                                FROM {context} ctx
                                    JOIN {role_assignments} ra ON ra.contextid = ctx.id
                                WHERE ctx.instanceid > 0 AND ctx.contextlevel = 50 $sql_inner_filter1
                                GROUP BY ctx.instanceid) l ON l.instanceid = c.id
                    LEFT JOIN (SELECT
                                    gi.courseid,
                                    {$grade_avg} AS grade
                                FROM {grade_items} gi
                                    LEFT JOIN {grade_grades} g ON g.itemid = gi.id
                                WHERE gi.courseid > 0 AND gi.itemtype = 'course' $sql_inner_filter2
                                GROUP BY gi.courseid) g ON g.courseid = c.id
                    LEFT JOIN (SELECT
                                    cc.course,
                                    COUNT(DISTINCT cc.userid) AS completed
                                FROM {course_completions} cc
                                WHERE cc.course >0 AND cc.timecompleted > 0 $sql_inner_filter3
                                GROUP BY cc.course) cc ON cc.course=c.id
                    {$sql_join}
              WHERE c.id > 1 {$sql_filter}
                    {$sql_having}
                    {$sql_order}", $params);
    }


    public function report13($params)
    {
        $columns = array_merge(
            array(
                "name",
                "visits",
                "timespend",
                "courses",
                "learners"),
            $this->get_filter_columns($params)
        );
        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "ctx.instanceid" => "courses"]);
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_in_sql($params->teacher_roles, "ra.roleid");
        $sql1 = $this->get_filter_in_sql($params->learner_roles, "r.roleid");

        if($params->sizemode){
            $sql_filter .= $this->get_filterdate_sql($params, "u.timecreated");
            $sql_columns .= ", '0' as timespend, '0' as visits";
            $sql_join = "";
        }else{
            $sql_filter_course = ($params->filter_course_visible) ? "" : " JOIN {course} c ON lit.courseid=c.id AND c.visible = 1";
            $date_filter = $this->get_filterdate_sql($params, "lil.timepoint");
            $sql_columns .= ", MAX(lit.timespend) AS timespend, MAX(lit.visits) AS visits";
            $sql_join = "
                    LEFT JOIN (SELECT lit.userid, SUM(lil.timespend) as timespend, SUM(lil.visits) as visits
                        FROM {local_intelliboard_tracking} lit
                            JOIN {local_intelliboard_logs} lil ON lil.trackid=lit.id
                            $sql_filter_course
                        WHERE lil.id>0 $date_filter
                        GROUP BY lit.userid) lit ON lit.userid = ra.userid";
        }

        $coursevisibilityfilter = '';
        if(!$params->filter_course_visible) {
            $coursevisibilityfilter = " JOIN {course} c ON ctx.instanceid = c.id AND
                                                      c.visible = 1";
        }

        return $this->get_report_data(
            "SELECT u.id,
                    CONCAT(u.firstname, ' ', u.lastname) AS name,
                    COUNT(DISTINCT ctx.instanceid) as courses,
                    SUM(v.learners) as learners
                    $sql_columns
               FROM {role_assignments} ra
               JOIN {context} AS ctx ON ctx.id = ra.contextid AND
                                        ctx.contextlevel = 50
                    {$coursevisibilityfilter}
               JOIN {user} u ON u.id = ra.userid
          LEFT JOIN (SELECT c.instanceid, COUNT(DISTINCT r.userid) as learners
                       FROM {role_assignments} r, {context} AS c
                      WHERE c.id = r.contextid $sql1
                   GROUP BY c.instanceid
                    ) v ON v.instanceid = ctx.instanceid
                    $sql_join
              WHERE u.id > 0 $sql_filter
           GROUP BY u.id $sql_having $sql_order",
            $params
        );
    }


    public function report14($params)
    {
        $columns = array_merge(
            array(
                "u.firstname",
                "u.lastname",
                "u.email",
                "u.lastaccess",
                "visits",
                "timespend",
                "courses",
                "grade"),
            $this->get_filter_columns($params)
        );
        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users"]);
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->vendor_filter('u.id', null, $params);
        $sql_vendor_filter = $this->vendor_filter(null, 'ctx.instanceid', $params);
        $grade_avg = intelliboard_grade_sql(true, $params);
        $sql_role = $this->get_filter_in_sql($params->learner_roles, "ra.roleid");

        if($params->sizemode){
            $sql_filter .= $this->get_filterdate_sql($params, "u.timecreated");
            $sql_columns .= ", '0' as timespend, '0' as visits";
            $sql_join = "";
        }else{
            $date_filter = $this->get_filterdate_sql($params, "lil.timepoint");
            $sql_columns = ", lit.timespend AS timespend, lit.visits AS visits";
            $sql_join = "
                    LEFT JOIN (SELECT lit.userid, SUM(lil.timespend) as timespend, SUM(lil.visits) as visits
                        FROM {local_intelliboard_tracking} lit
                            JOIN {local_intelliboard_logs} lil ON lil.trackid=lit.id
                        WHERE lil.id>0 $date_filter
                        GROUP BY lit.userid) lit ON lit.userid = u.id";
        }

        $sql_filter_course = '';
        if(!$params->filter_course_visible) {
            $sql_filter_course = " JOIN {course} c ON ctx.instanceid = c.id AND
                                                      c.visible = 1";
        }

        return $this->get_report_data(
            "SELECT u.id, u.lastaccess,
                    u.firstname,
                    u.lastname,
                    u.email,
                    grd.grade,
                    grd.courses
                    $sql_columns
               FROM {user} u
               JOIN (SELECT ra.userid, $grade_avg AS grade,
                            COUNT(DISTINCT ctx.instanceid) as courses
                       FROM {role_assignments} ra
                       JOIN {context} AS ctx ON ctx.id = ra.contextid AND
                                                ctx.contextlevel = 50
                       JOIN {user} u ON u.id = ra.userid
                  LEFT JOIN {grade_items} gi ON gi.courseid = ctx.instanceid AND
                                                gi.itemtype = 'course' {$sql_vendor_filter}
                  LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND
                                                g.userid = ra.userid AND
                                                g.finalgrade IS NOT NULL
                            {$sql_filter_course}
                      WHERE gi.itemtype = 'course' $sql_role
                   GROUP BY ra.userid
                     ) grd ON grd.userid =u.id
                     $sql_join
              WHERE u.id > 0 $sql_filter $sql_having $sql_order",
            $params
        );
    }

    public function report15($params)
    {
        $columns = array_merge(array("enrol", "courses", "users"), $this->get_filter_columns($params));


        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["ue.userid" => "users", "e.courseid" => "courses"]);
        $sql_filter .= $this->get_filter_enrol_sql($params, "e.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "ue.");

        return $this->get_report_data("
            SELECT MAX(e.id) AS id,
                e.enrol as enrol,
                COUNT(DISTINCT e.courseid) as courses,
                COUNT(ue.userid) as users,
                COUNT(DISTINCT ue.userid) as uniusers
            FROM {enrol} e
                LEFT JOIN {user_enrolments} ue ON ue.enrolid = e.id
            WHERE e.id > 0 $sql_filter GROUP BY e.enrol $sql_having $sql_order", $params);
    }

    public function report16($params)
    {
        $columns = array_merge(array(
            "c.fullname",
            "teacher",
            "total",
            "visits",
            "timespend",
            "posts",
            "discussions"
        ), $this->get_filter_columns($params, [null]));

        $sql_columns = $this->get_columns($params, [null]);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["c.id" => "courses"]);
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_teacher_roles = $this->get_filter_in_sql($params->teacher_roles, "ra.roleid");
        $forum_table = ($params->custom3 == 1)?'hsuforum':'forum';

        return $this->get_report_data("
            SELECT c.id,
                c.fullname,
                MAX(v.visits) AS visits,
                MAX(v.timespend) AS timespend,
                MAX(d.discussions) AS discussions,
                MAX(p.posts) AS posts,
                COUNT(*) AS total,
                (SELECT DISTINCT CONCAT(u.firstname,' ',u.lastname)
                    FROM {role_assignments} AS ra
                        JOIN {user} u ON ra.userid = u.id
                        JOIN {context} AS ctx ON ctx.id = ra.contextid
                    WHERE ctx.instanceid = c.id AND ctx.contextlevel = 50 $sql_teacher_roles LIMIT 1
                ) AS teacher
                {$sql_columns}
                FROM {course} c
                    LEFT JOIN {".$forum_table."} f ON f.course = c.id
                    LEFT JOIN (SELECT lit.courseid, SUM(lit.timespend) as timespend, SUM(lit.visits) as visits FROM {local_intelliboard_tracking} lit, {course_modules} cm, {modules} m WHERE lit.page = 'module' and cm.id = lit.param and m.id = cm.module and m.name='".$forum_table."' GROUP BY lit.courseid) v ON v.courseid = c.id
                    LEFT JOIN (SELECT course, count(*) discussions FROM {".$forum_table."_discussions} group by course) d ON d.course = c.id
                    LEFT JOIN (SELECT fd.course, count(*) posts FROM {".$forum_table."_discussions} fd, {".$forum_table."_posts} fp WHERE fp.discussion = fd.id group by fd.course) p ON p.course = c.id
            WHERE c.id > 0 $sql_filter GROUP BY c.id $sql_having $sql_order", $params);
    }

    public function report17($params)
        {
            global $CFG;
            $columns = array_merge(array(
                "c.fullname", "f.name", "avg_month", "avg_week", "avg_day", "popular_hour.hour", "popular_week.week", "popular_day.day"
            ), $this->get_filter_columns($params));

            $sql_columns = $this->get_columns($params, [null]);
            $sql_having = $this->get_filter_sql($params, $columns);
            $sql_order = $this->get_order_sql($params, $columns);
            $sql_filter = $this->get_teacher_sql($params, ["c.id" => "courses"]);
            $sql_filter .= $this->get_filter_course_sql($params, "c.");

            $forum_table = ($params->custom3 == 1)?'hsuforum':'forum';
            $sql_course1 = (!empty($params->courseid))?$this->get_filter_in_sql($params->courseid, "f.course",false):"f.id>0";
            $sql_course2 = (!empty($params->courseid))?$this->get_filter_in_sql($params->courseid, "f2.course",false):"f2.id>0";
            $sql_course3 = (!empty($params->courseid))?$this->get_filter_in_sql($params->courseid, "f2.course",false):"f2.id>0";
            $sql_course4 = (!empty($params->courseid))?$this->get_filter_in_sql($params->courseid, "f2.course",false):"f2.id>0";
            if ($CFG->dbtype == 'pgsql') {
                return $this->get_report_data("
                    SELECT
                      f.id as fid,
                      f.name,
                      MAX(c.fullname) AS fullname,
                      COUNT(DISTINCT fp.id) AS all_posts,
                      COUNT(DISTINCT fp.id)/GREATEST((EXTRACT(YEAR FROM age(CURRENT_TIMESTAMP, to_timestamp(MAX(cm.added)))) * 12 + EXTRACT(MONTH FROM age(CURRENT_TIMESTAMP, to_timestamp(MAX(cm.added))))),1) as avg_month,
                      COUNT(DISTINCT fp.id)/GREATEST(((EXTRACT(YEAR FROM age(CURRENT_TIMESTAMP, to_timestamp(MAX(cm.added)))) * 365 + EXTRACT(MONTH FROM age(CURRENT_TIMESTAMP, to_timestamp(MAX(cm.added)))) * 30.5 + EXTRACT(DAY FROM age(CURRENT_TIMESTAMP, to_timestamp(MAX(cm.added)))))/7),1) as avg_week,
                      COUNT(DISTINCT fp.id)/GREATEST((EXTRACT(YEAR FROM age(CURRENT_TIMESTAMP, to_timestamp(MAX(cm.added)))) * 365 + EXTRACT(MONTH FROM age(CURRENT_TIMESTAMP, to_timestamp(MAX(cm.added)))) * 30.5 + EXTRACT(DAY FROM age(CURRENT_TIMESTAMP, to_timestamp(MAX(cm.added))))),1) as avg_day,
                      MAX(popular_day.day) AS popular_day,
                      MAX(popular_hour.hour) AS popular_hour,
                      MAX(popular_week.week) AS popular_week
                      {$sql_columns}
                    FROM {".$forum_table."} f
                      JOIN {modules} m ON m.name='".$forum_table."'
                      LEFT JOIN {course_modules} cm ON cm.course=f.course AND cm.instance=f.id AND cm.module=m.id

                      LEFT JOIN {course} c ON c.id=f.course
                      LEFT JOIN {".$forum_table."_discussions} fd ON fd.forum=f.id
                      LEFT JOIN {".$forum_table."_posts} fp ON fp.discussion=fd.id

                      LEFT JOIN ( SELECT a.posts,a.day,a.id
                                    FROM ( SELECT
                                              COUNT(DISTINCT fp2.id) as posts,
                                              (CASE WHEN extract(dow from to_timestamp(fp2.created))=0 THEN 6 ELSE extract(dow from to_timestamp(fp2.created))-1 END) AS day,
                                              f2.id,
                                              ROW_NUMBER () OVER (partition by f2.id ORDER BY COUNT(DISTINCT fp2.id) DESC) AS row_number
                                            FROM {".$forum_table."} f2
                                              LEFT JOIN {".$forum_table."_discussions} fd2 ON fd2.forum=f2.id
                                              LEFT JOIN {".$forum_table."_posts} fp2 ON fp2.discussion=fd2.id
                                            WHERE $sql_course4
                                            GROUP BY day,f2.id
                                            ORDER BY posts DESC
                                        ) AS a
                                    WHERE a.row_number=1) as popular_day ON popular_day.id=f.id

                      LEFT JOIN ( SELECT a.posts,a.hour,a.id
                                    FROM ( SELECT
                                              COUNT(DISTINCT fp2.id) as posts,
                                              extract(hour from to_timestamp(fp2.created)) AS hour,
                                              f2.id,
                                              ROW_NUMBER () OVER (partition by f2.id ORDER BY COUNT(DISTINCT fp2.id) DESC) AS row_number
                                            FROM {".$forum_table."} f2
                                              LEFT JOIN {".$forum_table."_discussions} fd2 ON fd2.forum=f2.id
                                              LEFT JOIN {".$forum_table."_posts} fp2 ON fp2.discussion=fd2.id
                                            WHERE $sql_course3
                                            GROUP BY hour,f2.id
                                            ORDER BY posts DESC
                                        ) AS a
                                    WHERE a.row_number=1) as popular_hour ON popular_hour.id=f.id

                      LEFT JOIN ( SELECT a.posts,a.week,a.id
                                    FROM ( SELECT
                                              COUNT(DISTINCT fp2.id) as posts,
                                              CEILING(extract(hour from to_timestamp(fp2.created))/7) AS week,
                                              f2.id,
                                              ROW_NUMBER () OVER (partition by f2.id ORDER BY COUNT(DISTINCT fp2.id) DESC) AS row_number
                                            FROM {".$forum_table."} f2
                                              LEFT JOIN {".$forum_table."_discussions} fd2 ON fd2.forum=f2.id
                                              LEFT JOIN {".$forum_table."_posts} fp2 ON fp2.discussion=fd2.id
                                            WHERE $sql_course2
                                            GROUP BY week,f2.id
                                            ORDER BY posts DESC
                                        ) AS a
                                    WHERE a.row_number=1) as popular_week ON popular_week.id=f.id

                    WHERE $sql_course1 $sql_filter
                    GROUP BY f.id $sql_having $sql_order", $params);
            }else{
                return $this->get_report_data("
                    SELECT
                      f.id as fid,
                      f.name,
                      c.fullname,
                      COUNT(DISTINCT fp.id) AS all_posts,
                      COUNT(DISTINCT fp.id)/GREATEST(PERIOD_DIFF(DATE_FORMAT(NOW(), '%Y%m'),DATE_FORMAT(FROM_UNIXTIME(cm.added), '%Y%m')),1) as avg_month,
                      COUNT(DISTINCT fp.id)/GREATEST(CEILING(DATEDIFF(DATE_FORMAT(NOW(), '%Y%m%d'),DATE_FORMAT(FROM_UNIXTIME(cm.added), '%Y%m%d'))/7),1) as avg_week,
                      COUNT(DISTINCT fp.id)/GREATEST(DATEDIFF(DATE_FORMAT(NOW(), '%Y%m%d'),DATE_FORMAT(FROM_UNIXTIME(cm.added), '%Y%m%d')),1) as avg_day,
                      popular_day.day AS popular_day,
                      popular_hour.hour AS popular_hour,
                      popular_week.week AS popular_week
                      {$sql_columns}
                    FROM {".$forum_table."} f
                      JOIN {modules} m ON m.name='".$forum_table."'
                      LEFT JOIN {course_modules} cm ON cm.course=f.course AND cm.instance=f.id AND cm.module=m.id

                      LEFT JOIN {course} c ON c.id=f.course
                      LEFT JOIN {".$forum_table."_discussions} fd ON fd.forum=f.id
                      LEFT JOIN {".$forum_table."_posts} fp ON fp.discussion=fd.id

                      LEFT JOIN ( SELECT a.posts,a.day,a.id
                                    FROM ( SELECT
                                              COUNT(DISTINCT fp2.id) as posts,
                                              WEEKDAY(FROM_UNIXTIME(fp2.created,'%Y-%m-%d %T')) AS day,
                                              f2.id
                                            FROM {".$forum_table."} f2
                                              LEFT JOIN {".$forum_table."_discussions} fd2 ON fd2.forum=f2.id
                                              LEFT JOIN {".$forum_table."_posts} fp2 ON fp2.discussion=fd2.id
                                            WHERE $sql_course4
                                            GROUP BY day,f2.id
                                            ORDER BY posts DESC
                                        ) AS a
                                GROUP BY a.id) as popular_day ON popular_day.id=f.id

                      LEFT JOIN ( SELECT a.posts,a.hour,a.id
                                    FROM ( SELECT
                                              COUNT(DISTINCT fp2.id) as posts,
                                              FROM_UNIXTIME(fp2.created,'%H') AS hour,
                                              f2.id
                                            FROM {".$forum_table."} f2
                                              LEFT JOIN {".$forum_table."_discussions} fd2 ON fd2.forum=f2.id
                                              LEFT JOIN {".$forum_table."_posts} fp2 ON fp2.discussion=fd2.id
                                            WHERE $sql_course3
                                            GROUP BY hour,f2.id
                                            ORDER BY posts DESC
                                        ) AS a
                                GROUP BY a.id) as popular_hour ON popular_hour.id=f.id

                      LEFT JOIN ( SELECT a.posts,a.week,a.id
                                    FROM ( SELECT
                                              COUNT(DISTINCT fp2.id) as posts,
                                              CEILING(DAYOFMONTH(FROM_UNIXTIME(fp2.created,'%Y-%m-%d %T'))/7) AS week,
                                              f2.id
                                            FROM {".$forum_table."} f2
                                              LEFT JOIN {".$forum_table."_discussions} fd2 ON fd2.forum=f2.id
                                              LEFT JOIN {".$forum_table."_posts} fp2 ON fp2.discussion=fd2.id
                                            WHERE $sql_course2
                                            GROUP BY week,f2.id
                                            ORDER BY posts DESC
                                        ) AS a
                                GROUP BY a.id) as popular_week ON popular_week.id=f.id

                    WHERE $sql_course1 $sql_filter
                    GROUP BY f.id $sql_having $sql_order", $params);
            }
        }

    public function report18($params)
    {
        $columns = array_merge(array("f.name", "u.firstname","u.lastname","course", "discussions", "posts"), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $forum_table = ($params->custom3 == 1)?'hsuforum':'forum';

        return $this->get_report_data("
            SELECT MAX(fd.id) AS id,
                c.fullname as course,
                u.firstname,
                u.lastname,
                f.name,
                COUNT(DISTINCT fp.id) AS posts,
                COUNT(DISTINCT fd.id) AS discussions
                $sql_columns
            FROM
                {".$forum_table."_discussions} fd
                JOIN {user} u ON u.id = fd.userid
                JOIN {course} c ON c.id = fd.course
                LEFT JOIN {".$forum_table."} f ON f.id = fd.forum
                LEFT JOIN {".$forum_table."_posts} fp ON fp.discussion = fd.id AND fp.userid = u.id
            WHERE fd.id > 0 $sql_filter GROUP BY u.id, c.id, f.id $sql_having $sql_order", $params);
    }

    public function report18_graph($params)
    {
        global $CFG;

        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $params->length = -1;
        $forum_table = ($params->custom3 == 1)?'hsuforum':'forum';

        if ($CFG->dbtype == 'pgsql') {
            return $this->get_report_data("
						SELECT MIN(fd.id) AS id,
							COUNT(DISTINCT fp.id) AS count,
							(CASE WHEN extract(dow from to_timestamp(fp.created))=0 THEN 6 ELSE extract(dow from to_timestamp(fp.created))-1 END) AS day,
							(CASE WHEN extract(hour from to_timestamp(fp.created))>=6 AND extract(hour from to_timestamp(fp.created))<12 THEN '1' ELSE (
								 CASE WHEN extract(hour from to_timestamp(fp.created))>=12 AND extract(hour from to_timestamp(fp.created))<17 THEN '2' ELSE (
									  CASE WHEN extract(hour from to_timestamp(fp.created))>=17 AND extract(hour from to_timestamp(fp.created))<=23 THEN '3' ELSE (
										   CASE WHEN extract(hour from to_timestamp(fp.created))>=0 AND extract(hour from to_timestamp(fp.created))<6 THEN '4' ELSE 'undef' END
									  ) END
								 ) END
							) END) AS time_of_day
						FROM
							{".$forum_table."_discussions} fd
							LEFT JOIN {user} u ON u.id = fd.userid
							LEFT JOIN {course} c ON c.id = fd.course
							LEFT JOIN {".$forum_table."} f ON f.id = fd.forum
							LEFT JOIN {".$forum_table."_posts} fp ON fp.discussion = fd.id AND fp.userid = u.id
						WHERE fd.id>0 $sql_filter GROUP BY day,time_of_day ORDER BY time_of_day, day", $params);
        }else{
            return $this->get_report_data("
						SELECT MIN(fd.id) AS id,
							COUNT(DISTINCT fp.id) AS count,
							WEEKDAY(FROM_UNIXTIME(fp.created,'%Y-%m-%d %T')) AS day,
							IF(FROM_UNIXTIME(fp.created,'%H')>=6 && FROM_UNIXTIME(fp.created,'%H')<12,'1',
										 IF(FROM_UNIXTIME(fp.created,'%H')>=12 && FROM_UNIXTIME(fp.created,'%H')<17,'2',
										 IF(FROM_UNIXTIME(fp.created,'%H')>=17 && FROM_UNIXTIME(fp.created,'%H')<=23,'3',
										 IF(FROM_UNIXTIME(fp.created,'%H')>=0 && FROM_UNIXTIME(fp.created,'%H')<6,'4','undef')))) AS time_of_day
						FROM
							{".$forum_table."_discussions} fd
							LEFT JOIN {user} u ON u.id = fd.userid
							LEFT JOIN {course} c ON c.id = fd.course
							LEFT JOIN {".$forum_table."} f ON f.id = fd.forum
							LEFT JOIN {".$forum_table."_posts} fp ON fp.discussion = fd.id AND fp.userid = u.id
						WHERE fd.id>0 $sql_filter GROUP BY day,time_of_day ORDER BY time_of_day, day", $params);
        }

    }

    public function report19($params)
    {
        $columns = array_merge(array(
            "c.fullname", "teacher", "scorms"
        ), $this->get_filter_columns($params, [null]));

        $sql_columns = $this->get_columns($params, [null]);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_teacher_role = $this->get_filter_in_sql($params->teacher_roles, "ra.roleid");

        return $this->get_report_data("
            SELECT c.id,
                c.fullname, count(s.id) as scorms,
                (SELECT DISTINCT CONCAT(u.firstname,' ',u.lastname)
                    FROM {role_assignments} AS ra
                    JOIN {user} u ON ra.userid = u.id
                    JOIN {context} AS ctx ON ctx.id = ra.contextid
                    WHERE ctx.instanceid = c.id AND ctx.contextlevel = 50 $sql_teacher_role LIMIT 1) AS teacher
                {$sql_columns}
            FROM {course} c
                LEFT JOIN {scorm} s ON s.course = c.id
            WHERE c.category > 0 $sql_filter
            GROUP BY c.id $sql_having $sql_order", $params);
    }
    public function report20($params)
    {
        global $CFG;

        $columns = array_merge(array(
            "s.name", "c.fullname", "sl.visits", "sm.duration", "s.timemodified"
        ), $this->get_filter_columns($params, [null]));

        $sql_columns = $this->get_columns($params, [null]);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filterdate_sql($params, "s.timemodified");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->vendor_filter('sst.userid', 'c.id', $params);
        $sql_vendor_filter1 = $this->vendor_filter('lit.userid', 'lit.courseid', $params);
        $sql_vendor_filter2 = $this->vendor_filter('userid', null, $params);

        $time_func = ($CFG->dbtype == 'pgsql') ? "sum(value::time)" : "SUM(TIME_TO_SEC(value))";

        return $this->get_report_data("
            SELECT s.id,
                    c.fullname,
                    s.name,
                    s.timemodified,
                    count(sst.id) as attempts,
                    MAX(sl.visits) AS visits,
                    MAX(sm.duration) AS duration
                    {$sql_columns}
            FROM {scorm} s
                LEFT JOIN {scorm_scoes_track} sst ON sst.scormid = s.id AND sst.element = 'x.start.time'
                LEFT JOIN {course} c ON c.id = s.course
                LEFT JOIN {modules} m ON m.name = 'scorm'
                LEFT JOIN {course_modules} cm ON cm.module = m.id AND cm.course = c.id AND cm.instance = s.id
                LEFT JOIN (SELECT cm.instance, SUM(lit.visits) as visits
                             FROM {local_intelliboard_tracking} lit, {course_modules} cm, {modules} m
                            WHERE lit.page = 'module' and cm.id = lit.param and m.id = cm.module and m.name='scorm' {$sql_vendor_filter1}
                         GROUP BY cm.instance) sl ON sl.instance = s.id
                LEFT JOIN (SELECT scormid, $time_func AS duration
                             FROM {scorm_scoes_track}
                            WHERE element = 'cmi.core.total_time' {$sql_vendor_filter2}
                         GROUP BY scormid) AS sm ON sm.scormid = s.id
            WHERE s.id > 0 $sql_filter GROUP BY s.id, c.id $sql_having $sql_order", $params);
    }
    public function report21($params)
    {
        global $CFG;

        $columns = array_merge(array(
            "u.firstname", "u.lastname", "u.email", "u.idnumber", "s.name", "c.fullname", "attempts", "t.duration","t.starttime","cmc.timemodified", "score","l.timespend","l.visits","","cohortname"
        ), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_module_sql($params, "cm.");
        $sql_filter .= $this->get_filterdate_sql($params, "t.timemodified");
        $sql_filter .= $this->vendor_filter('t.userid', 'c.id', $params);
        $grade_single = intelliboard_grade_sql(false, $params);

        $sql_join = "";
        if($params->cohortid){
            if ($CFG->dbtype == 'pgsql') {
                $cohorts = "string_agg( DISTINCT coh.name, ', ')";
            } else {
                $cohorts = "GROUP_CONCAT( DISTINCT coh.name)";
            }
            $sql_columns .= ", coo.cohortname AS cohortname";
            $sql_cohort = $this->get_filter_in_sql($params->cohortid, "ch.cohortid");
            $sql_join = " LEFT JOIN (SELECT ch.userid, $cohorts as cohortname FROM {cohort} coh, {cohort_members} ch WHERE coh.id = ch.cohortid $sql_cohort GROUP BY ch.userid) coo ON coo.userid = u.id";
            $sql_filter .= " AND coo.cohortname IS NOT NULL";
        } else {
          $sql_columns .= ", '' as cohortname";
        }

        $time_func = ($CFG->dbtype == 'pgsql') ? "sum(CASE WHEN element = 'cmi.core.total_time' THEN value::time ELSE null END)" : "SUM( TIME_TO_SEC(CASE WHEN element = 'cmi.core.total_time' THEN value ELSE null END))";

        return $this->get_report_data("
            SELECT t.*,
                u.firstname,
                u.lastname,
                u.email,
                u.idnumber,
                s.name,
                c.fullname,
                cmc.completionstate,
                cmc.timemodified as completiondate,
                round(sg.score, 0) as score,
                l.timespend AS timespend_on_course,
                l.visits AS visits_on_course
                $sql_columns
                FROM (SELECT  MIN(id) AS id,
                            userid,
                            scormid,
                            MAX(timemodified) AS timemodified,
                            MIN(CASE WHEN element = 'x.start.time' THEN value ELSE null END) AS starttime,
                            $time_func AS duration,
                            COUNT(DISTINCT(attempt)) as attempts
                      FROM {scorm_scoes_track}
                      GROUP BY userid, scormid
                     ) t
                JOIN {user} u ON u.id = t.userid
                    JOIN {scorm} AS s ON s.id = t.scormid
                    JOIN {course} c ON c.id = s.course
                    JOIN {modules} m ON m.name = 'scorm'
                    JOIN {course_modules} cm ON cm.module = m.id AND cm.instance = s.id
                    LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id AND cmc.userid = u.id
                    LEFT JOIN (SELECT gi.iteminstance,
                                      $grade_single AS score,
                                      g.userid
                               FROM {grade_items} gi, {grade_grades} g
                               WHERE gi.itemmodule = 'scorm' and g.itemid = gi.id AND g.finalgrade IS NOT NULL
                               GROUP BY gi.iteminstance, g.userid, g.rawgrademax , g.finalgrade) AS sg ON sg.iteminstance = t.scormid and sg.userid= t.userid
                    LEFT JOIN (SELECT lit.userid,
                                      lit.courseid,
                                      SUM(lit.timespend) as timespend,
                                      SUM(lit.visits) as visits
                               FROM {local_intelliboard_tracking} lit
                               WHERE lit.courseid > 0
                               GROUP BY lit.courseid, lit.userid) l ON l.userid=u.id AND l.courseid=c.id
                    $sql_join
            WHERE t.userid > 0 $sql_filter $sql_having $sql_order", $params);
    }
    public function report22($params)
    {
        $columns = array_merge(
            array(
                "c.fullname",
                "teacher",
                "quizzes",
                "attempts",
                "duration",
                "grade"),
            $this->get_filter_columns($params, [null])
        );
        $sql_columns = $this->get_columns($params, [null]);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");


        $sql1 = $this->get_filter_in_sql($params->teacher_roles, "ra.roleid");

        return $this->get_report_data("
            SELECT MAX(qa.id) AS id,
                c.fullname,
                COUNT(DISTINCT q.id) AS quizzes,
                SUM(qa.timefinish - qa.timestart) AS duration,
                COUNT(DISTINCT qa.id) AS attempts,
                AVG((qa.sumgrades/q.sumgrades)*100) AS grade,
                (SELECT DISTINCT CONCAT(u.firstname,' ',u.lastname)
                    FROM {role_assignments} AS ra
                    JOIN {user} u ON ra.userid = u.id
                    JOIN {context} AS ctx ON ctx.id = ra.contextid
                    WHERE ctx.instanceid = c.id AND ctx.contextlevel = 50 $sql1 LIMIT 1) AS teacher
                {$sql_columns}
            FROM {quiz_attempts} qa
                LEFT JOIN {quiz} q ON q.id = qa.quiz
                LEFT JOIN {course} c ON c.id = q.course
            WHERE qa.id > 0 $sql_filter
            GROUP BY c.id $sql_having $sql_order", $params);
    }
    public function report23($params)
    {
        global $CFG;
        $columns = array_merge(array(
            "c.fullname", "teacher", "resources", "files_sorting"
        ), $this->get_filter_columns($params, [null]));

        $sql_columns = $this->get_columns($params, [null]);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql = $this->get_filter_in_sql($params->teacher_roles, "ra.roleid");

        if ($CFG->dbtype == 'pgsql') {
            $files = "string_agg(CASE WHEN f.id IS NOT NULL THEN CONCAT('/', ctx.id, '/mod_resource/content/', r.revision, f.filepath, f.filename) ELSE NULL END, '[[,]]')";
            $files_sorting = "string_agg(f.filename, ', ')";
        } else {
            $files = "GROUP_CONCAT(CASE WHEN f.id IS NOT NULL THEN CONCAT('/', ctx.id, '/mod_resource/content/', r.revision, f.filepath, f.filename) ELSE NULL END SEPARATOR '[[,]]')";
            $files_sorting = "GROUP_CONCAT(f.filename)";
        }

        $data = $this->get_report_data("
            SELECT c.id,
                c.fullname,
                count(r.id) as resources,
                $files AS files,
                $files_sorting AS files_sorting,
                (SELECT DISTINCT CONCAT(u.firstname,' ',u.lastname)
                    FROM {role_assignments} AS ra
                    JOIN {user} u ON ra.userid = u.id
                    JOIN {context} AS ctx ON ctx.id = ra.contextid
                    WHERE ctx.instanceid = c.id AND ctx.contextlevel = 50 $sql LIMIT 1) AS teacher
                {$sql_columns}
            FROM {course} c
                LEFT JOIN {resource} r ON r.course = c.id
                LEFT JOIN {modules} m ON m.name='resource'
                LEFT JOIN {course_modules} cm ON cm.module=m.id AND cm.instance=r.id
                LEFT JOIN {context} ctx ON ctx.contextlevel=70 AND ctx.instanceid=cm.id

                LEFT JOIN {files} f ON f.component='mod_resource' AND f.filearea='content' AND f.itemid=0 AND f.filesize>0 AND ctx.id=f.contextid
            WHERE c.id > 0 $sql_filter
            GROUP BY c.id $sql_having $sql_order", $params, false);

        foreach($data as &$item){
            $files = explode('[[,]]', $item->files);
            $list = array();
            foreach($files as $path){
                if(empty($path)){
                    continue;
                }
                $slice = explode('/', $path);
                $filename = array_pop($slice);
                $fullurl = moodle_url::make_file_url('/pluginfile.php', $path, true);
                $list[] = array('url'=>(string)$fullurl, 'name'=>$filename);
            }
            $item->files = $list;
        }

        return array('data'=>$data);
    }
    public function report24($params)
    {
        global $CFG;
        $columns = array_merge(array(
            "r.name", "c.fullname", "visits", "timespend", "r.timemodified", "files_sorting"
        ), $this->get_filter_columns($params, [null]));

        $sql_columns = $this->get_columns($params, [null]);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filterdate_sql($params, "r.timemodified");

        $sql_filter_inner = $this->get_filter_in_sql($params->courseid, "cm.course");

        if ($CFG->dbtype == 'pgsql') {
            $files = "string_agg(CASE WHEN f.id IS NOT NULL THEN CONCAT('/', ctx.id, '/mod_resource/content/[[revision]]', f.filepath, f.filename) ELSE NULL END, '[[,]]')";
            $files_sorting = "string_agg(f.filename, ', ')";
        } else {
            $files = "GROUP_CONCAT(CASE WHEN f.id IS NOT NULL THEN CONCAT('/', ctx.id, '/mod_resource/content/[[revision]]', f.filepath, f.filename) ELSE NULL END SEPARATOR '[[,]]')";
            $files_sorting = "GROUP_CONCAT(f.filename)";
        }

        $data = $this->get_report_data("
            SELECT r.id,
                c.fullname,
                r.name,
                r.timemodified,
                r.revision,
                MAX(sl.visits) AS visits,
                MAX(sl.timespend) AS timespend,
                MAX(fs.files) AS files,
                MAX(fs.files_sorting) AS files_sorting
                {$sql_columns}
              FROM {resource} r
                JOIN {course} c ON c.id = r.course
                LEFT JOIN (SELECT
                        cm.instance,
                        $files AS files,
                        $files_sorting AS files_sorting
                    FROM {files} f
                        JOIN {context} ctx ON ctx.id=f.contextid
                        JOIN {course_modules} cm ON cm.id=ctx.instanceid
                    WHERE f.component='mod_resource' AND f.filearea='content' AND f.itemid=0 AND f.filesize>0 $sql_filter_inner
                    GROUP BY cm.instance) fs ON fs.instance=r.id
                LEFT JOIN (SELECT cm.instance, SUM(lit.timespend) as timespend, SUM(lit.visits) as visits FROM {local_intelliboard_tracking} lit, {course_modules} cm, {modules} m WHERE lit.page = 'module' and cm.id = lit.param and m.id = cm.module and m.name='resource' GROUP BY cm.instance) sl ON sl.instance = r.id
            WHERE r.id > 0 $sql_filter
            GROUP BY r.id, c.id $sql_having $sql_order", $params, false);

        foreach($data as &$item){
            $files = explode('[[,]]', $item->files);
            $list = array();
            foreach($files as $path){
                if(empty($path)){
                    continue;
                }
                $path = str_replace('[[revision]]', $item->revision, $path);
                $slice = explode('/', $path);
                $filename = array_pop($slice);
                $fullurl = moodle_url::make_file_url('/pluginfile.php', $path, true);
                $list[] = array('url'=>(string)$fullurl, 'name'=>$filename);
            }
            $item->files = $list;
        }

        return array('data'=>$data);
    }
    public function report25($params)
    {
        $columns = array_merge([
            ["sql_column" => "component", "type" => "file_component"], "files", "filesize"
        ], $this->get_filter_columns($params));
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);

        return $this->get_report_data("
            SELECT MAX(id) AS id,
                component,
                count(id) as files,
                sum(filesize) as filesize
            FROM {files}
            WHERE filesize > 0
            GROUP BY component $sql_having $sql_order", $params);
    }

    public function report26($params)
    {
        $columns = array_merge(array("course", "user", "enrolled", "cc.timecompleted", "score", "completed", "l.visits", "l.timespend"), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "ue.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "e.");
        $grade_avg = intelliboard_grade_sql(true, $params);
        $completion = $this->get_completion($params, "cmc.");

        $this->params['userid1'] = intval($params->userid);
        $this->params['userid2'] = intval($params->userid);

        return $this->get_report_data("
            SELECT MAX(ue.id) AS id,
                u.id as uid,
                MAX(cmc.completed) AS completed,
                MAX(cmm.modules) AS modules,
                CONCAT(u.firstname, ' ', u.lastname) as user,
                c.id as cid,
                c.fullname as course,
                MAX(ue.timecreated) as enrolled,
                MAX(gc.score) as score,
                MAX(l.timespend) as timespend,
                MAX(l.visits) as visits,
                MAX(cc.timecompleted) as timecompleted
                $sql_columns
            FROM {user_enrolments} ue
                LEFT JOIN {user} u ON u.id = ue.userid
                LEFT JOIN {enrol} e ON e.id = ue.enrolid
                LEFT JOIN {course} c ON c.id = e.courseid
                LEFT JOIN {course_completions} cc ON cc.course = e.courseid and cc.userid = ue.userid
                LEFT JOIN (SELECT gi.courseid, g.userid, $grade_avg AS score FROM {grade_items} gi, {grade_grades} g WHERE gi.itemtype = 'course' AND g.itemid = gi.id GROUP BY gi.courseid, g.userid) as gc ON gc.courseid = c.id AND gc.userid = u.id
                LEFT JOIN (SELECT lit.userid, lit.courseid, SUM(lit.timespend) as timespend, SUM(lit.visits) as visits FROM {local_intelliboard_tracking} lit WHERE lit.courseid > 0 GROUP BY lit.courseid, lit.userid) as l ON l.courseid = c.id AND l.userid = u.id
                LEFT JOIN (SELECT cm.course, count(cm.id) as modules FROM {course_modules} cm WHERE cm.visible  =  1 AND cm.completion > 0 GROUP BY cm.course) as cmm ON cmm.course = c.id
                LEFT JOIN (SELECT cm.course, cmc.userid, count(cmc.id) as completed FROM {course_modules} cm, {course_modules_completion} cmc WHERE cmc.coursemoduleid = cm.id AND cm.visible  =  1 $completion GROUP BY cm.course, cmc.userid) as cmc ON cmc.course = c.id AND cmc.userid = u.id
            WHERE ue.userid IN (SELECT com.userid as id FROM {cohort_members} com WHERE cohortid IN (SELECT com.cohortid as id FROM {cohort_members} com WHERE userid = :userid1) and userid <> :userid2 ) $sql_filter
            GROUP BY u.id, c.id  $sql_having $sql_order", $params);
    }
    public function report27($params)
    {
        $columns = array_merge(array("course", "username", "email", "q.name", "qa.state", "qa.timestart", "qa.timefinish", "qa.timefinish", "grade"), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filterdate_sql($params, "qa.timestart");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");

        $this->params['userid1'] = intval($params->userid);
        $this->params['userid2'] = intval($params->userid);

        return $this->get_report_data("
            SELECT qa.id,
                q.name,
                c.fullname as course,
                qa.timestart,
                qa.timefinish,
                qa.state,
                CONCAT(u.firstname, ' ', u.lastname) username,
                u.email,
                (qa.sumgrades/q.sumgrades*100) as grade
                $sql_columns
            FROM {quiz_attempts} qa
                LEFT JOIN {quiz} q ON q.id = qa.quiz
                LEFT JOIN {user} u ON u.id = qa.userid
                LEFT JOIN {course} c ON c.id = q.course
            WHERE qa.id > 0 and qa.userid IN (SELECT com.userid as id FROM {cohort_members} com WHERE cohortid IN (SELECT com.cohortid as id FROM {cohort_members} com WHERE userid = :userid1) and userid <> :userid2) $sql_filter
            GROUP BY qa.id, q.id, c.id, u.id $sql_having $sql_order", $params);
    }

    public function report28($params)
    {
        global $CFG;

        $columns = array_merge(array(
            "c.fullname","gi.itemname", "m.name", "u.firstname", "u.lastname", "u.email", "graduated",
            "grade", "grade_course", "completionstate", "timespend", "visits","ta.tags","u.phone1", "u.phone2", "u.institution",
            "u.department", "u.address", "u.city", "u.country"
        ), $this->get_filter_columns($params));
        $sql_columns = $this->get_columns($params);
        $sql_columns .= $this->get_modules_sql(null, false, 'itemname');
        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "cm.course");
        $sql_filter .= $this->get_filter_in_sql($params->custom, "m.id");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_module_sql($params, "cm.");
        $grade_single = intelliboard_grade_sql(false, $params);
        $grade_course_single = intelliboard_grade_sql(false, $params, 'g_c.', 0, 'gi_c.');

        if ($params->custom2 == 1) {
            $sql_filter .= $this->get_filterdate_sql($params, "cmc.timemodified");
            $sql_filter .= $this->get_completion($params, "cmc.");
        } elseif($params->custom2 == 2) {
          //skip
        } else {
            $sql_filter .= $this->get_filterdate_sql($params, "g.timemodified");
        }
        if ($params->sizemode) {
            $sql_columns .= ", '0' as timespend, '0' as visits";
            $sql_join = "";
        } elseif($params->custom2 == 2) {
            $filter = $this->get_filterdate_sql($params, "l.timepoint");
            $sql_filter .= " AND l.visits > 0";
            $sql_columns .= ", l.timespend as timespend, l.visits as visits";
            $sql_join = " LEFT JOIN (SELECT t.userid, t.param, SUM(l.timespend) as timespend, SUM(l.visits) as visits FROM {local_intelliboard_tracking} t, {local_intelliboard_logs} l WHERE l.trackid = t.id AND t.page = 'module' $filter GROUP BY t.userid, t.param) l ON l.param = cm.id AND l.userid = u.id";
        } else{
            $sql_columns .= ", l.timespend as timespend, l.visits as visits";
            $sql_join = " LEFT JOIN (SELECT userid, param, SUM(timespend) as timespend, SUM(visits) as visits FROM {local_intelliboard_tracking} WHERE page = 'module' GROUP BY userid, param) l ON l.param = cm.id AND l.userid = u.id";
        }
        if ($CFG->dbtype == 'pgsql') {
            $rawname = "string_agg( DISTINCT t.rawname, ', ')";
        } else {
            $rawname = "GROUP_CONCAT( DISTINCT t.rawname)";
        }
        $sql_join .= $this->get_suspended_sql($params);

        return $this->get_report_data("
            SELECT CONCAT(ue.id, '_', cm.id),
                ue.userid,
                u.email,
                u.phone1,
                u.phone2,
                u.institution,
                u.department,
                u.address,
                u.city,
                u.country,
                u.firstname,
                u.lastname,
                c.fullname,
                g.timemodified as graduated,
                $grade_single AS grade,
                $grade_course_single AS grade_course,
                cm.completion,
                cmc.completionstate,
                m.name AS module_name,
                ta.tags,
                gi.itemname
                $sql_columns
            FROM {user_enrolments} ue
                JOIN {user} u ON u.id = ue.userid
                JOIN {enrol} e ON e.id = ue.enrolid
                JOIN {course} c ON c.id = e.courseid
                JOIN {course_modules} cm ON cm.course = c.id
                JOIN {modules} m ON m.id = cm.module
                LEFT JOIN {grade_items} gi ON gi.itemtype = 'mod' AND gi.iteminstance = cm.instance AND gi.itemmodule = m.name AND gi.gradetype = 1
                LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.userid = u.id
                LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id AND cmc.userid = u.id
                LEFT JOIN (SELECT i.itemid, $rawname AS tags
                  FROM {tag_instance} i, {tag} t
                  WHERE t.id = i.tagid AND i.itemtype = 'course_modules' GROUP BY i.itemid) ta ON ta.itemid = cm.id
                LEFT JOIN {grade_items} gi_c ON gi_c.itemtype = 'course' AND gi_c.courseid = c.id
                LEFT JOIN {grade_grades} g_c ON g_c.itemid = gi_c.id AND g_c.userid = u.id
                $sql_join
            WHERE ue.id > 0 $sql_filter $sql_having $sql_order", $params);
    }

    public function report29($params)
    {
        $columns = array_merge(array("user", "course", "g.grade"), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $grade_single = intelliboard_grade_sql(false, $params);

        if($params->filter){
            $sql_courses = array();
            $courses = explode(",", $params->filter);
            foreach($courses as $i=>$c){
                $data = explode("_", $c);
                $course = "pu$i"; $grade = "pa$i";
                $this->params[$course] = clean_param($data[1], PARAM_INT);
                $this->params[$grade] = clean_param($data[0], PARAM_INT);
                $sql_courses[] = "(e.courseid = :$course AND g.grade < :$grade)";
            }
            $sql_courses = "(" . implode(" OR ", $sql_courses) . ")";
        }else{
            $sql_courses = "e.courseid > 0";
        }

        return $this->get_report_data("
            SELECT MAX(ue.id) AS id,
                CONCAT(u.firstname, ' ', u.lastname) as user,
                c.fullname as course,
                MAX(g.grade) AS grade,
                MAX(gm.graded) AS graded,
                MAX(cm.modules) AS modules $sql_columns
            FROM {user_enrolments} ue
                JOIN {user} u ON u.id = ue.userid
                JOIN {enrol} e ON e.id = ue.enrolid
                JOIN {course} c ON c.id = e.courseid
                LEFT JOIN {course_completions} cc ON cc.course = e.courseid AND cc.userid = ue.userid
                LEFT JOIN (SELECT gi.courseid, g.userid, $grade_single AS grade FROM {grade_items} gi, {grade_grades} g WHERE gi.itemtype = 'course' AND g.itemid = gi.id GROUP BY  gi.courseid, g.userid,g.rawgrademax, g.finalgrade) as g ON g.courseid = c.id AND g.userid = u.id
                LEFT JOIN (SELECT gi.courseid, gg.userid, count(gg.id) graded FROM {grade_items} gi, {grade_grades} gg WHERE gi.itemtype = 'mod' AND gg.itemid = gi.id GROUP BY  gi.courseid, gg.userid) as gm ON gm.courseid = c.id AND gm.userid = u.id
                LEFT JOIN (SELECT courseid, count(id) as modules FROM {grade_items} WHERE itemtype = 'mod' GROUP BY courseid) as cm ON cm.courseid = c.id
            WHERE (cc.timecompleted IS NULL OR cc.timecompleted = 0) AND gm.graded >= cm.modules AND $sql_courses $sql_filter
            GROUP BY u.id, c.id $sql_having $sql_order", $params);
    }

    public function report30($params)
    {
        $columns = array_merge(array("user", "course", "enrolled", "cc.timecompleted"), $this->get_filter_columns($params));

        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");

        if($params->filter){
            $sql_courses = array();
            $courses = explode(",", $params->filter);
            foreach($courses as $i=>$c){
                $data = explode("_", $c);
                $course = "pu$i"; $grade = "pa$i";
                $this->params[$course] = clean_param($data[1], PARAM_INT);
                $this->params[$grade] = clean_param($data[0], PARAM_INT) / 1000;
                $sql_courses[] = "(cc.course = :$course AND cc.timecompleted > :$grade)";
            }
            $sql_filter .= " AND (" . implode(" OR ", $sql_courses) . ")";
        }else{
            $sql_filter .= " AND cc.course > 0";
        }

        return $this->get_report_data("
            SELECT cc.id, CONCAT(u.firstname, ' ', u.lastname) as user, c.fullname as course, cc.timecompleted
            FROM
                {course_completions} cc,
                {course} c,
                {user} u
            WHERE u.id= cc.userid AND c.id = cc.course $sql_filter $sql_having $sql_order", $params);
    }

    public function report31($params)
    {
        $columns = array_merge(array("user", "course", "lit.lastaccess"), $this->get_filter_columns($params));

        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_order = $this->get_order_sql($params, $columns);

        if($params->filter){
            $sql_courses = array();
            $courses = explode(",", $params->filter);
            foreach($courses as $i=>$c){
                $data = explode("_", $c);
                $course = "pu$i"; $grade = "pa$i";
                $this->params[$course] = clean_param($data[1], PARAM_INT);
                $this->params[$grade] = (time()-clean_param($data[0], PARAM_INT)*86400);
                $sql_courses[] = "(lit.courseid = :$course AND lit.lastaccess < :$grade)";
            }
            $sql_filter = " AND (" . implode(" OR ", $sql_courses) . ")";
        }else{
            $sql_filter = " AND lit.courseid > 0";
        }

        return $this->get_report_data("
            SELECT MAX(lit.id) AS id, CONCAT(u.firstname, ' ', u.lastname) as user, c.fullname as course, MAX(lit.lastaccess) AS lastaccess
            FROM {user} u
                LEFT JOIN {local_intelliboard_tracking} lit on lit.userid = u.id AND lit.lastaccess = (
                    SELECT MAX(lastaccess)
                        FROM {local_intelliboard_tracking}
                        WHERE userid = lit.userid and courseid = lit.courseid
                    )
                LEFT JOIN {course} c ON c.id = lit.courseid
            WHERE lit.id > 0 $sql_filter GROUP BY u.id, c.id $sql_order", $params);
    }
    public function report32($params)
    {
        global $CFG;
        $columns = array_merge(array("u.firstname", "u.lastname", "u.email", "courses","timesite","timecourses","timeactivities","u.timecreated","teacher", "cohortname"), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "ctx.instanceid" => "courses"]);
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_in_sql($params->learner_roles, "ra.roleid");
        $sql_filter .= $this->vendor_filter('u.id', 'ctx.instanceid', $params);
        $sql_join_filter = "";

        if ((isset($params->custom) and $params->custom == 1) or $params->userid) {
            $sql_join_date = $this->get_filterdate_sql($params, "l.timepoint");
            $sql_join_filter = "SELECT t.userid,
                        SUM(l.timespend) as timesite,
                        SUM(CASE WHEN t.courseid > 0 THEN l.timespend ELSE null END) as timecourses ,
                        SUM(CASE WHEN t.page = 'module' THEN l.timespend ELSE null END) as timeactivities
                    FROM {local_intelliboard_tracking} t, {local_intelliboard_logs} l
                    WHERE l.trackid = t.id $sql_join_date
                    GROUP BY t.userid";
        } else {
            $sql_join_filter = "SELECT userid,
                        SUM(timespend) as timesite,
                        SUM(CASE WHEN courseid > 0 THEN timespend ELSE null END) as timecourses,
                        SUM(CASE WHEN page = 'module' THEN timespend ELSE null END) as timeactivities
                    FROM {local_intelliboard_tracking} GROUP BY userid";
            $sql_filter .= $this->get_filterdate_sql($params, "u.timecreated");
        }
        $sql_join = "";
        if($params->cohortid){
            if ($CFG->dbtype == 'pgsql') {
                $cohorts = "string_agg( DISTINCT coh.name, ', ')";
            } else {
                $cohorts = "GROUP_CONCAT( DISTINCT coh.name)";
            }
            $sql_columns .= ", coo.cohortname AS cohortname";
            $sql_cohort = $this->get_filter_in_sql($params->cohortid, "ch.cohortid");
            $sql_join = " LEFT JOIN (SELECT ch.userid, $cohorts as cohortname FROM {cohort} coh, {cohort_members} ch WHERE coh.id = ch.cohortid $sql_cohort GROUP BY ch.userid) coo ON coo.userid = u.id";
            $sql_filter .= " AND coo.cohortname IS NOT NULL";
        } else {
          $sql_columns .= ", '' as cohortname";
        }

        $sql_join .= $this->get_suspended_sql($params, 'ctx.instanceid');

        return $this->get_report_data("
            SELECT u.id,
                u.firstname,
                u.lastname,
                u.email,
                u.timecreated,
                COUNT(DISTINCT ctx.instanceid) as courses,
                MAX(lit.timesite) AS timesite,
                MAX(lit.timecourses) AS timecourses,
                MAX(lit.timeactivities) AS timeactivities
                $sql_columns
            FROM {role_assignments} ra
                JOIN {user} u ON u.id = ra.userid
                JOIN {context} AS ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50
                LEFT JOIN ($sql_join_filter) as lit ON lit.userid = u.id
                $sql_join
            WHERE u.id > 0 $sql_filter
            GROUP BY u.id $sql_having $sql_order", $params);
    }
    public function get_scormattempts($params)
    {
        global $DB;

        $this->params['userid'] = intval($params->userid);
        $this->params['scormid'] = intval($params->filter);

        return $DB->get_records_sql("
            SELECT sst.attempt,
                (SELECT s.value FROM {scorm_scoes_track} s WHERE s.element = 'x.start.time' and s.userid = sst.userid and s.scormid = sst.scormid and s.attempt = sst.attempt) as starttime,
                (SELECT s.value FROM {scorm_scoes_track} s WHERE s.element = 'cmi.core.score.raw' and s.userid = sst.userid and s.scormid = sst.scormid and s.attempt = sst.attempt) as score,
                (SELECT s.value FROM {scorm_scoes_track} s WHERE s.element = 'cmi.core.lesson_status' and s.userid = sst.userid and s.scormid = sst.scormid and s.attempt = sst.attempt) as status,
                (SELECT s.value FROM {scorm_scoes_track} s WHERE s.element = 'cmi.core.total_time' and s.userid = sst.userid and s.scormid = sst.scormid and s.attempt = sst.attempt) as totaltime,
                (SELECT s.timemodified FROM {scorm_scoes_track} s WHERE element = 'cmi.core.total_time' and s.userid = sst.userid and s.scormid = sst.scormid and s.attempt = sst.attempt) as timemodified
            FROM {scorm_scoes_track} sst
            WHERE sst.userid = :userid  and sst.scormid = :scormid
            GROUP BY sst.attempt, sst.userid, sst.scormid", $this->params);
    }

    public function report34($params)
    {
       $columns = array_merge(array(
           "u.firstname",
           "u.lastname",
           "u.email",
           "timespend",
           "visits",
           "firstaccess",
           "lastaccess"
       ), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_filter_in_sql($params->courseid,'c.id');
        $sql_filter .= $this->get_filterdate_sql($params, "l.timepoint");

        return $this->get_report_data("
            SELECT u.id,
               u.firstname,
               u.lastname,
               u.email,
               MIN(t.firstaccess) AS firstaccess,
               MIN(t.lastaccess) AS lastaccess,
               SUM(l.timespend) AS timespend,
               SUM(l.visits) AS visits
               $sql_columns
            FROM  {user} u, {local_intelliboard_tracking} t, {local_intelliboard_logs} l
            WHERE l.trackid = t.id AND u.id = t.userid AND t.page LIKE 'local_intelliboard' AND t.param IN(6,7,8,9) $sql_filter
            GROUP BY u.id, t.courseid $sql_having $sql_order", $params);
    }

    public function report35($params)
    {
        $columns = array_merge(array(
           "u.firstname", "u.lastname", "u.email", "MAX(t.param)", "timespend", "visits", "firstaccess", "lastaccess"
        ), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_filter_in_sql($params->courseid,'c.id');
        $sql_filter .= $this->get_filterdate_sql($params, "l.timepoint");
        $sql_join = $this->get_suspended_sql($params, 't.courseid');

        return $this->get_report_data(
            "SELECT MAX(t.id) AS id,
                    u.firstname,
                    u.lastname,
                    u.email,
                    t.page,
                    MAX(t.param) AS param,
                    MIN(t.firstaccess) AS firstaccess,
                    MIN(t.lastaccess) AS lastaccess,
                    SUM(l.timespend) AS timespend,
                    SUM(l.visits) AS visits
                    {$sql_columns}
               FROM {local_intelliboard_tracking} t
               JOIN {user} u ON u.id = t.userid
               JOIN {local_intelliboard_logs} l ON l.trackid = t.id
                    {$sql_join}
              WHERE t.page LIKE 'local_intelliboard' AND t.param IN(6,7,8,9) {$sql_filter}
           GROUP BY u.id, t.page
                    {$sql_having}
                    {$sql_order}",
            $params
        );
    }

    public function report36($params)
    {
        $columns = array_merge(array("u.firstname", "u.lastname", "u.email", "ue.timecreated", "ul.firstaccess", "ul.lastaccess", "u.lastlogin", "c.fullname", "teacher"), $this->get_filter_columns($params));

        $sql_order = $this->get_order_sql($params, $columns);
        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "ue.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "e.");
        $sql_filter .= $this->get_filterdate_sql($params, "ue.timecreated");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");

        if($params->custom2 == 6){
            $this->params['lsta'] = strtotime("-90 days");
        }elseif($params->custom2 == 5){
            $this->params['lsta'] = strtotime("-30 days");
        }elseif($params->custom2 == 4){
            $this->params['lsta'] = strtotime("-17 days");
        }elseif($params->custom2 == 3){
            $this->params['lsta'] = strtotime("-5 days");
        }elseif($params->custom2 == 2){
            $this->params['lsta'] = strtotime("-5 days");
        }elseif($params->custom2 == 1){
            $this->params['lsta'] = strtotime("-3 days");
        }else{
            $this->params['lsta'] = strtotime("-1 days");
        }
        $sql_filter .= " AND ul.lastaccess < :lsta";
        $sql_teacher_roles = $this->get_filter_in_sql($params->teacher_roles, "ra.roleid");


        return $this->get_report_data("
            SELECT ue.id,
                u.firstname,
                u.lastname,
                u.email,
                ue.timecreated,
                ul.firstaccess,
                ul.lastaccess,
                u.lastlogin,
                c.fullname,
                (SELECT DISTINCT CONCAT(u.firstname,' ',u.lastname)
                    FROM {role_assignments} AS ra
                        JOIN {user} u ON ra.userid = u.id
                        JOIN {context} AS ctx ON ctx.id = ra.contextid
                    WHERE ctx.instanceid = c.id AND ctx.contextlevel = 50 $sql_teacher_roles LIMIT 1
                ) AS teacher
                $sql_columns
            FROM {user_enrolments} ue
                JOIN {user} u ON u.id = ue.userid
                JOIN {enrol} e ON e.id = ue.enrolid
                JOIN {course} c ON c.id = e.courseid
                LEFT JOIN {local_intelliboard_tracking} ul ON ul.userid = u.id AND ul.courseid = c.id AND ul.page = 'course'
            WHERE u.id > 0 $sql_filter $sql_having $sql_order", $params);
    }
    public function report37($params)
    {
        //deleted
    }
    public function report38($params)
    {
        global $CFG;

        $columns = array_merge(array(
            "c.startdate", "ccc.timeend", "course", "u.firstname","u.lastname", "u.email", "enrols", "enrolstart", "enrolend", "complete", "complete"
        ), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_in_sql($params->learner_roles, "ra.roleid");
        $sql_filter .= $this->get_filterdate_sql($params, "ue.timecreated");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "ue.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "e.");

        if ($CFG->version < 2016120509) {
            $sql_columns .= ", '' AS startdate, '' AS enddate";
        } else {
            $sql_columns .= ", c.startdate AS startdate, c.enddate AS enddate";
        }

        return $this->get_report_data("
            SELECT ue.id,
                ue.timecreated as enrolstart,
                ue.timeend as enrolend,
                ccc.timeend,
                c.enablecompletion,
                cc.timecompleted as complete,
                u.firstname,
                u.lastname,
                u.email,
                ue.userid,
                e.courseid,
                e.enrol AS enrols,
                c.fullname as course
                $sql_columns
            FROM
                {user_enrolments} ue
                JOIN {enrol} e ON e.id = ue.enrolid
                JOIN {context} ctx ON ctx.instanceid = e.courseid AND ctx.contextlevel = 50
                LEFT JOIN {role_assignments} ra ON ra.contextid = ctx.id AND ra.userid = ue.userid
                LEFT JOIN {user} u ON u.id = ue.userid
                LEFT JOIN {course} c ON c.id = e.courseid
                LEFT JOIN {course_completions} cc ON cc.course = e.courseid AND cc.userid = ue.userid
                LEFT JOIN {course_completion_criteria} ccc ON ccc.course = e.courseid AND ccc.criteriatype = 2
            WHERE ue.id > 0 $sql_filter $sql_having $sql_order", $params);
    }
    public function report39($params)
    {
        global $CFG;

        $columns = array_merge(array(
            "u.firstname",
            "u.lastname",
            "u.email",
            "u.timecreated",
            "u.firstaccess",
            "u.lastaccess",
            "lit1.timespend_site",
            "lit2.timespend_courses",
            "lit3.timespend_activities",
            "u.phone1",
            "u.phone2",
            "u.institution",
            "u.department",
            "u.address",
            "u.city",
            ["sql_column" => "u.country", "type" => "country"],
            "cohortname"
        ), $this->get_filter_columns($params));

        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users"]);
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->vendor_filter('u.id', null, $params);
        $sql_vendor_filter = $this->vendor_filter(null, 'courseid', $params);
        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_columns = $this->get_columns($params, ["u.id"]);

        $sql_join = "";

        if($params->cohortid){
            if ($CFG->dbtype == 'pgsql') {
                $cohorts = "string_agg( DISTINCT coh.name, ', ')";
            } else {
                $cohorts = "GROUP_CONCAT( DISTINCT coh.name)";
            }
            $sql_columns .= ", coo.cohortname AS cohortname";
            $sql_cohort = $this->get_filter_in_sql($params->cohortid, "ch.cohortid");
            $sql_join = " LEFT JOIN (SELECT ch.userid, {$cohorts} AS cohortname
                                       FROM {cohort} coh, {cohort_members} ch
                                      WHERE coh.id = ch.cohortid {$sql_cohort}
                                   GROUP BY ch.userid
                                    ) coo ON coo.userid = u.id";
            $sql_filter .= " AND coo.cohortname IS NOT NULL";
        } else {
            $sql_columns .= ", '' as cohortname";
        }

        if($params->custom == 3) {
            $sql_filter_join = $this->get_filterdate_sql($params, "timepoint");
            $sql_join .= " JOIN (SELECT t.userid
                                   FROM {local_intelliboard_tracking} t, {local_intelliboard_logs} l
                                  WHERE t.id = l.trackid {$sql_filter_join}
                               GROUP BY t.userid
                                ) l ON l.userid = u.id";
        } elseif($params->custom == 2) {
            $sql_filter .= $this->get_filterdate_sql($params, "u.lastaccess");
        } elseif($params->custom == 1) {
            $sql_filter .= $this->get_filterdate_sql($params, "u.firstaccess");
        } else {
            $sql_filter .= $this->get_filterdate_sql($params, "u.timecreated");
        }

        return $this->get_report_data(
            "SELECT u.id,
                    u.firstname,
                    u.lastname,
                    u.email,
                    u.phone1,
                    u.phone2,
                    u.institution,
                    u.department,
                    u.address,
                    u.city,
                    u.country,
                    u.timecreated,
                    u.firstaccess,
                    u.lastaccess,
                    lit1.timespend_site,
                    lit2.timespend_courses,
                    lit3.timespend_activities
                    {$sql_columns}
               FROM {user} u
          LEFT JOIN (SELECT userid, SUM(timespend) AS timespend_site
                       FROM {local_intelliboard_tracking}
                      WHERE courseid > 0 {$sql_vendor_filter}
                   GROUP BY userid
                    ) lit1 ON lit1.userid = u.id
          LEFT JOIN (SELECT userid, SUM(timespend) AS timespend_courses
                       FROM {local_intelliboard_tracking}
                      WHERE courseid > 0 {$sql_vendor_filter}
                   GROUP BY userid
                    ) lit2 ON lit2.userid = u.id
          LEFT JOIN (SELECT userid, SUM(timespend) AS timespend_activities
                       FROM {local_intelliboard_tracking}
                      WHERE page = 'module' {$sql_vendor_filter}
                   GROUP BY userid
                    ) lit3 ON lit3.userid = u.id
          {$sql_join}
              WHERE u.id > 0 {$sql_filter}
              {$sql_having}
              {$sql_order}",
            $params
        );
    }
    public function report40($params)
    {
        global $CFG;
        $columns = array_merge(array("course", "c.shortname","category", "u.firstname", "u.lastname", "email", "e.enrol", "ue.timecreated", "grade", "lastaccess", "", "cohortname"), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "ue.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "e.");
        $sql_filter .= $this->vendor_filter('u.id', 'c.id', $params);
        $sql1 = $this->get_filterdate_sql($params, "l.timepoint");
        $grade_single = intelliboard_grade_sql(false, $params);

        $sql_join = "";
        if($params->cohortid){
            if ($CFG->dbtype == 'pgsql') {
                $cohorts = "string_agg( DISTINCT coh.name, ', ')";
            } else {
                $cohorts = "GROUP_CONCAT( DISTINCT coh.name)";
            }
            $sql_columns .= ", coo.cohortname AS cohortname";
            $sql_cohort = $this->get_filter_in_sql($params->cohortid, "ch.cohortid");
            $sql_join = " LEFT JOIN (SELECT ch.userid, $cohorts as cohortname FROM {cohort} coh, {cohort_members} ch WHERE coh.id = ch.cohortid $sql_cohort GROUP BY ch.userid) coo ON coo.userid = u.id";
            $sql_filter .= " AND coo.cohortname IS NOT NULL";
        } else {
          $sql_columns .= ", '' as cohortname";
        }

        return $this->get_report_data("
            SELECT ue.id,
                u.firstname,
                u.lastname,
                u.email,
                ue.timecreated as enrolled,
                ue.userid,
                e.enrol AS enrols,
                ul.timeaccess AS lastaccess,
                $grade_single AS grade,
                c.id as cid,
                c.shortname,
                ca.name AS category,
                c.fullname as course
                $sql_columns
            FROM {user_enrolments} ue
                JOIN {user} u ON u.id = ue.userid
                JOIN {enrol} e ON e.id = ue.enrolid
                JOIN {course} c ON c.id = e.courseid
                LEFT JOIN {course_categories} ca ON ca.id = c.category
                LEFT JOIN {user_lastaccess} ul ON ul.courseid = c.id AND ul.userid = u.id
                LEFT JOIN {grade_items} gi ON gi.itemtype = 'course' AND gi.courseid = e.courseid
                LEFT JOIN {grade_grades} g ON g.userid = u.id AND g.itemid = gi.id AND g.finalgrade IS NOT NULL
                LEFT JOIN (SELECT MAX(t.id) AS id, t.userid,t.courseid FROM
                    {local_intelliboard_tracking} t,
                    {local_intelliboard_logs} l
                WHERE l.trackid = t.id $sql1
             GROUP BY t.courseid, t.userid) as l ON l.courseid = e.courseid AND l.userid = ue.userid $sql_join
            WHERE l.id IS NULL $sql_filter $sql_having $sql_order", $params);
    }
    public function report41($params)
    {
        global $CFG;
        $columns = array_merge(array(
            "cc.name", "course", "c.shortname", "u.firstname","u.lastname","email", "enrolled", "certificate", "ci.timecreated", "grade", "ci.code","u.phone1", "u.phone2", "u.institution", "u.department", "u.address", "u.city", "u.country",""
        ), $this->get_filter_columns($params));

        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filterdate_sql($params, "ci.timecreated");
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_columns = $this->get_columns($params, ["u.id"]);


        $grade_avg = intelliboard_grade_sql(true, $params, 'gg.', 0, 'gi.');
        $grade_course_avg = intelliboard_grade_sql(true, $params, 'ggc.', 0, 'gic.');

        if($params->custom3 == 1){
            if ($CFG->dbtype == 'pgsql') {
                $cm_id = "(cel.data::json->>'gradeitem')::int";
            } else {
                $cm_id = "JSON_UNQUOTE(JSON_EXTRACT(cel.data, '$.gradeitem'))";
            }

            return $this->get_report_data("
                SELECT ci.id,
                    u.firstname,
                    u.lastname,
                    u.email,
                    u.phone1,
                    u.phone2,
                    u.institution,
                    u.department,
                    u.address,
                    u.city,
                    u.country,
                    ce.name as certificate,
                    ci.timecreated,
                    ci.code,
                    ci.userid,
                    c.id as cid,
                    c.fullname as course,
                    c.shortname,
                    cc.name as category,
                    MAX(ue.timecreated) as enrolled,
                    CASE WHEN MAX(cm.id) IS NULL THEN $grade_course_avg ELSE $grade_avg END AS grade
                    $sql_columns
                FROM {customcert_issues} ci
                    LEFT JOIN {customcert} ce ON ce.id = ci.customcertid
                    LEFT JOIN {user} u ON u.id = ci.userid
                    LEFT JOIN {course} c ON c.id = ce.course
                    LEFT JOIN {course_categories} cc ON cc.id=c.category

                    LEFT JOIN {enrol} e ON e.courseid=c.id
                    LEFT JOIN {user_enrolments} ue ON ue.userid=u.id AND ue.enrolid=e.id

                    LEFT JOIN {customcert_pages} cp ON cp.templateid=ce.templateid
                    LEFT JOIN {customcert_elements} cel ON cel.pageid=cp.id AND cel.element='grade'

                    LEFT JOIN {course_modules} cm ON cm.id= $cm_id
                    LEFT JOIN {modules} m ON m.id=cm.module
                    LEFT JOIN {grade_items} gi ON gi.courseid=c.id AND gi.itemtype='mod' AND gi.itemmodule=m.name AND gi.iteminstance=cm.instance
                    LEFT JOIN {grade_grades} gg ON gg.itemid=gi.id AND gg.userid=u.id
                    LEFT JOIN {grade_items} gic ON gic.courseid=c.id AND gic.itemtype='course'
                    LEFT JOIN {grade_grades} ggc ON ggc.itemid=gic.id AND ggc.userid=u.id AND ggc.overridden=0
                WHERE ci.id > 0 $sql_filter
                GROUP BY ci.id,u.id,ce.id,c.id,cc.id $sql_having $sql_order", $params);
        }else{
            return $this->get_report_data("
                SELECT ci.id,
                    u.firstname,
                    u.lastname,
                    u.email,
                    u.phone1,
                    u.phone2,
                    u.institution,
                    u.department,
                    u.address,
                    u.city,
                    u.country,
                    ce.name as certificate,
                    ci.timecreated,
                    ci.code,
                    ci.userid,
                    c.id as cid,
                    c.fullname as course,
                    c.shortname,
                    cc.name as category,
                    MAX(ue.timecreated) as enrolled,
                    CASE WHEN ce.printgrade=1 THEN $grade_course_avg ELSE $grade_avg END AS grade
                    $sql_columns
                FROM {certificate_issues} ci
                    LEFT JOIN {certificate} ce ON ce.id = ci.certificateid
                    LEFT JOIN {user} u ON u.id = ci.userid
                    LEFT JOIN {course} c ON c.id = ce.course
                    LEFT JOIN {course_categories} cc ON cc.id=c.category

                    LEFT JOIN {enrol} e ON e.courseid=c.id
                    LEFT JOIN {user_enrolments} ue ON ue.userid=u.id AND ue.enrolid=e.id

                    LEFT JOIN {course_modules} cm ON cm.id=ce.printgrade
                    LEFT JOIN {modules} m ON m.id=cm.module
                    LEFT JOIN {grade_items} gi ON gi.courseid=c.id AND gi.itemtype='mod' AND gi.itemmodule=m.name AND gi.iteminstance=cm.instance
                    LEFT JOIN {grade_grades} gg ON gg.itemid=gi.id AND gg.userid=u.id
                    LEFT JOIN {grade_items} gic ON gic.courseid=c.id AND gic.itemtype='course'
                    LEFT JOIN {grade_grades} ggc ON ggc.itemid=gic.id AND ggc.userid=u.id AND ggc.overridden=0
                WHERE ci.id > 0 $sql_filter
                GROUP BY ci.id,u.id,ce.id,c.id,cc.id $sql_having $sql_order", $params);
        }

    }
    public function report43($params)
    {
        $columns = array_merge(array("user", "completed_courses", "grade", "visits", "timespend", "u.timecreated"), $this->get_filter_columns($params));

        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);

        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "ue.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "e.");
        $sql_filter .= $this->vendor_filter('u.id', 'c.id', $params);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_columns = $this->get_columns($params, ["u.id"]);
        $grade_avg = intelliboard_grade_sql(true, $params);

        if($params->sizemode){
            $sql_columns .= ", '0' as timespend, '0' as visits";
            $sql_join = "";
        }else{
            $sql_columns .= ", MAX(lit.timespend) AS timespend, MAX(lit.visits) AS visits";
            $sql_join = " LEFT JOIN (SELECT l.userid, SUM(l.timespend) as timespend, SUM(l.visits) as visits FROM {local_intelliboard_tracking} l GROUP BY l.userid) lit ON lit.userid = u.id";
        }

        return $this->get_report_data("
            SELECT u.id,
                CONCAT(u.firstname, ' ', u.lastname) as user,
                u.email,
                u.timecreated,
                $grade_avg AS grade,
                COUNT(DISTINCT e.courseid) as courses,
                COUNT(DISTINCT cc.course) as completed_courses
                $sql_columns
            FROM {user} u
                LEFT JOIN {user_enrolments} ue ON ue.userid = u.id
                LEFT JOIN {enrol} e ON e.id = ue.enrolid
                LEFT JOIN {course} c ON c.id = e.courseid
                LEFT JOIN {course_completions} cc ON cc.timecompleted > 0 AND cc.course = e.courseid AND cc.userid = ue.userid
                LEFT JOIN {grade_items} gi ON gi.itemtype = 'course' AND gi.courseid = e.courseid
                LEFT JOIN {grade_grades} g ON g.userid = ue.userid AND g.itemid = gi.id AND g.finalgrade IS NOT NULL
                $sql_join
            WHERE u.id > 0 $sql_filter
            GROUP BY u.id $sql_having $sql_order", $params);
    }
    public function report44($params)
    {
        $columns = array_merge(array(
            "c.fullname", "progress"
        ), $this->get_filter_columns($params, [null]));

        $sql_columns = $this->get_columns($params, [null]);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "ue.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "e.");
        $sql_order = $this->get_order_sql($params, $columns);

        return $this->get_report_data("
            SELECT c.id,
                c.fullname,
                COUNT(DISTINCT ue.userid) users,
                COUNT(DISTINCT cc.userid) as completed,
                ROUND(COUNT(DISTINCT cc.userid)/COUNT(DISTINCT ue.userid), 2) as progress
                {$sql_columns}
            FROM {user_enrolments} ue
                INNER JOIN {user} u ON u.id = ue.userid
                LEFT JOIN {enrol} e ON e.id = ue.enrolid
                LEFT JOIN {course} c ON c.id = e.courseid
                LEFT JOIN {course_completions} cc ON cc.course = e.courseid AND cc.userid = ue.userid AND cc.timecompleted > 0
            WHERE c.id > 0 $sql_filter
            GROUP BY c.id $sql_having $sql_order", $params);
    }
    public function report45($params)
    {
        $columns = array_merge(array("u.firstname", "u.lastname", "u.email", "all_att", "timespend", "highest_grade", "lowest_grade", "cmc.timemodified"), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "q.course" => "courses"]);
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->vendor_filter('u.id', 'q.course', $params);
        $completion = $this->get_completion($params, "cmc.");

        if($params->custom == 1){
            $sql_having .= (empty($sql_having))?' HAVING COUNT(DISTINCT qa.id)=0':str_replace(' HAVING ',' HAVING (',$sql_having). ') AND COUNT(DISTINCT qa.id)=0';
        }elseif($params->custom == 2){
            $sql_having .= (empty($sql_having))?' HAVING COUNT(DISTINCT qa.id)>0 AND (cmc.timemodified=0 OR cmc.timemodified IS NULL)':str_replace(' HAVING ',' HAVING (',$sql_having).') AND COUNT(DISTINCT qa.id)>0 AND (cmc.timemodified=0 OR cmc.timemodified IS NULL)';
        }
        $this->params['courseid'] = intval($params->courseid);
        $sql_join = $this->get_suspended_sql($params, 'q.course', 'u.id', false);

        return $this->get_report_data("
            SELECT u.id,
                u.firstname,
                u.lastname,
                u.email,
                COUNT(DISTINCT qa.id) as all_att,
                (MAX(qa.sumgrades)/q.sumgrades)*100 as highest_grade,
                (MIN(qa.sumgrades)/q.sumgrades)*100 as lowest_grade,
                SUM(qa.timefinish - qa.timestart) AS timespend,
                MAX(cmc.timemodified) AS timemodified
                $sql_columns
            FROM {quiz_attempts} qa
            JOIN {quiz} q ON q.id = qa.quiz
            JOIN {user} u ON u.id = qa.userid
            LEFT JOIN {modules} m ON m.name='quiz'
            LEFT JOIN {course_modules} cm ON cm.course = q.course AND cm.module=m.id AND cm.instance=q.id
            LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid=cm.id $completion AND cmc.userid=qa.userid
            $sql_join
            WHERE q.id= :courseid $sql_filter
            GROUP BY u.id, q.sumgrades $sql_having $sql_order", $params);
    }
    public function report42($params)
    {
        $columns = array_merge([
            "u.firstname",
            "u.lastname",
            "u.email",
            "c.fullname",
            "c.startdate",
            "started",
            "grade",
            "grade",
            "cmc.completed",
            "grade",
            "complete",
            "lit.visits",
            "lit.timespend"
        ], $this->get_filter_columns($params));

        $sql_columns    = $this->get_columns($params, ["u.id"]);
        $sql_having     = $this->get_filter_sql($params, $columns, false);
        $sql_order      = $this->get_order_sql($params, $columns);
        $course_filter  = $this->get_filter_in_sql($params->courseid, "e1.courseid");
        $sql_filter     = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter    .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter    .= $this->get_filter_user_sql($params, "u.");
        $sql_filter    .= $this->get_filter_course_sql($params, "c.");
        $sql_filter    .= $this->get_filter_enrol_sql($params, "", "ue.status");
        $sql_filter    .= $this->get_filter_enrol_sql($params, "", "ue.enrolstatus");
        $role_filter    = $this->get_filter_in_sql($params->learner_roles,'roleid');
        $grade_avg      = intelliboard_grade_sql(true, $params, 'g.', 2, 'gi.',true);
        $grade_single   = intelliboard_grade_sql(false, $params, 'g.', 2, 'gi.',true);
        $completion     = $this->get_completion($params, "cmc.");
        $grades         = [];
        $joins          = $this->group_aggregation('u.id', 'c.id', $params);

        if($params->custom){
            $sql_filter .= $this->get_filterdate_sql($params, "c.startdate");
        } else {
            $sql_filter .= $this->get_filterdate_sql($params, "ue.timecreated");
        }

        if($params->custom4 == 1) {
            $sql_filter .= ' AND cc.timecompleted IS NOT NULL';
        } elseif($params->custom4 == 2) {
            $sql_filter .= ' AND cc.timecompleted IS NULL';
        }

        if(!empty($params->custom2)){
            $book = explode(',',$params->custom2);

            foreach($book as $i=>$item){
                $grade = explode('-',$item);
                $grade0 = "grade0$i"; $grade1 = "grade1$i";
                $this->params[$grade0] = isset($grade[0]) ? clean_param($grade[0], PARAM_INT) : false;
                $this->params[$grade1] = isset($grade[1]) ? clean_param($grade[1], PARAM_FLOAT) : false;
                if($grade0 !== false and $grade1 !== false ){
                    $grades[] = "$grade_single BETWEEN :$grade0 AND :$grade1";
                }
            }

            if($grades){
                $sql_filter .= ' AND ('.implode(' OR ',$grades).')';
            }
        }

        return $this->get_report_data(
            "SELECT CONCAT(ra.userid, '_', c.id) AS id,
                    cri.gradepass,
                    u.email,
                    ra.userid,
                    ra.timemodified AS started,
                    c.id AS cid,
                    c.fullname,
                    c.startdate,
                    git.average,
                    {$grade_single} AS grade,
                    cmc.completed,
                    u.firstname,
                    u.lastname,
                    lit.timespend,
                    lit.visits,
                    c.enablecompletion,
                    cc.timecompleted AS complete
                    {$sql_columns}
               FROM (SELECT userid, contextid, MAX(timemodified) AS timemodified
                       FROM {role_assignments}
                      WHERE id > 0 {$role_filter}
                   GROUP BY userid, contextid
                    ) ra
               JOIN {user} u ON ra.userid = u.id
               JOIN {context} ctx ON ctx.id = ra.contextid
               JOIN {course} c ON c.id = ctx.instanceid
               JOIN (SELECT e1.courseid, ue1.userid, MAX(ue1.status) AS status, MAX(e1.status) AS enrolstatus,
                            MIN(ue1.timecreated) AS timecreated
                       FROM {user_enrolments} ue1
                       JOIN {enrol} e1 ON ue1.enrolid = e1.id {$course_filter}
                   GROUP BY e1.courseid, ue1.userid
                    ) ue ON ue.userid = u.id AND ue.courseid = c.id
                    {$joins}
          LEFT JOIN {course_completions} cc ON cc.course = c.id AND cc.userid = u.id
          LEFT JOIN {course_completion_criteria} cri ON cri.course = c.id AND cri.criteriatype = 6
          LEFT JOIN {grade_items} gi ON gi.courseid = c.id AND gi.itemtype = 'course'
          LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.userid =u.id
          LEFT JOIN (SELECT lit.userid, lit.courseid, SUM(lit.timespend) as timespend, SUM(lit.visits) as visits
                       FROM {local_intelliboard_tracking} lit
                      WHERE lit.courseid > 0
                   GROUP BY lit.courseid, lit.userid
                    ) lit ON lit.courseid = c.id AND lit.userid = u.id
          LEFT JOIN (SELECT gi.courseid, {$grade_avg} AS average
                       FROM {grade_items} gi, {grade_grades} g
                      WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL
                   GROUP BY gi.courseid
                    ) git ON git.courseid=c.id
          LEFT JOIN (SELECT cmc.userid, cm.course, COUNT(cmc.id) as completed
                       FROM {course_modules_completion} cmc, {course_modules} cm
                      WHERE cm.visible = 1 AND cmc.coursemoduleid = cm.id {$completion}
                   GROUP BY cm.course, cmc.userid
                    ) cmc ON cmc.course = c.id AND cmc.userid = u.id
              WHERE u.id > 0 {$sql_filter}
                    {$sql_having}
                    {$sql_order}",
            $params
        );
    }
    public function report46($params)
    {
        //deleted
    }
    public function report47($params)
    {
        global $CFG;

        $columns = array_merge(array(
            "firstname",
            "lastname",
            "email",
            "course_name",
            "role",
            "lsl.all_count",
            "user_action",
            "action_role",
            "action",
            "timecreated"
        ), $this->get_filter_columns($params, ["ra.userid"]));

        $sql_columns = $this->get_columns($params, ["ra.userid"]);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["ra.userid" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filterdate_sql($params, "ra.timemodified");

        if ($CFG->dbtype == 'pgsql') {
            $group_concat = "string_agg( DISTINCT r0.shortname, ', ')";
            $cast_start = 'cast(';
            $cast_end = ' as text)';
        } else {
            $group_concat = "GROUP_CONCAT( DISTINCT r0.shortname)";
            $cast_start = '';
            $cast_end = '';
        }
        $sql_join = $this->get_suspended_sql($params, 'c.id', 'u_related.id');

        return $this->get_report_data("
            SELECT MIN(ra.id),
                c.id AS courseid,
                c.fullname as course_name,
                $group_concat AS role,
                MIN(lsl.all_count) as all_count,
                CASE WHEN MIN(lsl.all_count)>1 THEN MAX(r.shortname) ELSE '-' END as action_role,
                CASE WHEN MIN(lsl.all_count)>1 THEN MAX(log.action) ELSE '-' END as action,
                CASE WHEN MIN(lsl.all_count)>1 THEN $cast_start MAX(log.timecreated) $cast_end ELSE '-' END as timecreated,
                CASE WHEN MIN(lsl.all_count)>1 THEN MAX(CONCAT(u_action.firstname, ' ', u_action.lastname)) ELSE '-' END AS user_action,
                MAX(u_action.id) as user_action_id,
                MAX(u_related.firstname) as firstname,
                MAX(u_related.lastname) as lastname,
                MAX(u_related.email) as email,
                MAX(u_related.id) as user_related_id
                $sql_columns
            FROM {role_assignments} ra
                LEFT JOIN {role} r0 ON r0.id = ra.roleid
                LEFT JOIN {context} ctx ON ctx.id = ra.contextid
                LEFT JOIN {course} c ON c.id=ctx.instanceid
                LEFT JOIN (SELECT lsl.courseid, lsl.relateduserid, MAX(lsl.id) as last_change, COUNT(lsl.id) as all_count
                           FROM {logstore_standard_log} lsl
                           WHERE (lsl.action='assigned' OR lsl.action='unassigned') AND lsl.target='role' AND lsl.contextlevel=50
                           GROUP BY lsl.courseid,lsl.relateduserid
                          ) as lsl ON lsl.courseid=c.id AND lsl.relateduserid=ra.userid
                LEFT JOIN {logstore_standard_log} log ON log.id=lsl.last_change
                LEFT JOIN {role} r ON r.id=log.objectid
                LEFT JOIN {user} u_action ON u_action.id=log.userid
                LEFT JOIN {user} u_related ON u_related.id=log.relateduserid
                $sql_join
            WHERE ra.id > 0 $sql_filter
            GROUP BY ra.userid, c.id $sql_having $sql_order", $params);
    }
    public function report58($params)
    {
        //deleted
    }
    public function report66($params)
    {
        global $CFG;
        $columns = array_merge(array(
            "u.firstname", "u.lastname", "u.email", "u.idnumber", "c.fullname", "assignment",
            "a.duedate", "s.status", "grade", "u.phone1", "u.phone2", "u.institution",
            "u.department", "u.address", "u.city",
            ["sql_column" => "u.country", "type" => "country"],""
        ), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_module_sql($params, "cm.");
        $sql_filter .= $this->get_filterdate_sql($params, "a.duedate");

        if (!$params->custom) {
          $sql_filter .= " AND (s.timemodified > a.duedate or s.timemodified IS NULL)";
        }
        if ($params->custom2 == 1) {
          $sql_filter .= " AND s.status='submitted' AND s.timemodified>0";
        }elseif ($params->custom2 == 2) {
            if ($CFG->dbtype == 'pgsql') {
                $sql_filter .= " AND s.status!='' AND a.duedate>0 AND a.duedate<EXTRACT(EPOCH FROM CURRENT_TIMESTAMP)";
            }else{
                $sql_filter .= " AND s.status!='' AND a.duedate>0 AND a.duedate<NOW()";
            }
        }elseif ($params->custom2 == 3) {
            if ($CFG->dbtype == 'pgsql') {
                $sql_filter .= " AND s.status!='' AND s.status!='submitted' AND (a.duedate=0 OR a.duedate>EXTRACT(EPOCH FROM CURRENT_TIMESTAMP))";
            }else{
                $sql_filter .= " AND s.status!='' AND s.status!='submitted' AND (a.duedate=0 OR a.duedate>NOW())";
            }
        }elseif ($params->custom2 == 4) {
            if ($CFG->dbtype == 'pgsql') {
                $sql_filter .= " AND a.duedate>0 AND a.duedate<EXTRACT(EPOCH FROM CURRENT_TIMESTAMP)";
            }else{
                $sql_filter .= " AND a.duedate>0 AND a.duedate<NOW()";
            }
          $sql_filter .= " AND a.duedate>0 AND a.duedate<NOW()";
        }

        if ($CFG->dbtype == 'pgsql') {
            $row_number = "row_number() OVER ()";
            $row_number_select = "";
        } else {
            $row_number = "@x:=@x+1";
            $row_number_select = "(SELECT @x:= 0) AS x, ";
        }
        $sql_join = $this->get_suspended_sql($params);
        $grade_single = intelliboard_grade_sql(false, $params, 'gg.', 0, 'gi.');

        return $this->get_report_data("
            SELECT $row_number as id,
                a.name as assignment,
                a.duedate,
                c.fullname as course,
                s.status,
                s.timemodified AS submitted,
                u.email,
                u.idnumber,
                u.phone1,
                u.phone2,
                u.institution,
                u.department,
                u.address,
                u.city,
                u.country,
                u.firstname,
                u.lastname,
                $grade_single AS grade
                $sql_columns
            FROM $row_number_select {assign} a
                LEFT JOIN (SELECT e.courseid, ue.userid FROM {user_enrolments} ue, {enrol} e WHERE e.id=ue.enrolid GROUP BY e.courseid, ue.userid) ue
                ON ue.courseid = a.course
                LEFT JOIN {user} u ON u.id = ue.userid
                LEFT JOIN {assign_submission} s ON s.assignment = a.id AND s.userid = u.id
                LEFT JOIN {course} c ON c.id = a.course
                LEFT JOIN {modules} m ON m.name = 'assign'
                LEFT JOIN {course_modules} cm ON cm.instance = a.id AND cm.module = m.id
                LEFT JOIN {grade_items} gi ON gi.courseid=c.id AND gi.itemtype='mod' AND gi.itemmodule=m.name AND gi.iteminstance=cm.instance
                LEFT JOIN {grade_grades} gg ON gg.itemid=gi.id AND gg.userid=u.id
                $sql_join
            WHERE a.id > 0 $sql_filter $sql_having $sql_order", $params);
    }

    public function report72($params)
    {
        global $CFG, $DB;

        require_once($CFG->dirroot.'/mod/scorm/locallib.php');
        require_once($CFG->dirroot.'/mod/scorm/report/reportlib.php');

        $this->params['attempt'] = (int)$params->custom;
        $this->params['courseid'] = (int)$params->courseid;

        if($params->userid){
            $this->params['userid'] = (int)$params->userid;
            $sql = " AND t.userid = :userid";
        }else{
            $sql = "";
        }
        $sql_filter .= $this->get_teacher_sql($params, ["u.id" => "users", "s.course" => "courses"]);

        $trackdata = $DB->get_records_sql("
            SELECT t.*, u.firstname, u.lastname
            FROM {scorm_scoes_track} t, {user} u, {scorm} s
            WHERE u.id = t.userid AND s.course = :courseid AND t.scormid = s.id AND t.attempt = :attempt $sql", $this->params);

        $questioncount = array_values($trackdata)[0]->scormid;

        // Defined in order to unify scorm1.2 and scorm2004. - $data = scorm_format_interactions($data);
        $data = array();
        foreach ($trackdata as $track) {
            if(isset($data[$track->userid])){
                $usertrack = $data[$track->userid];
            }else{
                $usertrack = new stdClass();
                $usertrack->score_raw = '';
                $usertrack->status = '';
                $usertrack->total_time = '00:00:00';
                $usertrack->session_time = '00:00:00';
                $usertrack->timemodified = 0;
                $usertrack->firstname = isset($track->firstname)?$track->firstname:'';
                $usertrack->lastname = isset($track->lastname)?$track->lastname:'';
            }
            $element = $track->element;
            $usertrack->{$element} = $track->value;
            switch ($element) {
                case 'cmi.core.lesson_status':
                case 'cmi.completion_status':
                    if ($track->value == 'not attempted') {
                        $track->value = 'notattempted';
                    }
                    $usertrack->status = $track->value;
                    break;
                case 'cmi.core.score.raw':
                case 'cmi.score.raw':
                    $usertrack->score_raw = (float) sprintf('%2.2f', $track->value);
                    break;
                case 'cmi.core.session_time':
                case 'cmi.session_time':
                    $usertrack->session_time = $track->value;
                    break;
                case 'cmi.core.total_time':
                case 'cmi.total_time':
                    $usertrack->total_time = $track->value;
                    break;
            }
            if (isset($track->timemodified) && ($track->timemodified > $usertrack->timemodified)) {
                $usertrack->timemodified = $track->timemodified;
            }
            $data[$track->userid] = $usertrack;
        }


        return array(
            "questioncount"   => $questioncount,
            "recordsTotal"    => count($trackdata),
            "recordsFiltered" => count($trackdata),
            "data"            => $data);
    }
    public function report73($params)
    {
        global $DB;

        $this->params['courseid'] = (int)$params->courseid;

        $sql = "";
        if($params->userid){
            $this->params['userid'] = (int)$params->userid;
            $data = $DB->get_records_sql("
                SELECT b.id, b.name, i.timemodified, i.location, i.progress, u.firstname, u.lastname, c.fullname
                FROM {scorm_ajax_buttons} b
                    LEFT JOIN {user} u ON u.id = :userid
                    LEFT JOIN {course} c ON c.id = :courseid
                    LEFT JOIN {scorm} s ON s.course = c.id
                    LEFT JOIN {scorm_ajax} a ON a.scormid = s.id
                    LEFT JOIN {scorm_ajax_info} i ON i.page = b.id AND i.userid = u.id AND i.relid = a.relid
                ORDER BY b.id", $this->params);
        }else{
            $data = $DB->get_records_sql("
                SELECT (n.id * u.id) as id, b.name, i.timemodified, i.location, i.progress, u.firstname, u.lastname, c.fullname
                FROM {scorm_ajax_buttons} b
                    LEFT JOIN {course} c ON c.id = :courseid
                    LEFT JOIN {scorm} s ON s.course = c.id
                    LEFT JOIN {scorm_ajax} a ON a.scormid = s.id
                    LEFT JOIN {scorm_ajax_info} i ON i.page = b.id AND i.relid = a.relid
                    LEFT JOIN {user} u ON u.id = i.userid
                WHERE u.id > 0
                GROUP BY b.id, u.id
                ORDER BY b.id", $this->params);
        }


        return array(
            "recordsTotal"    => count($data),
            "recordsFiltered" => count($data),
            "data"            => $data);
    }
    //Custom Report
    public function report75($params)
    {
        global $DB;

        $columns = array_merge(
            array("mco_name", "mc_name", "mci_userid", "mci_certid", "mu_firstname", "mu_lastname",  "mu_email", "enrol_date", "issue_date"),
            $this->get_filter_columns($params)
        );

        // filter by custom profile field
        $customfieldfilter = '';
        if($params->custom3) {
            $data = $DB->get_records_list('user_info_data', 'id', explode(',', $params->custom3));
            $usertablealias = $params->custom2 == 2 ? 'u' : 'mu';

            if($data) {
                $fieldid = 0;
                $values = array_map(function($item) use (&$fieldid) {
                    $fieldid = $item->fieldid;
                    return $item->data;
                }, $data);
                $customfieldfilter = "JOIN {user_info_data} uid ON uid.fieldid = :customfieldidfilter1 AND
                                                                   uid.userid = {$usertablealias}.id ".
                                     $this->get_filter_in_sql($values, 'uid.data');
                $this->params['customfieldidfilter1'] = $fieldid;
            }
        }

        if($params->custom2 == 2){
            $sql_columns = $this->get_columns($params, ["u.id"]);
            $sql_having = $this->get_filter_sql($params, $columns, false);
            $sql_order = $this->get_order_sql($params, $columns);
            $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
            $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
            $sql_filter .= $this->get_filterdate_sql($params, "ci.timecreated");

            return $this->get_report_data(
                "SELECT DISTINCT ci.id,
                        c.id AS mc_course,
                        c.fullname AS mco_name,
                        ce.name AS mc_name,
                        ci.userid AS mci_userid,
                        ce.id AS mci_certid,
                        u.firstname AS mu_firstname,
                        u.lastname AS mu_lastname,
                        u.email AS mu_email,
                        e.enrolldate AS enrol_date,
                        ci.timecreated AS issue_date
                        $sql_columns
                   FROM {local_certificate} ce
                   JOIN {local_certificate_issues} ci ON ci.certificateid = ce.id
                   JOIN {course} c ON c.id IN (ce.courses)
                   JOIN {user} u ON u.id = ci.userid
                   LEFT JOIN (SELECT en.courseid, e.userid, MAX(enrolldate) AS enrolldate FROM {local_transcripts_courses} e, {enrol} en WHERE en.id = e.enrolid GROUP BY en.courseid, e.userid ) e ON e.courseid = c.id AND e.userid = u.id
                        {$customfieldfilter}
                  WHERE ce.id > 0 $sql_filter $sql_having $sql_order", $params);

        } elseif($params->custom2 == 1){
            $certificate_table = 'customcert';
            $cert_issues_table = 'customcert_issues';
            $cert_id_field = 'customcertid';
        } else {
            $certificate_table = 'certificate';
            $cert_issues_table = 'certificate_issues';
            $cert_id_field = 'certificateid';
        }

        $columns = array_merge(
            array("mco_name", "mc_name", "mci_userid", "mci_certid", "mu_firstname", "mu_lastname",  "mu_email", "enrol_date", "issue_date"),
            $this->get_filter_columns($params, ["mu.id", "mco"])
        );
        $sql_columns = $this->get_columns($params, ["mu.id", "mco"]);
        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["mu.id" => "users", "mc.course" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "mc.course");
        $sql_filter .= $this->get_filterdate_sql($params, "mci.timecreated");

        $sql_join = $this->get_suspended_sql($params, 'mco.id', 'mu.id');

        return $this->get_report_data("
            SELECT DISTINCT mci.id,
                mc.course AS mc_course,
                mco.fullname AS mco_name,
                mc.name AS mc_name,
                mci.userid AS mci_userid,
                mci.{$cert_id_field} AS mci_certid,
                mu.firstname AS mu_firstname,
                mu.lastname AS mu_lastname,
                mu.email AS mu_email,
                ue.timestart AS enrol_date,
                mci.timecreated AS issue_date
                $sql_columns
            FROM {{$certificate_table}} mc
                LEFT JOIN {{$cert_issues_table}} AS mci ON mci.{$cert_id_field} = mc.id
                LEFT OUTER JOIN {user} AS mu ON mci.userid = mu.id
                LEFT OUTER JOIN {course} AS mco ON mc.course = mco.id
            JOIN {user_enrolments} ue ON ue.userid = mu.id
            JOIN {enrol} e ON e.id = ue.enrolid AND e.courseid = mco.id
                $sql_join
                {$customfieldfilter}
            WHERE mci.id > 0 $sql_filter $sql_having $sql_order", $params);
    }

    public function report76($params)
    {
        global $CFG, $DB;
        $columns = array_merge(array(
            "u.firstname",
            "u.lastname",
            "u.email",
            "feedback",
            "q_id",
            "question_number",
            "question",
            "answer",
            "feedback_time",
            "course_name",
            "course_shortname",
            "course_idnumber",
            "cc.timecompleted", "grade"),
            $this->get_filter_columns($params)
        );
        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "mf.course");
        $sql_filter .= $this->get_filterdate_sql($params, "mfc.timemodified");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $grade_single = intelliboard_grade_sql(false, $params);
        $sql_join = $this->get_suspended_sql($params);

        $data = $this->get_report_data("
            SELECT
                CONCAT(mfv.id, '_', u.id) as id,
                u.firstname,
                u.lastname,
                u.email,
                mfc.timemodified as feedback_time,
                cc.timecompleted,
                mfi.presentation,
                mfi.typ,
                mfi.id AS q_id,
                mfi.label AS question_number,
                mfi.name as question,
                mfv.value as answer,
                mf.name as feedback,
                mf.id as feedback_id,
                c.id as course_id,
                c.idnumber as course_idnumber,
                c.fullname as course_name,
                c.shortname as course_shortname,
                $grade_single AS grade
                $sql_columns
            FROM {feedback} AS mf
            LEFT JOIN {feedback_item} AS mfi ON mfi.feedback = mf.id
            LEFT JOIN {feedback_value} mfv ON mfv.item = mfi.id
            LEFT JOIN {feedback_completed} mfc ON mfc.id = mfv.completed
            LEFT JOIN {user} u ON mfc.userid = u.id
            LEFT JOIN {course} c ON c.id = mf.course
            LEFT JOIN {modules} m ON m.name = 'feedback'
            LEFT JOIN {course_modules} cm ON cm.module = m.id AND cm.course = c.id AND cm.instance = mf.id
            LEFT JOIN {grade_items} gi ON gi.itemtype = 'course' AND gi.courseid = c.id
            LEFT JOIN {grade_grades} g ON g.userid = u.id AND g.itemid = gi.id AND g.finalgrade IS NOT NULL
            LEFT JOIN {course_completions} cc ON cc.userid = u.id AND cc.course = c.id
            $sql_join
            WHERE mf.id > 0 $sql_filter $sql_having $sql_order", $params, false);

        if($params->custom == 1){
            require_once ($CFG->dirroot."/mod/feedback/lib.php");
            foreach($data as &$resp){
                $feedback = $DB->get_record('feedback', array('id'=>$resp->feedback_id));
                $feedbackstructure = new mod_feedback_structure($feedback, null);
                $items = $feedbackstructure->get_items();
                $itemobj = feedback_get_item_class($items[$resp->q_id]->typ);
                $resp->answer = trim($itemobj->get_printval($items[$resp->q_id], (object) ['value' => $resp->answer] ));
            }
            return array("data" => $data);
        }else{
            return array("data" => $data);
        }
    }

    public function report77($params)
    {
        $columns = array_merge(array(
            "c.fullname",
            "u.firstname",
            "u.lastname",
            "programs",
            "credits",
            "cc.timecompleted"),
            $this->get_filter_columns($params)
        );
        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filterdate_sql($params, "cc.timecompleted");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");

        $sql_having .= ($sql_having) ? " AND lesson <> '' AND programs <> '' AND credits <> ''" : "HAVING programs <> '' AND credits <> ''";

        $sql_cols = "";
        for($i = 1; $i < 25; $i++){
            $sql_cols .= " WHEN o.name = 'program_number_$i' THEN (SELECT value FROM {course_format_options} WHERE name = 'credit_hours_$i' AND courseid = c.id)";
        }
        $sql_columns .= ($sql_cols) ? ", CASE $sql_cols ELSE 'NONE' END AS credits" : ", '' AS credits";

        $sql_cols = "";
        for($i = 1; $i < 25; $i++){
            $sql_cols .= " WHEN o.name = 'program_number_$i' THEN (SELECT value FROM {course_format_options} WHERE name = 'lesson_name_$i' AND courseid = c.id)";
        }
        $sql_columns .= ($sql_cols) ? ", CASE $sql_cols ELSE 'NONE' END AS lesson" : ", '' AS lesson";

        return $this->get_report_data("
            SELECT
                CONCAT(cc.id, '-', o.id) AS id,
                u.firstname,
                u.lastname,
                u.email,
                c.fullname,
                cc.timecompleted,
                o.value AS programs
                $sql_columns
            FROM {course_completions} cc, {course} c, {user} u, {course_format_options} o
            WHERE cc.timecompleted > 0 AND u.id = cc.userid AND c.id = cc.course AND o.courseid = c.id AND o.name like '%program_%' $sql_filter $sql_having $sql_order", $params);
    }

    public function report79($params)
    {
        $columns = array_merge(array(
            "u.firstname",
            "u.lastname",
            "u.email",
            "c.fullname",
            "c.shortname",
            "category",
            "m.name",
            "activity",
            "l.visits",
            "l.timespend",
            "l.firstaccess",
            "l.lastaccess",
            "l.useragent",
            "l.useros",
            "l.userlang",
            "l.userip"),
            $this->get_filter_columns($params)
        );
        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_columns .= $this->get_modules_sql($params->custom);

        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "l.courseid");
        $sql_filter .= $this->get_filter_in_sql($params->custom, "m.id");
        $sql_filter .= $this->get_filterdate_sql($params, "l.lastaccess");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_module_sql($params, "cm.");
        $sql_vendor_filter = $this->vendor_filter('u.id', 'c.id', $params);

        $sqljoin = $this->get_suspended_sql($params);

        if($params->custom2){
            $learner_roles = $this->get_filter_in_sql($params->custom2, 'ra.roleid');
            $sqljoin .= " JOIN (SELECT ra.userid, ctx.instanceid
                          FROM {context} ctx, {role_assignments} ra WHERE ctx.contextlevel = 50 AND ra.contextid = ctx.id $learner_roles
                          GROUP BY ctx.instanceid, ra.userid) x ON x.userid = u.id AND x.instanceid = c.id";
        }

        return $this->get_report_data("
            SELECT
                l.id,
                u.firstname,
                u.lastname,
                u.email,
                c.fullname,
                c.shortname,
                ca.name AS category,
                l.param,
                l.visits,
                l.timespend,
                l.firstaccess,
                l.lastaccess,
                l.useragent,
                l.useros,
                l.userlang,
                l.userip,
                l.userid,
                l.courseid,
                m.name as module
                $sql_columns
            FROM {local_intelliboard_tracking} l
                JOIN {user} u ON u.id = l.userid
                JOIN {course} c ON c.id = l.courseid
                LEFT JOIN {course_categories} ca ON ca.id = c.category
                LEFT JOIN {course_modules} cm ON cm.id = l.param
                LEFT JOIN {modules} m ON m.id = cm.module
                {$sqljoin}
            WHERE l.page = 'module' $sql_filter {$sql_vendor_filter} $sql_having $sql_order", $params);
    }
    public function report80($params)
    {
        global $DB;

        if(!$params->custom3){
            return array("data" => null);
        }
        $columns = array_merge(array(
            "firstname",
            "lastname",
            "page",
            "fullname",
            "l.visits",
            "l.timespend",
            "firstaccess",
            "lastaccess"),
            $this->get_filter_columns($params)
        );

        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_filterdate_sql($params, "l.timepoint");

        $this->params['id'] = (int)$params->custom3;

        $item = $DB->get_record_sql("
            SELECT l.id, u.firstname, u.lastname, c.fullname, u.email, l.page, l.param, l.visits, l.timespend, l.firstaccess, l.lastaccess, 'none' as name
            FROM {local_intelliboard_tracking} l
                JOIN {user} u ON u.id = l.userid
                JOIN {course} c ON c.id = l.courseid
            WHERE l.id = :id", $this->params);

        if($item->id and $item->param){
            if($item->page == 'module'){
                $this->params['id'] = (int)$item->param;
                $cm = $DB->get_record_sql("SELECT cm.instance, m.name FROM {course_modules} cm, {modules} m WHERE cm.id = :id AND m.id = cm.module", $this->params);

                $this->params['id'] = (int)$cm->instance;
                $instance = $DB->get_record_sql("SELECT name FROM {".$cm->name."} WHERE id = :id", $this->params);
                $item->name = $instance->name;
            }
            $this->params['trackid'] = (int)$item->id;

            $data = $this->get_report_data("
                SELECT l.id, l.visits, l.timespend,
                    '' as firstaccess,
                    l.timepoint as lastaccess,
                    '' as firstname,
                    '' as lastname,
                    '' as email,
                    '' as param,
                    '' as name,
                    '' as fullname
                FROM {local_intelliboard_logs} l
                WHERE l.trackid = :trackid $sql_filter $sql_order", $params, false);
            foreach($data as $d){
                $d->firstname = $item->firstname;
                $d->lastname = $item->lastname;
                $d->fullname = $item->fullname;
                $d->name = $item->name;
                break;
            }
            return array("data" => $data);
        }
        return null;
    }
    public function report81($params)
    {
        $columns = array_merge(array(
            "u.firstname",
            "u.lastname",
            "c.fullname",
            "c.shortname",
            "module",
            "activity",
            "lit.visits",
            "lit.timespend",
            "lit.firstaccess",
            "lit.lastaccess",
            ""),
            $this->get_filter_columns($params)
        );
        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_columns .= $this->get_modules_sql('');
        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_in_sql($params->users, "ra.userid");
        $sql_filter .= $this->get_filter_in_sql($params->teacher_roles, "ra.roleid");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_module_sql($params, "cm.");
        $sql1 = $this->get_filterdate_sql($params, "lit.lastaccess");
        $sql_join = $this->get_suspended_sql($params);

        return $this->get_report_data("
            SELECT CONCAT(ra.id, '-', cm.id) as id,
             lit.id AS lid, u.firstname,u.lastname, u.email, c.fullname, c.shortname, lit.visits, lit.timespend, lit.firstaccess,lit.lastaccess, cm.instance, m.name as module $sql_columns
            FROM {role_assignments} AS ra
                JOIN {user} u ON ra.userid = u.id
                JOIN {context} AS ctx ON ctx.id = ra.contextid
                JOIN {course} c ON c.id = ctx.instanceid
                LEFT JOIN {course_modules} cm ON cm.course = c.id
                LEFT JOIN {modules} m ON m.id = cm.module
                LEFT JOIN {local_intelliboard_tracking} lit ON lit.userid = u.id AND lit.param = cm.id and lit.page = 'module' $sql1
                $sql_join
            WHERE ctx.contextlevel = 50 $sql_filter $sql_having $sql_order", $params);
    }
    public function report82($params)
    {
        $columns = array_merge(array(
            "u.firstname",
            "u.lastname",
            "c.fullname",
            "c.shortname",
            "forums",
            "discussions",
            "posts",
            "users_discussions",
            "users_posts",
            "visits",
            "timespend",
            "",
            "",
        ),$this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_in_sql($params->teacher_roles,'ra.roleid');

        $sql1 = ($params->timestart) ? $this->get_filterdate_sql($params, 'd.timemodified') : '';
        $sql2 = ($params->timestart) ? $this->get_filterdate_sql($params, 'p.created') : '';
        $sql3 = ($params->timestart) ? $this->get_filterdate_sql($params, 'l.lastaccess') : ''; //XXX
        $sql4 = ($params->timestart) ? $this->get_filterdate_sql($params, 'd1.timemodified') : '';
        $sql7 = ($params->timestart) ? $this->get_filterdate_sql($params, 'ds.timemodified') : '';
        $sql5 = ($params->timestart) ? $this->get_filterdate_sql($params, 'p1.created') : '';

        $forum_table = ($params->custom3 == 1)?'hsuforum':'forum';


        if($params->sizemode){
            $sql_columns .= ", '0' as timespend, '0' as visits";
            $sql_join = "";
        }else{
            $sql_columns .= ", MAX(l.timespend) AS timespend, MAX(l.visits) AS visits";
            $sql_join = " LEFT JOIN (SELECT l.userid, l.courseid, SUM(l.timespend) as timespend, SUM(l.visits) as visits FROM {local_intelliboard_tracking} l, {modules} m, {course_modules} cm WHERE l.page = 'module' and m.name = 'forum' AND cm.id = l.param AND cm.module = m.id $sql3 GROUP BY l.userid, l.courseid ) l ON l.userid = u.id AND l.courseid = c.id";
        }

        return $this->get_report_data(
            "SELECT MAX(ra.id) AS id,
                    u.firstname,
                    u.lastname,
                    u.email,
                    c.fullname,
                    c.shortname,
                    COUNT(distinct f.id) as forums,
                    COUNT(distinct d.id) as discussions,
                    COUNT(distinct p.id) as posts,
                    COUNT(distinct d1.id) as users_discussions,
                    COUNT(distinct p1.id) as users_posts
                    {$sql_columns}
               FROM {role_assignments} AS ra
               JOIN {user} u ON ra.userid = u.id
               JOIN {context} AS ctx ON ctx.id = ra.contextid
               JOIN {course} c ON c.id = ctx.instanceid
          LEFT JOIN {{$forum_table}} f ON f.course = c.id
          LEFT JOIN {modules} m ON m.name = 'forum'
          LEFT JOIN {course_modules} cm ON cm.instance = f.id AND cm.module = m.id
          LEFT JOIN {{$forum_table}_discussions} d ON d.course = c.id AND d.forum = f.id $sql1
          LEFT JOIN {{$forum_table}_posts} p ON p.discussion = d.id AND p.parent > 0 $sql2
          LEFT JOIN {{$forum_table}_discussions} d1 ON d1.course = c.id AND d1.forum = f.id AND d1.userid = u.id $sql4
          LEFT JOIN {{$forum_table}_posts} p1 ON p1.discussion = d.id AND p1.parent > 0 AND p1.userid = u.id $sql5
                    {$sql_join}
              WHERE ctx.contextlevel = 50 {$sql_filter}
           GROUP BY u.id, c.id
                    {$sql_having}
                    {$sql_order}",
            $params
        );
    }

    public function report83($params)
    {
        $columns = array_merge(array(
            "u.firstname",
            "u.lastname",
            "c.fullname",
            "c.shortname",
            "visits",
            "timespend",
            "last_access",
            "enrolled",
            "completed"
        ),$this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_in_sql($params->users, "ra.userid");
        $sql_filter .= $this->get_filter_in_sql($params->teacher_roles,'ra.roleid');

        $sql1 = $this->get_filter_in_sql($params->learner_roles, 'ra.roleid');
        $sql2 = '';
        $sql3 = '';

        if ($params->sizemode) {
            $sql_columns .= ", '0' AS timespend, '0' AS visits, '' AS last_access";
            $sql_join = "";
        } else {
            $sql_columns .= ", MAX(l.timespend) AS timespend, MAX(l.visits) AS visits, MAX(l.last_access) AS last_access";
            $sql_join = " LEFT JOIN (SELECT userid, courseid, SUM(timespend) AS timespend, SUM(visits) AS visits,
                                            MAX(lastaccess) AS last_access
                                       FROM {local_intelliboard_tracking}
                                   GROUP BY userid, courseid
                                    ) l ON l.userid = u.id AND l.courseid = c.id";
        }

        if ($params->custom == 3 && !$params->sizemode) {
            $sql_filter .= $this->get_filterdate_sql($params, 'l.last_access');
        }

        if ($params->custom == 2) {
            $sql3 = ($params->timestart) ? $this->get_filterdate_sql($params, 'l.timepoint') : '';

            $sql_join = " LEFT JOIN (SELECT t.userid, t.courseid, SUM(l.timespend) AS timespend, SUM(l.visits) AS visits,
                                            MAX(lastaccess) AS last_access
                                       FROM {local_intelliboard_tracking} t, {local_intelliboard_logs} l
                                      WHERE l.trackid = t.id $sql3
                                   GROUP BY t.userid, t.courseid
                                    ) l ON l.userid = u.id AND l.courseid = c.id";
        } elseif ($params->custom == 1) {
            $sql2 = ($params->timestart) ? $this->get_filterdate_sql($params, 'timecompleted') : '';
        } else {
            $sql1 .= ($params->timestart) ? $this->get_filterdate_sql($params, 'ra.timemodified') : '';
        }


        return $this->get_report_data(
            "SELECT MAX(ra.id) AS id,
                    u.firstname,
                    u.lastname,
                    u.email,
                    c.fullname,
                    c.shortname,
                    MAX(e.enrolled) AS enrolled,
                    MAX(cc.completed) AS completed
                    $sql_columns
               FROM {role_assignments} ra
               JOIN {user} u ON ra.userid = u.id
               JOIN {context} AS ctx ON ctx.id = ra.contextid
               JOIN {course} c ON c.id = ctx.instanceid
          LEFT JOIN (SELECT ctx.instanceid, COUNT(DISTINCT ra.userid) AS enrolled
                       FROM {role_assignments} ra, {context} ctx
                      WHERE ctx.id = ra.contextid AND ctx.contextlevel = 50 $sql1
                   GROUP BY ctx.instanceid
                    ) AS e ON e.instanceid = ctx.instanceid
          LEFT JOIN (SELECT course, COUNT(id) AS completed
                       FROM {course_completions}
                      WHERE timecompleted > 0 $sql2
                   GROUP BY course
                    ) cc ON cc.course = ctx.instanceid
                    $sql_join
              WHERE ctx.contextlevel = 50 $sql_filter
           GROUP BY u.id, c.id, u.firstname, u.lastname, u.email, c.fullname, c.shortname
                    $sql_having
                    $sql_order",
            $params
        );
    }

    public function report84($params)
    {
        $columns = array_merge(array(
            "u.firstname",
            "u.lastname",
            "c.fullname",
            "c.shortname",
            "assignments",
            "completed",
            "submissions",
            "grades",
            "visits",
            "timespend"
        ),$this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_in_sql($params->teacher_roles,'ra.roleid');
        $sql_filter .= $this->get_filter_in_sql($params->users,'ra.userid');
        $completion = $this->get_completion($params, "cmc.");

        $sql1 = ($params->timestart) ? $this->get_filterdate_sql($params, 'cmc.timemodified') : '';
        $sql2 = ($params->timestart) ? $this->get_filterdate_sql($params, 's.timemodified') : '';
        $sql3 = ($params->timestart) ? $this->get_filterdate_sql($params, 'g.timemodified') : '';
        $sql4 = ($params->timestart) ? $this->get_filterdate_sql($params, 'l.lastaccess') : ''; //XXX
        $sql4 .= $this->get_filter_in_sql($params->courseid, "l.courseid");
        $sql5 = $this->get_filter_in_sql($params->courseid, "a.course");

        if($params->sizemode){
            $sql_columns .= ", '0' as timespend, '0' as visits";
            $sql_join = "";
        }else{
            $sql_columns .= ", l.timespend, visits";
            $sql_join = " LEFT JOIN (SELECT l.userid, l.courseid, SUM(l.timespend) as timespend, SUM(l.visits) as visits FROM {local_intelliboard_tracking} l, {modules} m, {course_modules} cm WHERE l.page = 'module' and m.name = 'assign' AND cm.id = l.param AND cm.module = m.id $sql4 GROUP BY l.userid, l.courseid ) l ON l.userid = u.id AND l.courseid = c.id";
        }

        return $this->get_report_data("
            SELECT ra.id AS id,
                   u.firstname,
                   u.lastname,
                   u.email,
                   c.fullname,
                   c.shortname,
                   stat.assignments,
                   stat.completed,
                   stat.submissions,
                   stat.grades
                   $sql_columns
            FROM {role_assignments} AS ra
                JOIN {user} u ON ra.userid = u.id
                JOIN {context} AS ctx ON ctx.id = ra.contextid
                JOIN {course} c ON c.id = ctx.instanceid
                LEFT JOIN (SELECT
                              a.course AS courseid,
                              COUNT(distinct a.id) AS assignments,
                              COUNT(distinct cmc.coursemoduleid) AS completed,
                              COUNT(distinct s.assignment) AS submissions,
                              COUNT(distinct g.assignment) AS grades
                            FROM {assign} a
                                 LEFT JOIN {modules} m ON m.name = 'assign'
                                 LEFT JOIN {course_modules} cm ON cm.instance = a.id AND cm.module = m.id
                                 LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id $completion $sql1
                                 LEFT JOIN {assign_submission} s ON s.status = 'submitted' AND s.assignment = a.id $sql2
                                 LEFT JOIN {assign_grades} g ON g.assignment = a.id $sql3
                            WHERE a.course >0 $sql5
                            GROUP BY a.course) stat ON stat.courseid=c.id
                $sql_join
            WHERE ctx.contextlevel = 50 $sql_filter $sql_having $sql_order", $params);
    }

    public function report85($params)
    {
        $columns = array_merge(array(
            "u.firstname",
            "u.lastname",
            "registered",
            "loggedin",
            "loggedout"),
            $this->get_filter_columns($params)
        );
        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users"]);
        $sql_filter .= $this->get_filterdate_sql($params, "l1.timecreated");
        $sql_filter .= $this->get_filter_in_sql($params->users,'u.id');
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_join = "";
        if($params->custom2){
            $sql = $this->get_filter_in_sql($params->custom2, 'roleid');
            $sql_filter .= " AND l1.userid IN (SELECT DISTINCT userid FROM {role_assignments} WHERE userid > 0 $sql)";
        }

        return $this->get_report_data("
            SELECT l1.id,
               u.firstname,
               u.lastname,
               u.timecreated AS registered,
               l1.userid,
               l1.timecreated AS loggedin,
               (SELECT l2.timecreated FROM {logstore_standard_log} l2 WHERE l2.userid = l1.userid and l2.action = 'loggedout' and l2.id > l1.id LIMIT 1) AS loggedout
               $sql_columns
            FROM {logstore_standard_log} l1
                JOIN {user} u ON u.id = l1.userid
            WHERE l1.action = 'loggedin' $sql_filter $sql_having $sql_order", $params);
    }

    public function report87($params)
    {
        $columns = array_merge(array("fieldname", "users"), $this->get_filter_columns($params));

        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["userid" => "users"]);
        $sql_filter .= $this->get_filter_in_sql($params->custom, "fieldid");

        if(!$params->custom){
            return array();
        }

        $data = $this->get_report_data("
            SELECT id, data as fieldname, COUNT(*) as users
            FROM {user_info_data}
            WHERE data <> '' $sql_filter
            GROUP BY data $sql_having $sql_order", $params, false);

        return array("data" => $data, 'custom'=> $params->custom);
    }

    public function report88($params)
    {
        global $DB;

        $sql_select = array();
        $sql_filter1 = $this->get_filterdate_sql($params, "g.timecreated");
        $sql_filter2 = $this->get_filterdate_sql($params, "g.timecreated");
        $sql_filter3 = $this->get_filterdate_sql($params, "g.timecreated");
        $sql_filter4 = $this->get_filterdate_sql($params, "g.timecreated");
        $sql_filter5 = $this->get_filterdate_sql($params, "g.timecreated");
        $sql_filter6 = "";
        $sql_filter7 = "";
        if($params->courseid){
            $sql_filter1 .= $this->get_filter_in_sql($params->courseid, "gi.courseid");
            $sql_filter2 .= $this->get_filter_in_sql($params->courseid, "gi.courseid");
            $sql_filter3 .= $this->get_filter_in_sql($params->courseid, "gi.courseid");
            $sql_filter4 .= $this->get_filter_in_sql($params->courseid, "gi.courseid");
            $sql_filter5 .= $this->get_filter_in_sql($params->courseid, "gi.courseid");
            $this->params['csid'] = intval($params->courseid);
            $sql_select[] = "(SELECT fullname FROM {course} WHERE id = :csid) as course";
        }else{
            return array();
        }
        if($params->users){
            $sql_filter1 .= $this->get_filter_in_sql($params->users, "g.userid");
            $sql_filter2 .= $this->get_filter_in_sql($params->users, "g.userid");
            $sql_filter3 .= $this->get_filter_in_sql($params->users, "g.userid");
            $sql_filter4 .= $this->get_filter_in_sql($params->users, "g.userid");
            $sql_filter5 .= $this->get_filter_in_sql($params->users, "g.userid");
            $sql_filter6 .= $this->get_filter_in_sql($params->users, "userid");
            $sql_filter7 .= $this->get_filter_in_sql($params->users, "cmc.userid");
            $sql_filter8 = $this->get_filter_in_sql($params->users, "id");
            $sql_select[] = "(SELECT CONCAT(firstname,' ',lastname) FROM {user} WHERE id > 0 $sql_filter8) as user";
        }
        $sql_select = ($sql_select) ? ", " . implode(",", $sql_select) : "";
        $completion = $this->get_completion($params, "cmc.");
        $this->params['cx1'] = intval($params->courseid);
        $this->params['cx2'] = intval($params->courseid);
        $this->params['cx3'] = intval($params->courseid);
        $this->params['cx4'] = intval($params->courseid);

        $data = $DB->get_record_sql("SELECT
            (SELECT COUNT(g.finalgrade) FROM {grade_items} gi, {grade_grades} g WHERE
            g.itemid = gi.id AND gi.itemtype = 'mod' AND g.finalgrade IS NOT NULL
            $sql_filter1 AND ((g.finalgrade/g.rawgrademax)*100 ) < 60) AS grade_f,

            (SELECT COUNT(g.finalgrade) FROM {grade_items} gi, {grade_grades} g WHERE
            g.itemid = gi.id AND gi.itemtype = 'mod' AND g.finalgrade IS NOT NULL
            $sql_filter2 AND ((g.finalgrade/g.rawgrademax)*100 ) > 60 and ((g.finalgrade/g.rawgrademax)*100 ) < 70) AS grade_d,

            (SELECT COUNT(g.finalgrade) FROM {grade_items} gi, {grade_grades} g WHERE
            g.itemid = gi.id AND gi.itemtype = 'mod' AND g.finalgrade IS NOT NULL
            $sql_filter3 AND ((g.finalgrade/g.rawgrademax)*100 ) > 70 and ((g.finalgrade/g.rawgrademax)*100 ) < 80) AS grade_c,


            (SELECT COUNT(g.finalgrade) FROM {grade_items} gi, {grade_grades} g WHERE
            g.itemid = gi.id AND gi.itemtype = 'mod' AND g.finalgrade IS NOT NULL
            $sql_filter4 AND ((g.finalgrade/g.rawgrademax)*100 ) > 80 and ((g.finalgrade/g.rawgrademax)*100 ) < 90) AS grade_b,

            (SELECT COUNT(g.finalgrade) FROM {grade_items} gi, {grade_grades} g WHERE
            g.itemid = gi.id AND gi.itemtype = 'mod' AND g.finalgrade IS NOT NULL
            $sql_filter5 AND ((g.finalgrade/g.rawgrademax)*100 ) > 90) AS grade_a,

            (SELECT COUNT(DISTINCT param) FROM {local_intelliboard_tracking} WHERE page = 'module' AND courseid = :cx1 $sql_filter6) as  modules_visited,

            (SELECT count(id) FROM {course_modules} WHERE visible = 1 AND course = :cx2) as modules_all,

            (SELECT count(id) FROM {course_modules} WHERE visible = 1 and completion > 0 AND course = :cx3) as modules,

            (SELECT count(cmc.id) FROM {course_modules} cm, {course_modules_completion} cmc WHERE cmc.coursemoduleid = cm.id AND cm.visible = 1
            $completion AND cm.course = :cx4 $sql_filter7) as modules_completed
            $sql_select
        ", $this->params);

        return array("data" => $data, "timestart"=>$params->timestart, "timefinish"=>$params->timefinish);
    }

    public function report89($params)
    {
        $columns = array_merge(array(
            "emploee_id",
            "emploee_name",
            "manager_name",
            "tr.form_origin",
            "tr.complited_date",
            "tr.education",
            "position",
            "job_title",
            "tr.review_year",
            "overal_rating",
            "overal_perfomance_rating",
            "behaviors_rating",
            "tr.promotability_hp1",
            "mobility",
            "tr.behaviors_growth",
            "tr.behaviors_accountability",
            "tr.behaviors_champions",
            "tr.behaviors_self_aware",
            "tr.behaviors_initiative",
            "tr.behaviors_judgment",
            "tr.behaviors_makes_people",
            "tr.behaviors_leadership",
            "tr.behaviors_effective_com",
            "tr.behaviors_gets_result",
            "tr.behaviors_integrative",
            "tr.behaviors_intelligent",
            "tr.name",
            "tr.jde_eeid"),
            $this->get_filter_columns($params)
        );
        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users"]);
        $sql_filter .= $this->get_filter_user_sql($params, "u.");

        return $this->get_report_data("SELECT tr.id,
                tr.user_id as emploee_id,
                CONCAT(u.firstname, ' ', u.lastname) as emploee_name,
                tr.manager as manager_name,
                tr.form_origin,
                tr.education,
                tr.name AS form_name,
                tr.jde_eeid AS form_jde,
                ps.fullname AS position,
                tr.title as job_title,
                tr.review_year,
                tr.overal_review_rating as overal_rating,
                tr.goals_perfomance_overal as overal_perfomance_rating,
                tr.behaviors_overal as behaviors_rating,
                tr.behaviors_growth,
                tr.behaviors_accountability,
                tr.behaviors_champions,
                tr.behaviors_self_aware,
                tr.behaviors_initiative,
                tr.behaviors_judgment,
                tr.behaviors_makes_people,
                tr.behaviors_leadership,
                tr.behaviors_effective_com,
                tr.behaviors_gets_result,
                tr.behaviors_integrative,
                tr.behaviors_intelligent,
                tr.complited_date,
                tr.promotability_hp1,
                tr.promotability_hp2,
                tr.promotability_trusted,
                tr.promotability_placement,
                tr.promotability_too_new,
                relocatability as mobility
                $sql_columns
            FROM {local_talentreview} tr
                LEFT JOIN {user} u ON u.id = tr.user_id
                LEFT JOIN {pos_assignment} pa ON pa.userid = u.id
                LEFT JOIN {pos} ps ON ps.id = pa.positionid
            WHERE tr.id > 0 $sql_filter $sql_having $sql_order", $params);
    }
    public function report90($params)
    {
        $columns = array_merge(array(
            'outcome_shortname',
            'outcome_fullname',
            'outcome_description',
            'activity',
            'sci.scale',
            'average_grade',
            'grades',
            'course_fullname',
            'course_shortname',
            'category',
            'c.startdate'
        ), $this->get_filter_columns($params, [null]));

        $sql_columns = $this->get_columns($params, [null]);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["g.userid" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->custom2,'o.id');
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $grade_avg = intelliboard_grade_sql(true, $params);

        $data = $this->get_report_data("
            SELECT gi.id,
                gi.itemname as activity,
                c.shortname as course_shortname,
                c.fullname as course_fullname,
                o.fullname as outcome_fullname,
                o.shortname as outcome_shortname,
                o.description as outcome_description,
                sci.scale,
                ca.name as category,
                c.startdate,
                $grade_avg AS average_grade,
                COUNT(DISTINCT g.id) AS grades
                {$sql_columns}
            FROM {grade_outcomes} o
                LEFT JOIN {course} c ON c.id = o.courseid
                LEFT JOIN {course_categories} ca ON ca.id = c.category
                LEFT JOIN {scale} sci ON sci.id = o.scaleid
                LEFT JOIN {grade_items} gi ON gi.outcomeid = o.id
                LEFT JOIN {grade_grades} g ON g.itemid = gi.id
            WHERE gi.itemtype = 'mod' $sql_filter
            GROUP BY gi.id,
                g.itemid,
                gi.itemname,
                c.shortname,
                c.fullname,
                o.fullname,
                o.shortname,
                o.description,
                sci.scale,
                ca.name,
                c.startdate $sql_having $sql_order", $params, false);

        foreach($data as $k=>$v){
            $scale = explode(',', $v->scale);
            $percent = $v->average_grade / count($scale);
            $iter = 1 / count($scale);
            $index = round( ($percent / $iter), 0, PHP_ROUND_HALF_DOWN)-1;
            $data[$k]->scale = (isset($scale[$index]))?$scale[$index]:'';
        }

        return array("data"=>$data);
    }


    public function report91($params)
    {
        $columns = array_merge(array(
            "c.fullname",
            "cs.section",
            "activity",
            "completed"),
            $this->get_filter_columns($params)
        );
        $sql_columns =  $this->get_modules_sql('');
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid,'c.id');
        $sql_filter .= $this->get_filter_module_sql($params, "cm.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql1 = $this->get_filterdate_sql($params, "cmc.timemodified");
        $completion = $this->get_completion($params, "cmc.");

        $data = $this->get_report_data("
            SELECT cm.id,
                cm.visible as module_visible,
                cs.section,
                cs.name,
                cs.visible,
                c.fullname,
                COUNT(DISTINCT cmc.id) as completed
                $sql_columns
            FROM {course} c
                LEFT JOIN {course_modules} cm ON cm.course = c.id
                LEFT JOIN {modules} m ON m.id = cm.module
                LEFT JOIN {course_sections} cs ON cs.id = cm.section AND cs.course = cm.course
                LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id $completion $sql1
            WHERE cm.id > 0 $sql_filter
            GROUP BY cm.id, cs.id, c.id, m.id $sql_having $sql_order", $params, false);

        return array("data" => $data, "timestart"=>$params->timestart, "timefinish"=>$params->timefinish);
    }
    public function report92($params)
    {
        $columns = array_merge(array(
            "u.firstname",
            "u.lastname",
            "u.email",
            "u.timecreated",
            "u.firstaccess",
            "u.lastaccess",
            "u.lastlogin"),
            $this->get_filter_columns($params)
        );
        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users"]);
        $sql_filter .= $this->get_filterdate_sql($params, "u.timecreated");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->vendor_filter('u.id', null, $params);

        if($params->custom2 == 6){
            $this->params['lsta'] = strtotime("-90 days");
        }elseif($params->custom2 == 5){
            $this->params['lsta'] = strtotime("-30 days");
        }elseif($params->custom2 == 4){
            $this->params['lsta'] = strtotime("-17 days");
        }elseif($params->custom2 == 3){
            $this->params['lsta'] = strtotime("-5 days");
        }elseif($params->custom2 == 2){
            $this->params['lsta'] = strtotime("-5 days");
        }elseif($params->custom2 == 1){
            $this->params['lsta'] = strtotime("-3 days");
        }else{
            $this->params['lsta'] = strtotime("-1 days");
        }
        $sql_filter .= " AND u.lastaccess < :lsta";
        $sql_join = "";

        if($params->custom){
            $sql_filter .= $this->get_filter_in_sql($params->custom, 'ra.roleid');
            $sql_join = "LEFT JOIN {role_assignments} ra ON ra.userid = u.id ";
        }

        return $this->get_report_data("
            SELECT DISTINCT u.id,
                u.firstname,
                u.lastname,
                u.email,
                u.timecreated,
                u.firstaccess,
                u.lastaccess,
                u.lastlogin
                $sql_columns
            FROM {user} u
                $sql_join
            WHERE u.id > 0 $sql_filter $sql_having $sql_order", $params);
    }

    public function report93($params)
    {
        $columns = array_merge(array(
            "u.firstname",
            "u.lastname",
            "u.email",
            "c.fullname",
            "enrolled",
            "progress",
            "grade",
            "modules_completed",
            "cc.timecompleted"),
            $this->get_filter_columns($params)
        );
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid,'c.id');
        $sql_filter .= $this->get_filterdate_sql($params, "ue.timecreated");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "ue.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "e.");
        $completion = $this->get_completion($params, "x.");
        $grade_avg = intelliboard_grade_sql(true, $params);

        return $this->get_report_data("
            SELECT max(ue.id) AS id,
                u.firstname,
                u.lastname,
                u.email,
                MAX(ue.timecreated) as enrolled,
                c.id AS courseid,
                u.id AS userid,
                c.fullname,
                $grade_avg AS grade,
                MAX(m.modules) AS modules,
                MAX(cc.timecompleted) AS timecompleted,
                MAX(cmc.completed) as modules_completed,
                round(((MAX(cmc.completed)/MAX(m.modules))*100), 0) as progress
                $sql_columns
            FROM {user_enrolments} ue
                JOIN {user} u ON u.id = ue.userid
                JOIN {enrol} e ON e.id = ue.enrolid
                JOIN {course} c ON c.id = e.courseid
           LEFT JOIN {grade_items} gi ON gi.courseid=c.id AND gi.itemtype = 'course'
           LEFT JOIN {grade_grades} g ON gi.id=g.itemid AND g.userid=u.id
                LEFT JOIN {course_completions} cc ON cc.timecompleted > 0 AND cc.course = e.courseid and cc.userid = ue.userid
                LEFT JOIN (SELECT course, count(id) as modules FROM {course_modules} WHERE visible = 1 AND completion > 0 GROUP BY course) as m ON m.course = c.id
                LEFT JOIN (SELECT cm.course, x.userid, COUNT(DISTINCT x.id) as completed FROM {course_modules} cm, {course_modules_completion} x WHERE x.coursemoduleid = cm.id AND cm.visible = 1 $completion GROUP BY cm.course, x.userid) as cmc ON cmc.course = c.id AND cmc.userid = ue.userid
            WHERE ue.id > 0 $sql_filter
            GROUP BY u.id, c.id $sql_having $sql_order", $params);
    }
    public function report94($params)
    {
        $columns = array_merge(array("u.id", "u.firstname", "u.lastname", "u.email", "submitted", "attempted","u.phone1", "u.phone2", "u.institution", "u.department", "u.address", "u.city", "u.country"), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid,'c.id');
        $sql_filter .= $this->get_filterdate_sql($params, "qa.timemodified");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");

        return $this->get_report_data("
            SELECT u.id,
               u.firstname,
               u.lastname,
               u.email,
               u.phone1,
               u.phone2,
               u.institution,
               u.department,
               u.address,
               u.city,
               u.country,
               COUNT(DISTINCT(qa.quiz)) as submitted,
               COUNT(DISTINCT(qa.id)) as attempted
               $sql_columns
            FROM {quiz_attempts} qa, {user} u, {quiz} q, {course} c
            WHERE qa.quiz = q.id AND c.id = q.course AND qa.userid = u.id $sql_filter
            GROUP BY u.id $sql_having $sql_order", $params);
    }
    public function report95($params)
    {
        $columns = array_merge(array(
            "c.id", "c.fullname", "submitted", "attempted"
        ), $this->get_filter_columns($params, [null]));

        $sql_columns = $this->get_columns($params, [null]);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["c.id" => "courses", "qa.userid" => "users"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid,'c.id');
        $sql_filter .= $this->get_filterdate_sql($params, "qa.timemodified");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");

        return $this->get_report_data("
            SELECT c.id,
               c.fullname,
               COUNT(DISTINCT(qa.quiz)) as submitted,
               COUNT(DISTINCT(qa.id)) as attempted
               {$sql_columns}
            FROM {quiz_attempts} qa, {quiz} q, {course} c
            WHERE qa.quiz = q.id AND c.id = q.course $sql_filter
            GROUP BY c.id $sql_having $sql_order", $params);
    }
    public function report96($params)
    {
        $columns = array_merge(array( "co.name", "submitted", "attempted"), $this->get_filter_columns($params));

        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["c.id" => "courses", "qa.userid" => "users", "co.id" => "cohorts"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid,'c.id');
        $sql_filter .= $this->get_filter_in_sql($params->cohortid,'co.id');
        $sql_filter .= $this->get_filterdate_sql($params, "qa.timemodified");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");

        return $this->get_report_data("
            SELECT co.id,
               co.name,
               COUNT(DISTINCT(qa.quiz)) as submitted,
               COUNT(DISTINCT(qa.id)) as attempted
            FROM {quiz_attempts} qa, {quiz} q, {course} c, {cohort} co, {cohort_members} cm
            WHERE qa.quiz = q.id AND c.id = q.course AND cm.userid = qa.userid AND co.id = cm.cohortid $sql_filter
            GROUP BY co.id $sql_having $sql_order", $params);
    }
    public function report97($params)
    {
        global $CFG;
        $columns = array_merge(array("c.id", "c.fullname", "c.shortname","category", "c.startdate", "ue.enrolled", "x.users", "inactiveusers", "cc.completed", "x.lastaccess", "timespend", "visits","teacher"), $this->get_filter_columns($params, [null]));

        $sql_columns = $this->get_columns($params, [null]);
        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["c.id" => "courses"]);
        $sql_vendor_filter = $this->vendor_filter('ue.userid', 'e.courseid', $params);
        $sql_vendor_filter1 = $this->vendor_filter('t.userid', 't.courseid', $params);
        $sql_vendor_filter2 = $this->vendor_filter('userid', 'course', $params);
        $sql_filter .= $this->get_filter_in_sql($params->courseid,'c.id');
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->vendor_filter(null, 'c.id', $params);
        $sql_enrolfilter = $this->get_filter_enrol_sql($params, "ue.");
        $sql_enrolfilter .= $this->get_filter_enrol_sql($params, "e.");
        $sql_timefilter = $this->get_filterdate_sql($params, "l.timepoint");

        $sql_teacher_roles = $this->get_filter_in_sql($params->teacher_roles, "ra.roleid");
        if ($CFG->dbtype == 'pgsql') {
            $group_concat = "string_agg( DISTINCT CONCAT(u.firstname,' ',u.lastname), ', ')";
        } else {
            $group_concat = "GROUP_CONCAT(DISTINCT CONCAT(u.firstname,' ',u.lastname) SEPARATOR ', ')";
        }

        return $this->get_report_data("
            SELECT c.id,
                   c.fullname,
                   c.shortname,
                   ca.name AS category,
                   c.startdate,
                   x.users,
                   (ue.enrolled - x.users) AS inactiveusers,
                   ue.enrolled,
                   cc.completed,
                   x.timespend,
                   x.visits,
                   x.lastaccess,
                   (SELECT $group_concat
                      FROM {role_assignments} AS ra
                        JOIN {user} u ON ra.userid = u.id
                        JOIN {context} AS ctx ON ctx.id = ra.contextid
                      WHERE ctx.instanceid = c.id AND ctx.contextlevel = 50 $sql_teacher_roles
                   ) AS teacher
                   {$sql_columns}
            FROM {course} c
            LEFT JOIN {course_categories} ca ON ca.id = c.category
            LEFT JOIN (SELECT course, COUNT(*) AS completed
                         FROM {course_completions}
                        WHERE timecompleted > 0 {$sql_vendor_filter2}
                     GROUP BY course
                      ) cc ON cc.course = c.id
            LEFT JOIN (SELECT e.courseid, COUNT(DISTINCT ue.userid) AS enrolled
                FROM {user_enrolments} ue, {enrol} e
                WHERE ue.enrolid = e.id {$sql_enrolfilter} {$sql_vendor_filter}
                GROUP BY e.courseid) ue ON ue.courseid = c.id
            LEFT JOIN (SELECT t.courseid, COUNT(DISTINCT t.userid) as users, MAX(lastaccess) AS lastaccess, SUM(l.timespend) AS timespend, SUM(l.visits) AS visits
                FROM  {local_intelliboard_tracking} t, {local_intelliboard_logs} l
                WHERE l.trackid = t.id $sql_timefilter {$sql_vendor_filter1}
                GROUP BY t.courseid) x ON x.courseid = c.id
            WHERE c.id > 0 $sql_filter $sql_having $sql_order ", $params);
    }

    public function report98($params)
    {
        $columns = array_merge(array("u.id", "u.firstname", "u.lastname", "u.email", "c.fullname", "c.shortname","category", "visits", "timespend", "timecompleted", "timecompleted"), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid,'c.id');
        $sql_filter .= $this->get_filterdate_sql($params, "l.timepoint");
        $sql_vendor_filter = $this->vendor_filter('u.id', 'c.id', $params);

        $sql_join = $this->get_suspended_sql($params);

        return $this->get_report_data("
            SELECT max(t.id) as tid, u.id AS id,
               u.firstname,
               u.lastname,
               u.email,
               c.fullname,
               c.shortname,
               ca.name AS category,
               MAX(cc.timecompleted) AS timecompleted,
               SUM(l.timespend) AS timespend,
               SUM(l.visits) AS visits
               $sql_columns
            FROM {local_intelliboard_logs} l
              JOIN {local_intelliboard_tracking} t ON t.id = l.trackid
              JOIN {user} u ON u.id = t.userid
              JOIN {course} c ON c.id = t.courseid
              JOIN {course_categories} ca ON ca.id = c.category
              LEFT JOIN {course_completions} cc ON cc.userid = u.id AND cc.course = c.id
              $sql_join
            WHERE l.id > 0 $sql_filter {$sql_vendor_filter} GROUP BY u.id, c.id, ca.id $sql_having $sql_order", $params);
    }

    public function report78($params)
    {
        $columns = array_merge(array(
            "u.id",
            "u.firstname",
            "u.lastname",
            "u.middlename",
            "u.email",
            "u.idnumber",
            "u.username",
            "u.phone1",
            "u.phone2",
            "u.institution",
            "u.department",
            "u.address",
            "u.city",
            ["sql_column" => "u.country", "type" => "country"],
            "u.auth",
            "u.confirmed",
            "u.suspended",
            "u.deleted",
            "u.timecreated",
            "u.timemodified",
            "u.firstaccess",
            "u.lastaccess",
            "u.lastlogin",
            "u.currentlogin",
            "u.lastip"), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users"]);
        $sql_filter .= $this->get_filterdate_sql($params, "u.timecreated");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");

        return $this->get_report_data("
            SELECT u.id,
                u.firstname,
                u.lastname,
                u.middlename,
                u.email,
                u.idnumber,
                u.username,
                u.phone1,
                u.phone2,
                u.institution,
                u.department,
                u.address,
                u.city,
                u.country,
                u.auth,
                u.confirmed,
                u.suspended,
                u.deleted,
                u.timecreated,
                u.timemodified,
                u.firstaccess,
                u.lastaccess,
                u.lastlogin,
                u.currentlogin,
                u.lastip $sql_columns
            FROM {user} u WHERE u.id > 0 $sql_filter $sql_having $sql_order", $params);
    }

    public function report74($params){

        $date = explode("-", $params->custom);
        $year = (int) $date[2];
        $start = (int) $date[0];
        $end = (int) $date[1];

        if(!$year or !$start or !$end or !$params->custom2){
            return array();
        }
        $position = ($params->custom2)?$params->custom2:4;

        $sql_select = "";
        $sql_join = "";
        if($start < $end){
            while($start <= $end){
                $this->params['startdate'.$start] = strtotime("$start/1/$year");
                $this->params['enddate'.$start] = strtotime("$start/1/$year +1 month");
                $this->params['position'.$start] = $position;
                $sql_select .= ", k$start.users as month_$start";
                $sql_join .= "LEFT JOIN (SELECT p.organisationid, COUNT(distinct u.id) as users FROM {user} u, {pos_assignment} p, {pos} ps WHERE ps.id = :position$start AND ps.visible = 1 AND p.positionid = ps.id AND p.userid = u.id AND u.timecreated BETWEEN :startdate$start AND :enddate$start GROUP BY p.organisationid) k$start ON  k$start.organisationid = o.id ";
                $start++;
            }
        }

        $data = $this->get_report_data("
                SELECT  o.id,
                        o.fullname as organization,
                        o.typeid,
                        t.fullname as type,
                        s.svp,
                        k0.total
                        $sql_select
                FROM {org} o
                    LEFT JOIN {org_type} t ON t.id = o.typeid
                    LEFT JOIN (SELECT o2.organisationid, o1.typeid, GROUP_CONCAT( DISTINCT o2.data) AS svp FROM {org_type_info_field} o1, {org_type_info_data} o2 WHERE o1.id = o2.fieldid AND o1.shortname LIKE '%svp%' GROUP BY o2.organisationid, o1.typeid) s ON s.organisationid = o.id AND s.typeid = t.id

                    LEFT JOIN (SELECT f.typeid, d.organisationid, d.data as total FROM {org_type_info_field} f, {org_type_info_data} d WHERE f.shortname = 'techtotal' AND d.fieldid = f.id GROUP BY f.typeid, d.organisationid) k0 ON k0.organisationid = o.id AND k0.typeid = t.id

                $sql_join
                WHERE o.visible = 1 ORDER BY o.typeid, o.fullname",$params,false);

        return array(
            "recordsTotal"    => count($data),
            "recordsFiltered" => count($data),
            "data"            => $data);

    }

    public function report71($params){
        global $CFG;
        $columns = array_merge(array(
            "u.firstname", "u.lastname","u.email", "ue.timecreated", "e.enrol", "e.cost", "e.currency", "c.fullname"
        ), $this->get_filter_columns($params));
        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid,'c.id');
        $sql_filter .= $this->get_filterdate_sql($params, "ue.timecreated");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "ue.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "e.");

        $data = $this->get_report_data("
            SELECT ue.id,
                c.fullname,
                ue.timecreated,
                u.firstname,
                u.lastname,
                u.email,
                e.enrol,
                e.cost,
                e.currency
                $sql_columns
            FROM
                {user_enrolments} ue,
                {enrol} e,
                {user} u,
                {course} c
            WHERE e.courseid = c.id AND e.cost IS NOT NULL AND ue.enrolid = e.id AND u.id = ue.userid $sql_filter $sql_having $sql_order", $params, false);

        $func = ($CFG->dbtype == 'pgsql') ? '::int':'';
        $data2 = $this->get_report_data("
            SELECT floor(ue.timecreated / 86400) * 86400 as timepoint, SUM(e.cost{$func}) as amount
            FROM {user_enrolments} ue, {enrol} e,{course} c,{user} u
            WHERE e.courseid = c.id AND e.cost IS NOT NULL AND ue.enrolid = e.id AND u.id = ue.userid $sql_filter
            GROUP BY timepoint
            ORDER BY timepoint ASC", $params, false);

        return array("data2" => $data2, "data" => $data);
    }
    public function report70($params){
        global $DB;

        $columns = array_merge(array(
            "c.fullname", "forum", "d.name", "posts", "fp.student_posts", "ratio", "d.timemodified", "user", ""
        ), $this->get_filter_columns($params, [null]));

        $sql_columns = $this->get_columns($params, [null]);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["c.id" => "courses"]);
        $sql_filter .= $this->get_filterdate_sql($params,'d.timemodified');
        $sql1 = $this->get_filterdate_sql($params,'d.timemodified');

        $params->custom = clean_param($params->custom, PARAM_SEQUENCE);
        $params->custom2 = clean_param($params->custom2, PARAM_SEQUENCE);
        $params->custom3 = clean_param($params->custom3, PARAM_INT);

        $forum_table = ($params->custom3 == 1)?'hsuforum':'forum';

        $sql_filter .= $this->get_filter_in_sql($params->courseid,'c.id');
        $sql_filter .= $this->get_filter_in_sql($params->custom,'d.forum');

        if(isset($params->custom2) and $params->custom2){
            $roles = $this->get_filter_in_sql($params->custom2,'ra.roleid');
        }else{
            $roles = $this->get_filter_in_sql($params->teacher_roles,'ra.roleid');
        }


        $learner_roles = $this->get_filter_in_sql($params->learner_roles,'ra.roleid');

        $data2 = $DB->get_records_sql("
                  SELECT  floor(p.created / 86400) * 86400 as timepoint,
                          count(distinct p.id) as posts
                  FROM {role_assignments} ra
                    LEFT JOIN {context} ctx ON ctx.id = ra.contextid
                    LEFT JOIN {course} c ON c.id = ctx.instanceid
                    LEFT JOIN {".$forum_table."_discussions} d ON d.course = c.id
                    LEFT JOIN {".$forum_table."_posts} p ON p.userid = ra.userid AND p.discussion =d.id
                  WHERE ctx.contextlevel = 50  AND floor(p.created / 86400) > 0 $sql_filter $roles
                  GROUP BY timepoint
                  ORDER BY timepoint ASC", $this->params);

        $data3 = $DB->get_records_sql("
                  SELECT  floor(p.created / 86400) * 86400 as timepoint,
                          count(distinct p.id) as student_posts
                  FROM {role_assignments} ra
                    LEFT JOIN {context} ctx ON ctx.id = ra.contextid
                    LEFT JOIN {course} c ON c.id = ctx.instanceid
                    LEFT JOIN {".$forum_table."_discussions} d ON d.course = c.id
                    LEFT JOIN {".$forum_table."_posts} p ON p.userid = ra.userid AND p.discussion = d.id
                  WHERE ctx.contextlevel = 50  AND floor(p.created / 86400) > 0 $sql_filter $learner_roles
                  GROUP BY timepoint
                  ORDER BY timepoint ASC", $this->params);

        $data4 = $DB->get_record_sql("
                  SELECT  count(distinct p.id) as posts
                  FROM {role_assignments} AS ra
                    LEFT JOIN {context} AS ctx ON ctx.id = ra.contextid
                    LEFT JOIN {course} c ON c.id = ctx.instanceid
                    LEFT JOIN {".$forum_table."_discussions} d ON d.course = c.id
                    LEFT JOIN {".$forum_table."_posts} p ON p.userid = ra.userid AND p.discussion = d.id
                  WHERE ctx.contextlevel = 50 $sql_filter $roles", $this->params);

        $data5 = $DB->get_record_sql("
                  SELECT count(distinct p.id) as posts
                  FROM {role_assignments} AS ra
                    LEFT JOIN {context} AS ctx ON ctx.id = ra.contextid
                    LEFT JOIN {course} c ON c.id = ctx.instanceid
                    LEFT JOIN {".$forum_table."_discussions} d ON d.course = c.id
                    LEFT JOIN {".$forum_table."_posts} p ON p.userid = ra.userid AND p.discussion = d.id
                  WHERE ctx.contextlevel = 50 $sql_filter $learner_roles", $this->params);

        $f1 = intval($data4->posts);
        $f2 = intval($data5->posts);
        $f3 = ($f2<1)?0:$f1 / $f2;
        $f3 = number_format($f3,2);

        $data6 = array($f1, $f2, $f3);


        $data = $this->get_report_data("
                SELECT CONCAT(d.id, '-', u.id) as id,
                    c.fullname,
                    d.name,
                    f.name as forum,
                    CONCAT(u.firstname, ' ', u.lastname) as user,
                    count(distinct p.id) as posts, d.timemodified,
                    MAX(fp.student_posts) AS student_posts, round((count(distinct p.id) / MAX(fp.student_posts) ), 2) as ratio
                    {$sql_columns}
                FROM {role_assignments} AS ra
                LEFT JOIN {user} u ON u.id = ra.userid
                LEFT JOIN {context} AS ctx ON ctx.id = ra.contextid
                LEFT JOIN {course} c ON c.id = ctx.instanceid
                LEFT JOIN {".$forum_table."_discussions} d ON d.course = c.id
                LEFT JOIN {".$forum_table."} f ON f.id = d.forum
                LEFT JOIN {".$forum_table."_posts} p ON p.userid = ra.userid AND p.discussion =d.id
                LEFT JOIN {modules} m ON m.name = '{$forum_table}'
                LEFT JOIN {course_modules} cm ON cm.module = m.id AND cm.course = c.id AND cm.instance = f.id
                LEFT JOIN (
                       SELECT p.discussion AS id, count(distinct p.id) as student_posts FROM {role_assignments} AS ra
                          LEFT JOIN {context} AS ctx ON ctx.id = ra.contextid
                          LEFT JOIN {course} c ON c.id = ctx.instanceid
                          LEFT JOIN {".$forum_table."_discussions} d ON d.course = c.id
                          LEFT JOIN {".$forum_table."_posts} p ON p.userid = ra.userid AND p.discussion = d.id
                       WHERE ctx.contextlevel = 50 $sql1 $learner_roles
                       GROUP BY p.discussion
                   ) fp ON fp.id = d.id
                WHERE ctx.contextlevel = 50 AND p.discussion > 0 $sql_filter $roles
                GROUP BY d.id, f.id, u.id, c.id $sql_having $sql_order", $params, false);



        return array( "data"            => $data,
            "data2"            => $data2,
            "data3"            => $data3,
            "data6"            => $data6);
    }
    public function report67($params)
    {
        global $DB;
        $columns = array_merge(array("l.timecreated", "l.userid", "u.firstname", "u.lastname", "u.email", "course", "c.shortname","category", "l.objecttable", "activity", "l.origin", "l.ip"), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= ($params->courseid) ? $this->get_filter_in_sql($params->courseid,'l.courseid') : "";
        $sql_filter .= $this->get_filterdate_sql($params, "l.timecreated");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_module_sql($params, "cm.");

        $list = clean_param($params->custom, PARAM_SEQUENCE);
        if($list){
            $sql_columns .=  $this->get_modules_sql($list);
            $modules = $this->get_modules_sql($list, true);
            $where = [];
            foreach ($modules as $module) {
              $where[] = $DB->sql_like('l.objecttable', ":objecttable_".$module->name, false, false);
              $this->params['objecttable_'.$module->name] = "%".$module->name."%";
            }
            $sql_filter .= ' AND ('.implode(' OR ',$where).')';
            //$sql_filter .= $this->get_filter_in_sql($list,'m.id');
        }else{
            $sql_columns .=  $this->get_modules_sql('');
        }

        if($params->custom2){
            $where = [];
            foreach(explode(',', $params->custom2) as $item){
                $item = clean_param($item, PARAM_ALPHANUMEXT);
                $where[] = $DB->sql_like('l.objecttable', ":".$item, false, false);
                $this->params[$item] = $item;
            }
            $sql_filter .= ' AND ('.implode(' OR ',$where).')';
        }

        $sql_join = "";
        if($params->cohortid){
            $sql_join = "LEFT JOIN {cohort_members} ch ON ch.userid = u.id";
            $sql_filter .= $this->get_filter_in_sql($params->cohortid,'ch.cohortid');

        }
        $sql_join .= $this->get_suspended_sql($params);

        return $this->get_report_data("
                SELECT l.id,
                    l.courseid,
                    l.userid,
                    l.contextinstanceid AS cmid,
                    l.objecttable,
                    l.origin,
                    l.ip,
                    c.fullname AS course,
                    c.shortname,
                    ca.name AS category,
                    u.email,
                    u.firstname,
                    u.lastname,
                    l.timecreated,
                    (CASE WHEN l.objecttable = 'forum_discussions' THEN (SELECT name FROM {forum_discussions} WHERE id = l.objectid) ELSE (CASE WHEN l.objecttable = 'forum_posts' THEN (SELECT subject FROM {forum_posts} WHERE id = l.objectid) ELSE '' END) END) AS forumitem
                    $sql_columns
                FROM {logstore_standard_log} l
                    LEFT JOIN {course} c ON c.id = l.courseid
                    LEFT JOIN {course_categories} ca ON ca.id = c.category
                    LEFT JOIN {user} u ON u.id = l.userid
                    LEFT JOIN {modules} m ON m.name = SUBSTRING(l.component, 5)
                    LEFT JOIN {course_modules} cm ON cm.id = l.contextinstanceid
                    $sql_join
                WHERE l.component LIKE '%mod_%' $sql_filter $sql_having $sql_order", $params);
    }
    public function report68($params)
    {
        global $CFG;

        if ($CFG->dbtype == 'pgsql') {
            return [];
        }

        $columns = array("qz.name", "ansyes", "ansno");
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["ua.userid" => "users", "qz.course" => "courses"]);
        $sql_select = "";
        $sql_from = "";

        $sql_filter .= $this->get_filter_in_sql($params->custom,'qz.id');

        if($params->courseid){
            $sql_filter .= $this->get_filter_in_sql($params->courseid,'qz.course');
            $sql_filter .= " AND c.id = qz.course ";
            $sql_select .= ", c.fullname as course";
            $sql_from .= ", {course} c";
        }
        if($params->users){
            $sql_filter .= $this->get_filter_in_sql($params->users,'qt.userid');
            $users = explode(",", $params->users);
            if(count($users) == 1 and !empty($users)){
                $sql_select .= ", CONCAT(u.firstname, ' ', u.lastname) as username";
                $sql_from .= ", {user} u";
                $sql_filter .= " AND u.id = qt.userid";
            }else{
                $sql_select .= ", '' as username";
                $sql_from .= "";
            }
        }
        if($params->cohortid){
            if($params->custom2){
                $this->params['cohortid1'] = $params->cohortid;
                $this->params['cohortid2'] = $params->cohortid;
                $this->params['cohortid3'] = $params->cohortid;
                $sql_filter .= " AND qt.userid IN(SELECT b.muserid FROM {local_elisprogram_uset_asign} a, {local_elisprogram_usr_mdl} b WHERE (a.clusterid = :cohortid1 OR a.clusterid IN (SELECT id FROM {local_elisprogram_uset} WHERE parent = :cohortid2)) AND b.cuserid = a.userid)";
                $sql_group = "GROUP BY qt.quiz, qt.attempt";
                $sql_select .= ", cm.cohorts";
                $sql_from .= ", (SELECT GROUP_CONCAT(name) as cohorts FROM {local_elisprogram_uset} WHERE id IN (:cohortid3)) cm";
            }else{
                $this->params['cohortid1'] = $params->cohortid;
                $this->params['cohortid2'] = $params->cohortid;
                $sql_filter .= " AND qt.userid IN(SELECT userid FROM {cohort_members} WHERE cohortid  IN (:cohortid1))";
                $sql_group = "GROUP BY qt.quiz, qt.attempt";
                $sql_select .= ", cm.cohorts";
                $sql_from .= ", (SELECT GROUP_CONCAT(name) as cohorts FROM {cohort} WHERE id  IN (:cohortid2)) cm";
            }
        }else{
            $sql_group = "GROUP BY qt.quiz, qt.attempt";
        }

        return $this->get_report_data("
                SELECT qas.id, qt.id AS attempt,
                    qz.name,
                    qt.userid,
                    qt.timestart,
                    qt.quiz,
                    qt.attempt,
                    SUM(IF(d.value=0,1,0)) AS ansyes,
                    SUM(IF(d.value=1,1,0)) AS ansno,
                    SUM(IF(d.value=2,1,0)) AS ansne,
                    (SELECT MAX(attempt) FROM {quiz_attempts}) AS attempts $sql_select
                FROM
                    {quiz} qz,
                    {quiz_attempts} qt,
                    {question_attempts} qa,
                    {question_attempt_steps} qas,
                    {question_attempt_step_data} d $sql_from
                WHERE
                    qz.id = qt.quiz AND
                    qa.questionusageid = qt.uniqueid AND
                    qas.questionattemptid = qa.id AND (d.value = '1' OR d.value = '0' OR d.value = '2') AND qas.userid = qt.userid AND
                    d.attemptstepid = qas.id AND qas.state = 'complete' AND qt.state <> 'inprogress'  $sql_filter
                $sql_group $sql_having
                ORDER BY qt.attempt ASC", $params);
    }


    public function report69($params)
    {
        global $CFG;

        if ($CFG->dbtype == 'pgsql') {
            return [];
        }
        $columns = array("qz.name", "ansyes", "ansno");
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "qz.course" => "courses"]);

        $sql_select = "";
        $sql_from = "";
        $sql_attempts = "";

        $params->custom = clean_param($params->custom, PARAM_SEQUENCE);
        if($params->custom){
            $sql_filter .= $this->get_filter_in_sql($params->custom,'qz.id');
            $sql_attempts = " WHERE ".$this->get_filter_in_sql($params->custom,'quiz',false);
        }
        if($params->courseid){
            $sql_filter .= $this->get_filter_in_sql($params->courseid,'qz.course');
            $sql_filter .= " AND c.id = qz.course ";
            $sql_select .= ", c.fullname as course";
            $sql_from .= " {course} c,";

        }
        if($params->cohortid){
            if($params->custom2){
                $in_sql = $this->get_filter_in_sql($params->cohortid,'a.clusterid',false);
                $in_sql2 = $this->get_filter_in_sql($params->cohortid,'parent',false);
                $sql_filter .= " AND qt.userid IN(SELECT b.muserid FROM {local_elisprogram_uset_asign} a, {local_elisprogram_usr_mdl} b WHERE ($in_sql OR a.clusterid IN (SELECT id FROM {local_elisprogram_uset} WHERE $in_sql2)) AND b.cuserid = a.userid)";
                $sql_group = "GROUP BY qt.quiz, qt.attempt, ti.tagid";
                $sql_select .= ", cm.cohorts";
                $in_sql = $this->get_filter_in_sql($params->cohortid,'id',false);
                $sql_from .= "(SELECT GROUP_CONCAT(name) as cohorts FROM {local_elisprogram_uset} WHERE $in_sql) cm, ";
            }else{
                $in_sql = $this->get_filter_in_sql($params->cohortid,'cohortid',false);
                $sql_filter .= " AND qt.userid IN(SELECT userid FROM {cohort_members} WHERE $in_sql)";
                $sql_group = "GROUP BY qt.quiz, qt.attempt, ti.tagid";

                $in_sql = $this->get_filter_in_sql($params->cohortid,'id',false);
                $sql_select .= ", cm.cohorts";
                $sql_from .= " (SELECT GROUP_CONCAT(name) as cohorts FROM {cohort} WHERE $in_sql) cm,";
            }
        }else{
            $sql_group = "GROUP BY qt.quiz, qt.attempt, ti.tagid";
        }
        if($params->users){
            $in_sql = $this->get_filter_in_sql($params->users,'qt.userid',false);
            $data = $this->get_report_data("
                SELECT qas.id, qt.id AS attempt,
                    qz.name,
                    qt.userid,
                    COUNT(DISTINCT qt.userid) AS users,
                    qt.timestart,
                    qt.quiz,
                    qt.attempt,
                    SUM(IF(d.value=0,1,0)) AS ansyes,
                    SUM(IF(d.value=1,1,0)) AS ansno,
                    SUM(IF(d.value=2,1,0)) AS ansne,
                    (SELECT MAX(attempt) FROM {quiz_attempts} $sql_attempts) AS attempts, t.rawname AS tag, ti.tagid,
                    CONCAT(u.firstname, ' ', u.lastname) AS username $sql_select
                FROM
                    {quiz} qz, {user} u, $sql_from
                    {quiz_attempts} qt,
                    {question_attempt_steps} qas,
                    {question_attempt_step_data} d,
                    {question_attempts} qa
                    LEFT JOIN {tag_instance} ti ON ti.itemtype ='question' AND ti.itemid = qa.questionid
                    LEFT JOIN {tag} t ON t.id = ti.tagid

                WHERE
                    qz.id = qt.quiz AND
                    qa.questionusageid = qt.uniqueid AND
                    qas.questionattemptid = qa.id AND (d.value = '1' OR d.value = '0' OR d.value = '2') AND qas.userid = qt.userid AND
                    d.attemptstepid = qas.id AND qas.state = 'complete' AND qt.state != 'inprogress' AND u.id = qt.userid AND
                    $in_sql $sql_filter
                $sql_group $sql_having ORDER BY qt.attempt, ti.tagid ASC ", $params, false);

            //$sql_filter .= " AND qt.userid NOT IN ($params->users)";
        }else{
            $data = false;
        }

        $data2 = $this->get_report_data("
             SELECT qas.id, qt.id AS attempt,
                    qz.name,
                    qt.userid,
                    COUNT(DISTINCT qt.userid) AS users,
                    qt.timestart,
                    qt.quiz,
                    qt.attempt,
                    SUM(IF(d.value=0,1,0)) AS ansyes,
                    SUM(IF(d.value=1,1,0)) AS ansno,
                    SUM(IF(d.value=2,1,0)) AS ansne,
                    (SELECT MAX(attempt) FROM {quiz_attempts} $sql_attempts) AS attempts, t.rawname AS tag, ti.tagid $sql_select
                FROM
                    {quiz} qz, $sql_from
                    {quiz_attempts} qt,
                    {question_attempt_steps} qas,
                    {question_attempt_step_data} d,
                    {question_attempts} qa
                    LEFT JOIN {tag_instance} ti ON ti.itemtype ='question' AND ti.itemid = qa.questionid
                    LEFT JOIN {tag} t ON t.id = ti.tagid

                WHERE
                    qz.id = qt.quiz AND
                    qa.questionusageid = qt.uniqueid AND
                    qas.questionattemptid = qa.id AND (d.value = '1' OR d.value = '0' OR d.value = '2') AND qas.userid = qt.userid AND
                    d.attemptstepid = qas.id AND qas.state = 'complete' AND qt.state != 'inprogress' $sql_filter
                $sql_group $sql_having ORDER BY qt.attempt, ti.tagid ASC ", $params,false);

        if(!$data and !$params->users){
            $data = $data2;
            $data2 = array();
        }

        return array(
            "data2"         =>  $data2,
            "data"            => $data);
    }

    public function get_max_attempts($params){
        global $DB;

        $sql1 = "";
        $sql2 = "";
        if($params->filter){
            $sql1 .= " AND q.course = :course1 ";
            $sql2 .= " AND q.course = :course2 ";
            $this->params['course1'] = intval($params->filter);
            $this->params['course2'] = intval($params->filter);
        }
        if($params->custom){
            $sql1 .= " AND q.id = :custom1 ";
            $sql2 .= " AND q.id = :custom2 ";
            $this->params['custom1'] = intval($params->custom);
            $this->params['custom2'] = intval($params->custom);
        }
        return $DB->get_record_sql("
                SELECT
                    (SELECT COUNT(DISTINCT t.tagid) AS tags
                        FROM {quiz} q, {quiz_slots} qs, {tag_instance} t
                        WHERE qs.quizid = q.id AND t.itemid = qs.questionid AND t.itemtype ='question' $sql1
                        GROUP BY q.course
                        ORDER BY tags DESC
                        LIMIT 1) AS tags,
                    (SELECT MAX(qm.attempt) FROM {quiz_attempts} qm, {quiz} q WHERE qm.quiz = q.id $sql2) AS attempts
               ", $this->params);
    }
    public function report56($params)
    {
        //deleted
    }
    function report99($params)
    {
        $columns = array_merge(array(
            "u.firstname","u.lastname","u.email","course","name","dateissued","dateexpire"
        ), $this->get_filter_columns($params));

        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filterdate_sql($params, "bi.dateissued");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, 'c.id');

        $system_b = ($params->custom == 1)?' OR b.courseid IS NULL ':'';
        if(!empty($params->courseid)){
            $sql_filter .= " AND (" . $this->get_filter_in_sql($params->courseid, 'b.courseid', false) . " $system_b)";
        }else{
            $sql_filter .= " AND (b.courseid IS NOT NULL $system_b)";
        }
        $sql_join = $this->get_suspended_sql($params);


        return $this->get_report_data("
            SELECT
              bi.id,
              u.id AS userid,
              u.firstname,
              u.lastname,
              u.email,
              c.fullname as course,
              c.id AS cid,
              b.name,
              bi.dateissued,
              bi.dateexpire
              $sql_columns
            FROM {badge} b
              LEFT JOIN {badge_issued} bi ON bi.badgeid=b.id
              LEFT JOIN {user} u ON u.id=bi.userid
              LEFT JOIN {course} c ON c.id=b.courseid
              $sql_join
            WHERE bi.id IS NOT NULL AND bi.visible = 1 $sql_filter $sql_having $sql_order", $params);
    }
    function report99_graph($params)
    {
        global $CFG;
        $sql_filter = $this->get_filterdate_sql($params, "bi.dateissued");

        $system_b = ($params->custom == 1)?' OR b.courseid IS NULL ':'';
        if(!empty($params->courseid)){
            $sql_filter .= " AND (" . $this->get_filter_in_sql($params->courseid, 'b.courseid', false) . " $system_b)";
        }else{
            $sql_filter .= " AND (b.courseid IS NOT NULL $system_b)";
        }
        unset($params->start);

        if ($CFG->dbtype == 'pgsql') {
            return $this->get_report_data("
				SELECT
				  MAX(bi.id) AS id,
				  COUNT(bi.id) as badges,
				  to_char(to_timestamp(bi.dateissued), 'YYYY-MM-DD') as time
				FROM {badge} b
				  LEFT JOIN {badge_issued} bi ON bi.badgeid=b.id
				WHERE bi.id IS NOT NULL AND bi.visible = 1 $sql_filter
				GROUP BY time", $params);
        }else{
            return $this->get_report_data("
				SELECT
				  bi.id,
				  COUNT(bi.id) as badges,
				  FROM_UNIXTIME(bi.dateissued,'%Y-%m-%d') as time
				FROM {badge} b
				  LEFT JOIN {badge_issued} bi ON bi.badgeid=b.id
				WHERE bi.id IS NOT NULL AND bi.visible = 1 $sql_filter
				GROUP BY time", $params);
        }
    }

    function report33($params)
    {
        global $CFG;
        $columns = array_merge(array(
            "c.shortname","cou.fullname","modules","users", "enrolled_student", "users_completed"
        ), $this->get_filter_columns($params, [null, "cou"]));

        $sql_columns = $this->get_columns($params, [null, "cou"]);
        $sql_filter = $this->get_teacher_sql($params, ["ra.userid" => "users", "cou" => "cc.courseid"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid,'cc.courseid');
        $sql_filter .= $this->sql_cohort_members_filter($params, "cu.userid");
        $sql_filter .= $this->sql_cohort_members_filter($params, "cou_com.userid");
        $userfilter = $this->sql_cohort_members_filter($params, "ra.userid");
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_modules = ltrim($this->get_modules_sql(''),' ,');
        $learner_roles = $this->get_filter_in_sql($params->learner_roles,'ra.roleid');

        if ($CFG->dbtype == 'pgsql') {
            $group_concat = "string_agg( DISTINCT (SELECT $sql_modules
              FROM {course_modules} cm
                LEFT JOIN {modules} m ON m.id=cm.module
              WHERE cm.id = comm.cmid
            ), ', ')";
        } else {
            $group_concat = "GROUP_CONCAT(DISTINCT (SELECT $sql_modules
              FROM {course_modules} cm
                LEFT JOIN {modules} m ON m.id=cm.module
              WHERE cm.id = comm.cmid
            ))";
        }

        return $this->get_report_data(
            "SELECT c.id,
                    c.shortname AS competency,
                    cou.fullname AS course,
                    c.path,
                    ROUND((COUNT(DISTINCT cu.userid)*100) / COUNT(DISTINCT ra.userid), 1) AS users,
                    COUNT(DISTINCT ra.userid) AS enrolled_student,
                    COUNT(DISTINCT cou_com.userid) AS users_completed,
                    {$group_concat} AS modules
                    {$sql_columns}
               FROM {competency_coursecomp} cc
          LEFT JOIN {competency} c ON c.id = cc.competencyid
          LEFT JOIN {competency_usercompcourse} cu ON cu.courseid = cc.courseid AND cu.competencyid = c.id AND cu.proficiency = 1
          LEFT JOIN {context} con ON con.contextlevel = 50 AND con.instanceid = cc.courseid
          LEFT JOIN {role_assignments} ra ON ra.contextid = con.id {$learner_roles} {$userfilter}
          LEFT JOIN {competency_modulecomp} comm ON comm.competencyid = c.id
          LEFT JOIN {course} cou ON cou.id = cc.courseid
          LEFT JOIN {course_completions} cou_com ON cou_com.course = cc.courseid AND cou_com.timecompleted > 0
              WHERE cc.id > 0 {$sql_filter}
           GROUP BY c.id, cou.id {$sql_having} {$sql_order}",
            $params
        );
    }
    function report86($params)
    {
        global $CFG;
        require_once ($CFG->dirroot . "/competency/classes/competency.php");
        $columns = array_merge(array(
            "u.firstname","u.lastname","course","comu.grade","comu.proficiency"
        ), $this->get_filter_columns($params));

        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->sql_cohort_members_filter($params, "ra.userid");
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_columns = $this->get_columns($params, ["u.id"]);
        $learner_roles = $this->get_filter_in_sql($params->learner_roles,'ra.roleid');
        $this->params['competency_id'] = clean_param($params->custom, PARAM_INT);

        $competency = new \core_competency\competency($params->custom);
        $scale = $competency->get_scale();
        $sql_join = $this->get_suspended_sql($params);

        $data =  $this->get_report_data(
            "SELECT u.id,
                    u.firstname,
                    u.lastname,
                    MAX(comu.grade) AS grade,
                    MAX(comu.proficiency) AS proficiency,
                    c.fullname as course
                    {$sql_columns}
               FROM {competency} com
          LEFT JOIN {competency_coursecomp} comc ON comc.competencyid = com.id
          LEFT JOIN {context} con ON con.contextlevel=50 AND con.instanceid=comc.courseid
          LEFT JOIN {role_assignments} ra ON ra.contextid = con.id {$learner_roles}
          LEFT JOIN {user} u ON u.id=ra.userid
          LEFT JOIN {competency_usercompcourse} comu ON comu.competencyid = com.id AND comu.userid = u.id
          LEFT JOIN {course} c ON c.id=comc.courseid
                    {$sql_join}
              WHERE com.id = :competency_id AND u.id IS NOT NULL {$sql_filter}
           GROUP BY u.id,c.id {$sql_having} {$sql_order}",
            $params, false
        );

        return array('data'=>$data, 'scale'=>$scale->scale_items);
    }
    function report100($params)
    {
        $columns = array_merge(array(
            "u.firstname",
            "u.lastname",
            "cp.name",
            "cp.timecreated",
            "cp.status",
            "progress"
        ), $this->get_filter_columns($params));

        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users"], true);
        $sql_filter .= $this->sql_cohort_members_filter($params);
        $sql_filter .= $this->get_filter_in_sql($params->custom, 'ct.id');
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_columns = $this->get_columns($params, ["u.id"]);
        $this->params['template_id'] = (int)$params->custom;

        return $this->get_report_data(
            "SELECT cp.id,
                    u.firstname,
                    u.lastname,
                    u.id AS userid,
                    cp.name,
                    cp.timecreated,
                    cp.status,
                    ROUND((SUM(cu.proficiency)/COUNT(ctc.id))*100,2) AS progress
                    {$sql_columns}
               FROM {competency_template} ct
          LEFT JOIN {competency_templatecomp} ctc ON ctc.templateid = ct.id
          LEFT JOIN {competency_plan} cp ON cp.templateid = ct.id
          LEFT JOIN {competency_usercomp} cu ON cu.competencyid = ctc.competencyid AND cu.userid = cp.userid
          LEFT JOIN {user} u ON u.id = cp.userid
              WHERE cp.id IS NOT NULL {$sql_filter}
           GROUP BY cp.id, u.id {$sql_having} {$sql_order}",
            $params
        );
    }
    function report101($params)
    {
        $columns = array_merge(array(
            "c.fullname","f.name","fd.name","fp.subject","fp.created","u.firstname","last_reply.time","count_reply","last_reply.time","last_reply.firstname"
        ), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filterdate_sql($params, "fp.created");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, 'f.course');
        $courseid1 = $this->get_filter_in_sql($params->courseid, 'f.course');
        $forum_table = ($params->custom3 == 1)?'hsuforum':'forum';
        $sql_join = $this->get_suspended_sql($params);

        return $this->get_report_data("
            SELECT
              CONCAT(fp.id, '-', fd.id),
              c.fullname AS course,
              f.name AS forum,
              fd.name AS discussion,
              fp.subject AS post,
              u.id AS userid,
              u.firstname,
              u.lastname,
              fp.created,
              (SELECT COUNT(id) FROM {".$forum_table."_posts} WHERE parent=fp.id) as count_reply,
              last_reply.time AS last_reply_time,
              last_reply.firstname AS last_reply_firstname,
              last_reply.lastname AS last_reply_lastname,
              last_reply.id AS last_reply_userid
              $sql_columns
            FROM {".$forum_table."} f
              LEFT JOIN {".$forum_table."_discussions} fd ON fd.forum=f.id
              LEFT JOIN {".$forum_table."_posts} fp ON fp.discussion=fd.id
              LEFT JOIN {course} c ON c.id=f.course
              LEFT JOIN {user} u ON u.id=fp.userid
              LEFT JOIN {modules} m ON m.name = '{$forum_table}'
              LEFT JOIN {course_modules} cm ON cm.module = m.id AND cm.course = c.id AND cm.instance = f.id
              $sql_join
              LEFT JOIN (SELECT *
                          FROM(
                            SELECT
                              post.parent,
                              post.created AS time,
                              ur.firstname,
                              ur.lastname,
                              ur.id
                            FROM {".$forum_table."_posts} post
                              JOIN {user} ur ON ur.id=post.userid
                              JOIN {".$forum_table."_discussions} d ON d.id=post.discussion
                              JOIN {".$forum_table."} f ON d.forum=f.id
                            WHERE ur.id > 0 AND CONCAT(post.parent, '-', post.created) IN(SELECT CONCAT(parent, '-', MAX(created)) FROM {".$forum_table."_posts} GROUP BY parent) $courseid1
                            ORDER BY post.created DESC
                          ) p
                         GROUP BY p.parent, p.time, p.firstname, p.lastname, p.id) last_reply ON last_reply.parent=fp.id
            WHERE u.id IS NOT NULL $sql_filter $sql_having $sql_order", $params);
    }
    function report102($params)
    {
        global $CFG;
        $columns = array_merge(array(
            "course",
            "assignment",
            "u.firstname",
            "u.lastname",
            "u.idnumber",
            "u.lastaccess",
            "lit.timespend",
            "ass_s.timecreated",
            "ass_g.grade",
            "ass_g.timemodified",
            "first_submission_file.filename",
            "cmc.completionstate",
            "ass_sl.timemodified",
            "ass_gl.grade",
            "ass_gl.timemodified",
            "last_submission_file.filename"
        ), $this->get_filter_columns($params));

        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filterdate_sql($params, "ass_s.timecreated");
        $sql_filter .= $this->get_filter_in_sql($params->courseid,'c.id');
        $sql_filter .= $this->get_filter_in_sql($params->custom,'ass.id');
        $learner_roles = $this->get_filter_in_sql($params->learner_roles,'ra.roleid');
        if ($CFG->dbtype == 'pgsql') {
            $row_number = "row_number() OVER ()";
            $row_number_select = "";
            $group_concat = "string_agg( DISTINCT CONCAT(f.filename), ', ')";
        } else {
            $row_number = "@x:=@x+1";
            $row_number_select = "(SELECT @x:= 0) AS x, ";
            $group_concat = "GROUP_CONCAT(DISTINCT CONCAT(f.filename) SEPARATOR ', ')";
        }
        $sql_join = $this->get_suspended_sql($params);

        return $this->get_report_data("
            SELECT
              $row_number AS uniqueid,
              ass_s.id,
              c.fullname AS course,
              u.firstname,
              u.lastname,
              u.idnumber,
              u.id AS userid,
              ass.name AS assignment,
              u.lastaccess,
              lit.timespend,
              ass_s.timecreated AS date_first_submission,
              ass_g.grade AS grade_first_submission,
              ass_g.timemodified AS grade_time_first_submission,
              first_submission_file.filename AS first_submission_file,
              cmc.completionstate,
              ass_sl.timemodified AS date_last_submission,
              ass_gl.grade AS grade_last_submission,
              ass_gl.timemodified AS grade_time_last_submission,
              last_submission_file.filename AS last_submission_file,
              ass_s.attemptnumber,
              ass_sl.attemptnumber AS attemptnumber2,
              sc.scale
              $sql_columns
            FROM $row_number_select {course} c
                LEFT JOIN {context} con ON con.contextlevel=50 AND con.instanceid=c.id
                LEFT JOIN {role_assignments} ra ON ra.contextid=con.id $learner_roles
                LEFT JOIN {user} u ON u.id=ra.userid
                LEFT JOIN {local_intelliboard_tracking} lit ON lit.userid=u.id AND lit.page='course' AND lit.param=c.id

                LEFT JOIN {assign} ass ON c.id=ass.course

                LEFT JOIN {assign_submission} ass_s ON ass_s.assignment=ass.id AND ass_s.attemptnumber=0 AND ass_s.userid = u.id AND ass_s.status = 'submitted'
                LEFT JOIN {assign_grades} ass_g ON ass_g.userid=ass_s.userid AND ass_g.assignment=ass_s.assignment AND ass_g.attemptnumber=ass_s.attemptnumber
                LEFT JOIN (SELECT f.itemid, $group_concat AS filename
                             FROM {files} f
                             WHERE f.component='assignsubmission_file' AND f.filearea='submission_files' AND f.filesize>0
                             GROUP BY f.itemid) first_submission_file ON first_submission_file.itemid=ass_s.id

                LEFT JOIN {assign_submission} ass_sl ON ass_sl.assignment=ass.id AND ass_sl.latest=1 AND ass_sl.userid=ass_s.userid AND ass_sl.id <> ass_s.id
                LEFT JOIN {assign_grades} ass_gl ON ass_gl.userid=ass_sl.userid AND ass_gl.assignment=ass_sl.assignment AND ass_gl.attemptnumber=ass_sl.attemptnumber
                LEFT JOIN (SELECT f.itemid, $group_concat AS filename
                             FROM {files} f
                             WHERE f.component='assignsubmission_file' AND f.filearea='submission_files' AND f.filesize>0
                             GROUP BY f.itemid) last_submission_file ON last_submission_file.itemid=ass_sl.id

                JOIN {modules} m ON m.name='assign'
                LEFT JOIN {course_modules} cm ON cm.course=ass.course AND cm.module=m.id AND cm.instance=ass.id
                LEFT JOIN {course_modules_completion} cmc ON cmc.userid=ass_s.userid AND cmc.coursemoduleid=cm.id

                LEFT JOIN {grade_items} gi ON gi.courseid=ass.course AND gi.itemtype='mod' AND gi.itemmodule='assign' AND gi.iteminstance=ass.id
                LEFT JOIN {scale} sc ON sc.id=gi.scaleid
                $sql_join
            WHERE u.id IS NOT NULL $sql_filter $sql_having $sql_order", $params);
    }
     function report103($params)
    {
        global $CFG, $DB;

        $columns = array_merge(array(
            "t.fullname","t.name","t.activity","t.due_date","t.firstname","t.lastname", "t.email", "t.time_on", ""
        ), $this->get_filter_columns($params));

        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["t.userid" => "users", "t.courseid" => "courses"]);
        $sql_columns1 = $this->get_columns($params, ["u.id"]);
        $sql_columns2 = $this->get_columns($params, ["u.id"]);
        $sql_columns3 = $this->get_columns($params, ["u.id"]);
        $gropaggr = $this->group_aggregation('t.userid', 't.courseid', $params);
        $sql_filter1 = $this->get_filter_in_sql($params->courseid,'c.id');
        $sql_filter1 .= $this->get_filter_user_sql($params, "u.");
        $sql_filter1 .= $this->get_filter_course_sql($params, "c.");
        $sql_filter1 .= $this->get_filter_enrol_sql($params, "ue.");
        $sql_filter1 .= $this->get_filter_enrol_sql($params, "e.");
        $sql_filter1 .= $this->get_filterdate_sql($params, "s.timemodified");

        $sql_filter2 = $this->get_filter_in_sql($params->courseid,'c.id');
        $sql_filter2 .= $this->get_filter_user_sql($params, "u.");
        $sql_filter2 .= $this->get_filter_course_sql($params, "c.");
        $sql_filter2 .= $this->get_filter_enrol_sql($params, "ue.");
        $sql_filter2 .= $this->get_filter_enrol_sql($params, "e.");
        $sql_filter2 .= $this->get_filterdate_sql($params, "quiza.timefinish");

        $sql_filter3 = $this->get_filter_in_sql($params->courseid,'c.id');
        $sql_filter3 .= $this->get_filter_user_sql($params, "u.");
        $sql_filter3 .= $this->get_filter_course_sql($params, "c.");
        $sql_filter3 .= $this->get_filter_enrol_sql($params, "ue.");
        $sql_filter3 .= $this->get_filter_enrol_sql($params, "e.");
        $sql_filter3 .= $this->get_filterdate_sql($params, "trs.submission_modified");
        $sql1 = $sql2 = $sql3 = '';

        $uniqueid = $CFG->dbtype == 'pgsql' ? 'ROUND(RANDOM() * 10000000000)' : 'ROUND(RAND() * 10000000000)';
        $turnitinsql = "";

        if ($DB->get_manager()->table_exists('turnitintooltwo_submissions')) {
            $turnitinsql = "UNION ALL
               (SELECT {$uniqueid} AS uniqueid,
                       cm.id AS cmid,
                       tr.id,
                       tr.name,
                       e.courseid,
                       u.id AS userid,
                       u.firstname,
                       u.lastname,
                       u.email,
                       c.fullname,
                       tp.dtdue AS due_date,
                       trs.submission_modified AS time_on,
                       'turnitintooltwo' AS activity,
                       0 AS slot,
                       0 AS attempt
                       $sql_columns3
                  FROM {turnitintooltwo_submissions} trs
             LEFT JOIN {turnitintooltwo} tr ON tr.id = trs.turnitintooltwoid
             LEFT JOIN {turnitintooltwo_parts} tp ON tp.turnitintooltwoid = tr.id
             LEFT JOIN {course} c ON c.id=tr.course
             LEFT JOIN {user} u ON u.id = trs.userid
                  JOIN {modules} m ON m.name='turnitintooltwo'
                  JOIN {course_modules} cm ON cm.course=c.id AND cm.module=m.id AND
                                              cm.instance=tr.id AND cm.visible=1
                  JOIN {enrol} e ON e.courseid = c.id
                  JOIN {user_enrolments} ue ON ue.enrolid=e.id AND ue.userid=u.id
                  JOIN {grade_items} gi ON gi.courseid=tr.course AND gi.itemtype='mod' AND
                                           gi.itemmodule='turnitintooltwo' AND gi.iteminstance=tr.id
                  JOIN {grade_grades} gg ON gg.itemid=gi.id AND gg.userid=u.id AND gg.overridden=0
                       $sql3
                 WHERE trs.submission_modified IS NOT NULL AND gg.finalgrade IS NULL
                       $sql_filter3)";
        }

        return $this->get_report_data(
            "SELECT t.*
               FROM (

               (SELECT DISTINCT CONCAT(cm.id,'_',u.id,'_',s.id) AS uniqueid,
                       cm.id AS cmid,
                       a.id,
                       a.name,
                       e.courseid,
                       u.id AS userid,
                       u.firstname,
                       u.lastname,
                       u.email,
                       c.fullname,
                       a.duedate AS due_date,
                       s.timemodified AS time_on,
                       'assignment' AS activity,
                       0 AS slot,
                       0 AS attempt
                       $sql_columns1
                  FROM {assign_submission} s
             LEFT JOIN {assign_grades} g ON s.assignment = g.assignment AND
                                            s.userid = g.userid AND
                                            g.attemptnumber = s.attemptnumber
             LEFT JOIN {assign} a ON a.id = s.assignment
             LEFT JOIN {course} c ON c.id=a.course
             LEFT JOIN {user} u ON u.id = s.userid
                  JOIN {modules} m ON m.name='assign'
                  JOIN {course_modules} cm ON cm.course=c.id AND cm.module=m.id AND cm.instance=a.id AND cm.visible=1
                  JOIN {enrol} e ON e.courseid=c.id
                  JOIN {user_enrolments} ue ON ue.enrolid=e.id AND ue.userid=u.id
                  JOIN {grade_items} gi ON gi.courseid=a.course AND gi.itemtype='mod' AND gi.itemmodule='assign' AND gi.iteminstance=a.id
                  JOIN {grade_grades} gg ON gg.itemid=gi.id AND gg.userid=u.id AND gg.overridden=0
                       $sql1
                 WHERE s.latest = 1 AND s.timemodified IS NOT NULL AND
                       s.status = 'submitted' AND
                       (s.timemodified >= g.timemodified OR g.timemodified IS NULL OR g.grade IS NULL)
                       $sql_filter1)

            UNION ALL

               (SELECT DISTINCT CONCAT(cm.id,'_',u.id,'_',quiza.id) AS uniqueid,
                       cm.id AS cmid,
                       qz.id,
                       qz.name,
                       e.courseid,
                       u.id AS userid,
                       u.firstname,
                       u.lastname,
                       u.email,
                       c.fullname,
                       qz.timeclose as due_date,
                       quiza.timefinish as time_on,
                       'quiz' AS activity,
                       qa.slot,
                       quiza.id AS attempt
                       $sql_columns2
                  FROM {quiz_attempts} quiza
             LEFT JOIN {question_attempts} qa ON qa.questionusageid = quiza.uniqueid
             LEFT JOIN {question_attempt_steps} qas ON qas.questionattemptid = qa.id AND
                       qas.sequencenumber = (SELECT MAX(sequencenumber)
                                               FROM {question_attempt_steps}
                                              WHERE questionattemptid = qa.id)
             LEFT JOIN {quiz} qz ON qz.id = quiza.quiz
             LEFT JOIN {course} c ON c.id=qz.course
             LEFT JOIN {user} u ON u.id=quiza.userid
                  JOIN {modules} m ON m.name='quiz'
                  JOIN {course_modules} cm ON cm.course=c.id AND cm.module=m.id AND cm.instance=qz.id AND cm.visible=1
                  JOIN {enrol} e ON e.courseid=c.id
                  JOIN {user_enrolments} ue ON ue.enrolid=e.id AND ue.userid=u.id
                  JOIN {grade_items} gi ON gi.courseid=c.id AND gi.itemtype='mod' AND gi.itemmodule='quiz' AND gi.iteminstance=qz.id
                  JOIN {grade_grades} gg ON gg.itemid=gi.id AND gg.userid=u.id AND gg.overridden=0
                       $sql2
                 WHERE quiza.preview = 0 AND quiza.state = 'finished' AND qas.state='needsgrading'
                       $sql_filter2)
               {$turnitinsql}
            ) t
              {$gropaggr}
              WHERE t.userid > 0 $sql_filter $sql_having $sql_order ",
            $params
       );
    }
    function report104($params)
    {
        global $CFG;
        $columns = array_merge(array("u.firstname","u.lastname","u.email"), $this->get_filter_columns($params),array('teacher'));
        $modules = $this->get_course_modules($params);
        $sql_select = '';
        foreach($modules['modules'] as $module){
            $completion = $this->get_completion($params, "");

            $module = (object)$module;
            $sql_select .= ", (SELECT timemodified FROM {course_modules_completion} WHERE userid = u.id AND coursemoduleid = $module->id $completion) AS completed_$module->id";
            $columns[] = "completed_$module->id";
        }
        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid,'ctx.instanceid');
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql = $this->get_filter_in_sql($params->learner_roles, "ra.roleid");

        $sql_teacher_roles = $this->get_filter_in_sql($params->teacher_roles, "ra_t.roleid");
        if ($CFG->dbtype == 'pgsql') {
            $group_concat = "string_agg( DISTINCT CONCAT(u_t.firstname,' ',u_t.lastname), ', ')";
        } else {
            $group_concat = "GROUP_CONCAT(DISTINCT CONCAT(u_t.firstname,' ',u_t.lastname) SEPARATOR ', ')";
        }

        $data = $this->get_report_data("
            SELECT
              ra.id,
              ra.userid,
              u.email,
              u.username,
              u.idnumber,
              u.firstname,
              u.lastname,
              (SELECT $group_concat
                FROM {role_assignments} AS ra_t
                  JOIN {user} u_t ON ra_t.userid = u_t.id
                WHERE ra_t.contextid = ctx.id $sql_teacher_roles
              ) AS teacher
              $sql_select
              $sql_columns

            FROM {context} ctx
              LEFT JOIN {role_assignments} ra ON ctx.id = ra.contextid $sql
              LEFT JOIN {user} u ON u.id=ra.userid
            WHERE ctx.contextlevel = 50 AND u.id IS NOT NULL $sql_filter $sql_having $sql_order", $params,false);

        return array('modules' => $modules['modules'],
            'data'    => $data);
    }
    function get_cohort_courses($params)
    {
      global $CFG, $DB;

      if (!$params->cohortid) {
        return [];
      }
      $sql_course = $this->get_filter_course_sql($params, "c.");
      $sql_course .= $this->get_filter_enrol_sql($params, "e.");
      $sql_course .= $this->get_filter_in_sql($params->cohortid, "e.customint1");

      return $DB->get_records_sql("SELECT DISTINCT c.id, c.fullname, c.shortname
        FROM {enrol} e, {course} c WHERE c.id = e.courseid $sql_course", $this->params);

    }

    function get_hospitals($params)
    {
      global $CFG, $DB;

      $sql = "";

      if ($params->userid and !is_siteadmin($params->userid) and !has_capability('local/management:isadmin', \context_system::instance(), $params->userid)) {
        $this->params['userid'] = (int) $params->userid;
        $sql = " AND h.id IN (SELECT instanceid FROM {local_management_users} WHERE type = 'hospital' AND userid = :userid)";
      }

      return $DB->get_records_sql("SELECT h.* FROM {local_management_hospital} h WHERE h.status = 1 $sql", $this->params);

    }

    function get_hospital_info($params)
    {
      global $DB;

      return [
        'cohort' => $DB->get_record("cohort", ['id' => (int) $params->custom2]),
        'course' => $DB->get_record("course", ['id' => (int) $params->custom3]),
        'hospital' => $DB->get_record("local_management_hospital", ['id' => (int) $params->custom])
      ];
    }

    function get_hospital_cohorts($params)
    {
      global $CFG, $DB;

      $sql = "";
      if ($params->userid) {
        $this->params['userid'] = (int) $params->userid;
        $sql = " AND c.id IN (SELECT instanceid FROM {local_management_users} WHERE type = 'cohort' AND userid = :userid)";
      }
      if ($params->custom) {
        $this->params['hospitalid'] = (int) $params->custom;
        $sql = " AND c.hospitalid = :hospitalid";
      }

      return $DB->get_records_sql("SELECT co.* FROM {local_management_cohort} c, {cohort} co WHERE co.id = c.cohortid AND co.visible = 1 AND c.status = 1 $sql", $this->params);

    }


    function get_course_modules($params)
    {
        global $DB;

        $sql_modules = $this->get_modules_sql('');
        $course = (!empty($params->custom))?clean_param($params->custom, PARAM_INT):clean_param($params->courseid, PARAM_INT);
        $sql_filter = "AND cm.completion>0";
        if ($params->custom2 == 'all') {
            $sql_filter = "";
        }

        $modules = $DB->get_records_sql("
                SELECT
                    cm.id,
                    m.name AS type,
                    cm.instance AS instance
                    $sql_modules
                FROM {course_modules} cm
                    LEFT JOIN {modules} m ON m.id=cm.module
                WHERE cm.course=:course $sql_filter",array('course'=>$course));

        return array('modules' => $modules);
    }
    function get_course_assignments($params)
    {
        global $DB;

        $sql = $this->get_teacher_sql($params, ["c.id" => "courses"]);
        $sql .= $this->get_filter_in_sql($params->courseid,'a.course');


        return $DB->get_records_sql("
            SELECT a.id,
                a.name,
                c.id AS courseid,
                c.fullname AS coursename
            FROM {assign} a
                JOIN {course} c ON c.id = a.course WHERE c.id > 0 $sql", $this->params);
    }

    function report105($params)
    {
        $columns = array_merge(array(
            "u.firstname","u.lastname","c.shortname","ce.descidentifier","ce.url","ce.timecreated"
        ), $this->get_filter_columns($params));

        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users"]);
        $sql_filter .= $this->get_filter_in_sql($params->custom2,'c.id');
        $sql_filter .= $this->sql_cohort_members_filter($params, "cu.userid");

        $data = $this->get_report_data(
            "SELECT ce.id,
                    u.id AS userid,
                    u.firstname,
                    u.lastname,
                    c.shortname,
                    ce.url,
                    ce.descidentifier,
                    ce.desccomponent,
                    ce.desca,
                    ce.timecreated
                    {$sql_columns}
               FROM {competency_evidence} ce
          LEFT JOIN {competency_usercomp} cu ON ce.usercompetencyid = cu.id
          LEFT JOIN {competency} c ON c.id = cu.competencyid
          LEFT JOIN {user} u ON u.id = cu.userid
              WHERE u.id IS NOT NULL {$sql_filter} {$sql_having} {$sql_order}",
            $params, false
        );

        foreach($data as &$item){
            $item->desc = get_string($item->descidentifier,$item->desccomponent,$item->desca);
        }

        return array('data'=>$data);
    }
    function report106($params)
    {
        $params->custom = json_decode($params->custom);
        $this->params['module'] = clean_param($params->custom->module, PARAM_INT);
        $this->params['course'] = clean_param($params->custom->course, PARAM_INT);

        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        if($params->timestart > 0){
            $sql_filter .= "AND (lit.lastaccess<:timestart OR lit.lastaccess IS NULL)";
            $this->params['timestart'] = $params->timestart;
        }

        return $this->get_report_data("
            SELECT
              u.id,
              u.firstname,
              u.lastname,
              MAX(lit.lastaccess) AS lastaccess
            FROM {enrol} e
              LEFT JOIN {user_enrolments} ue ON ue.enrolid=e.id
              LEFT JOIN {user} u ON u.id=ue.userid
              LEFT JOIN {local_intelliboard_tracking} lit ON lit.userid=ue.userid AND lit.page='module' AND lit.param=:module
            WHERE e.courseid=:course AND ue.userid IS NOT NULL $sql_filter
            GROUP BY u.id", $params);
    }
    function report107($params)
    {
        global $CFG;
        $columns = array("c.fullname","d.name");

        $sql_syb_where = $sql_join = $sql_filter = '';
        $sql_columns = 'd.id,';
        $sql_group = '';
        $sql_filter = $this->get_teacher_sql($params, ["c.id" => "courses"]);
        if($params->custom == 2){
            $sql_columns = 'CONCAT(d.id,u.id) as id,u.id as userid,u.firstname,u.lastname,';
            $sql_syb_where =  ' AND dr.userid=u.id';
            $sql_join =  "LEFT JOIN {data_records} mdr ON mdr.dataid=d.id
                          LEFT JOIN {user} u ON u.id=mdr.userid";
            $sql_filter .= ' AND u.id IS NOT NULL';
            $sql_filter .= $this->get_filter_in_sql($params->users,'u.id');
            $sql_group = ' GROUP BY d.id,u.id';

            $columns[] = 'u.firstname';
            $columns[] = 'u.lastname';
            $columns[] = 'avg_answer';
        }elseif($params->custom == 3){
            $sql_columns = 'CONCAT(d.id,u.id,mdr.id) as id,u.id as userid,u.firstname,u.lastname,mdr.timecreated as submitted,';
            $sql_syb_where =  ' AND dr.userid=u.id AND dr.id=mdr.id';
            $sql_join =  "LEFT JOIN {data_records} mdr ON mdr.dataid=d.id
                          LEFT JOIN {user} u ON u.id=mdr.userid";
            $sql_filter .= ' AND u.id IS NOT NULL';
            $sql_filter .= $this->get_filter_in_sql($params->users,'u.id');
            $sql_group = ' GROUP BY d.id,u.id,mdr.id';

            $columns[] = 'u.firstname';
            $columns[] = 'u.lastname';
            $columns[] = 'avg_answer';
            $columns[] = 'mdr.timecreated';

            $custom3 = explode(',',$params->custom3);
            if(!empty($custom3)){
                foreach($custom3 as $item){
                    $item = clean_param($item, PARAM_INT);
                    $sql_columns .= "(SELECT content FROM {data_content} WHERE fieldid=$item AND recordid=mdr.id) as field_$item,";
                }
            }
        }

        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter .= $this->get_filter_in_sql($params->courseid,'d.course');
        $sql_filter .= $this->get_filter_in_sql($params->custom2,'d.id');

        if ($CFG->dbtype == 'pgsql') {
            return $this->get_report_data("
				SELECT
				  $sql_columns
				  d.name,
				  MAX(c.fullname) as course,
				  MAX(c.id) as courseid,
				  (
					SELECT
					  AVG(CAST(dc.content AS integer))
					FROM {data_records} dr
					  LEFT JOIN {data_content} dc ON dc.recordid=dr.id
					  LEFT JOIN {data_fields} df ON df.id=dc.fieldid
					WHERE df.type='menu' AND dr.dataid=d.id $sql_syb_where
				  ) avg_answer

				FROM {data} d
				  LEFT JOIN {course} c ON c.id=d.course
				  $sql_join
				WHERE d.id > 0 $sql_filter $sql_group $sql_having $sql_order
				", $params);
        }else{
            return $this->get_report_data("
				SELECT
				  $sql_columns
				  d.name,
				  c.fullname as course,
				  c.id as courseid,
				  (
					SELECT
					  AVG(dc.content)
					FROM {data_records} dr
					  LEFT JOIN {data_content} dc ON dc.recordid=dr.id
					  LEFT JOIN {data_fields} df ON df.id=dc.fieldid
					WHERE df.type='menu' AND dr.dataid=d.id AND dc.content REGEXP '[0-9]' $sql_syb_where
				  ) avg_answer

				FROM {data} d
				  LEFT JOIN {course} c ON c.id=d.course
				  $sql_join
				WHERE d.id > 0 $sql_filter $sql_group $sql_having $sql_order
				", $params);
        }

    }
    function report108($params)
    {
        global $CFG;
        $columns = array("c.fullname","d.name","df.name","count_answers","avg_answer");

        $max_answer = clean_param($params->custom, PARAM_INT);
        $sql = '';
        for($i=0;$i<=$max_answer;$i++){
            $columns[] = "count_answer_$i";
            $prev = $i-1;
            if($i==0){
                $sql .= ",SUM(CASE WHEN dc.content = '' THEN 1 ELSE 0 END) AS count_answer_$i";
            }else{
                if ($CFG->dbtype == 'pgsql') {
                    $sql .= ",SUM(CASE WHEN CAST(dc.content AS integer)>$prev AND CAST(dc.content AS integer)<=$i THEN 1 ELSE 0 END ) AS count_answer_$i";
                }else{
                    $sql .= ",SUM(CASE WHEN dc.content>$prev AND dc.content<=$i THEN 1 ELSE 0 END ) AS count_answer_$i";
                }
            }
        }

        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid,'d.course');
        $sql_filter .= $this->get_filter_in_sql($params->custom2,'d.id');

        if ($CFG->dbtype == 'pgsql') {
            return $this->get_report_data("
				SELECT
				  df.id,
				  MIN(c.fullname) AS course,
				  MIN(d.name) AS name,
				  df.name AS question,
				  COUNT(DISTINCT dc.id) AS count_answers,
				  (
					SELECT
					  AVG(CAST(dc2.content AS integer))
					FROM {data_content} dc2
					WHERE dc2.fieldid=df.id AND dc2.content ~ '[0-9]'
				  ) avg_answer
				  $sql

				FROM {data} d
				  LEFT JOIN {course} c ON c.id=d.course
				  LEFT JOIN {data_fields} df ON df.dataid=d.id
				  LEFT JOIN {data_content} dc ON dc.fieldid=df.id
				WHERE df.type='menu' AND df.param1 ~ '[0-9]' $sql_filter
				GROUP BY df.id $sql_having $sql_order
				", $params);
        }else{
            return $this->get_report_data("
				SELECT
				  df.id,
				  c.fullname AS course,
				  d.name,
				  df.name AS question,
				  COUNT(DISTINCT dc.id) AS count_answers,
				  (
					SELECT
					  AVG(dc2.content)
					FROM {data_content} dc2
					WHERE dc2.fieldid=df.id AND dc2.content REGEXP '[0-9]'
				  ) avg_answer
				  $sql

				FROM {data} d
				  LEFT JOIN {course} c ON c.id=d.course
				  LEFT JOIN {data_fields} df ON df.dataid=d.id
				  LEFT JOIN {data_content} dc ON dc.fieldid=df.id
				WHERE df.type='menu' AND df.param1 REGEXP '[0-9]' $sql_filter
				GROUP BY df.id $sql_having $sql_order
				", $params);
        }

    }
    function report109($params)
    {
        $columns = array_merge(array(
            "c.fullname",
            "c.shortname",
            "category",
            "parent_category",
            "a.name",
            "a.duedate",
            "u.firstname",
            "u.lastname",
            "u.idnumber",
            "s.attemptnumber",
            "s.timemodified",
            "u2.firstname",
            "s.status",
            "g.timemodified",
            "g.timemodified"
            ), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filterdate_sql($params, "s.timemodified");

        return $this->get_report_data("
            SELECT l.id,
                a.name as assignment,
                a.id as assignmentid,
                a.duedate,
                c.fullname as course,
                c.shortname,
                c.id as courseid,
                s.status,
                s.userid,
                s.timemodified,
                u.firstname,
                u.lastname,
                u.idnumber,
                u2.firstname AS marker_firstname,
                u2.lastname AS marker_lastname,
                f.workflowstate,
                s.attemptnumber,
                cmc.completionstate,
                g.timemodified AS graded,
                cc.name AS category,
                (SELECT name FROM {course_categories} WHERE id = cc.parent) AS parent_category,
                g.grade
                $sql_columns
            FROM {assign_user_mapping} l
                JOIN {user} u ON u.id = l.userid
                JOIN {assign} a ON a.id = l.assignment
                JOIN {course} c ON c.id = a.course
                JOIN {course_categories} cc ON cc.id = c.category
                JOIN {enrol} e ON e.courseid = c.id
                JOIN {user_enrolments} ue ON ue.enrolid = e.id AND ue.userid = u.id
                LEFT JOIN {assign_submission} s ON s.userid = u.id AND s.assignment = a.id
                LEFT JOIN {assign_grades} g ON g.assignment = a.id AND g.userid = u.id AND g.attemptnumber = s.attemptnumber
                LEFT JOIN {assign_user_flags} f ON f.assignment = a.id AND f.userid = u.id
                LEFT JOIN {user} u2 ON u2.id = f.allocatedmarker
                LEFT JOIN {modules} m ON m.name = 'assign'
                LEFT JOIN {course_modules} cm ON cm.instance = a.id AND cm.module = m.id
                LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid=cm.id AND cmc.userid = u.id
            WHERE l.id > 0 $sql_filter $sql_having $sql_order", $params);
    }
    function report110($params)
    {
        $columns = array_merge(array(
            "c.fullname",
            "cc.name",
            "internet_explorer",
            "firefox",
            "chrome",
            "safari",
            "microsoft_edge",
            "opera",
            "netscape",
            "maxthon",
            "konqueror",
            "mobile_browser",
            "other_browser",
            "windows_10",
            "windows_8_1",
            "windows_8",
            "windows_7",
            "windows_vista",
            "windows_server",
            "windows_xp",
            "windows_2000",
            "windows_me",
            "windows_98",
            "windows_95",
            "windows_3_11",
            "mac_os_x",
            "mac_os_9",
            "linux",
            "ubuntu",
            "iphone",
            "ipod",
            "ipad",
            "android",
            "blackberry",
            "mobile",
            "other_platform",
        ), $this->get_filter_columns($params, [null]));

        $sql_columns = $this->get_columns($params, [null]);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["lit.userid" => "users", "lit.courseid" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "lit.courseid");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");

        return $this->get_report_data("
            SELECT
              c.id,
              c.fullname AS course,
              cc.name AS category_name,

              ROUND((SUM(CASE WHEN lit.useragent='Internet Explorer' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS internet_explorer,
              ROUND((SUM(CASE WHEN lit.useragent='Firefox' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS firefox,
              ROUND((SUM(CASE WHEN lit.useragent='Chrome' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS chrome,
              ROUND((SUM(CASE WHEN lit.useragent='Safari' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS safari,
              ROUND((SUM(CASE WHEN lit.useragent='Microsoft Edge' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS microsoft_edge,
              ROUND((SUM(CASE WHEN lit.useragent='Opera' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS opera,
              ROUND((SUM(CASE WHEN lit.useragent='Netscape' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS netscape,
              ROUND((SUM(CASE WHEN lit.useragent='Maxthon' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS maxthon,
              ROUND((SUM(CASE WHEN lit.useragent='Konqueror' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS konqueror,
              ROUND((SUM(CASE WHEN lit.useragent='Mobile browser' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS mobile_browser,
              ROUND((SUM(CASE WHEN lit.useragent='Unknown Browser' OR lit.useragent IS NULL THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS other_browser,

              ROUND((SUM(CASE WHEN lit.useros='Windows 10' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS windows_10,
              ROUND((SUM(CASE WHEN lit.useros='Windows 8.1' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS windows_8_1,
              ROUND((SUM(CASE WHEN lit.useros='Windows 8' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS windows_8,
              ROUND((SUM(CASE WHEN lit.useros='Windows 7' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS windows_7,
              ROUND((SUM(CASE WHEN lit.useros='Windows Vista' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS windows_vista,
              ROUND((SUM(CASE WHEN lit.useros='Windows Server 2003/XP x64' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS windows_server,
              ROUND((SUM(CASE WHEN lit.useros='Windows XP' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS windows_xp,
              ROUND((SUM(CASE WHEN lit.useros='Windows 2000' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS windows_2000,
              ROUND((SUM(CASE WHEN lit.useros='Windows ME' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS windows_me,
              ROUND((SUM(CASE WHEN lit.useros='Windows 98' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS windows_98,
              ROUND((SUM(CASE WHEN lit.useros='Windows 95' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS windows_95,
              ROUND((SUM(CASE WHEN lit.useros='Windows 3.11' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS windows_3_11,
              ROUND((SUM(CASE WHEN lit.useros='Mac OS X' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS mac_os_x,
              ROUND((SUM(CASE WHEN lit.useros='Mac OS 9' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS mac_os_9,
              ROUND((SUM(CASE WHEN lit.useros='Linux' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS linux,
              ROUND((SUM(CASE WHEN lit.useros='Ubuntu' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS ubuntu,
              ROUND((SUM(CASE WHEN lit.useros='iPhone' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS iphone,
              ROUND((SUM(CASE WHEN lit.useros='iPod' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS ipod,
              ROUND((SUM(CASE WHEN lit.useros='iPad' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS ipad,
              ROUND((SUM(CASE WHEN lit.useros='Android' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS android,
              ROUND((SUM(CASE WHEN lit.useros='BlackBerry' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS blackberry,
              ROUND((SUM(CASE WHEN lit.useros='Mobile' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS mobile,
              ROUND((SUM(CASE WHEN lit.useros='Unknown OS Platform' OR lit.useros IS NULL THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS other_platform
              {$sql_columns}
            FROM {local_intelliboard_tracking} lit
              LEFT JOIN {course} c ON c.id = lit.courseid
              LEFT JOIN {course_categories} cc ON cc.id = c.category
            WHERE lit.courseid>1 AND c.id IS NOT NULL $sql_filter
            GROUP BY lit.courseid,c.id,cc.id $sql_having $sql_order", $params);
    }

    function report111($params)
    {
        $columns = array_merge(array(
            "u.firstname",
            "u.lastname",
            "u.email",
            "u.idnumber",
            "c.fullname",
            "cc.name",
            "internet_explorer",
            "firefox",
            "chrome",
            "safari",
            "microsoft_edge",
            "opera",
            "netscape",
            "maxthon",
            "konqueror",
            "mobile_browser",
            "other_browser",
            "windows_10",
            "windows_8_1",
            "windows_8",
            "windows_7",
            "windows_vista",
            "windows_server",
            "windows_xp",
            "windows_2000",
            "windows_me",
            "windows_98",
            "windows_95",
            "windows_3_11",
            "mac_os_x",
            "mac_os_9",
            "linux",
            "ubuntu",
            "iphone",
            "ipod",
            "ipad",
            "android",
            "blackberry",
            "mobile",
            "other_platform",
        ), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "lit.courseid");
        $sql_filter .= $this->get_filter_in_sql($params->users, "lit.userid");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->vendor_filter('lit.userid', 'lit.courseid', $params);
        $sql_join = $this->get_suspended_sql($params);

        return $this->get_report_data("
            SELECT
              MAX(lit.id) AS id,
              u.id AS userid,
              u.firstname,
              u.lastname,
              u.email,
              u.idnumber,
              c.id AS courseid,
              c.fullname AS course,
              cc.name AS category_name,

              ROUND((SUM(CASE WHEN lit.useragent='Internet Explorer' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS internet_explorer,
              ROUND((SUM(CASE WHEN lit.useragent='Firefox' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS firefox,
              ROUND((SUM(CASE WHEN lit.useragent='Chrome' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS chrome,
              ROUND((SUM(CASE WHEN lit.useragent='Safari' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS safari,
              ROUND((SUM(CASE WHEN lit.useragent='Microsoft Edge' OR lit.useragent='Edge' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS microsoft_edge,
              ROUND((SUM(CASE WHEN lit.useragent='Opera' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS opera,
              ROUND((SUM(CASE WHEN lit.useragent='Netscape' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS netscape,
              ROUND((SUM(CASE WHEN lit.useragent='Maxthon' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS maxthon,
              ROUND((SUM(CASE WHEN lit.useragent='Konqueror' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS konqueror,
              ROUND((SUM(CASE WHEN lit.useragent='Mobile browser' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS mobile_browser,
              ROUND((SUM(CASE WHEN lit.useragent='Unknown Browser' OR lit.useragent IS NULL THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS other_browser,

              ROUND((SUM(CASE WHEN lit.useros='Windows 10' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS windows_10,
              ROUND((SUM(CASE WHEN lit.useros='Windows 8.1' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS windows_8_1,
              ROUND((SUM(CASE WHEN lit.useros='Windows 8' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS windows_8,
              ROUND((SUM(CASE WHEN lit.useros='Windows 7' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS windows_7,
              ROUND((SUM(CASE WHEN lit.useros='Windows Vista' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS windows_vista,
              ROUND((SUM(CASE WHEN lit.useros='Windows Server 2003/XP x64' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS windows_server,
              ROUND((SUM(CASE WHEN lit.useros='Windows XP' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS windows_xp,
              ROUND((SUM(CASE WHEN lit.useros='Windows 2000' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS windows_2000,
              ROUND((SUM(CASE WHEN lit.useros='Windows ME' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS windows_me,
              ROUND((SUM(CASE WHEN lit.useros='Windows 98' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS windows_98,
              ROUND((SUM(CASE WHEN lit.useros='Windows 95' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS windows_95,
              ROUND((SUM(CASE WHEN lit.useros='Windows 3.11' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS windows_3_11,
              ROUND((SUM(CASE WHEN lit.useros='Mac OS X' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS mac_os_x,
              ROUND((SUM(CASE WHEN lit.useros='Mac OS 9' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS mac_os_9,
              ROUND((SUM(CASE WHEN lit.useros='Linux' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS linux,
              ROUND((SUM(CASE WHEN lit.useros='Ubuntu' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS ubuntu,
              ROUND((SUM(CASE WHEN lit.useros='iPhone' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS iphone,
              ROUND((SUM(CASE WHEN lit.useros='iPod' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS ipod,
              ROUND((SUM(CASE WHEN lit.useros='iPad' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS ipad,
              ROUND((SUM(CASE WHEN lit.useros='Android' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS android,
              ROUND((SUM(CASE WHEN lit.useros='BlackBerry' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS blackberry,
              ROUND((SUM(CASE WHEN lit.useros='Mobile' THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS mobile,
              ROUND((SUM(CASE WHEN lit.useros='Unknown OS Platform' OR lit.useros IS NULL THEN lit.timespend ELSE 0 END)*100)/SUM(lit.timespend),2) AS other_platform
              $sql_columns
            FROM {local_intelliboard_tracking} lit
              LEFT JOIN {course} c ON c.id = lit.courseid
              LEFT JOIN {course_categories} cc ON cc.id = c.category
              LEFT JOIN {user} u ON u.id=lit.userid
              $sql_join
            WHERE lit.courseid>1 AND c.id IS NOT NULL $sql_filter
            GROUP BY lit.courseid,lit.userid,u.id,c.id,cc.id $sql_having $sql_order", $params);
    }

    function report112($params)
    {
        global $CFG;
        $columns = array_merge(array(
            "temp.course",
            "temp.category_name",
            "temp.most_active_day",
            "temp.time_of_day",
            "temp.sunday_hours",
            "temp.sunday_percent",
            "temp.monday_hours",
            "temp.monday_percent",
            "temp.tuesday_hours",
            "temp.tuesday_percent",
            "temp.wednesday_hours",
            "temp.wednesday_percent",
            "temp.thursday_hours",
            "temp.thursday_percent",
            "temp.friday_hours",
            "temp.friday_percent",
            "temp.saturday_hours",
            "temp.saturday_percent",
        ), $this->get_filter_columns($params, [null]));

        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $datefilter = '';

        if($params->timestart && $params->timefinish) {
            $datefilter = "AND (lil.timepoint BETWEEN {$params->timestart} AND {$params->timefinish})";
        }

        $sql_columns1 = $this->get_columns($params, [null]);
        $sql_columns2 = $this->get_columns($params, [null]);
        $sql_filter = $this->get_teacher_sql($params, ["c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "lit.courseid");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");

        $sql_filter2 = $this->get_teacher_sql($params, ["c.id" => "courses"]);
        $sql_filter2 .= $this->get_filter_in_sql($params->courseid, "lit.courseid");
        $sql_filter2 .= $this->get_filter_course_sql($params, "c.");

        $sql_filter3 = $this->get_teacher_sql($params, ["c.id" => "courses"]);
        $sql_filter3 .= $this->get_filter_in_sql($params->courseid, "lit.courseid");
        $sql_filter3 .= $this->get_filter_course_sql($params, "c.");

        $sql_filter4 = $this->get_teacher_sql($params, ["c.id" => "courses"]);
        $sql_filter4 .= $this->get_filter_in_sql($params->courseid, "lit.courseid");
        $sql_filter4 .= $this->get_filter_course_sql($params, "c.");

        $sql_filter5 = $this->get_teacher_sql($params, ["c.id" => "courses"]);
        $sql_filter5 .= $this->get_filter_in_sql($params->courseid, "lit.courseid");
        $sql_filter5 .= $this->get_filter_course_sql($params, "c.");

        $sql_filter6 = $this->get_teacher_sql($params, ["c.id" => "courses"]);
        $sql_filter6 .= $this->get_filter_in_sql($params->courseid, "lit.courseid");
        $sql_filter6 .= $this->get_filter_course_sql($params, "c.");

        if ($CFG->dbtype == 'pgsql') {
            return $this->get_report_data("
				SELECT * FROM (SELECT
				  CAST(CONCAT(MAX(lit.id),MAX(lid.id)) AS bigint) AS id,
				  c.id AS courseid,
				  c.fullname AS course,
				  cc.name AS category_name,
				  MAX(popular_day.day) AS most_active_day,
				  MAX(popular_time.active_time_of_day) AS most_active_time_of_day,

				  SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 1 THEN lil.timespend ELSE 0 END) AS monday_hours,
				  ROUND(SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 1 THEN lil.timespend ELSE 0 END)*100/SUM(lil.timespend), 2) AS monday_percent,

				  SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 2 THEN lil.timespend ELSE 0 END) AS tuesday_hours,
				  ROUND(SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 2 THEN lil.timespend ELSE 0 END)*100/SUM(lil.timespend), 2) AS tuesday_percent,

				  SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 3 THEN lil.timespend ELSE 0 END) AS wednesday_hours,
				  ROUND(SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 3 THEN lil.timespend ELSE 0 END)*100/SUM(lil.timespend), 2) AS wednesday_percent,

				  SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 4 THEN lil.timespend ELSE 0 END) AS thursday_hours,
				  ROUND(SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 4 THEN lil.timespend ELSE 0 END)*100/SUM(lil.timespend), 2) AS thursday_percent,

				  SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 5 THEN lil.timespend ELSE 0 END) AS friday_hours,
				  ROUND(SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 5 THEN lil.timespend ELSE 0 END)*100/SUM(lil.timespend), 2) AS friday_percent,

				  SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 6 THEN lil.timespend ELSE 0 END) AS saturday_hours,
				  ROUND(SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 6 THEN lil.timespend ELSE 0 END)*100/SUM(lil.timespend), 2) AS saturday_percent,

				  SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 0 THEN lil.timespend ELSE 0 END) AS sunday_hours,
				  ROUND(SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 0 THEN lil.timespend ELSE 0 END)*100/SUM(lil.timespend), 2) AS sunday_percent,

				  CASE WHEN lid.timepoint>=0 AND lid.timepoint<6 THEN 1 ELSE
					CASE WHEN lid.timepoint>=6 AND lid.timepoint<9 THEN 2 ELSE
					  CASE WHEN lid.timepoint>=9 AND lid.timepoint<12 THEN 3 ELSE
						CASE WHEN lid.timepoint>=12 AND lid.timepoint<15 THEN 4 ELSE
						  CASE WHEN lid.timepoint>=15 AND lid.timepoint<18 THEN 5 ELSE
							CASE WHEN lid.timepoint>=18 AND lid.timepoint<21 THEN 6 ELSE
							  CASE WHEN lid.timepoint>=21 AND lid.timepoint<=23 THEN 7 ELSE 0 END END END END END END END AS time_of_day
                  {$sql_columns1}

				FROM {local_intelliboard_tracking} lit
				  LEFT JOIN {local_intelliboard_logs} lil ON lil.trackid=lit.id {$datefilter}
				  LEFT JOIN {local_intelliboard_details} lid ON lid.logid=lil.id
				  LEFT JOIN (SELECT
								  lit.courseid,
								  (CASE WHEN extract(dow from to_timestamp(lil.timepoint))=0 THEN 6 ELSE extract(dow from to_timestamp(lil.timepoint))-1 END) AS day,
								  SUM(lil.timespend) AS sum_timespend,
								  ROW_NUMBER () OVER (partition by lit.courseid ORDER BY SUM(lil.timespend) DESC) AS row_number

								FROM {local_intelliboard_tracking} lit
								  LEFT JOIN {local_intelliboard_logs} lil ON lil.trackid=lit.id {$datefilter}
								  LEFT JOIN {course} c ON c.id = lit.courseid
								WHERE lit.courseid>1 AND c.id IS NOT NULL $sql_filter
								GROUP BY lit.courseid,day
							  )  AS popular_day ON popular_day.courseid=lit.courseid AND popular_day.row_number=1
				  LEFT JOIN (SELECT
								  lit.courseid,
								  CASE WHEN lid.timepoint>=0 AND lid.timepoint<6 THEN 1 ELSE
									CASE WHEN lid.timepoint>=6 AND lid.timepoint<9 THEN 2 ELSE
									  CASE WHEN lid.timepoint>=9 AND lid.timepoint<12 THEN 3 ELSE
										CASE WHEN lid.timepoint>=12 AND lid.timepoint<15 THEN 4 ELSE
										  CASE WHEN lid.timepoint>=15 AND lid.timepoint<18 THEN 5 ELSE
											CASE WHEN lid.timepoint>=18 AND lid.timepoint<21 THEN 6 ELSE
											  CASE WHEN lid.timepoint>=21 AND lid.timepoint<=23 THEN 7 ELSE 0 END END END END END END END AS active_time_of_day,
									 SUM(lid.timespend) AS sum_timespend,
								  ROW_NUMBER () OVER (partition by lit.courseid ORDER BY SUM(lid.timespend) DESC) AS row_number

								FROM {local_intelliboard_tracking} lit
								  LEFT JOIN {local_intelliboard_logs} lil ON lil.trackid=lit.id {$datefilter}
								  LEFT JOIN {local_intelliboard_details} lid ON lid.logid=lil.id
								  LEFT JOIN {course} c ON c.id = lit.courseid
								WHERE lit.courseid>1 AND c.id IS NOT NULL $sql_filter5
								GROUP BY lit.courseid,active_time_of_day
							  ) AS popular_time ON popular_time.courseid=lit.courseid AND popular_time.row_number=1

				  LEFT JOIN {course} c ON c.id = lit.courseid
				  LEFT JOIN {course_categories} cc ON cc.id = c.category

				WHERE lit.courseid>1 AND c.id IS NOT NULL $sql_filter2
				GROUP BY lit.courseid,
						c.id,
						cc.id,
						CASE WHEN lid.timepoint>=0 AND lid.timepoint<6 THEN 1 ELSE
							CASE WHEN lid.timepoint>=6 AND lid.timepoint<9 THEN 2 ELSE
							  CASE WHEN lid.timepoint>=9 AND lid.timepoint<12 THEN 3 ELSE
								CASE WHEN lid.timepoint>=12 AND lid.timepoint<15 THEN 4 ELSE
								  CASE WHEN lid.timepoint>=15 AND lid.timepoint<18 THEN 5 ELSE
									CASE WHEN lid.timepoint>=18 AND lid.timepoint<21 THEN 6 ELSE
									  CASE WHEN lid.timepoint>=21 AND lid.timepoint<=23 THEN 7 ELSE 0 END END END END END END END
				HAVING CASE WHEN lid.timepoint>=0 AND lid.timepoint<6 THEN 1 ELSE
						CASE WHEN lid.timepoint>=6 AND lid.timepoint<9 THEN 2 ELSE
						  CASE WHEN lid.timepoint>=9 AND lid.timepoint<12 THEN 3 ELSE
							CASE WHEN lid.timepoint>=12 AND lid.timepoint<15 THEN 4 ELSE
							  CASE WHEN lid.timepoint>=15 AND lid.timepoint<18 THEN 5 ELSE
								CASE WHEN lid.timepoint>=18 AND lid.timepoint<21 THEN 6 ELSE
								  CASE WHEN lid.timepoint>=21 AND lid.timepoint<=23 THEN 7 ELSE 0 END END END END END END END > 0

				UNION

				SELECT
				  MAX(lit.id) AS id,
				  c.id AS courseid,
				  c.fullname AS course,
				  cc.name AS category_name,
				  MAX(popular_day.day) AS most_active_day,
				  MAX(popular_time.active_time_of_day) AS most_active_time_of_day,

				  SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 1 THEN lil.timespend ELSE 0 END) AS monday_hours,
				  ROUND(SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 1 THEN lil.timespend ELSE 0 END)*100/SUM(lil.timespend), 2) AS monday_percent,

				  SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 2 THEN lil.timespend ELSE 0 END) AS tuesday_hours,
				  ROUND(SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 2 THEN lil.timespend ELSE 0 END)*100/SUM(lil.timespend), 2) AS tuesday_percent,

				  SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 3 THEN lil.timespend ELSE 0 END) AS wednesday_hours,
				  ROUND(SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 3 THEN lil.timespend ELSE 0 END)*100/SUM(lil.timespend), 2) AS wednesday_percent,

				  SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 4 THEN lil.timespend ELSE 0 END) AS thursday_hours,
				  ROUND(SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 4 THEN lil.timespend ELSE 0 END)*100/SUM(lil.timespend), 2) AS thursday_percent,

				  SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 5 THEN lil.timespend ELSE 0 END) AS friday_hours,
				  ROUND(SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 5 THEN lil.timespend ELSE 0 END)*100/SUM(lil.timespend), 2) AS friday_percent,

				  SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 6 THEN lil.timespend ELSE 0 END) AS saturday_hours,
				  ROUND(SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 6 THEN lil.timespend ELSE 0 END)*100/SUM(lil.timespend), 2) AS saturday_percent,

				  SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 0 THEN lil.timespend ELSE 0 END) AS sunday_hours,
				  ROUND(SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 0 THEN lil.timespend ELSE 0 END)*100/SUM(lil.timespend), 2) AS sunday_percent,

				  0 AS time_of_day
                  {$sql_columns2}

				FROM {local_intelliboard_tracking} lit
				  LEFT JOIN {local_intelliboard_logs} lil ON lil.trackid=lit.id {$datefilter}
				  LEFT JOIN (SELECT
								  lit.courseid,
								  (CASE WHEN extract(dow from to_timestamp(lil.timepoint))=0 THEN 6 ELSE extract(dow from to_timestamp(lil.timepoint))-1 END)  AS day,
								  SUM(lil.timespend) AS sum_timespend,
								  ROW_NUMBER () OVER (partition by lit.courseid ORDER BY SUM(lil.timespend) DESC) AS row_number

								FROM {local_intelliboard_tracking} lit
								  LEFT JOIN {local_intelliboard_logs} lil ON lil.trackid=lit.id {$datefilter}
								  LEFT JOIN {course} c ON c.id = lit.courseid
								WHERE lit.courseid>1 AND c.id IS NOT NULL $sql_filter3
								GROUP BY lit.courseid,day
							  ) AS popular_day ON popular_day.courseid=lit.courseid AND popular_day.row_number=1

				  LEFT JOIN (SELECT
								  lit.courseid,
								  CASE WHEN lid.timepoint>=0 AND lid.timepoint<6 THEN 1 ELSE
									CASE WHEN lid.timepoint>=6 AND lid.timepoint<9 THEN 2 ELSE
									  CASE WHEN lid.timepoint>=9 AND lid.timepoint<12 THEN 3 ELSE
										CASE WHEN lid.timepoint>=12 AND lid.timepoint<15 THEN 4 ELSE
										  CASE WHEN lid.timepoint>=15 AND lid.timepoint<18 THEN 5 ELSE
											CASE WHEN lid.timepoint>=18 AND lid.timepoint<21 THEN 6 ELSE
											  CASE WHEN lid.timepoint>=21 AND lid.timepoint<=23 THEN 7 ELSE 0 END END END END END END END AS active_time_of_day,
								  SUM(lid.timespend) AS sum_timespend,
								  ROW_NUMBER () OVER (partition by lit.courseid ORDER BY SUM(lid.timespend) DESC) AS row_number

								FROM {local_intelliboard_tracking} lit
								  LEFT JOIN {local_intelliboard_logs} lil ON lil.trackid=lit.id {$datefilter}
								  LEFT JOIN {local_intelliboard_details} lid ON lid.logid=lil.id
								  LEFT JOIN {course} c ON c.id = lit.courseid
								WHERE lit.courseid>1 AND c.id IS NOT NULL $sql_filter6
								GROUP BY lit.courseid,active_time_of_day
							  ) AS popular_time ON popular_time.courseid=lit.courseid AND popular_time.row_number=1
				  LEFT JOIN {course} c ON c.id = lit.courseid
				  LEFT JOIN {course_categories} cc ON cc.id = c.category
				WHERE lit.courseid>1 AND c.id IS NOT NULL $sql_filter4
				GROUP BY lit.courseid,c.id,cc.id ) AS temp WHERE temp.courseid > 0 $sql_having $sql_order", $params);

        }else{
            return $this->get_report_data("
				SELECT * FROM (SELECT
				  CONCAT(lit.id,lid.id) AS id,
				  c.id AS courseid,
				  c.fullname AS course,
				  cc.name AS category_name,
				  popular_day.day AS most_active_day,
				  popular_time.active_time_of_day AS most_active_time_of_day,

				  SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 0,lil.timespend,0)) AS monday_hours,
				  ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 0,lil.timespend,0))*100/SUM(lil.timespend), 2) AS monday_percent,

				  SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 1,lil.timespend,0)) AS tuesday_hours,
				  ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 1,lil.timespend,0))*100/SUM(lil.timespend), 2) AS tuesday_percent,

				  SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 2,lil.timespend,0)) AS wednesday_hours,
				  ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 2,lil.timespend,0))*100/SUM(lil.timespend), 2) AS wednesday_percent,

				  SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 3,lil.timespend,0)) AS thursday_hours,
				  ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 3,lil.timespend,0))*100/SUM(lil.timespend), 2) AS thursday_percent,

				  SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 4,lil.timespend,0)) AS friday_hours,
				  ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 4,lil.timespend,0))*100/SUM(lil.timespend), 2) AS friday_percent,

				  SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 5,lil.timespend,0)) AS saturday_hours,
				  ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 5,lil.timespend,0))*100/SUM(lil.timespend), 2) AS saturday_percent,

				  SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 6,lil.timespend,0)) AS sunday_hours,
				  ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 6,lil.timespend,0))*100/SUM(lil.timespend), 2) AS sunday_percent,

				  IF(lid.timepoint>=0 && lid.timepoint<6,1,
					IF(lid.timepoint>=6 && lid.timepoint<9,2,
					  IF(lid.timepoint>=9 && lid.timepoint<12,3,
						IF(lid.timepoint>=12 && lid.timepoint<15,4,
						  IF(lid.timepoint>=15 && lid.timepoint<18,5,
							IF(lid.timepoint>=18 && lid.timepoint<21,6,
							  IF(lid.timepoint>=21 && lid.timepoint<=23,7,0))))))) AS time_of_day
                  {$sql_columns1}

				FROM {local_intelliboard_tracking} lit
				  LEFT JOIN {local_intelliboard_logs} lil ON lil.trackid=lit.id {$datefilter}
				  LEFT JOIN {local_intelliboard_details} lid ON lid.logid=lil.id
				  LEFT JOIN (SELECT a.courseid,a.day,a.sum_timespend
							 FROM (
									SELECT
									  lit.courseid,
									  WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) AS day,
									  SUM(lil.timespend) AS sum_timespend

									FROM {local_intelliboard_tracking} lit
									  LEFT JOIN {local_intelliboard_logs} lil ON lil.trackid=lit.id {$datefilter}
									  LEFT JOIN {course} c ON c.id = lit.courseid
									WHERE lit.courseid>1 AND c.id IS NOT NULL $sql_filter
									GROUP BY lit.courseid,day
									HAVING sum_timespend>0
									ORDER BY sum_timespend DESC
								  ) AS a
							 GROUP BY a.courseid) AS popular_day ON popular_day.courseid=lit.courseid
				  LEFT JOIN (SELECT a.courseid,a.active_time_of_day,a.sum_timespend
							 FROM (
									SELECT
									  lit.courseid,
									  IF(lid.timepoint>=0 && lid.timepoint<6,1,
											IF(lid.timepoint>=6 && lid.timepoint<9,2,
											   IF(lid.timepoint>=9 && lid.timepoint<12,3,
												  IF(lid.timepoint>=12 && lid.timepoint<15,4,
													 IF(lid.timepoint>=15 && lid.timepoint<18,5,
														IF(lid.timepoint>=18 && lid.timepoint<21,6,
														   IF(lid.timepoint>=21 && lid.timepoint<=23,7,0))))))) AS active_time_of_day,
										 SUM(lid.timespend) AS sum_timespend

									FROM {local_intelliboard_tracking} lit
									  LEFT JOIN {local_intelliboard_logs} lil ON lil.trackid=lit.id {$datefilter}
									  LEFT JOIN {local_intelliboard_details} lid ON lid.logid=lil.id
									  LEFT JOIN {course} c ON c.id = lit.courseid
									WHERE lit.courseid>1 AND c.id IS NOT NULL $sql_filter5
									GROUP BY lit.courseid,active_time_of_day
									HAVING sum_timespend>0
									ORDER BY sum_timespend DESC
								  ) AS a
							 GROUP BY a.courseid) AS popular_time ON popular_time.courseid=lit.courseid

				  LEFT JOIN {course} c ON c.id = lit.courseid
				  LEFT JOIN {course_categories} cc ON cc.id = c.category

				WHERE lit.courseid>1 AND c.id IS NOT NULL $sql_filter2
				GROUP BY lit.courseid, `time_of_day`
				HAVING `time_of_day`>0

				UNION

				SELECT
				  lit.id,
				  c.id AS courseid,
				  c.fullname AS course,
				  cc.name AS category_name,
				  popular_day.day AS most_active_day,
				  popular_time.active_time_of_day AS most_active_time_of_day,

				  SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 0,lil.timespend,0)) AS monday_hours,
				  ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 0,lil.timespend,0))*100/SUM(lil.timespend), 2) AS monday_percent,

				  SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 1,lil.timespend,0)) AS tuesday_hours,
				  ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 1,lil.timespend,0))*100/SUM(lil.timespend), 2) AS tuesday_percent,

				  SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 2,lil.timespend,0)) AS wednesday_hours,
				  ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 2,lil.timespend,0))*100/SUM(lil.timespend), 2) AS wednesday_percent,

				  SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 3,lil.timespend,0)) AS thursday_hours,
				  ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 3,lil.timespend,0))*100/SUM(lil.timespend), 2) AS thursday_percent,

				  SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 4,lil.timespend,0)) AS friday_hours,
				  ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 4,lil.timespend,0))*100/SUM(lil.timespend), 2) AS friday_percent,

				  SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 5,lil.timespend,0)) AS saturday_hours,
				  ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 5,lil.timespend,0))*100/SUM(lil.timespend), 2) AS saturday_percent,

				  SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 6,lil.timespend,0)) AS sunday_hours,
				  ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 6,lil.timespend,0))*100/SUM(lil.timespend), 2) AS sunday_percent,

				  0 AS time_of_day
                  {$sql_columns2}

				FROM {local_intelliboard_tracking} lit
				  LEFT JOIN {local_intelliboard_logs} lil ON lil.trackid=lit.id {$datefilter}
				  LEFT JOIN (SELECT a.courseid,a.day,a.sum_timespend
							 FROM (
									SELECT
									  lit.courseid,
									  WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) AS day,
									  SUM(lil.timespend) AS sum_timespend

									FROM {local_intelliboard_tracking} lit
									  LEFT JOIN {local_intelliboard_logs} lil ON lil.trackid=lit.id {$datefilter}
									  LEFT JOIN {course} c ON c.id = lit.courseid
									WHERE lit.courseid>1 AND c.id IS NOT NULL $sql_filter3
									GROUP BY lit.courseid,day
									HAVING sum_timespend>0
									ORDER BY sum_timespend DESC
								  ) AS a
							 GROUP BY a.courseid) AS popular_day ON popular_day.courseid=lit.courseid

				  LEFT JOIN (SELECT a.courseid,a.active_time_of_day,a.sum_timespend
							 FROM (
									SELECT
									  lit.courseid,
									  IF(lid.timepoint>=0 && lid.timepoint<6,1,
											IF(lid.timepoint>=6 && lid.timepoint<9,2,
											   IF(lid.timepoint>=9 && lid.timepoint<12,3,
												  IF(lid.timepoint>=12 && lid.timepoint<15,4,
													 IF(lid.timepoint>=15 && lid.timepoint<18,5,
														IF(lid.timepoint>=18 && lid.timepoint<21,6,
														   IF(lid.timepoint>=21 && lid.timepoint<=23,7,0))))))) AS active_time_of_day,
										 SUM(lid.timespend) AS sum_timespend

									FROM {local_intelliboard_tracking} lit
									  LEFT JOIN {local_intelliboard_logs} lil ON lil.trackid=lit.id {$datefilter}
									  LEFT JOIN {local_intelliboard_details} lid ON lid.logid=lil.id
									  LEFT JOIN {course} c ON c.id = lit.courseid
									WHERE lit.courseid>1 AND c.id IS NOT NULL $sql_filter6
									GROUP BY lit.courseid,active_time_of_day
									HAVING sum_timespend>0
									ORDER BY sum_timespend DESC
								  ) AS a
							 GROUP BY a.courseid) AS popular_time ON popular_time.courseid=lit.courseid
				  LEFT JOIN {course} c ON c.id = lit.courseid
				  LEFT JOIN {course_categories} cc ON cc.id = c.category
				WHERE lit.courseid>1 AND c.id IS NOT NULL $sql_filter4
				GROUP BY lit.courseid ) AS temp WHERE temp.courseid > 0 $sql_having $sql_order", $params);
        }
    }

    function report113($params)
    {
        global $CFG;
        $columns = array_merge(array(
            "temp.firstname",
            "temp.lastname",
            "temp.email",
            "temp.idnumber",
            "temp.course",
            "temp.category_name",
            "temp.most_active_day",
            "temp.time_of_day",
            "temp.sunday_hours",
            "temp.sunday_percent",
            "temp.monday_hours",
            "temp.monday_percent",
            "temp.tuesday_hours",
            "temp.tuesday_percent",
            "temp.wednesday_hours",
            "temp.wednesday_percent",
            "temp.thursday_hours",
            "temp.thursday_percent",
            "temp.friday_hours",
            "temp.friday_percent",
            "temp.saturday_hours",
            "temp.saturday_percent",
        ), $this->get_filter_columns($params));
        $datefilter = '';

        if($params->timestart && $params->timefinish) {
            $datefilter = "AND (lil.timepoint BETWEEN {$params->timestart} AND {$params->timefinish})";
        }

        $sql_columns1 = $this->get_columns($params, ["u.id"]);
        $sql_columns2 = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);

        $sql_filter1 = $this->get_teacher_sql($params, ["c.id" => "courses", "u.id" => "users"]);
        $sql_filter1 .= $this->get_filter_in_sql($params->courseid, "lit.courseid");
        $sql_filter1 .= $this->get_filter_in_sql($params->users, "lit.userid");
        $sql_filter1 .= $this->get_filter_user_sql($params, "u.");
        $sql_filter1 .= $this->get_filter_course_sql($params, "c.");

        $sql_filter2 = $this->get_teacher_sql($params, ["c.id" => "courses", "u.id" => "users"]);
        $sql_filter2 .= $this->get_filter_in_sql($params->courseid, "lit.courseid");
        $sql_filter2 .= $this->get_filter_in_sql($params->users, "lit.userid");
        $sql_filter2 .= $this->get_filter_user_sql($params, "u.");
        $sql_filter2 .= $this->get_filter_course_sql($params, "c.");

        $sql_filter3 = $this->get_teacher_sql($params, ["c.id" => "courses", "u.id" => "users"]);
        $sql_filter3 .= $this->get_filter_in_sql($params->courseid, "lit.courseid");
        $sql_filter3 .= $this->get_filter_course_sql($params, "c.");

        $sql_filter4 = $this->get_teacher_sql($params, ["c.id" => "courses", "u.id" => "users"]);
        $sql_filter4 .= $this->get_filter_in_sql($params->courseid, "lit.courseid");
        $sql_filter4 .= $this->get_filter_course_sql($params, "c.");

        $sql_filter5 = $this->get_teacher_sql($params, ["c.id" => "courses", "u.id" => "users"]);
        $sql_filter5 .= $this->get_filter_in_sql($params->courseid, "lit.courseid");
        $sql_filter5 .= $this->get_filter_course_sql($params, "c.");

        $sql_filter6 = $this->get_teacher_sql($params, ["c.id" => "courses", "u.id" => "users"]);
        $sql_filter6 .= $this->get_filter_in_sql($params->courseid, "lit.courseid");
        $sql_filter6 .= $this->get_filter_course_sql($params, "c.");

        if ($CFG->dbtype == 'pgsql') {
            if($params->custom == 1){
                $time_of_day = "CASE WHEN lid.timepoint>=0 AND lid.timepoint<6 THEN 1 ELSE
                                    CASE WHEN lid.timepoint>=6 AND lid.timepoint<12 THEN 2 ELSE
                                        CASE WHEN lid.timepoint>=12 AND lid.timepoint<17 THEN 3 ELSE
                                          CASE WHEN lid.timepoint>=17 AND lid.timepoint<=23 THEN 4 ELSE 0 END END END END";

            }elseif($params->custom == 2){
                $time_of_day = "CASE WHEN lid.timepoint>=0 AND lid.timepoint<6 THEN 1 ELSE
                                    CASE WHEN lid.timepoint>=6 AND lid.timepoint<12 THEN 2 ELSE
                                        CASE WHEN lid.timepoint>=12 AND lid.timepoint<17 THEN 3 ELSE
                                          CASE WHEN lid.timepoint>=17 AND lid.timepoint<=23 THEN 4 ELSE 0 END END END END";
                if(empty($sql_having)){
                    $sql_having = " HAVING temp.time_of_day=0";
                }else{
                    $having_request = str_replace(' HAVING ', '',$sql_having);
                    $sql_having = " HAVING (".$having_request.") AND temp.time_of_day=0";
                }

            }else{
                $time_of_day = "CASE WHEN lid.timepoint>=0 AND lid.timepoint<6 THEN 1 ELSE
                                    CASE WHEN lid.timepoint>=6 AND lid.timepoint<9 THEN 2 ELSE
                                      CASE WHEN lid.timepoint>=9 AND lid.timepoint<12 THEN 3 ELSE
                                        CASE WHEN lid.timepoint>=12 AND lid.timepoint<15 THEN 4 ELSE
                                          CASE WHEN lid.timepoint>=15 AND lid.timepoint<18 THEN 5 ELSE
                                            CASE WHEN lid.timepoint>=18 AND lid.timepoint<21 THEN 6 ELSE
                                              CASE WHEN lid.timepoint>=21 AND lid.timepoint<=23 THEN 7 ELSE 0 END END END END END END END";
            }

            return $this->get_report_data("
				SELECT * FROM (SELECT
				  CAST(CONCAT(MAX(lit.id),MAX(lid.id)) AS bigint) AS id,
				  u.id AS userid,
				  u.firstname,
				  u.lastname,
				  u.email,
				  u.idnumber,
				  c.id AS courseid,
				  c.fullname AS course,
				  cc.name AS category_name,
				  MAX(popular_day.day) AS most_active_day,
				  MAX(popular_time.active_time_of_day) AS most_active_time_of_day,

				  SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 1 THEN lil.timespend ELSE 0 END) AS monday_hours,
				  ROUND(SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 1 THEN lil.timespend ELSE 0 END)*100/SUM(lil.timespend), 2) AS monday_percent,

				  SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 2 THEN lil.timespend ELSE 0 END) AS tuesday_hours,
				  ROUND(SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 2 THEN lil.timespend ELSE 0 END)*100/SUM(lil.timespend), 2) AS tuesday_percent,

				  SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 3 THEN lil.timespend ELSE 0 END) AS wednesday_hours,
				  ROUND(SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 3 THEN lil.timespend ELSE 0 END)*100/SUM(lil.timespend), 2) AS wednesday_percent,

				  SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 4 THEN lil.timespend ELSE 0 END) AS thursday_hours,
				  ROUND(SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 4 THEN lil.timespend ELSE 0 END)*100/SUM(lil.timespend), 2) AS thursday_percent,

				  SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 5 THEN lil.timespend ELSE 0 END) AS friday_hours,
				  ROUND(SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 5 THEN lil.timespend ELSE 0 END)*100/SUM(lil.timespend), 2) AS friday_percent,

				  SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 6 THEN lil.timespend ELSE 0 END) AS saturday_hours,
				  ROUND(SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 6 THEN lil.timespend ELSE 0 END)*100/SUM(lil.timespend), 2) AS saturday_percent,

				  SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 0 THEN lil.timespend ELSE 0 END) AS sunday_hours,
				  ROUND(SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 0 THEN lil.timespend ELSE 0 END)*100/SUM(lil.timespend), 2) AS sunday_percent,

				  $time_of_day AS time_of_day
				  $sql_columns1

				FROM {local_intelliboard_tracking} lit
				  LEFT JOIN {local_intelliboard_logs} lil ON lil.trackid=lit.id {$datefilter}
				  LEFT JOIN {local_intelliboard_details} lid ON lid.logid=lil.id
				  LEFT JOIN (SELECT
								  lit.courseid,
								  lit.userid,
								  (CASE WHEN extract(dow from to_timestamp(lil.timepoint))=0 THEN 6 ELSE extract(dow from to_timestamp(lil.timepoint))-1 END) AS day,
								  SUM(lil.timespend) AS sum_timespend,
								  ROW_NUMBER () OVER (partition by lit.courseid, lit.userid ORDER BY SUM(lil.timespend) DESC) AS row_number

								FROM {local_intelliboard_tracking} lit
								  LEFT JOIN {local_intelliboard_logs} lil ON lil.trackid=lit.id {$datefilter}
								  LEFT JOIN {course} c ON c.id = lit.courseid
								  LEFT JOIN {user} u ON u.id=lit.userid
								WHERE lit.courseid>1 AND c.id IS NOT NULL $sql_filter3
								GROUP BY lit.courseid,day,lit.userid
							) AS popular_day ON popular_day.courseid=lit.courseid AND popular_day.userid=lit.userid AND popular_day.row_number=1

				  LEFT JOIN (SELECT
								 lit.courseid,
								 lit.userid,
								 $time_of_day AS active_time_of_day,
								 SUM(lid.timespend) AS sum_timespend,
								 ROW_NUMBER () OVER (partition by lit.courseid, lit.userid ORDER BY SUM(lid.timespend) DESC) AS row_number

							   FROM {local_intelliboard_tracking} lit
								 LEFT JOIN {local_intelliboard_logs} lil ON lil.trackid=lit.id {$datefilter}
								 LEFT JOIN {local_intelliboard_details} lid ON lid.logid=lil.id
								 LEFT JOIN {course} c ON c.id = lit.courseid
								 LEFT JOIN {user} u ON u.id=lit.userid
							   WHERE lit.courseid>1 AND c.id IS NOT NULL $sql_filter5
							   GROUP BY lit.courseid,active_time_of_day,lit.userid
							) AS popular_time ON popular_time.courseid=lit.courseid AND popular_time.userid=lit.userid AND popular_time.row_number=1

				  LEFT JOIN {course} c ON c.id = lit.courseid
				  LEFT JOIN {course_categories} cc ON cc.id = c.category
				  LEFT JOIN {user} u ON u.id=lit.userid

				WHERE lit.courseid>1 AND c.id IS NOT NULL $sql_filter1
				GROUP BY lit.courseid,
						lit.userid,
						u.id,
						c.id,
						cc.id,
						$time_of_day
				HAVING $time_of_day > 0

				UNION

				SELECT
				  MAX(lit.id) AS id,
				  u.id AS userid,
				  u.firstname,
				  u.lastname,
				  u.email,
				  u.idnumber,
				  c.id AS courseid,
				  c.fullname AS course,
				  cc.name AS category_name,
				  MAX(popular_day.day) AS most_active_day,
				  MAX(popular_time.active_time_of_day) AS most_active_time_of_day,

				  SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 1 THEN lil.timespend ELSE 0 END) AS monday_hours,
				  ROUND(SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 1 THEN lil.timespend ELSE 0 END)*100/SUM(lil.timespend), 2) AS monday_percent,

				  SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 2 THEN lil.timespend ELSE 0 END) AS tuesday_hours,
				  ROUND(SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 2 THEN lil.timespend ELSE 0 END)*100/SUM(lil.timespend), 2) AS tuesday_percent,

				  SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 3 THEN lil.timespend ELSE 0 END) AS wednesday_hours,
				  ROUND(SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 3 THEN lil.timespend ELSE 0 END)*100/SUM(lil.timespend), 2) AS wednesday_percent,

				  SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 4 THEN lil.timespend ELSE 0 END) AS thursday_hours,
				  ROUND(SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 4 THEN lil.timespend ELSE 0 END)*100/SUM(lil.timespend), 2) AS thursday_percent,

				  SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 5 THEN lil.timespend ELSE 0 END) AS friday_hours,
				  ROUND(SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 5 THEN lil.timespend ELSE 0 END)*100/SUM(lil.timespend), 2) AS friday_percent,

				  SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 6 THEN lil.timespend ELSE 0 END) AS saturday_hours,
				  ROUND(SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 6 THEN lil.timespend ELSE 0 END)*100/SUM(lil.timespend), 2) AS saturday_percent,

				  SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 0 THEN lil.timespend ELSE 0 END) AS sunday_hours,
				  ROUND(SUM(CASE WHEN extract(dow from to_timestamp(lil.timepoint)) = 0 THEN lil.timespend ELSE 0 END)*100/SUM(lil.timespend), 2) AS sunday_percent,

				  0 AS time_of_day
				  $sql_columns2

				FROM {local_intelliboard_tracking} lit
				  LEFT JOIN {local_intelliboard_logs} lil ON lil.trackid=lit.id {$datefilter}
				  JOIN {local_intelliboard_details} lid ON lid.logid=lil.id
				  LEFT JOIN (SELECT
								  lit.courseid,
								  lit.userid,
								  (CASE WHEN extract(dow from to_timestamp(lil.timepoint))=0 THEN 6 ELSE extract(dow from to_timestamp(lil.timepoint))-1 END) AS day,
								  SUM(lil.timespend) AS sum_timespend,
								  ROW_NUMBER () OVER (partition by lit.courseid, lit.userid ORDER BY SUM(lil.timespend) DESC) AS row_number

								FROM {local_intelliboard_tracking} lit
								  LEFT JOIN {local_intelliboard_logs} lil ON lil.trackid=lit.id {$datefilter}
								  LEFT JOIN {course} c ON c.id = lit.courseid
								  LEFT JOIN {user} u ON u.id=lit.userid
								WHERE lit.courseid>1 AND c.id IS NOT NULL $sql_filter4
								GROUP BY lit.courseid,day,lit.userid
							) AS popular_day ON popular_day.courseid=lit.courseid AND popular_day.userid=lit.userid AND popular_day.row_number=1

				  LEFT JOIN (SELECT
								 lit.courseid,
								 lit.userid,
								 CASE WHEN lid.timepoint>=0 AND lid.timepoint<6 THEN 1 ELSE
									CASE WHEN lid.timepoint>=6 AND lid.timepoint<9 THEN 2 ELSE
									  CASE WHEN lid.timepoint>=9 AND lid.timepoint<12 THEN 3 ELSE
										CASE WHEN lid.timepoint>=12 AND lid.timepoint<15 THEN 4 ELSE
										  CASE WHEN lid.timepoint>=15 AND lid.timepoint<18 THEN 5 ELSE
											CASE WHEN lid.timepoint>=18 AND lid.timepoint<21 THEN 6 ELSE
											  CASE WHEN lid.timepoint>=21 AND lid.timepoint<=23 THEN 7 ELSE 0 END END END END END END END AS active_time_of_day,
								 SUM(lid.timespend) AS sum_timespend,
								 ROW_NUMBER () OVER (partition by lit.courseid, lit.userid ORDER BY SUM(lid.timespend) DESC) AS row_number

							   FROM {local_intelliboard_tracking} lit
								 LEFT JOIN {local_intelliboard_logs} lil ON lil.trackid=lit.id {$datefilter}
								 LEFT JOIN {local_intelliboard_details} lid ON lid.logid=lil.id
								 LEFT JOIN {course} c ON c.id = lit.courseid
								 LEFT JOIN {user} u ON u.id=lit.userid
							   WHERE lit.courseid>1 AND c.id IS NOT NULL $sql_filter6
							   GROUP BY lit.courseid,active_time_of_day,lit.userid
							) AS popular_time ON popular_time.courseid=lit.courseid AND popular_time.userid=lit.userid AND popular_time.row_number=1

				  LEFT JOIN {course} c ON c.id = lit.courseid
				  LEFT JOIN {course_categories} cc ON cc.id = c.category
				  LEFT JOIN {user} u ON u.id=lit.userid

				WHERE lit.courseid>1 AND c.id IS NOT NULL $sql_filter2
				GROUP BY lit.courseid,lit.userid,c.id,cc.id,u.id ) AS temp WHERE temp.userid > 0 $sql_having $sql_order", $params);
        }else{
            if($params->custom == 1){
                $time_of_day = "IF(lid.timepoint>=0 && lid.timepoint<6,1,
                                    IF(lid.timepoint>=6 && lid.timepoint<12,2,
                                        IF(lid.timepoint>=12 && lid.timepoint<17,3,
                                          IF(lid.timepoint>=17 && lid.timepoint<=23,4,0))))";
            }elseif($params->custom == 2){
                $time_of_day = "IF(lid.timepoint>=0 && lid.timepoint<6,1,
                                    IF(lid.timepoint>=6 && lid.timepoint<12,2,
                                        IF(lid.timepoint>=12 && lid.timepoint<17,3,
                                          IF(lid.timepoint>=17 && lid.timepoint<=23,4,0))))";
                if(empty($sql_having)){
                    $sql_having = " HAVING temp.time_of_day=0";
                }else{
                    $having_request = str_replace(' HAVING ', '',$sql_having);
                    $sql_having = " HAVING (".$having_request.") AND temp.time_of_day=0";
                }
            }else{
                $time_of_day = "IF(lid.timepoint>=0 && lid.timepoint<6,1,
                                    IF(lid.timepoint>=6 && lid.timepoint<9,2,
                                      IF(lid.timepoint>=9 && lid.timepoint<12,3,
                                        IF(lid.timepoint>=12 && lid.timepoint<15,4,
                                          IF(lid.timepoint>=15 && lid.timepoint<18,5,
                                            IF(lid.timepoint>=18 && lid.timepoint<21,6,
                                              IF(lid.timepoint>=21 && lid.timepoint<=23,7,0)))))))";
            }

            return $this->get_report_data("
				SELECT * FROM (SELECT
				  CONCAT(lit.id,lid.id) AS id,
				  u.id AS userid,
				  u.firstname,
				  u.lastname,
				  u.email,
				  u.idnumber,
				  c.id AS courseid,
				  c.fullname AS course,
				  cc.name AS category_name,
				  popular_day.day AS most_active_day,
				  popular_time.active_time_of_day AS most_active_time_of_day,

				  SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 0,lil.timespend,0)) AS monday_hours,
				  ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 0,lil.timespend,0))*100/SUM(lil.timespend), 2) AS monday_percent,

				  SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 1,lil.timespend,0)) AS tuesday_hours,
				  ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 1,lil.timespend,0))*100/SUM(lil.timespend), 2) AS tuesday_percent,

				  SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 2,lil.timespend,0)) AS wednesday_hours,
				  ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 2,lil.timespend,0))*100/SUM(lil.timespend), 2) AS wednesday_percent,

				  SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 3,lil.timespend,0)) AS thursday_hours,
				  ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 3,lil.timespend,0))*100/SUM(lil.timespend), 2) AS thursday_percent,

				  SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 4,lil.timespend,0)) AS friday_hours,
				  ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 4,lil.timespend,0))*100/SUM(lil.timespend), 2) AS friday_percent,

				  SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 5,lil.timespend,0)) AS saturday_hours,
				  ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 5,lil.timespend,0))*100/SUM(lil.timespend), 2) AS saturday_percent,

				  SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 6,lil.timespend,0)) AS sunday_hours,
				  ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 6,lil.timespend,0))*100/SUM(lil.timespend), 2) AS sunday_percent,

				  $time_of_day AS time_of_day
				  $sql_columns1

				FROM {local_intelliboard_tracking} lit
				  LEFT JOIN {local_intelliboard_logs} lil ON lil.trackid=lit.id {$datefilter}
				  LEFT JOIN {local_intelliboard_details} lid ON lid.logid=lil.id
				  LEFT JOIN (SELECT CONCAT(a.courseid,a.userid) AS id ,a.courseid,a.day,a.sum_timespend,a.userid
							  FROM (
								SELECT
								  lit.courseid,
								  lit.userid,
								  WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) AS day,
								  SUM(lil.timespend) AS sum_timespend

								FROM {local_intelliboard_tracking} lit
								  LEFT JOIN {local_intelliboard_logs} lil ON lil.trackid=lit.id {$datefilter}
								  LEFT JOIN {course} c ON c.id = lit.courseid
								  LEFT JOIN {user} u ON u.id=lit.userid
								WHERE lit.courseid>1 AND c.id IS NOT NULL $sql_filter3
								GROUP BY lit.courseid,day,lit.userid
								HAVING sum_timespend>0
								ORDER BY sum_timespend DESC
								   ) AS a
							GROUP BY a.courseid, a.userid) AS popular_day ON popular_day.courseid=lit.courseid AND popular_day.userid=lit.userid

				  LEFT JOIN (SELECT CONCAT(a.courseid,a.userid) AS id ,a.courseid,a.active_time_of_day,a.sum_timespend,a.userid
								FROM (
									   SELECT
										 lit.courseid,
										 lit.userid,
										 $time_of_day AS active_time_of_day,
										 SUM(lid.timespend) AS sum_timespend

									   FROM {local_intelliboard_tracking} lit
										 LEFT JOIN {local_intelliboard_logs} lil ON lil.trackid=lit.id {$datefilter}
										 LEFT JOIN {local_intelliboard_details} lid ON lid.logid=lil.id
										 LEFT JOIN {course} c ON c.id = lit.courseid
										 LEFT JOIN {user} u ON u.id=lit.userid
									   WHERE lit.courseid>1 AND c.id IS NOT NULL $sql_filter5
									   GROUP BY lit.courseid,active_time_of_day,lit.userid
									   HAVING sum_timespend>0
									   ORDER BY sum_timespend DESC
									 ) AS a
								GROUP BY a.courseid, a.userid) AS popular_time ON popular_time.courseid=lit.courseid AND popular_time.userid=lit.userid

				  LEFT JOIN {course} c ON c.id = lit.courseid
				  LEFT JOIN {course_categories} cc ON cc.id = c.category
				  LEFT JOIN {user} u ON u.id=lit.userid

				WHERE lit.courseid>1 AND c.id IS NOT NULL $sql_filter1
				GROUP BY lit.courseid,lit.userid,`time_of_day`
				HAVING `time_of_day`>0

				UNION

				SELECT
				  lit.id,
				  u.id AS userid,
				  u.firstname,
				  u.lastname,
				  u.email,
				  u.idnumber,
				  c.id AS courseid,
				  c.fullname AS course,
				  cc.name AS category_name,
				  popular_day.day AS most_active_day,
				  popular_time.active_time_of_day AS most_active_time_of_day,

				  SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 0,lil.timespend,0)) AS monday_hours,
				  ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 0,lil.timespend,0))*100/SUM(lil.timespend), 2) AS monday_percent,

				  SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 1,lil.timespend,0)) AS tuesday_hours,
				  ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 1,lil.timespend,0))*100/SUM(lil.timespend), 2) AS tuesday_percent,

				  SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 2,lil.timespend,0)) AS wednesday_hours,
				  ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 2,lil.timespend,0))*100/SUM(lil.timespend), 2) AS wednesday_percent,

				  SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 3,lil.timespend,0)) AS thursday_hours,
				  ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 3,lil.timespend,0))*100/SUM(lil.timespend), 2) AS thursday_percent,

				  SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 4,lil.timespend,0)) AS friday_hours,
				  ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 4,lil.timespend,0))*100/SUM(lil.timespend), 2) AS friday_percent,

				  SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 5,lil.timespend,0)) AS saturday_hours,
				  ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 5,lil.timespend,0))*100/SUM(lil.timespend), 2) AS saturday_percent,

				  SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 6,lil.timespend,0)) AS sunday_hours,
				  ROUND(SUM(IF(WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) = 6,lil.timespend,0))*100/SUM(lil.timespend), 2) AS sunday_percent,

				  0 AS time_of_day
				  $sql_columns2

				FROM {local_intelliboard_tracking} lit
				  LEFT JOIN {local_intelliboard_logs} lil ON lil.trackid=lit.id {$datefilter}
				  JOIN {local_intelliboard_details} lid ON lid.logid=lil.id
				  LEFT JOIN (SELECT CONCAT(a.courseid,a.userid) AS id ,a.courseid,a.day,a.sum_timespend,a.userid
							  FROM (
								SELECT
								  lit.courseid,
								  lit.userid,
								  WEEKDAY(FROM_UNIXTIME(lil.timepoint,'%Y-%m-%d %T')) AS day,
								  SUM(lil.timespend) AS sum_timespend

								FROM {local_intelliboard_tracking} lit
								  LEFT JOIN {local_intelliboard_logs} lil ON lil.trackid=lit.id {$datefilter}
								  LEFT JOIN {course} c ON c.id = lit.courseid
								  LEFT JOIN {user} u ON u.id=lit.userid
								WHERE lit.courseid>1 AND c.id IS NOT NULL $sql_filter4
								GROUP BY lit.courseid,day,lit.userid
								HAVING sum_timespend>0
								ORDER BY sum_timespend DESC
								   ) AS a
							GROUP BY a.courseid, a.userid) AS popular_day ON popular_day.courseid=lit.courseid AND popular_day.userid=lit.userid

				  LEFT JOIN (SELECT CONCAT(a.courseid,a.userid) AS id ,a.courseid,a.active_time_of_day,a.sum_timespend,a.userid
								FROM (
									   SELECT
										 lit.courseid,
										 lit.userid,
										 IF(lid.timepoint>=0 && lid.timepoint<6,1,
											IF(lid.timepoint>=6 && lid.timepoint<9,2,
											   IF(lid.timepoint>=9 && lid.timepoint<12,3,
												  IF(lid.timepoint>=12 && lid.timepoint<15,4,
													 IF(lid.timepoint>=15 && lid.timepoint<18,5,
														IF(lid.timepoint>=18 && lid.timepoint<21,6,
														   IF(lid.timepoint>=21 && lid.timepoint<=23,7,0))))))) AS active_time_of_day,
										 SUM(lid.timespend) AS sum_timespend

									   FROM {local_intelliboard_tracking} lit
										 LEFT JOIN {local_intelliboard_logs} lil ON lil.trackid=lit.id {$datefilter}
										 LEFT JOIN {local_intelliboard_details} lid ON lid.logid=lil.id
										 LEFT JOIN {course} c ON c.id = lit.courseid
										 LEFT JOIN {user} u ON u.id=lit.userid
									   WHERE lit.courseid>1 AND c.id IS NOT NULL $sql_filter6
									   GROUP BY lit.courseid,active_time_of_day,lit.userid
									   HAVING sum_timespend>0
									   ORDER BY sum_timespend DESC
									 ) AS a
								GROUP BY a.courseid, a.userid) AS popular_time ON popular_time.courseid=lit.courseid AND popular_time.userid=lit.userid

				  LEFT JOIN {course} c ON c.id = lit.courseid
				  LEFT JOIN {course_categories} cc ON cc.id = c.category
				  LEFT JOIN {user} u ON u.id=lit.userid

				WHERE lit.courseid>1 AND c.id IS NOT NULL $sql_filter2
				GROUP BY lit.courseid,lit.userid ) AS temp WHERE temp.userid > 0 $sql_having $sql_order", $params);
        }
    }

    function report114($params){
        global $DB, $CFG;

        $columns = array_merge(array(
            "course_name",
            "course_created",
            "c.startdate",
            "u.firstname",
            "u.lastname",
            "learners",
            "events",
            "completed_modules",
            "post_per_day",
            "visits",
            "assignment",
            "quiz",
            "workshop",
            "modules",
        ), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filterdate_sql($params, 'c.timecreated');
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_in_sql($params->teacher_roles,'ra.roleid');
        $learner_roles1 = $this->get_filter_in_sql($params->learner_roles,'ras.roleid');
        $learner_roles2 = $this->get_filter_in_sql($params->learner_roles,'ras.roleid');
        $learner_roles3 = $this->get_filter_in_sql($params->learner_roles,'ras.roleid');
        $learner_roles4 = $this->get_filter_in_sql($params->learner_roles,'ras.roleid');
        $sql_cm1 = $this->get_filter_module_sql($params, "cm.");
        $sql_cm2 = $this->get_filter_module_sql($params, "cm.");
        $completion = $this->get_completion($params, "cmc.");

        if ($CFG->dbtype == 'pgsql') {
            $count_days = "date_part('day',age(CURRENT_TIMESTAMP, to_timestamp(c.timecreated)))";
        }else{
            $count_days = 'TO_DAYS(NOW()) - TO_DAYS(FROM_UNIXTIME(c.timecreated))';
        }

        return $this->get_report_data("
            SELECT
                ra.id,
                c.id AS courseid,
                c.fullname AS course_name,
                c.timecreated AS course_created,
                c.startdate,
                u.id AS teacher_id,
                u.lastname,
                u.firstname,
                (SELECT COUNT(distinct ras.userid) FROM {role_assignments} ras, {context} con WHERE con.id = ras.contextid AND con.contextlevel = 50 $learner_roles1 AND con.instanceid = c.id) AS learners,
                (SELECT COUNT(distinct e.id) FROM {event} e, {role_assignments} ras, {context} con WHERE ras.userid = e.userid $learner_roles2 AND con.id = ras.contextid AND con.contextlevel = 50 AND e.courseid = con.instanceid AND con.instanceid = c.id) AS events,
                (SELECT CASE WHEN ($count_days) > 0 THEN (COUNT(DISTINCT fp.id)/($count_days)) ELSE 0 END FROM {role_assignments} ras, {context} con, {forum_discussions} fd, {forum_posts} fp WHERE con.id = ras.contextid $learner_roles3 AND con.contextlevel = 50 AND fd.course = con.instanceid AND fp.userid = ras.userid AND fp.discussion = fd.id AND con.instanceid = c.id) AS post_per_day,
                (SELECT CASE WHEN COUNT(DISTINCT timepoint) > 0 THEN (SUM(lil.visits) / COUNT(DISTINCT timepoint)) ELSE 0 END FROM {role_assignments} ras, {context} con, {local_intelliboard_tracking} lit, {local_intelliboard_logs} lil WHERE con.id = ras.contextid $learner_roles4 AND con.contextlevel = 50 AND con.instanceid = lit.courseid AND ras.userid = lit.userid AND lil.trackid = lit.id AND con.instanceid = c.id) AS visits,
                (SELECT COUNT(DISTINCT cm.id) FROM {course_modules} cm WHERE cm.course = c.id $sql_cm1) AS modules,
                (SELECT COUNT(DISTINCT cmc.id) FROM {course_modules} cm LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id $completion $sql_cm2 WHERE cm.course = c.id) AS completed_modules,
                (SELECT COUNT(id) FROM {assign} WHERE course = c.id) AS assignment,
                (SELECT COUNT(id) FROM {quiz} WHERE course = c.id) AS quiz,
                (SELECT COUNT(id) FROM {workshop} WHERE course = c.id) AS workshop
                $sql_columns
            FROM {role_assignments} ra
                JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50
                JOIN {course} c ON c.id= ctx.instanceid
                JOIN {user} u ON u.id=ra.userid
            WHERE ra.id > 0 $sql_filter $sql_having $sql_order", $params);
    }

    function report114_graph($params){
        global $CFG;
        $sql_filter = $this->get_filter_in_sql($params->courseid, "c.id");
        $teacher_roles = $this->get_filter_in_sql($params->teacher_roles,'ra.roleid');
        $learner_roles = $this->get_filter_in_sql($params->learner_roles,'ra_students.roleid');
        $params->length = -1;
        if ($CFG->dbtype == 'pgsql') {
            $date = "to_char(to_timestamp(e.timestart), 'YYYY-MM-DD')";
        }else{
            $date = "DATE_FORMAT(DATE(FROM_UNIXTIME(e.timestart)), \"%Y-%m-%d\")";
        }

        return $this->get_report_data("
            SELECT
              MAX(e.id) AS id,
              $date AS date,
              COUNT(DISTINCT CASE WHEN e.userid=ra_students.userid THEN e.id ELSE NULL END) AS student_events,
              COUNT(DISTINCT CASE WHEN e.userid=ra.userid THEN e.id ELSE NULL END) AS teacher_events

            FROM {course} c
              LEFT JOIN {context} con ON con.contextlevel = 50 AND con.instanceid = c.id
              LEFT JOIN {role_assignments} ra ON ra.contextid = con.id $teacher_roles
              LEFT JOIN {role_assignments} ra_students ON ra_students.contextid = con.id $learner_roles
              LEFT JOIN {event} e ON e.courseid=c.id AND (e.userid = ra_students.userid OR e.userid=ra.userid)
            WHERE e.id IS NOT NULL $sql_filter
            GROUP BY date
            ORDER BY e.timestart", $params);
    }

    function report115($params){
        global $DB, $PAGE;

        $columns = array_merge(array(
            "teacher_photo",
            "u.firstname",
            "u.lastname",
            "u.email",
            "c.fullname",
            "c.timecreated",
            "enrolled_students",
            "assignments",
            "needs_grading",
            "avg_time_to_grade",
            "logins_by_teacher",
            "teacher_posts",
            "teacher_posts",
            "teacher_events",
            "added_course_module",
            "modified_course_module",
        ), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_in_sql($params->teacher_roles,'ra.roleid');
        $sql_log_timecreated = $this->get_filterdate_sql($params, 'lil.timepoint');
        $sql_fp_created = $this->get_filterdate_sql($params, 'fp.created');
        $sql_fp_created1 = $this->get_filterdate_sql($params, 'fp.created');
        $sql_fp_created2 = $this->get_filterdate_sql($params, 'fp.created');

        $this->params['grade_period'] = strtotime('+7 days');

        $learner_roles1 = $this->get_filter_in_sql($params->learner_roles,'ras.roleid');
        $learner_roles2 = $this->get_filter_in_sql($params->learner_roles,'ras.roleid');
        $learner_roles3 = $this->get_filter_in_sql($params->learner_roles,'ras.roleid');
        $teacher_roles1 = $this->get_filter_in_sql($params->teacher_roles,'ras.roleid');
        $teacher_roles2 = $this->get_filter_in_sql($params->teacher_roles,'ras.roleid');
        $teacher_roles3 = $this->get_filter_in_sql($params->teacher_roles,'ras.roleid');
        $teacher_roles4 = $this->get_filter_in_sql($params->teacher_roles,'ras.roleid');

        $sql_join = $this->get_suspended_sql($params);

        $data = $this->get_report_data("
            SELECT
                  ra.id,
                  c.id AS courseid,
                  c.fullname AS course_name,
                  c.timecreated AS course_created,
                  u.id AS teacher_id,
                  u.lastname,
                  u.firstname,
                  u.email,
                  u.picture AS teacher_picture,
                  u.firstnamephonetic AS teacher_firstnamephonetic,
                  u.lastnamephonetic AS teacher_lastnamephonetic,
                  u.middlename AS teacher_middlename,
                  u.alternatename AS teacher_alternatename,
                  u.imagealt AS teacher_imagealt,
                  (SELECT COUNT(distinct ras.userid) FROM {role_assignments} ras, {context} con WHERE con.id = ras.contextid AND con.contextlevel = 50 $learner_roles1 AND con.instanceid = c.id) AS enrolled_students,
                  (SELECT COUNT(ass.id) FROM {assign} ass WHERE ass.course = c.id) AS assignments,
                  (SELECT COUNT(DISTINCT sub.id)
                   FROM {assign_submission} sub
                     LEFT JOIN {assign_grades} as_grade ON as_grade.userid = sub.userid AND as_grade.assignment = sub.assignment AND as_grade.attemptnumber = sub.attemptnumber
                     JOIN {assign} ass ON ass.id=sub.assignment
                   WHERE sub.status = 'submitted' AND sub.latest=1 AND sub.timecreated > :grade_period AND (as_grade.grade IS NULL OR as_grade.grade<0) AND ass.course=c.id) AS needs_grading,
                  (SELECT AVG(as_grade.timecreated-sub.timecreated)
                   FROM {assign_submission} sub
                     LEFT JOIN {assign_grades} as_grade ON as_grade.userid = sub.userid AND as_grade.assignment = sub.assignment AND as_grade.attemptnumber = sub.attemptnumber
                     JOIN {assign} ass ON ass.id=sub.assignment
                   WHERE sub.status = 'submitted' AND sub.latest=1 AND as_grade.grade IS NOT NULL AND as_grade.grade>=0 AND ass.course=c.id) AS avg_time_to_grade,
                  (SELECT SUM(lil.visits) / COUNT(DISTINCT timepoint) FROM {local_intelliboard_tracking} lit, {local_intelliboard_logs} lil WHERE lil.trackid = lit.id AND lit.courseid = c.id $sql_log_timecreated) AS logins_by_teacher,
                  (SELECT COUNT(DISTINCT fd.id) FROM {forum_discussions} fd, {forum_posts} fp
                  WHERE fp.discussion=fd.id AND fd.course = c.id $sql_fp_created) AS teacher_discussions,
                  (SELECT COUNT(DISTINCT fp.id) FROM {forum_discussions} fd, {forum_posts} fp
                  WHERE fp.discussion = fd.id AND fd.course = c.id $sql_fp_created1) AS teacher_posts,
                  (SELECT COUNT(DISTINCT fp.id) FROM {role_assignments} ras, {context} con, {forum_discussions} fd, {forum_posts} fp
                  WHERE con.id = ras.contextid $learner_roles2 AND con.contextlevel = 50 AND fp.discussion=fd.id AND fd.course=con.instanceid AND con.instanceid = c.id $sql_fp_created2) AS students_posts,
                  (SELECT COUNT(distinct id) FROM {event} WHERE userid = u.id) AS teacher_events,
                  (SELECT COUNT(distinct e.id) FROM {event} e, {role_assignments} ras, {context} con
                  WHERE ras.userid = e.userid $learner_roles3 AND con.id = ras.contextid AND con.contextlevel = 50 AND e.courseid = con.instanceid AND con.instanceid = c.id) AS students_events,
                  (SELECT COUNT(id) FROM {course_modules} WHERE course = c.id) AS added_course_module,
                  '' AS teacher_photo
                  $sql_columns

                FROM {role_assignments} ra
                  JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50
                  JOIN {course} c ON c.id= ctx.instanceid
                  JOIN {user} u ON u.id=ra.userid
                  $sql_join
                WHERE ra.id > 0 $sql_filter $sql_having $sql_order", $params,false);

        foreach($data as &$item){
            $user = new stdClass();
            $user->id = $item->teacher_id;
            $user->lastname = $item->lastname;
            $user->firstname = $item->firstname;
            $user->email = $item->email;
            $user->picture = $item->teacher_picture;
            $user->firstnamephonetic = $item->teacher_firstnamephonetic;
            $user->lastnamephonetic = $item->teacher_lastnamephonetic;
            $user->middlename = $item->teacher_middlename;
            $user->alternatename = $item->teacher_alternatename;
            $user->imagealt = $item->teacher_imagealt;

            $userpicture = new user_picture($user);
            $userpicture->size = 1; // Size f1.
            $item->teacher_photo = $userpicture->get_url($PAGE)->out(false);
        }
        return array('data'=>$data);
    }

    function report116($params){

        $columns = array_merge(array(
            "c.fullname",
            "w.name",
            "cm.added",
            "u.firstname",
            "u.lastname",
            "accessed",
            "log.action",
            "grade"
        ), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $grade_single = intelliboard_grade_sql(false, $params);

        if($params->custom == 1){
            $sql_filter .= $this->get_filterdate_sql($params, 'log.timecreated');
        }else{
            $sql_filter .= $this->get_filterdate_sql($params, 'cm.added');
        }
        $sql_join = "";
        if(!empty($params->custom2)){
            $sql = $this->get_filter_in_sql($params->custom2, "ras.roleid");
            $sql .= $this->get_filter_in_sql($params->courseid, "con.instanceid");

            $sql_join .= " JOIN (SELECT DISTINCT ras.userid, con.instanceid FROM {role_assignments} ras, {context} con WHERE con.id = ras.contextid AND con.contextlevel = 50 $sql GROUP BY ras.userid, con.instanceid) ra ON ra.userid = u.id AND ra.instanceid = c.id";
        }

        $sql_join .= $this->get_suspended_sql($params);

        return $this->get_report_data("
            SELECT
              log.id,
              log.timecreated AS accessed,
              log.userid,
              log.action,
              c.id AS courseid,
              c.fullname AS course_name,
              w.name AS wiki_name,
              cm.added AS timecreated,
              u.firstname,
              u.lastname,
              $grade_single AS grade
              $sql_columns
            FROM {logstore_standard_log} log
                JOIN {course_modules} cm ON cm.id = log.contextinstanceid
                JOIN {wiki} w ON w.id = cm.instance
                JOIN {user} u ON u.id = log.userid
                JOIN {course} c ON c.id = log.courseid
                LEFT JOIN {grade_items} gi ON gi.itemtype = 'course' AND gi.courseid = c.id
                LEFT JOIN {grade_grades} g ON g.userid = u.id AND g.itemid = gi.id AND g.finalgrade IS NOT NULL
                $sql_join
            WHERE log.component='mod_wiki' $sql_filter $sql_having $sql_order", $params);
    }

    function report117($params){

        $columns = array_merge(array(
            "c.fullname",
            "w.name",
            "count_students",
            "avg_time_students",
            "avg_time_teachers",
            "percent_viewed",
            "percent_edited",
            "percent_comment",
            "avg_grade"
        ), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["ra.userid" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $grade_avg = intelliboard_grade_sql(true, $params);
        $teacher_roles = $this->get_filter_in_sql($params->teacher_roles,'ra.roleid', false);
        $learner_roles = $this->get_filter_in_sql($params->learner_roles,'ra.roleid', false);

        return $this->get_report_data("
            SELECT
              w.id,
              c.id AS courseid,
              c.fullname AS course_name,
              w.name AS wiki_name,
              COUNT(DISTINCT CASE WHEN ra.roleid IN(5) THEN ra.userid ELSE NULL END) AS count_students,
              AVG(CASE WHEN $learner_roles THEN lit.timespend ELSE NULL END) AS avg_time_students,
              AVG(CASE WHEN $teacher_roles THEN lit.timespend ELSE NULL END) AS avg_time_teachers,

              ROUND((COUNT( DISTINCT CASE WHEN log.action='created' AND log.target='comment' THEN log.id ELSE NULL END)*100)/COUNT(DISTINCT log.id),2) AS percent_comment,
              ROUND((COUNT( DISTINCT CASE WHEN log.action='viewed' THEN log.id ELSE NULL END)*100)/COUNT(DISTINCT log.id),2) AS percent_viewed,
              ROUND((COUNT( DISTINCT CASE WHEN (log.action='created' AND log.target<>'comment') OR log.action='updated' OR log.action='deleted' THEN log.id ELSE NULL END)*100)/COUNT(DISTINCT log.id),2) AS percent_edited,
              $grade_avg AS avg_grade
              $sql_columns
            FROM {wiki} w
              LEFT JOIN {course} c ON w.course=c.id
              LEFT JOIN {context} con ON con.contextlevel = 50 AND con.instanceid = c.id
              LEFT JOIN {role_assignments} ra ON ra.contextid = con.id

              JOIN {modules} m ON m.name='wiki'
              LEFT JOIN {course_modules} cm ON cm.course=w.course AND cm.instance=w.id AND cm.module=m.id
              LEFT JOIN {local_intelliboard_tracking} lit ON lit.userid=ra.userid AND lit.courseid=c.id AND lit.page='module' AND lit.param=cm.id

              LEFT JOIN {logstore_standard_log} log ON log.courseid=w.course AND log.component='mod_wiki' AND log.contextinstanceid=cm.id

              LEFT JOIN {grade_items} gi ON gi.itemtype = 'course' AND gi.courseid = c.id
              LEFT JOIN {grade_grades} g ON g.userid = ra.userid AND g.itemid = gi.id AND g.finalgrade IS NOT NULL
            WHERE c.id>1 $sql_filter
            GROUP BY w.id,c.id $sql_having $sql_order", $params);
    }

    function report118($params){
        global $CFG;

        if ($CFG->dbtype == 'pgsql') {
            $group_concat = "string_agg(DISTINCT CONCAT(ra.roleid),', ')";
        } else {
            $group_concat = "GROUP_CONCAT(DISTINCT ra.roleid)";
        }

        $columns = array_merge(array(
            "c.fullname",
            "ch.name",
            "lil.timepoint",
            "timeaccess",
            "timespend",
            ["sql_column" => $group_concat, "type" => "rolename"],
            "u.firstname",
            "u.lastname",
            "visits"
        ), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filterdate_sql($params, 'lil.timepoint');

        $data = $this->get_report_data("
            SELECT
              CONCAT(ch.id,u.id, lil.timepoint) AS id,
              c.id AS courseid,
              c.fullname AS course_name,
              ch.name AS chat_name,
              lil.timepoint AS timepoint,
              MIN(log.timecreated) AS timeaccess,
              MAX(lil.timespend) AS timespend,
              $group_concat AS roles,
              u.firstname,
              u.lastname,
              u.id AS userid,
              MAX(lil.visits) AS visits
              $sql_columns

            FROM {chat} ch
              LEFT JOIN {course} c ON ch.course=c.id
              LEFT JOIN {context} con ON con.contextlevel = 50 AND con.instanceid = c.id
              LEFT JOIN {role_assignments} ra ON ra.contextid = con.id
              LEFT JOIN {user} u ON u.id=ra.userid

              JOIN {modules} m ON m.name='chat'
              LEFT JOIN {course_modules} cm ON cm.course=c.id AND cm.module=m.id AND cm.instance=ch.id
              LEFT JOIN {local_intelliboard_tracking} lit ON lit.userid=ra.userid AND lit.param=cm.id AND lit.page='module'
              LEFT JOIN {local_intelliboard_logs} lil ON lil.trackid=lit.id

              LEFT JOIN {logstore_standard_log} log ON log.courseid=c.id AND log.component='mod_chat' AND log.contextinstanceid=cm.id AND log.userid=u.id AND log.timecreated BETWEEN lil.timepoint AND lil.timepoint+86400
            WHERE lil.timepoint IS NOT NULL $sql_filter
            GROUP BY ch.id,c.id,u.id,lil.timepoint $sql_having $sql_order", $params, false);

        $roles = role_fix_names(get_all_roles());
        foreach($data as &$item){
            $user_roles = explode(',', $item->roles);
            $item->roles = array();
            foreach($roles as $role){
                if(in_array($role->id, $user_roles)){
                    $item->roles[] = $role->localname;
                }
            }
            $item->roles = implode(', ', $item->roles);
        }

        return array('data'=>$data);
    }

    function report119($params){

        $columns = array_merge(array(
            "c.fullname",
            "ch.name",
            "lil.timepoint",
            "srudent_timespend",
            "teacher_timespend",
            "students",
            "student_messages",
            "teacher_messages"
        ), $this->get_filter_columns($params, [null]));

        $sql_columns = $this->get_columns($params, [null]);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["ra.userid" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filterdate_sql($params, 'lil.timepoint');

        $teacher_roles1 = $this->get_filter_in_sql($params->teacher_roles,'ra.roleid', false);
        $teacher_roles2 = $this->get_filter_in_sql($params->teacher_roles,'ra.roleid', false);
        $teacher_roles3 = $this->get_filter_in_sql($params->teacher_roles,'ra.roleid', false);
        $learner_roles1 = $this->get_filter_in_sql($params->learner_roles,'ra.roleid', false);
        $learner_roles2 = $this->get_filter_in_sql($params->learner_roles,'ra.roleid', false);
        $learner_roles3 = $this->get_filter_in_sql($params->learner_roles,'ra.roleid', false);
        $learner_roles4 = $this->get_filter_in_sql($params->learner_roles,'ra.roleid', false);

        return $this->get_report_data("
            SELECT
              CONCAT(ch.id,lil.timepoint) AS id,
              c.id AS courseid,
              c.fullname AS course_name,
              ch.name AS chat_name,
              lil.timepoint AS timepoint,
              SUM(DISTINCT CASE WHEN $learner_roles1 THEN lil.timespend + ra.userid ELSE 0 END) - SUM(DISTINCT CASE WHEN $learner_roles2 THEN ra.userid ELSE 0 END) AS srudent_timespend,
              SUM(DISTINCT CASE WHEN $teacher_roles1 THEN lil.timespend + ra.userid ELSE 0 END) - SUM(DISTINCT CASE WHEN $teacher_roles2 THEN ra.userid ELSE 0 END) AS teacher_timespend,
              COUNT(DISTINCT CASE WHEN $learner_roles3 THEN ra.userid ELSE NULL END) AS students,
              COUNT(DISTINCT CASE WHEN $learner_roles4 THEN chm.id ELSE NULL END) AS student_messages,
              COUNT(DISTINCT CASE WHEN $teacher_roles3 THEN chm.id ELSE NULL END) AS teacher_messages
              {$sql_columns}

            FROM {chat} ch
              LEFT JOIN {course} c ON ch.course=c.id
              LEFT JOIN {context} con ON con.contextlevel = 50 AND con.instanceid = c.id
              LEFT JOIN {role_assignments} ra ON ra.contextid = con.id

              JOIN {modules} m ON m.name='chat'
              LEFT JOIN {course_modules} cm ON cm.course=c.id AND cm.module=m.id AND cm.instance=ch.id
              LEFT JOIN {local_intelliboard_tracking} lit ON lit.userid=ra.userid AND lit.param=cm.id AND lit.page='module'
              LEFT JOIN {local_intelliboard_logs} lil ON lil.trackid=lit.id
              LEFT JOIN {chat_messages} chm ON chm.userid=ra.userid AND chm.chatid=ch.id AND chm.timestamp BETWEEN lil.timepoint AND lil.timepoint+86400

            WHERE c.id>1 AND lil.timepoint IS NOT NULL $sql_filter
            GROUP BY ch.id,lil.timepoint,c.id $sql_having $sql_order", $params);
    }

    function report120($params)
    {
        global $CFG;
        $columns = array_merge(array(
            "c.fullname",
            "quizname",
            "que.id",
            "que.name",
            "que.questiontext",
            "answer",
            "grade"), $this->get_filter_columns($params));


        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["c.id" => "courses", "qa.userid" => "users"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_in_sql($params->custom, "q.id");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filterdate_sql($params, "qa.timemodified");

        if ($CFG->dbtype == 'pgsql') {
            $group_concat = "string_agg(DISTINCT(CONCAT(ans.answer,'-spr-',ROUND(ans.fraction, 0))), ', ')";
        } else {
            $group_concat = "GROUP_CONCAT(DISTINCT(CONCAT(ans.answer,'-spr-',ROUND(ans.fraction, 0))))";
        }

        return $this->get_report_data("
            SELECT max(qua.id) AS id,
                c.fullname,
                q.name AS quizname,
                que.id AS queid,
                que.name,
                que.questiontext,
                $group_concat AS answer,
                ROUND(AVG(qas.fraction), 1) AS grade
            FROM {quiz} q
                LEFT JOIN {course} c ON c.id = q.course
                LEFT JOIN {quiz_attempts} qa ON qa.quiz = q.id
                LEFT JOIN {question_attempts} qua ON qua.questionusageid = qa.uniqueid
                LEFT JOIN {question_attempt_steps} qas ON qas.questionattemptid = qua.id AND qas.fraction IS NOT NULL
                LEFT JOIN {question} que ON que.id = qua.questionid
                LEFT JOIN {question_answers} ans ON ans.question = que.id
            WHERE que.id IS NOT NULL $sql_filter GROUP BY que.id, q.name, c.fullname $sql_having $sql_order", $params);
    }


    function report121($params)
    {
        global $DB;

        $columns = array_merge(array("question", "scale", "scale", "scale"), $this->get_filter_columns($params));

        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter1 = $this->get_filter_in_sql($params->custom, "q.id");
        $sql_filter2 = $this->get_filter_in_sql($params->custom, "q.id");
        $sql_filter2 .= $this->get_filter_in_sql($params->users, "qa.userid");

        $sql_filter3 = $this->get_filter_in_sql($params->custom, "qid");
        $sql_filter3 .= $this->get_filter_in_sql($params->users, "userid");

        $data = $this->get_report_data("
            SELECT qq.id, qq.name AS question, ROUND(AVG(((qp.rank + 1)/qq.length) *100),0) AS scale
                FROM
                {questionnaire} q
                JOIN {questionnaire_question} qq ON qq.survey_id = q.id AND qq.deleted = 'n' AND qq.type_id = 8
                JOIN {questionnaire_attempts} qa ON qa.qid = q.id
                JOIN {questionnaire_quest_choice} qc ON qc.question_id = qq.id
                JOIN {questionnaire_response_rank} qp ON qp.response_id = qa.rid AND qp.question_id = qq.id AND qp.choice_id = qc.id
            WHERE q.id > 0 $sql_filter1
            GROUP BY qq.id, qq.name $sql_having $sql_order", $params, false);

        $data2 = $this->get_report_data("
            SELECT qq.id, ROUND(AVG(((qp.rank + 1)/qq.length) *100),0) AS scale
                FROM
                {questionnaire} q
                JOIN {questionnaire_question} qq ON qq.survey_id = q.id AND qq.deleted = 'n' AND qq.type_id = 8
                JOIN {questionnaire_attempts} qa ON qa.qid = q.id
                JOIN {questionnaire_quest_choice} qc ON qc.question_id = qq.id
                JOIN {questionnaire_response_rank} qp ON qp.response_id = qa.rid AND qp.question_id = qq.id AND qp.choice_id = qc.id
            WHERE q.id > 0 $sql_filter2
            GROUP BY qq.id", $params, false);

        $row = $DB->get_record_sql("SELECT MAX(rid) AS attempt FROM {questionnaire_attempts} WHERE id > 0 $sql_filter3",$this->params);
        $attempt = isset($row->attempt) ? intval($row->attempt) : 0;
        $sql_filter2 .= $this->get_filter_in_sql($attempt, "qa.rid");

        $data3 = $this->get_report_data("
            SELECT qq.id, ROUND(AVG(((qp.rank + 1)/qq.length) *100),0) AS scale
                FROM
                {questionnaire} q
                JOIN {questionnaire_question} qq ON qq.survey_id = q.id AND qq.deleted = 'n' AND qq.type_id = 8
                JOIN {questionnaire_attempts} qa ON qa.qid = q.id
                JOIN {user} u ON u.id = qa.userid
                JOIN {questionnaire_quest_choice} qc ON qc.question_id = qq.id
                JOIN {questionnaire_response_rank} qp ON qp.response_id = qa.rid AND qp.question_id = qq.id AND qp.choice_id = qc.id
            WHERE q.id > 0 $sql_filter2
            GROUP BY qq.id", $params, false);

        if ($data) {
            foreach ($data as $key => $value) {
                $data[$key]->scale2 = isset($data2[$key]->scale) ? intval($data2[$key]->scale) : 0;
                $data[$key]->scale3 = isset($data3[$key]->scale) ? intval($data3[$key]->scale) : 0;
            }
        }
        return array('data'=> $data);
    }
    function report122($params)
    {
        $columns = array_merge(array(
            "u.firstname",
            "u.lastname",
            "u.idnumber",
            "u.email",
            "c.shortname",
            "grade1",
            "grade2", ""), $this->get_filter_columns($params));

        if (!$params->custom or !$params->custom2) {
            return array();
        }


        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "ue.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "e.");

        $sql_join = "";
        $sql_filter1 = "";
        $sql_filter2 = "";
        $sql_select1 = "'0' AS grade1,";
        $sql_select2 = "'0' AS grade2,";
        if ($params->custom) {
            $date = explode(",", $params->custom);
            $params->timestart = $date[0];
            $params->timefinish = $date[1];
            if ($params->timestart and $params->timefinish) {
                $sql = $this->get_filterdate_sql($params, "g1.timemodified");
                $grade = intelliboard_grade_sql(true, $params, 'g1.');
                $sql_select1 = "$grade AS grade1,";
                $sql_filter1 = "LEFT JOIN {grade_grades_history} g1 ON g1.userid = u.id AND g1.itemid = i.id AND g1.finalgrade IS NOT NULL $sql";
            }
        }
        if ($params->custom2) {
            $date = explode(",", $params->custom2);
            $params->timestart = $date[0];
            $params->timefinish = $date[1];
            if ($params->timestart and $params->timefinish) {
                $sql = $this->get_filterdate_sql($params, "g2.timemodified");
                $grade = intelliboard_grade_sql(true, $params, 'g2.');
                $sql_select2 = "$grade AS grade2,";
                $sql_filter2 = "LEFT JOIN {grade_grades_history} g2 ON g2.userid = u.id AND g2.itemid = i.id AND g2.finalgrade IS NOT NULL $sql";
            }
        }
        if ($sql_filter1 or $sql_filter2) {
            $sql_join = "JOIN {grade_items} i ON itemtype = 'course' AND i.courseid = c.id";
        }

        return $this->get_report_data("
            SELECT
                MAX(ue.id) AS id,
                $sql_select1
                $sql_select2
                u.id AS userid,
                u.firstname,
                u.lastname,
                u.idnumber,
                u.email,
                c.id AS courseid,
                c.shortname, c.fullname
            FROM {user_enrolments} ue
                JOIN {enrol} e ON e.id = ue.enrolid
                JOIN {course} c ON c.id = e.courseid
                JOIN {user} u ON u.id = ue.userid
                $sql_join
                $sql_filter1
                $sql_filter2
            WHERE u.id > 0 $sql_filter

            GROUP BY c.id, u.id $sql_having $sql_order", $params);
    }
    function report123($params)
    {
        $columns = array_merge(array(
            "u.firstname",
            "u.lastname",
            "u.idnumber",
            "u.email",
            "courses",
            "grade", ""), $this->get_filter_columns($params));

        if (!$params->custom or !$params->custom2) {
            return array();
        }
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "ue.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "e.");

        $sql_join = "";
        $sql_filter1 = "";
        $sql_filter2 = "";
        $sql_select1 = "'0' AS grade1,";
        $sql_select2 = "'0' AS grade2,";
        if ($params->custom) {
            $date = explode(",", $params->custom);
            $params->timestart = $date[0];
            $params->timefinish = $date[1];
            if ($params->timestart and $params->timefinish) {
                $sql = $this->get_filterdate_sql($params, "g1.timemodified");
                $grade = intelliboard_grade_sql(true, $params, 'g1.');
                $sql_select1 = "$grade AS grade1,";
                $sql_filter1 = "JOIN {grade_grades_history} g1 ON g1.userid = u.id AND g1.itemid = i.id AND g1.finalgrade IS NOT NULL $sql";
            }
        }
        if ($params->custom2) {
            $date = explode(",", $params->custom2);
            $params->timestart = $date[0];
            $params->timefinish = $date[1];
            if ($params->timestart and $params->timefinish) {
                $sql = $this->get_filterdate_sql($params, "g2.timemodified");
                $grade = intelliboard_grade_sql(true, $params, 'g2.');
                $sql_select2 = "$grade AS grade2,";
                $sql_filter2 = "JOIN {grade_grades_history} g2 ON g2.userid = u.id AND g2.itemid = i.id AND g2.finalgrade IS NOT NULL $sql";
            }
        }
        if ($sql_filter1 or $sql_filter2) {
            $sql_join = "JOIN {grade_items} i ON itemtype = 'course' AND i.courseid = c.id";
        }

        return $this->get_report_data("
            SELECT
                u.userid,
                u.firstname,
                u.lastname,
                u.idnumber,
                u.email,
                SUM(CASE WHEN (u.grade2 - u.grade1) < 50 THEN 1 ELSE 0 END) AS grade,
                COUNT(u.courseid) AS courses
                FROM (SELECT
                        MAX(ue.id) AS id,
                        $sql_select1
                        $sql_select2
                        u.firstname,
                        u.lastname,
                        u.idnumber,
                        u.email,
                        u.id AS userid,
                        c.id AS courseid
                    FROM {user_enrolments} ue
                        JOIN {enrol} e ON e.id = ue.enrolid
                        JOIN {course} c ON c.id = e.courseid
                        JOIN {user} u ON u.id = ue.userid
                        $sql_join
                        $sql_filter1
                        $sql_filter2
                    WHERE u.id > 0 $sql_filter
                    GROUP BY c.id, u.id) u
            GROUP BY u.userid $sql_having $sql_order", $params);
    }
    function report124($params)
    {
        global $CFG;

        $columns = array_merge(array(
            "u.firstname",
            "u.lastname",
            "u.email",
            "gr.groups",
            "c.fullname",
            "s.name"
        ), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->custom, "s.id");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filterdate_sql($params, "a.time");

        if ($CFG->dbtype == 'pgsql') {
            $group_concat = "string_agg( CONCAT(a.question, '|', a.answer1), ', ')";
            $group_concat2 = "string_agg( DISTINCT g.name, ', ')";
        } else {
            $group_concat = "GROUP_CONCAT(CONCAT(a.question, '|', a.answer1))";
            $group_concat2 = "GROUP_CONCAT(DISTINCT g.name)";
        }

        return $this->get_report_data("
            SELECT
                MAX(a.id) AS id,
                u.firstname,
                u.lastname,
                u.email,
                s.name AS survey,
                c.fullname,
                gr.groups,
                $group_concat AS answers
                $sql_columns
            FROM {survey_answers} a
            JOIN {survey} s ON s.id = a.survey
            JOIN {user} u ON u.id = a.userid
            JOIN {course} c ON c.id = s.course
       LEFT JOIN {modules} m ON m.name = 'survey'
       LEFT JOIN {course_modules} cm ON cm.module = m.id AND cm.course = c.id AND cm.instance = s.id
            LEFT JOIN (SELECT m.userid, g.courseid, $group_concat2 AS groups FROM {groups} g, {groups_members} m WHERE m.groupid = g.id GROUP BY m.userid, g.courseid) gr ON gr.userid = u.id AND gr.courseid = c.id
            WHERE a.id > 0 $sql_filter
            GROUP BY u.id, s.id, c.fullname, gr.groups $sql_having $sql_order", $params);
    }

    function report125($params)
    {
        global $DB, $CFG;

        $responce_user_field = (get_config('mod_questionnaire', 'version')<2017050101)?'username':'userid';
        $responce_survey_id = (get_config('mod_questionnaire', 'version')<2017111101)?'survey_id':'surveyid';
        $responce_questionnaireid = (get_config('mod_questionnaire', 'version')<2018050102)?'survey_id':'questionnaireid';
        $responce_rank = (get_config('mod_questionnaire', 'version')<2018050104)?'rank':'rankvalue';

        $columns = array_merge(array(
            "r.submitted",
            "u.idnumber",
            "u.firstname",
            "u.lastname",
            "u.email",
            "gr.groups",
            "c.fullname",
            "q.questionnairename"
        ), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filterdate_sql($params, "r.submitted");
        $sql = $this->get_filter_in_sql($params->custom, "q.id");

        if ($CFG->dbtype == 'pgsql') {
            $group_concat = "string_agg(DISTINCT CONCAT(g.name), ', ')";
            $answers = "string_agg(
                CONCAT (q.question, 'intelli_sep_q', CASE WHEN q.response_table = 'response_text'
                                                          THEN (SELECT a.response
                                                                  FROM {questionnaire_response_text} a
                                                                 WHERE a.response_id = r.id AND a.question_id = q.question
                                                               )
                                                          ELSE
                                                     CASE WHEN q.response_table = 'response_bool'
                                                          THEN (SELECT a.choice_id
                                                                  FROM {questionnaire_response_bool} a
                                                                 WHERE a.response_id = r.id AND a.question_id = q.question
                                                               )
                                                          ELSE
                                                     CASE WHEN q.response_table = 'resp_single'
                                                          THEN (SELECT h.content
                                                                  FROM {questionnaire_resp_single} a, {questionnaire_quest_choice} h
                                                                 WHERE a.response_id = r.id AND a.question_id = q.question AND
                                                                       h.id = a.choice_id AND h.question_id = q.question
                                                               )
                                                          ELSE
                                                     CASE WHEN q.response_table = 'response_date'
                                                          THEN (SELECT a.response
                                                                  FROM {questionnaire_response_date} a
                                                                 WHERE a.response_id = r.id AND a.question_id = q.question
                                                               )
                                                          ELSE
                                                     CASE WHEN q.response_table = 'response_rank'
                                                          THEN (SELECT string_agg(CONCAT (h.content, ' - ', (a.{$responce_rank} + 1))::character varying, 'intelli_sep_a')
                                                                  FROM {questionnaire_response_rank} a, {questionnaire_quest_choice} h
                                                                 WHERE a.response_id = r.id AND a.question_id = q.question AND h.id = a.choice_id AND
                                                                       h.question_id = q.question
                                                               )
                                                           ELSE
                                                      CASE WHEN q.response_table = 'resp_multiple'
                                                           THEN (SELECT string_agg(h.content::character varying, 'intelli_sep_a')
                                                           FROM {questionnaire_resp_multiple} a, {questionnaire_quest_choice} h
                                                          WHERE a.response_id = r.id AND a.question_id = q.question AND h.id = a.choice_id AND h.question_id = q.question)
                                                           ELSE '-'
                                                            END END END END END END
                        )::character varying, 'intelli_sep_m')";
        } else {
            $DB->execute("SET SESSION group_concat_max_len = 1000000");
            $group_concat = "GROUP_CONCAT(DISTINCT g.name)";
            $answers = "GROUP_CONCAT(
                CONCAT (q.question, 'intelli_sep_q', CASE WHEN q.response_table = 'response_text'
                                                          THEN (SELECT a.response
                                                                  FROM {questionnaire_response_text} a
                                                                 WHERE a.response_id = r.id AND a.question_id = q.question
                                                               )
                                                          ELSE
                                                     CASE WHEN q.response_table = 'response_bool'
                                                          THEN (SELECT a.choice_id
                                                                  FROM {questionnaire_response_bool} a
                                                                 WHERE a.response_id = r.id AND a.question_id = q.question
                                                               )
                                                          ELSE
                                                     CASE WHEN q.response_table = 'response_date'
                                                          THEN (SELECT a.response
                                                                  FROM {questionnaire_response_date} a
                                                                 WHERE a.response_id = r.id AND a.question_id = q.question
                                                               )
                                                          ELSE
                                                     CASE WHEN q.response_table = 'resp_single'
                                                          THEN (SELECT h.content
                                                                  FROM {questionnaire_resp_single} a, {questionnaire_quest_choice} h
                                                                 WHERE a.response_id = r.id AND a.question_id = q.question AND
                                                                       h.id = a.choice_id AND h.question_id = q.question
                                                               )
                                                          ELSE
                                                     CASE WHEN q.response_table = 'response_rank'
                                                          THEN (SELECT GROUP_CONCAT(CONCAT (h.content, ' - ', (a.{$responce_rank} + 1)) SEPARATOR 'intelli_sep_a')
                                                                  FROM {questionnaire_response_rank} a, {questionnaire_quest_choice} h
                                                                 WHERE a.response_id = r.id AND a.question_id = q.question AND h.id = a.choice_id AND
                                                                       h.question_id = q.question
                                                               )
                                                           ELSE
                                                      CASE WHEN q.response_table = 'resp_multiple'
                                                           THEN (SELECT GROUP_CONCAT(h.content SEPARATOR 'intelli_sep_a')
                                                           FROM {questionnaire_resp_multiple} a, {questionnaire_quest_choice} h
                                                          WHERE a.response_id = r.id AND a.question_id = q.question AND h.id = a.choice_id AND h.question_id = q.question)
                                                           ELSE '-'
                                                            END  END END END END END
                        ) SEPARATOR 'intelli_sep_m')";
        }

        return $this->get_report_data(
            "SELECT r.id,
                    u.firstname,
                    u.lastname,
                    u.email,
                    u.idnumber,
                    c.fullname,
                    q.questionnaire,
                    q.questionnairename,
                    gr.groups,
                    r.submitted,
                    {$answers} AS answers
                    {$sql_columns}
               FROM (SELECT q.id AS questionnaire, q.name AS questionnairename, q.course, qq.id AS question, t.has_choices, t.response_table
                       FROM {questionnaire} q
                       JOIN {questionnaire_survey} qs ON qs.id = q.sid
                       JOIN {questionnaire_question} qq ON qs.id = qq.{$responce_survey_id} AND qq.deleted = 'n'
                       JOIN {questionnaire_question_type} t ON qq.type_id = t.typeid
                      WHERE q.id > 0 {$sql}
                   ORDER BY qq.position
                    ) q
          LEFT JOIN {questionnaire_response} r ON r.{$responce_questionnaireid} = q.questionnaire
               JOIN {user} u ON u.id = r.{$responce_user_field}
               JOIN {course} c ON c.id = q.course
          LEFT JOIN {modules} m ON m.name = 'questionnaire'
          LEFT JOIN {course_modules} cm ON cm.module = m.id AND cm.course = c.id AND cm.instance = q.questionnaire
          LEFT JOIN (SELECT m.userid, g.courseid, {$group_concat} AS groups
                       FROM {groups} g, {groups_members} m
                      WHERE m.groupid = g.id
                   GROUP BY m.userid, g.courseid
                    ) gr ON gr.userid = u.id AND gr.courseid = c.id
              WHERE r.complete = 'y' $sql_filter
           GROUP BY u.id, r.id, c.id, q.questionnaire, q.questionnairename, gr.groups, cm.id $sql_having $sql_order", $params);
    }
     public function report126($params)
    {
        $columns = array_merge(array(
            "u.firstname",
            "u.lastname",
            "u.email",
            "c.fullname",
            "e.cost",
            "e.currency",
            "p.payment_status",
            "p.timeupdated"
        ), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid,'c.id');
        $sql_filter .= $this->get_filterdate_sql($params, "p.timeupdated");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "e.");

        return $this->get_report_data("
            SELECT p.id,
                c.fullname,
                p.timeupdated,
                p.payment_status,
                u.firstname,
                u.lastname,
                u.email,
                e.enrol,
                e.cost,
                e.currency
                $sql_columns
            FROM
                {enrol_paypal} p,
                {enrol} e,
                {user} u,
                {course} c
            WHERE e.courseid = c.id AND p.instanceid = e.id AND u.id = p.userid $sql_filter $sql_having $sql_order", $params);
    }
     public function report127($params)
    {
        global $CFG;

        $columns = array_merge( array(
                            "u.firstname",
                            "u.lastname",
                            "u.email",
                            "s.item_name",
                            "courses",
                            "s.amount",
                            "s.payment_status",
                            "s.timeupdated"), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid,'c.id');
        $sql_filter .= $this->get_filterdate_sql($params, "s.timeupdated");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");

        if ($CFG->dbtype == 'pgsql') {
            $group_concat = "string_agg( DISTINCT CONCAT(c.fullname), ', ')";
        } else {
            $group_concat = "GROUP_CONCAT(DISTINCT c.fullname)";
        }

        return $this->get_report_data("
            SELECT s.id,
            s.payment_status,
            s.userid,
            s.item_name,
            s.amount,
            s.timeupdated,
            u.firstname,
            u.lastname,
            u.email,
            $group_concat AS courses
            $sql_columns
            FROM {local_shoppingcart_checkout} s
                JOIN {local_shoppingcart_relations} r ON r.type = 'course' AND r.productid IN (s.items)
                JOIN {course} c ON c.id = r.instanceid
                JOIN {user} u ON u.id = s.userid
            WHERE u.id > 0 $sql_filter
            GROUP BY s.id, u.id $sql_having $sql_order", $params);
    }
    function report128($params)
    {
        global $CFG;
        if($params->custom == 1){
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
            $CFG->debug = 32767;
            $CFG->debugdisplay = true;
        }
        try {
            require($CFG->libdir.'/gradelib.php');

            $columns = array_merge(array(
                "u.firstname",
                "u.lastname",
                "u.idnumber"
            ), $this->get_filter_columns($params));

            $courses = explode(',', $params->courseid);
            $items = array();
            foreach ($courses as $course) {
                $items += (array)grade_item::fetch_all(array('itemtype' => 'category', 'courseid' => $course)) + (array)grade_item::fetch_all(array('itemtype' => 'course', 'courseid' => $course));
            }

            $items_sql_1 = $items_sql_2 = $items_sql_3 = '';
            $items_list = array();
            $unique_items = array();
            foreach ($items as $item) {
                if (!$item) {
                    continue;
                }

                $item_name = "grade_".$item->itemtype."_".$item->id;
                $items_sql_1 .= "gt.".$item_name.", ";
                $items_sql_2 .= "MAX(t.".$item_name.") AS ".$item_name.", ";
                $items_sql_3 .= "CASE WHEN gg.itemid = ".$item->id." THEN gg.finalgrade ELSE NULL END AS ".$item_name.", ";
                $items_list[] = $item->id;
                $unique_items[$item->get_name(true)] = "grade_".$item->itemtype."_".$item->id;
            }
            foreach ($unique_items as $unique_item) {
                $columns[] = $unique_item;
            }
            $columns[] = 'c.fullname';

            $sql_columns = $this->get_columns($params, ["u.id"]);
            $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
            $sql_filter .= $this->get_filter_course_sql($params, "c.");
            $sql_filter .= $this->get_filter_user_sql($params, "u.");
            $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
            $sql_filter .= $this->get_filter_in_sql($params->learner_roles,'ra.roleid');
            $sql_having = $this->get_filter_sql($params, $columns, false);
            $sql_order = $this->get_order_sql($params, $columns);
            $sql_join = $this->get_suspended_sql($params);

            $data = $this->get_report_data("SELECT CONCAT(u.id,c.id) AS uniqueid,
                                  u.id,
                                  u.firstname,
                                  u.lastname,
                                  u.idnumber,
                                  c.id AS courseid,
                                  $items_sql_1
                                  c.fullname
                                  $sql_columns
                                FROM {course} c
                                    JOIN {context} con ON con.contextlevel = 50 AND con.instanceid = c.id
                                    JOIN {role_assignments} ra ON ra.contextid = con.id
                                    JOIN {user} u ON u.id=ra.userid
                                    LEFT JOIN (
                                        SELECT
                                            $items_sql_2
                                            t.userid,
                                            t.courseid
                                        FROM (
                                             SELECT gg.itemid,
                                                    $items_sql_3
                                                    gg.userid,
                                                    gi.courseid
                                             FROM {grade_grades} gg
                                                JOIN {grade_items} gi ON gi.id=gg.itemid
                                             WHERE gg.itemid IN (".implode(',',$items_list).")
                                             ) t
                                        GROUP BY t.userid, t.courseid
                                    ) gt ON gt.userid=u.id AND gt.courseid=c.id
                                  $sql_join
                                WHERE c.id>0 $sql_filter $sql_having $sql_order",$params,false);

            foreach ($data as &$record) {
                $array = $record;
                foreach($array as $item=>$value){
                    $id = (int)str_replace ( array('grade_category_','grade_course_') , '' , $item );
                    if ($id > 0 && !empty($value)) {
                        $grade_grade = grade_grade::fetch(array('itemid'=>$id,'userid'=>$record->id));

                        if(isset($items[$id])){
                            $name = $items[$id]->get_name(true);
                            $record->{$name} = grade_format_gradevalue($value, $items[$id], true, $CFG->grade_displaytype, $CFG->grade_decimalpoints);
                            $record->{$name} .= ' of ' . grade_format_gradevalue($grade_grade->get_grade_max(), $grade_grade->grade_item, true, $CFG->grade_displaytype, $CFG->grade_decimalpoints);
                        }
                    }
                }
            }

            return array('data'=>$data);
        } catch (Exception $e) {
            return array('data'=> array(0=>'Exception: ',  $e->getMessage()));
        }
    }

    function report137($params)
    {
        $columns = array_merge( array(
            "registrar",
            "course",
            "activities_completed",
            "total_activities",
            "module_grade",
            "module_max_grade",
            "program_grade"), $this->get_filter_columns($params));

        if (!$params->custom or !$params->users) {
            return [];
        }
        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, array("c.id"=>"courses"));
        $sql_filter .= $this->get_filter_in_sql($params->courseid,'c.id');
        $sql_filter .= $this->get_filterdate_sql($params, "ue.timecreated");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_in_sql($params->custom,'ra.roleid');
        $sql_filter .= $this->get_filter_in_sql($params->users,'u.id');

        return $this->get_report_data("
            SELECT
              ue.id AS id,
              MIN(u.id) AS userid,
              CONCAT(REG.firstname, ' ', REG.lastname) as registrar,
              c.fullname AS course,
              c.id AS courseid,
              IFNULL((SELECT COUNT(gg.finalgrade) FROM {grade_grades} AS gg
              JOIN {grade_items} AS gi ON gg.itemid = gi.id WHERE gi.courseid = c.id
              AND gg.userid=REG.id AND gi.itemtype='mod' GROUP BY gg.userid,gi.courseid), 0) AS activities_completed,
              IFNULL((SELECT COUNT(gi.itemname) FROM {grade_items} AS gi WHERE gi.courseid = c.id AND gi.itemtype='mod'), 0) AS total_activities,
              IFNULL(gc.grade,0) AS module_grade,
              ROUND(gc_category_max.grademax,0) as module_max_grade,
              MIN(t_category.grade) as program_grade
            FROM {user} u
              JOIN {role_assignments} ra ON ra.userid = u.id
              JOIN {context} ctx ON ctx.id = ra.contextid
              JOIN {role} R on ra.roleid = R.id
              JOIN {user} REG on ctx.instanceid = REG.id
              JOIN {user_enrolments} ue on ue.userid = REG.id
              JOIN {enrol} e ON e.id = ue.enrolid
              JOIN {course} c ON c.id = e.courseid
              LEFT JOIN {course_completions} cc ON cc.course = c.id AND cc.userid = ue.userid
              LEFT JOIN {context} REGctx ON c.id = REGctx.instanceid
              LEFT JOIN {role_assignments} AS REGlra ON REGlra.contextid = REGctx.id and u.id = REGlra.userid AND REGlra.roleid=5
              LEFT JOIN (SELECT course, COUNT(id) AS cmnums FROM {course_modules} WHERE visible = 1 GROUP BY course) AS cm ON cm.course = c.id
              LEFT JOIN (SELECT course, COUNT(id) AS cmnumx FROM {course_modules} WHERE visible = 1 AND completion = 1 GROUP BY course) cmx ON cmx.course = c.id
              LEFT JOIN (SELECT  course, COUNT(id) AS cmnuma FROM {course_modules} WHERE visible = 1 AND module = 1 GROUP BY course) cma ON cma.course = c.id
              LEFT JOIN (SELECT  cm.course,  cmc.userid,  COUNT(cmc.id) AS cmcnums FROM {course_modules} cm, {course_modules_completion} cmc WHERE cmc.coursemoduleid = cm.id AND cm.visible = 1 AND cmc.completionstate = 1 GROUP BY cm.course, cmc.userid) cmc ON cmc.course = c.id AND cmc.userid = REG.id
              LEFT JOIN ( SELECT cm.course,  cmc.userid,  COUNT(cmc.id) AS cmcnuma FROM {course_modules} cm, {course_modules_completion} cmc WHERE  cmc.coursemoduleid = cm.id AND cm.module = 1 AND cm.visible = 1 AND cmc.completionstate = 1 GROUP BY  cm.course, cmc.userid) AS cmca ON cmca.course = c.id AND cmca.userid = REG.id
              LEFT JOIN (SELECT gi.courseid, g.userid, ROUND(((SUM(g.finalgrade) / SUM(g.rawgrademax)) * 100), 2) AS grade
              FROM {grade_items} gi, {grade_grades} g
              WHERE  gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL
              GROUP BY gi.courseid, g.userid) AS gc ON gc.courseid = c.id AND gc.userid = REG.id

              LEFT JOIN (SELECT gi.courseid, SUM(gi.grademax) grademax FROM {grade_items} gi WHERE gi.itemtype = 'course' GROUP BY gi.courseid) AS gc_category_max ON gc_category_max.courseid = c.id

              JOIN (SELECT px.category, g.userid, ROUND(((SUM(g.finalgrade))), 1) AS grade FROM {grade_items} gi, {grade_grades} g, {course} px
              WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND gi.courseid = px.id AND g.finalgrade IS NOT NULL GROUP BY px.category, g.userid) AS t_category ON c.category = t_category.category AND REG.id = t_category.userid

            WHERE ctx.contextlevel = 30 $sql_filter
            GROUP BY REGlra.roleid,REG.email, REG.ID , c.id, c.fullname,e.roleid , ue.id, ue.userid, u.email, cma.cmnuma, c.fullname, u.firstname, u.lastname,
            cmca.cmcnuma, c.id, c.category, gc_category_max.grademax $sql_having $sql_order", $params);
    }

    function report139_header($params)
    {
        global $CFG;
        require_once($CFG->dirroot.'/grade/lib.php');
        $profilefields = grade_helper::get_user_profile_fields($params->courseid);

        $exporttitle = array();
        foreach ($profilefields as $field) {
            $exporttitle[] = ['translate' => true, 'key' => $field->shortname];
        }

        $exporttitle[] = ['translate' => true, 'key' => 'suspended'];

        $grade_items = grade_item::fetch_all(array('courseid'=>$params->courseid));
        foreach ($grade_items as $grade_item) {
            $column = ['parts' => []];

            if ($grade_item->itemtype == 'mod') {
                $column['parts'][] = [
                    'translate' => true,
                    'key' => strtolower(
                        get_string('modulename', $grade_item->itemmodule)
                    )
                ];
                $column['parts'][] = [
                    'translate' => false, 'value' =>$grade_item->get_name()
                ];
                $column['format'] = '%s'.get_string('labelsep', 'langconfig').'%s (%s)';
            } else {
                if($grade_item->is_course_item()) {
                    $column['parts'][] = ['translate' => true, 'key' => 'course'];
                    $column['parts'][] = ['translate' => true, 'key' => 'total'];
                    $column['format'] = '%s %S (%s)';
                } else {
                    $column['parts'][] = [
                        'translate' => false, 'value' => $grade_item->get_name()
                    ];
                    $column['format'] = '%s (%s)';
                }
            }

            $column['parts'][] = ['translate' => true, 'key' => 'real'];
            $exporttitle[] = $column;
            array_pop($column['parts']);

            $column['parts'][] = ['key' => 'percentage', 'translate' => true];
            $exporttitle[] = $column;
            array_pop($column['parts']);

            $column['parts'][] = ['key' => 'feedback', 'translate' => true];
            $exporttitle[] = $column;

        }
        $exporttitle[] = [
            'translate' => true,
            'key' => 'last_downloaded_from_this_course',
        ];

        return array('data'=>$exporttitle,'grade_items'=>$grade_items);
    }

    function report139($params)
    {
        global $CFG,$DB;
        require_once($CFG->dirroot.'/grade/lib.php');
        $grade_items = grade_item::fetch_all(array('courseid'=>$params->courseid));
        $profilefields = grade_helper::get_user_profile_fields($params->courseid);
        $geub = new grade_export_update_buffer();
        $gui = new graded_users_iterator($DB->get_record('course', array('id'=>$params->courseid)),$grade_items);
        $gui->require_active_enrolment(1);
        $gui->allow_user_custom_fields();
        $gui->init();
        $data = array();
        $filtercolumns = explode(',', $params->filter_columns);

        while ($userdata = $gui->next_user()) {
            $user = $userdata->user;

            // search filter (start)
            $allowrow = false;

            foreach ($filtercolumns as $col) {
                if (empty($params->filter)) {
                    $allowrow = true;
                    break;
                }

                if ($col == 0 && (stripos($user->firstname, $params->filter) ==! false || stripos($user->firstname, $params->filter) === 0)) {
                    $allowrow = true;
                }

                if ($col == 1 && (stripos($user->lastname, $params->filter) ==! false || stripos($user->lastname, $params->filter) === 0)) {
                    $allowrow = true;
                }
            }

            if ($filtercolumns && !$allowrow) {
                continue;
            }
            // search filter (end)

            $exportdata = array();

            foreach ($profilefields as $field) {
                $fieldvalue = grade_helper::get_user_field_value($user, $field);
                $exportdata[] = $fieldvalue;
            }
            $issuspended = ($user->suspendedenrolment) ? get_string('yes') : '';
            $exportdata[] = $issuspended;
            foreach ($userdata->grades as $itemid => $grade) {
                $exportdata[] = grade_format_gradevalue($grade->finalgrade,$grade->grade_item,true,1,$CFG->grade_decimalpoints);
                $exportdata[] = grade_format_gradevalue($grade->finalgrade,$grade->grade_item,true,2,$CFG->grade_decimalpoints);
                $exportdata[] = strip_tags(format_text($userdata->feedbacks[$itemid]->feedback, $userdata->feedbacks[$itemid]->feedbackformat));
            }
            // Time exported.
            $exportdata[] = time();
            $data[] = $exportdata;
        }
        $gui->close();
        $geub->close();

        return array('data'=>$data);
    }

    function report140($params)
    {
        global $CFG;
        $columns = array_merge(array(
            "fi.name","avg_value"
        ), $this->get_filter_columns($params, [null, null]));

        $sql_columns = $this->get_columns($params, [null, null]);
        $sql_filter = $this->get_filter_in_sql($params->custom,'f.id');
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter .= $this->get_filterdate_sql($params, "fc.timemodified");

        if ($CFG->dbtype == 'pgsql') {
            $avg_val = 'CAST (fv.value AS numeric)';
        } else {
            $avg_val = 'fv.value';
        }

        return $this->get_report_data("
        	SELECT
              fi.id,
              fi.name,
			  AVG($avg_val) as avg_value
              $sql_columns
			FROM {feedback} f
			  JOIN {feedback_completed} fc ON fc.feedback=f.id
			  JOIN {feedback_item} fi ON fi.feedback=f.id
			  JOIN {feedback_value} fv ON fv.completed=fc.id AND fv.item=fi.id
         LEFT JOIN {modules} m ON m.name = 'feedback'
         LEFT JOIN {course_modules} cm ON cm.module = m.id AND cm.instance = f.id
			WHERE f.id > 0 $sql_filter GROUP BY fi.id $sql_having $sql_order", $params);
    }

    function report141($params)
    {
        global $CFG;
        require_once($CFG->libdir.'/gradelib.php');

        $columns = array_merge( array(
            "u.idnumber",
            "u.username",
            "u.firstname",
            "u.lastname",
            "c.fullname",
            "c.shortname",
            "numtakensessions",
            "points",
            "atd_percent",
            "grade",
            "grade",
            "teacher"
        ), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_filter_in_sql($params->courseid,'c.id');
        $student_filter_roles = $this->get_filter_in_sql($params->learner_roles,'ra.roleid');
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_teacher_sql($params, array("c.id"=>"courses", "u.id"=>"users"));
        $grade_avg = intelliboard_grade_sql(true, $params,'gg.');
        $sql_join = $this->get_suspended_sql($params);

        $sql_teacher_roles = $this->get_filter_in_sql($params->teacher_roles, "ra.roleid");
        if ($CFG->dbtype == 'pgsql') {
            $group_concat = "string_agg( DISTINCT CONCAT(u.firstname,' ',u.lastname), ', ')";
        } else {
            $group_concat = "GROUP_CONCAT(DISTINCT CONCAT(u.firstname,' ',u.lastname) SEPARATOR ', ')";
        }

        if (!$params->custom) {
            $sql_filter .= ' AND rac.userid  IS NOT NULL';
        }

        $data = $this->get_report_data(
            "SELECT CONCAT(u.id,'_',c.id) AS id,
                    u.id AS userid,
                    u.idnumber,
                    u.username,
                    u.firstname,
                    u.lastname,
                    c.id AS course_id,
                    c.fullname AS course_name,
                    c.shortname AS course_shortname,
                    COUNT(DISTINCT atl.id) AS numtakensessions,
                    SUM(stg.grade) AS points,
                    SUM(stm.maxgrade) AS maxpoints,
                    (100 * SUM(stg.grade)) / SUM(stm.maxgrade) AS atd_percent,
                    {$grade_avg} AS grade,
                    (SELECT {$group_concat}
                       FROM {role_assignments} AS ra
                       JOIN {user} u ON ra.userid = u.id
                       JOIN {context} AS ctx ON ctx.id = ra.contextid
                      WHERE ctx.instanceid = c.id AND ctx.contextlevel = 50 {$sql_teacher_roles}
                    ) AS teacher
                    {$sql_columns}
               FROM {attendance} a
               JOIN {course} c ON c.id = a.course
               JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = 50
               JOIN {attendance_sessions} ats ON ats.attendanceid = a.id
               JOIN {attendance_log} atl ON ats.id = atl.sessionid
               JOIN {attendance_statuses} stg ON stg.id = atl.statusid AND stg.deleted = 0 AND
                                                 stg.visible = 1 AND stg.attendanceid = ats.attendanceid
               JOIN {user} u ON atl.studentid = u.id
          LEFT JOIN (SELECT ra.contextid, ra.userid
                       FROM {role_assignments} ra
                      WHERE ra.id > 0 {$student_filter_roles}
                   GROUP BY ra.contextid, ra.userid
                    ) rac ON rac.contextid = ctx.id AND rac.userid = u.id
          LEFT JOIN (SELECT
                              t.attendanceid,
                              t.studentid,
                              SUM(t.maxgrade) AS maxgrade
                            FROM (SELECT
                                    ass2.attendanceid,
                                    atl.studentid,
                                    MAX(ass2.grade) AS maxgrade
                                  FROM {attendance_log} atl
                                         JOIN {attendance_statuses} ass ON ass.id=atl.statusid
                                         JOIN {attendance_statuses} ass2 ON ass2.attendanceid=ass.attendanceid
                                         JOIN {attendance} att ON att.id=ass.attendanceid
                                  WHERE ass2.deleted = 0 AND ass2.visible = 1
                                  GROUP BY ass2.attendanceid,atl.studentid, atl.sessionid) t
                            GROUP BY t.attendanceid,t.studentid) stm ON stm.studentid=u.id AND stm.attendanceid = a.id
               JOIN {grade_items} gi ON gi.courseid = c.id AND gi.itemtype = 'course'
          LEFT JOIN {grade_grades} gg ON gg.itemid=gi.id AND gg.userid = u.id
                    {$sql_join}
              WHERE c.id > 0 {$sql_filter}
           GROUP BY u.id, c.id
                    {$sql_having}
                    {$sql_order}",
            $params, false
        );

        foreach($data AS &$item){
            if(isset($params->scale_real) and $params->scale_real){
                $item->grade_letter = '-';
                continue;
            }
            $context = context_course::instance($item->course_id, IGNORE_MISSING);
            $letters = grade_get_letters($context);

            foreach ($letters as $boundary => $letter) {
                if ($item->grade >= $boundary) {
                    $item->grade_letter = $letter;
                    break;
                }
            }
        }

        return array('data'=>$data);
    }

    function report142($params)
    {
        global $CFG;

        $columns = array_merge(array(
            "u.firstname",
            "u.lastname",
            "u.email",
            "cohorts",
            "u.timecreated",
            "lit.firstaccess",
            "ul.timeaccess",
            "u.lastlogin",
            "c.fullname",
            "course_completion",
            "m.modules",
            "cmc.completed",
            "teacher"
        ), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_filter_in_sql($params->courseid,'c.id');
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "ue.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "e.");
        $sql_filter .= $this->get_filterdate_sql($params, "ue.timecreated");
        $sql_filter .= $this->get_teacher_sql($params, array("c.id"=>"courses", "u.id"=>"users"));
        $sql_teacher_roles = $this->get_filter_in_sql($params->teacher_roles, "ra.roleid");
        $sql_vendor_filter = $this->vendor_filter('ue.userid', 'c.id', $params);
        $sqlfiltermodule = $this->get_filter_module_sql($params, "cm.");

        if($params->custom2 == 6){
            $this->params['lsta'] = strtotime("-90 days");
        }elseif($params->custom2 == 5){
            $this->params['lsta'] = strtotime("-30 days");
        }elseif($params->custom2 == 4){
            $this->params['lsta'] = strtotime("-14 days");
        }elseif($params->custom2 == 3){
            $this->params['lsta'] = strtotime("-7 days");
        }elseif($params->custom2 == 2){
            $this->params['lsta'] = strtotime("-5 days");
        }elseif($params->custom2 == 1){
            $this->params['lsta'] = strtotime("-3 days");
        }else{
            $this->params['lsta'] = strtotime("-1 days");
        }
        $sql_filter .= " AND (ul.timeaccess < :lsta OR ul.timeaccess IS NULL)";
        $completion = $this->get_completion($params, "cmc.");

        // cohort filter
        if ($CFG->dbtype == 'pgsql') {
            $cohorts = "string_agg(DISTINCT coh.name, ', ')";
        } else {
            $cohorts = "GROUP_CONCAT(DISTINCT coh.name SEPARATOR ', ')";
        }
        $sql_cohort_join = " LEFT JOIN (SELECT ch.userid, $cohorts as cohortname
                                          FROM {cohort} coh
                                          JOIN {cohort_members} ch ON ch.cohortid = coh.id
                                      GROUP BY ch.userid) coo ON coo.userid = u.id";
        $cohortfilter = '';
        if ($params->cohortid) {
            $sql_cohort = $this->get_filter_in_sql($params->cohortid, "ch.cohortid");
            $cohortfilter = " JOIN {cohort_members} ch4 ON ch4.userid = u.id " . $this->get_filter_in_sql($params->cohortid, "ch4.cohortid");
        }

        if($params->custom4 == 1) {
            $sql_filter .= ' AND cc1.timecompleted IS NOT NULL';
        } elseif($params->custom4 == 2) {
            $sql_filter .= ' AND cc1.timecompleted IS NULL';
        }

        return $this->get_report_data("
                        SELECT
                          DISTINCT CONCAT(u.id,'_',c.id,'_', MIN(ue.id)) AS id,
                          u.id AS userid,
                          u.firstname,
                          u.lastname,
                          u.email,
                          coo.cohortname AS cohorts,
                          ue.timecreated,
                          u.lastlogin,
                          lit.firstaccess,
                          ul.timeaccess AS lastaccess,
                          m.modules,
                          cmc.completed,
                          cc1.timecompleted as course_completion,
                          c.id AS course_id,
                          c.fullname AS course_name,
                          (SELECT DISTINCT CONCAT(u.firstname,' ',u.lastname)
                                FROM {role_assignments} AS ra
                                    JOIN {user} u ON ra.userid = u.id
                                    JOIN {context} AS ctx ON ctx.id = ra.contextid
                                WHERE ctx.instanceid = c.id AND ctx.contextlevel = 50 $sql_teacher_roles LIMIT 1
                            ) AS teacher
                          $sql_columns

                        FROM {course} c
                          JOIN {enrol} e ON e.courseid=c.id
                          JOIN {user_enrolments} ue ON ue.enrolid=e.id
                          JOIN {user} u ON u.id=ue.userid
                               {$sql_cohort_join}
                               {$cohortfilter}
                          LEFT JOIN {course_completions} cc1 ON cc1.userid = u.id AND cc1.course = c.id
                          LEFT JOIN (SELECT cm.course, COUNT(cm.id) AS modules
                                       FROM {course_modules} cm
                                      WHERE cm.deletioninprogress = 0 {$sqlfiltermodule}
                                   GROUP BY cm.course
                               ) m ON m.course = c.id
                          LEFT JOIN (SELECT cm.course, cmc.userid, COUNT(cmc.id) AS completed FROM {course_modules} cm, {course_modules_completion} cmc WHERE cmc.coursemoduleid = cm.id AND cm.visible  =  1 $completion GROUP BY cm.course, cmc.userid) cmc ON cmc.course = c.id AND cmc.userid = u.id



                          LEFT JOIN {local_intelliboard_tracking} lit ON lit.page='course' AND lit.param=c.id AND lit.userid=u.id
                          LEFT JOIN {user_lastaccess} ul ON ul.courseid = c.id AND ul.userid = u.id

                        WHERE c.id > 0 $sql_filter {$sql_vendor_filter}
                        GROUP BY u.id, c.id, u.firstname, u.lastname, u.email, cohorts, ue.timecreated,
                              u.lastlogin, lit.firstaccess, ul.timeaccess, m.modules, cmc.completed, course_completion, course_id,
                              course_name, teacher {$this->groupAdditionalColumns}
                              $sql_having $sql_order", $params);

    }
    function report143($params)
    {
        global $CFG;

        $columns = array_merge(array(
            "u.firstname","u.lastname","u.email","lit.firstaccess","u.idnumber","c.fullname","c.shortname","grps.groups","ca.name","c.timecreated","enddate","ra.timemodified","cc.timecompleted","grade"
        ), $this->get_filter_columns($params));
        $modules = $this->get_course_modules($params);
        $sql_select = '';
        $grade = intelliboard_grade_sql(false, $params,'gg.');
        foreach($modules['modules'] as $module){
            $completion = $this->get_completion($params, "");

            $module = (object)$module;
            $sql_select .= ", (SELECT timemodified FROM {course_modules_completion} WHERE userid = u.id AND coursemoduleid = $module->id $completion LIMIT 1) AS completed_$module->id";
            $columns[] = "completed_$module->id";
            $sql_select .= ", (SELECT $grade FROM {grade_items} gi, {grade_grades} gg WHERE gg.userid = u.id AND gg.itemid=gi.id AND gi.itemtype='mod' AND gi.iteminstance = $module->instance AND gi.itemmodule = '$module->type' LIMIT 1) AS grade_$module->id";
            $columns[] = "grade_$module->id";
            $sql_select .= ", (SELECT lit.timespend FROM {local_intelliboard_tracking} lit WHERE lit.userid=u.id AND lit.param=$module->id AND lit.page='module' LIMIT 1) AS timespend_$module->id";
            $columns[] = "timespend_$module->id";
        }
        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "ctx.instanceid" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid,'ctx.instanceid');
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_in_sql($params->learner_roles, "ra.roleid");
        $sql_join = $this->get_suspended_sql($params);
        $grade_single = intelliboard_grade_sql(false, $params);

        if($params->custom3 == 1) {
            $sql_filter .= " AND cc.timecompleted IS NOT NULL";
        }

        if($params->custom3 == 2) {
            $sql_filter .= " AND cc.timecompleted IS NULL";
        }
        if ($CFG->dbtype == 'pgsql') {
            $groups = "string_agg(DISTINCT g.name, ', ')";
        } else {
            $groups = "GROUP_CONCAT(DISTINCT g.name SEPARATOR ', ')";
        }

        if ($CFG->version < 2016120509) {
            $sql_columns .= ", '' AS startdate, '' AS enddate";
        } else {
            $sql_columns .= ", c.startdate AS startdate, c.enddate AS enddate";
        }

        $data = $this->get_report_data("
            SELECT
              ra.id,
              ra.userid,
              u.email,
              lit.firstaccess,
              u.username,
              u.idnumber,
              u.firstname,
              u.lastname,
              c.fullname,
              c.shortname,
              grps.groups,
              c.timecreated,
              cc.timecompleted,
              ca.name AS category,
              ra.timemodified AS enrolled,
              $grade_single AS grade
              $sql_select
              $sql_columns
            FROM {role_assignments} ra
              JOIN {context} ctx ON ctx.contextlevel = 50 AND ctx.id = ra.contextid
              JOIN {user} u ON u.id=ra.userid
              JOIN {course} c ON c.id=ctx.instanceid
         LEFT JOIN (SELECT g.courseid, {$groups} as groups
                      FROM {groups} g
                  GROUP BY g.courseid
                   ) as grps ON grps.courseid = c.id
              JOIN {course_categories} ca ON ca.id = c.category
         LEFT JOIN {course_completions} cc ON cc.course = c.id AND cc.userid = u.id
         LEFT JOIN {local_intelliboard_tracking} lit ON lit.page='course' AND lit.param=c.id AND lit.userid=u.id
              JOIN {grade_items} gi ON gi.courseid = c.id AND gi.itemtype = 'course'
         LEFT JOIN {grade_grades} g ON g.itemid=gi.id AND g.userid = u.id
                   $sql_join
            WHERE u.id > 0 $sql_filter $sql_having $sql_order", $params,false);

        return array('modules' => $modules['modules'],
            'data'    => $data);
    }

    public function report144($params) {
        $columns = array_merge(
            array(
                "sessionname",
                "owner",
                "coursename",
                "numberofattendees",
                "numberofactiveattendees"
            ),
            $this->get_filter_columns($params)
        );

        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_having  = $this->get_filter_sql($params, $columns, false);
        $sql_filter  = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filterdate_sql($params, "m.starttime");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "m.courseid", true, true);

        $sql = "SELECT m.id,
                       m.meetingname AS sessionname,
                       m.starttime AS date,
                       ((m.endtime - m.starttime) / 60) AS length,
                       c.visible,
                       u.deleted,
                       u.suspended,
                       u.username,
                       m.courseid,
                       c.fullname AS coursename,
                       CONCAT(u.firstname, ' ', u.lastname) as owner,
                       (SELECT count(id)
                          FROM {local_intelliboard_bbb_atten}
                         WHERE localmeetingid = m.id)
                       AS numberofattendees,
                       (SELECT count(id)
                          FROM {local_intelliboard_bbb_atten}
                          WHERE localmeetingid = m.id AND islisteningonly = 'false')
                       AS numberofactiveattendees
                       {$sql_columns}
                  FROM {local_intelliboard_bbb_meet} AS m
                  JOIN {course} AS c ON c.id = m.courseid
             LEFT JOIN {user} AS u ON u.id = m.ownerid
                 WHERE m.endtime IS NOT NULL {$sql_filter} {$sql_having} {$sql_order}";

        return $this->get_report_data($sql, $this->params);
    }

    public function report145($params) {
        if(!$params->custom) {
            return ['data' => []];
        }

        $columns = array_merge(
            array(
                "coursename",
                "participant",
                "userrole",
                "timespend"
            ),
            $this->get_filter_columns($params)
        );

        $sql_having  = $this->get_filter_sql($params, $columns, false);
        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter  = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filterdate_sql($params, "m.starttime");
        $sql_filter .= $this->get_filter_in_sql($params->custom, 'ba.localmeetingid', true, true);

        $numerictypecast = DBHelper::get_typecast('numeric');

        $sql = "SELECT ba.id,
                       ba.fullname AS participant,
                       ba.role AS userrole,
                       ba.ispresenter,
                       ba.hasjoinedvoice AS voice,
                       ba.hasvideo,
                       c.visible,
                       c.fullname AS coursename,
                       u.deleted,
                       u.suspended,
                       u.username,
                       m.starttime,
                       ba.localmeetingid,
                       ba.islisteningonly AS isactive,
                       ((ba.departuretime - ba.arrivaltime) / 60) AS timespend
                       {$sql_columns}
                  FROM {local_intelliboard_bbb_atten} AS ba
            INNER JOIN {local_intelliboard_bbb_meet} AS m ON m.id = ba.localmeetingid
            INNER JOIN {course} AS c ON c.id = m.courseid
            INNER JOIN {user} AS u ON u.id = ba.userid{$numerictypecast}
                 WHERE ba.id > 0 {$sql_filter} {$sql_having} {$sql_order}";

        return $this->get_report_data($sql, $this->params);
    }


    function report149($params)
    {
        $columns = array_merge( array(
            "u.firstname",
            "u.lastname",
            "c.fullname",
            "t.rawname",
            "rightanswer",
            "wronganswer",
            "u.email",
            "teacher"), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_filter_in_sql($params->courseid,'c.id');
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filterdate_sql($params, "qas.timecreated");
        $sql_filter .= $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_teacher_roles = $this->get_filter_in_sql($params->teacher_roles, "ra.roleid");

        $sql_join = '';
        if($params->cohortid){
            $sql_join = "LEFT JOIN {cohort_members} ch ON ch.userid = u.id";
            $sql_filter .= $this->get_filter_in_sql($params->cohortid, "ch.cohortid");
        }

        return $this->get_report_data("
                        SELECT
                            concat_ws('', t.id, u.id, c.id) as unique_id,
                            t.id AS tag_id,
                            t.rawname AS tag,
                            u.id AS user_id,
                            u.firstname,
                            u.lastname,
                            u.email,
                            c.id AS course_id,
                            c.fullname,

                            (SUM(CASE WHEN qas.state LIKE '%partial' OR qas.state LIKE '%right' THEN 1 ELSE 0 END)/COUNT(qas.id))*100 AS rightanswer,
                            ((COUNT(qas.id) - SUM(CASE WHEN qas.state LIKE '%partial' OR qas.state LIKE '%right' THEN 1 ELSE 0 END))/COUNT(qas.id))*100 AS wronganswer,

                            (SELECT DISTINCT u.email
                                FROM {role_assignments} AS ra
                                    JOIN {user} u ON ra.userid = u.id
                                    JOIN {context} AS ctx ON ctx.id = ra.contextid
                                WHERE ctx.instanceid = c.id AND ctx.contextlevel = 50 $sql_teacher_roles LIMIT 1
                            ) AS teacher
                            $sql_columns

                        FROM {quiz} q
                          LEFT JOIN {quiz_attempts} qa ON qa.quiz=q.id
                          LEFT JOIN {question_attempts} qua ON qua.questionusageid=qa.uniqueid
                          LEFT JOIN {question_attempt_steps} qas ON qas.questionattemptid=qua.id AND qas.fraction IS NOT NULL
                          LEFT JOIN {question} que ON que.id=qua.questionid

                          LEFT JOIN {tag_instance} ti ON ti.itemtype='question' AND ti.itemid=que.id
                          LEFT JOIN {tag} t ON t.id=ti.tagid

                          JOIN {user} u ON u.id=qa.userid
                          JOIN {course} c ON c.id=q.course
                          $sql_join
                        WHERE que.id IS NOT NULL $sql_filter
                        GROUP BY t.id, u.id, c.id $sql_having $sql_order", $params);
    }

    function report150($params)
    {
      global $CFG;

        $columns = array_merge( array(
            "oa.id",
            "oa.userid",
            "oa.mingrade",
            "oa.maxgrade",
            "oa.rawgrade",
            "c.shortname",
            "teacher",
            "outcome_doc_number",
            "outcome_id_number",
            "outcome_description",
            "assignment_name",
            "assignment_id",
            "cm.instance",
            "oa2.component"
        ), $this->get_filter_columns($params, [null]));

        $sql_columns = $this->get_columns($params, [null]);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_filter_in_sql($params->courseid,'c.id');
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_teacher_sql($params, ["c.id" => "courses"]);
        $sql_teacher_roles = $this->get_filter_in_sql($params->teacher_roles, "ra.roleid");

        if ($CFG->dbtype == 'pgsql') {
            $group = "oa.id, c.id, o.id, a.id, oa2.component";
        } else {
            $group = "oa.id";
        }

        return $this->get_report_data("
                        SELECT
                            oa.id AS attempts_id,
                            oa.userid AS user_id,
                            oa.mingrade,
                            oa.maxgrade,
                            oa.rawgrade,
                            c.shortname AS course_shortname,
                            o.docnum AS outcome_doc_number,
                            o.idnumber AS outcome_id_number,
                            o.description AS outcome_description,
                            a.name AS assignment_name,
                            a.id as assignment_id,
                            MAX(cm.instance) AS instance,
                            oa2.component,
                            (SELECT DISTINCT CONCAT(u.firstname,' ',u.lastname)
                                FROM {role_assignments} AS ra
                                    JOIN {user} u ON ra.userid = u.id
                                    JOIN {context} AS ctx ON ctx.id = ra.contextid
                                WHERE ctx.instanceid = c.id AND ctx.contextlevel = 50 $sql_teacher_roles LIMIT 1
                            ) AS teacher
                            {$sql_columns}
                        FROM {outcome_attempts} oa
                          LEFT JOIN {outcome_used_areas} oua ON oua.id = oa.outcomeusedareaid
                          LEFT JOIN {course_modules} cm ON cm.id = oua.cmid
                          LEFT JOIN {course} c ON cm.course = c.id
                          LEFT JOIN {outcome_area_outcomes} oau ON oua.outcomeareaid = oau.outcomeareaid
                          LEFT JOIN {outcome} o ON oau.outcomeid = o.id
                          LEFT JOIN {outcome_areas} oa2 ON oua.outcomeareaid = oa2.id
                          LEFT JOIN {assign} a ON a.id = cm.instance
                        WHERE oa2.component NOT LIKE 'mod_quiz' $sql_filter
                        GROUP BY $group $sql_having $sql_order", $params);
    }

    function report151($params)
    {
        global $CFG;

        $columns = array_merge( array(
            "o.docnum",
            "c.shortname",
            "teacher",
            "a.name",
            "oa.component",
            "o.description"
        ), $this->get_filter_columns($params, [null]));

        $sql_columns = $this->get_columns($params, [null]);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_filter_in_sql($params->courseid,'c.id');
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_teacher_sql($params, ["c.id" => "courses"]);
        $sql_teacher_roles = $this->get_filter_in_sql($params->teacher_roles, "ra.roleid");

        if ($CFG->dbtype == 'pgsql') {
            $group = "c.shortname, o.docnum, a.name, oa.component, o.description";
        } else {
            $group = "c.shortname, o.docnum";
        }

        return $this->get_report_data("
                        SELECT
                            o.docnum AS outcome_doc_number,
                            c.shortname AS course_shortname,
                            a.name AS assignment_name,
                            oa.component,
                            o.description AS outcome_description,
                            (SELECT DISTINCT CONCAT(u.firstname,' ',u.lastname)
                                FROM {role_assignments} AS ra
                                    JOIN {user} u ON ra.userid = u.id
                                    JOIN {context} AS ctx ON ctx.id = ra.contextid
                                WHERE ctx.instanceid = c.id AND ctx.contextlevel = 50 $sql_teacher_roles LIMIT 1
                            ) AS teacher
                            {$sql_columns}
                        FROM {outcome_used_areas} oua
                           LEFT JOIN {course_modules} cm ON cm.id = oua.cmid
                           LEFT JOIN {course} c ON cm.course = c.id
                           LEFT JOIN {outcome_area_outcomes} oau ON oua.outcomeareaid = oau.outcomeareaid
                           LEFT JOIN {outcome} o ON oau.outcomeid = o.id
                           LEFT JOIN {outcome_areas} oa ON oua.outcomeareaid = oa.id
                           LEFT JOIN {assign} a ON a.id = cm.instance
                        WHERE oa.component NOT LIKE '' $sql_filter
                        GROUP BY $group $sql_having $sql_order", $params);
    }
    function report152($params)
    {
        $columns = ["c.fullname", "learners", "learners1", "learners2"];

        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, 'c.id');
        $sql_filter .= $this->get_filter_in_sql($params->learner_roles, "ra.roleid");
        $sql_filter .= $this->get_filterdate_sql($params, "ra.timemodified");
        $sql_vendor_filter = $this->vendor_filter('u.id', 'c.id', $params);

        return $this->get_report_data("
            SELECT c.id,
                c.fullname,
                COUNT(DISTINCT ra.userid) as learners,
                COUNT(DISTINCT t1.userid) as learners1,
                COUNT(DISTINCT t2.userid) as learners2
            FROM {role_assignments} ra
                JOIN {context} AS ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50
                JOIN {course} c ON c.id = ctx.instanceid
                JOIN {user} u ON u.id = ra.userid
                LEFT JOIN {local_intelliboard_tracking} t1 ON t1.page = 'module' AND t1.courseid = c.id AND t1.userid = ra.userid
                LEFT JOIN {local_intelliboard_tracking} t2 ON t2.page = 'course' AND t2.courseid = c.id AND t2.userid = ra.userid AND t1.id IS NULL
            WHERE c.id > 0 $sql_filter {$sql_vendor_filter}
            GROUP BY c.id $sql_having $sql_order", $params);
    }
    function report154($params)
    {
        global $CFG;
        $columns = array_merge(array("la.id", "u.id", "la.timeseen", "c.fullname", "c.shortname", "u.firstname", "u.lastname", "u.email", "u.username", "t.firstname", "t.lastname", "t.email", "t.username", "l.name", "lp.contents", "useranswer", "teacher_feedback"), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, 'c.id');
        $sql_filter .= $this->get_filterdate_sql($params, "la.timeseen");
        $sql_filter .= $this->get_filter_in_sql($params->users, "t.id");
        $sql_filter .= $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_teacher_roles = $this->get_filter_in_sql($params->teacher_roles, "ra.roleid");
        $sql_teacher_roles .= $this->get_filter_in_sql($params->users, "ra.userid");
        $sql_join = $this->get_suspended_sql($params);

        if ($CFG->dbtype == 'pgsql') {
            $sql_select = ",CASE WHEN POSITION(';}' IN la.useranswer)>0 THEN SPLIT_PART(SPLIT_PART(la.useranswer,';',8),':',-1) ELSE la.useranswer END AS useranswer
						   ,CASE WHEN POSITION(';}' IN la.useranswer)>0 THEN SPLIT_PART(SPLIT_PART(la.useranswer,';',12),':',-1) ELSE '' END AS teacher_feedback";
        } else {
            $sql_select = ",CASE WHEN LOCATE(';}',la.useranswer)>0 THEN SUBSTRING_INDEX(SUBSTRING_INDEX(la.useranswer,';',8),':',-1) ELSE la.useranswer END AS useranswer
						   ,CASE WHEN LOCATE(';}',la.useranswer)>0 THEN SUBSTRING_INDEX(SUBSTRING_INDEX(la.useranswer,';',12),':',-1) ELSE '' END AS teacher_feedback";
        }

        return $this->get_report_data("
            SELECT
              CONCAT(la.id,'-',lp.id) AS unique_id,
			  la.id AS id,
              la.timeseen,
              c.id AS courseid,
              c.fullname,
              c.shortname,
              u.id AS userid,
              u.firstname,
              u.lastname,
              u.email,
              u.username,
              t.id AS teacher_userid,
              t.firstname AS teacher_firstname,
              t.lastname As teacher_lastname,
              t.email AS teacher_email,
              t.username AS teacher_username,
              l.name AS lesson_name,
              lp.contents AS question_name
              $sql_select
              $sql_columns
            FROM {lesson} l
              JOIN {lesson_attempts} la ON la.lessonid=l.id
              JOIN {course} c ON c.id=l.course
              JOIN {user} u ON u.id=la.userid
              $sql_join
              JOIN {lesson_pages} lp ON lp.id=la.pageid
              LEFT JOIN (SELECT a.courseid, u.firstname, u.lastname, u.email, u.username, u.id
                         FROM
                           (SELECT ctx.instanceid AS courseid, MIN(ra.userid) AS userid
                             FROM {role_assignments} AS ra
                              JOIN {context} AS ctx ON ctx.id = ra.contextid
                             WHERE ctx.contextlevel = 50 $sql_teacher_roles
                             GROUP BY ctx.instanceid) a
                         JOIN {user} u ON u.id = a.userid
                        ) t ON t.courseid=c.id
            WHERE l.id>0 $sql_filter $sql_having $sql_order", $params);
    }
    function report155($params)
    {
        $columns = array_merge([
            "c.fullname", "completed_learners", "not_completed_learners", "not_accessed_learners", "all_learners"
        ], $this->get_filter_columns($params, [null]));

        $sql_columns = $this->get_columns($params, [null]);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, 'c.id');
        $sql_filter .= $this->get_filter_in_sql($params->learner_roles, "ra.roleid");
        $sql_filter .= $this->get_filterdate_sql($params, "ra.timemodified");
        $sql_vendor_filter = $this->vendor_filter('ra.userid', 'c.id', $params);
        $sql_vendor_filter1 = $this->vendor_filter('cmc.userid', 'cm.course', $params);
        $sql_join = $this->get_suspended_sql($params);

        return $this->get_report_data("
            SELECT c.id,
                   c.fullname,
                   COUNT(DISTINCT ra.userid) as all_learners,
                   COUNT(DISTINCT CASE WHEN comp.userid IS NOT NULL THEN u.id ELSE NULL END) as completed_learners,
                   COUNT(DISTINCT CASE WHEN comp.userid IS NULL AND lit.id IS NOT NULL THEN u.id ELSE NULL END) as not_completed_learners,
                   COUNT(DISTINCT CASE WHEN lit.id IS NULL THEN u.id ELSE NULL END) as not_accessed_learners
                   {$sql_columns}
            FROM {role_assignments} ra
                   JOIN {context} AS ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50
                   JOIN {course} c ON c.id = ctx.instanceid
                   JOIN {user} u ON u.id = ra.userid

                   LEFT JOIN (SELECT cm.course, cmc.userid
                              FROM {course_modules} cm
                                 JOIN  {course_modules_completion} cmc ON cmc.coursemoduleid=cm.id AND cmc.completionstate=1
                             WHERE cmc.id > 0 {$sql_vendor_filter1}
                              GROUP BY cm.course,cmc.userid
                             ) comp ON comp.userid=u.id AND comp.course=c.id

                   LEFT JOIN {local_intelliboard_tracking} lit ON lit.page = 'course' AND lit.param = c.id AND lit.userid = ra.userid
                   $sql_join
            WHERE c.id > 0 $sql_filter {$sql_vendor_filter}
            GROUP BY c.id $sql_having $sql_order", $params);
    }

    function report156($params)
    {
        global $CFG;

        $columns = array_merge(array(
            "c.fullname", "c.shortname", "CONCAT(u.firstname,' ',u.lastname)", "u.email", "gr.groups", "ue.enrol_start_date"
        ), $this->get_filter_columns($params));
        $modules = $this->get_course_modules($params);
        $sql_select = '';
        foreach($modules['modules'] as $module){
            $completion = $this->get_completion($params, "");

            $module = (object)$module;
            $sql_select .= ", (SELECT timemodified FROM {course_modules_completion} WHERE userid = u.id AND coursemoduleid = $module->id $completion) AS completed_$module->id";
            $columns[] = "completed_$module->id";
        }

        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid,'ctx.instanceid');
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filterdate_sql($params, "ra.timemodified");
        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql = $this->get_filter_in_sql($params->learner_roles, "ra.roleid");
        $sql_join = $this->get_suspended_sql($params);
        $enrolfilter = $this->get_filter_enrol_sql($params, 'e1.');
        $enrolfilter .= $this->get_filter_enrol_sql($params, 'ue1.');

        if ($params->custom and $CFG->dbtype != 'pgsql') {
          if (is_numeric($params->custom)) {
            $sql_filter = " AND dd.data = " . intval($params->custom);
          } else {
            $sql_arr = [];
            $list = explode(",", $params->custom);
            foreach ($list as $item) {
              $item = intval($item);
              $sql_arr[] = "FIND_IN_SET($item,dd.data)";
            }
          }
          $sql_filter .= " AND u.id IN (SELECT DISTINCT dd.userid FROM {user_info_field} ff, {user_info_data} dd WHERE ff.datatype = 'vendor' AND dd.fieldid = ff.id AND (".implode(" OR ", $sql_arr) ."))";
        }

        if ($CFG->dbtype == 'pgsql') {
            $group_concat2 = "string_agg( DISTINCT g.name, ', ')";
        } else {
            $group_concat2 = "GROUP_CONCAT(DISTINCT g.name)";
        }

        $data = $this->get_report_data(
            "SELECT ra.id,
                    u.email,
                    u.firstname,
                    u.lastname,
                    CONCAT(u.firstname,' ',u.lastname) AS user_name,
                    c.fullname,
                    c.shortname,
                    gr.groups,
                    ue.enrol_start_date,
                    l.timeaccess AS accessed
                    {$sql_select}
                    {$sql_columns}
               FROM {context} ctx
          LEFT JOIN {role_assignments} ra ON ctx.id = ra.contextid $sql
          LEFT JOIN {user} u ON u.id=ra.userid
          LEFT JOIN {course} c ON c.id=ctx.instanceid
          LEFT JOIN {user_lastaccess} l ON l.courseid = c.id AND l.userid = ra.userid
          LEFT JOIN (SELECT e1.courseid, ue1.userid,
                            CASE WHEN MIN(ue1.timestart) > 0 THEN MIN(ue1.timestart) ELSE MIN(ue1.timecreated) END AS enrol_start_date
                       FROM {enrol} e1
                       JOIN {user_enrolments} ue1 ON ue1.enrolid = e1.id
                      WHERE e1.id > 0 {$enrolfilter}
                   GROUP BY e1.courseid, ue1.userid
                    ) ue ON ue.courseid = c.id AND ue.userid = u.id
          LEFT JOIN (SELECT m.userid, g.courseid, $group_concat2 AS groups
                       FROM {groups} g, {groups_members} m
                      WHERE m.groupid = g.id
                   GROUP BY m.userid, g.courseid
                    ) gr ON gr.userid = u.id AND gr.courseid = c.id
                    {$sql_join}
              WHERE ctx.contextlevel = 50 AND u.id IS NOT NULL AND ue.enrol_start_date IS NOT NULL {$sql_filter}
              {$sql_having}
              {$sql_order}",
            $params,false
        );

        $additional_data = $this->get_report_data("
            SELECT
                   COUNT(DISTINCT ra.userid) as all_learners,
                   COUNT(DISTINCT CASE WHEN comp.userid IS NOT NULL THEN u.id ELSE NULL END) as completed_learners,
                   COUNT(DISTINCT CASE WHEN comp.userid IS NULL AND l.timeaccess IS NOT NULL THEN u.id ELSE NULL END) as not_completed_learners,
                   COUNT(DISTINCT CASE WHEN l.timeaccess IS NULL THEN u.id ELSE NULL END) as not_accessed_learners
            FROM {role_assignments} ra
                   JOIN {context} AS ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50
                   JOIN {course} c ON c.id = ctx.instanceid
                   JOIN {user} u ON u.id = ra.userid

                   LEFT JOIN (SELECT cm.course, cmc.userid
                              FROM {course_modules} cm
                                 JOIN  {course_modules_completion} cmc ON cmc.coursemoduleid=cm.id AND cmc.completionstate=1
                              GROUP BY cm.course,cmc.userid
                             ) comp ON comp.userid=u.id AND comp.course=c.id

                   LEFT JOIN {user_lastaccess} l ON l.courseid = c.id AND l.userid = ra.userid
            WHERE c.id > 0 $sql_filter $sql", $params,false);

        return array('modules' => $modules['modules'],
            'additional_data'    => $additional_data,
            'data'    => $data);
    }

    public function report157($params) {
        $rolesofteacher = get_config('local_intelliboard', 'filter10');
        $rolesofstudent = get_config('local_intelliboard', 'filter11');

        $columns = array_merge(
            array(
                "co.fullname",
            ),
            $this->get_filter_columns($params, [null])
        );

        $sql_columns = $this->get_columns($params, [null]);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_having  = $this->get_filter_sql($params, $columns, false);
        $sql_filter  = $this->get_filterdate_sql($params, "f.timecreated");
        $sql_filter .= $this->get_teacher_sql($params, ["co.id" => "courses", "u.id" => "users"]);
        $sql_filter .= $this->get_filter_course_sql($params, "co.");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "co.id", true, true);

        $sql = "SELECT t.coursename,
                       SUM(
                           CASE WHEN t.video = 1 AND t.roleid IN({$rolesofteacher})THEN 1 ELSE 0 END
                          ) AS countvideo_teacher,
                       SUM(
                           CASE WHEN t.audio = 1 AND t.roleid IN({$rolesofteacher}) THEN 1 ELSE 0 END
                          ) AS countaudio_teacher,
                       SUM(
                           CASE WHEN t.whiteboard = 1 AND t.roleid IN({$rolesofteacher}) THEN 1 ELSE 0 END
                          ) AS countwhiteboard_teacher,
                       SUM(
                           CASE WHEN t.snapshot = 1 AND t.roleid IN({$rolesofteacher}) THEN 1 ELSE 0 END
                          ) AS countsnapshot_teacher,
                       SUM(
                           CASE WHEN t.video = 1 AND t.roleid IN({$rolesofteacher}) THEN t.filesize ELSE 0 END
                          ) AS videos_size_teacher,
                       SUM(
                           CASE WHEN t.audio = 1 AND t.roleid IN({$rolesofteacher}) THEN t.filesize ELSE 0 END
                          ) AS audios_size_teacher,
                       SUM(
                            CASE WHEN t.whiteboard = 1 AND t.roleid IN({$rolesofteacher}) THEN t.filesize ELSE 0 END
                          ) AS whiteboards_size_teacher,
                       SUM(
                            CASE WHEN t.snapshot = 1 AND t.roleid IN({$rolesofteacher}) THEN t.filesize ELSE 0 END
                          ) AS snapshots_size_teacher,
                       SUM(
                           CASE WHEN t.video = 1 AND t.roleid IN({$rolesofstudent}) THEN 1 ELSE 0 END
                          ) AS countvideo_student,
                       SUM(
                           CASE WHEN t.audio = 1 AND t.roleid IN({$rolesofstudent}) THEN 1 ELSE 0 END
                          ) AS countaudio_student,
                       SUM(
                           CASE WHEN t.whiteboard = 1 AND t.roleid IN({$rolesofstudent}) THEN 1 ELSE 0 END
                          ) AS countwhiteboard_student,
                       SUM(
                           CASE WHEN t.snapshot = 1 AND t.roleid IN({$rolesofstudent}) THEN 1 ELSE 0 END
                          ) AS countsnapshot_student,
                       SUM(
                           CASE WHEN t.video = 1 AND t.roleid IN({$rolesofstudent}) THEN t.filesize ELSE 0 END
                          ) AS videos_size_student,
                       SUM(
                           CASE WHEN t.audio = 1 AND t.roleid IN({$rolesofstudent}) THEN t.filesize ELSE 0 END
                          ) AS audios_size_student,
                       SUM(
                           CASE WHEN t.whiteboard = 1 AND t.roleid IN({$rolesofstudent}) THEN t.filesize ELSE 0 END
                          ) AS whiteboards_size_student,
                       SUM(
                           CASE WHEN t.snapshot = 1 AND t.roleid IN({$rolesofstudent}) THEN t.filesize ELSE 0 END
                          ) AS snapshots_size_student
                       {$sql_columns}
                  FROM (SELECT DISTINCT f.contenthash, f.filesize, f.filearea, cm.course, co.id, co.fullname AS coursename,
                               CASE WHEN filename LIKE '%.mp4' THEN 1 ELSE 0 END AS video,
                               CASE WHEN filename LIKE 'poodllfile%.mp3' THEN 1 ELSE 0 END AS audio,
                               CASE WHEN filename LIKE 'upfile_literallycanvas_%.jpg' THEN 1 ELSE 0 END AS whiteboard,
                               CASE WHEN filename LIKE 'upfile_%.jpg' AND filename NOT LIKE 'upfile_literallycanvas_%.jpg'
                                    THEN 1
                                    ELSE 0
                               END AS snapshot,
                               ra.roleid
                          FROM {files} f
                          JOIN {context} c ON c.id = f.contextid
                          JOIN {course_modules} cm ON cm.id = c.instanceid
                          JOIN {user} u ON u.id = f.userid
                          JOIN {course} AS co ON co.id = cm.course
                     LEFT JOIN {context} c1 ON c1.instanceid = co.id AND c1.contextlevel = :coursecontextlevel
                     LEFT JOIN {role_assignments} ra ON ra.contextid = c1.id AND ra.userid = f.userid
                         WHERE f.filearea NOT LIKE 'draft' AND
                               (f.filename LIKE 'poodllfile%.mp3' OR f.filename LIKE '%.mp4' OR filename LIKE 'upfile_%.jpg') AND
                               f.component IN ('assignsubmission_onlinepoodll', 'assignfeedback_poodll', 'question', 'mod_data')
                               {$sql_filter} {$sql_having} {$sql_order}
                       ) t
                  JOIN {course} c ON c.id = t.id
              GROUP BY t.course, t.coursename";
        $this->params['coursecontextlevel'] = CONTEXT_COURSE;

        return $this->get_report_data($sql, $this->params);
    }
    public function report158($params) {
        global $DB;
        // get IDs of modules
        $modules = $DB->get_records_sql_menu(
            "SELECT name, id
                   FROM {modules}
                  WHERE name IN ('assign', 'data', 'quiz')"
        );

        $columns = array_merge(
            array(
                "coursename",
                "activityname",
            ),
            $this->get_filter_columns($params, [null])
        );

        $sql_columns = $this->get_columns($params, [null]);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_having  = $this->get_filter_sql($params, $columns, false);
        $sql_filter  = $this->get_filterdate_sql($params, "f.timecreated");
        $sql_filter .= $this->get_teacher_sql($params, ["co.id" => "courses", "u.id" => "users"]);
        $sql_filter .= $this->get_filter_course_sql($params, "co.");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "co.id", true, true);

        $sql = "SELECT MAX(t.fid) AS id, t.contextid, t.activityname, t.coursename,
                       SUM(CASE WHEN t.video = 1 THEN 1 ELSE 0 END) AS countvideo,
                       SUM(CASE WHEN t.audio = 1 THEN 1 ELSE 0 END) AS countaudio,
                       SUM(CASE WHEN t.whiteboard = 1 THEN 1 ELSE 0 END) AS countwhiteboard,
                       SUM(CASE WHEN t.snapshot = 1 THEN 1 ELSE 0 END) AS countsnapshot,
                       SUM(CASE WHEN t.video = 1 THEN t.filesize ELSE 0 END) AS videos_size,
                       SUM(CASE WHEN t.audio = 1 THEN t.filesize ELSE 0 END) AS audios_size,
                       SUM(CASE WHEN t.whiteboard = 1 THEN t.filesize ELSE 0 END) AS whiteboards_size,
                       SUM(CASE WHEN t.snapshot = 1 THEN t.filesize ELSE 0 END) AS snapshots_size
                       {$sql_columns}
                  FROM (SELECT DISTINCT f.contenthash, f.id as fid, f.filesize, co.id, co.fullname AS coursename, f.contextid,
                               CASE WHEN filename LIKE '%.mp4' THEN 1 ELSE 0 END AS video,
                               CASE WHEN filename LIKE 'poodllfile%.mp3' THEN 1 ELSE 0 END AS audio,
                               CASE WHEN filename LIKE 'upfile_literallycanvas_%.jpg' THEN 1 ELSE 0 END AS whiteboard,
                               CASE WHEN filename LIKE 'upfile_%.jpg' AND filename NOT LIKE 'upfile_literallycanvas_%.jpg' THEN 1 ELSE 0 END AS snapshot,
                               CASE
                                    WHEN a.name IS NOT NULL THEN a.name
                                    WHEN q.name IS NOT NULL THEN q.name
                                    WHEN d.name IS NOT NULL THEN d.name
                               END AS activityname
                          FROM {files} f
                          JOIN {context} c ON c.id = f.contextid
                          JOIN {course_modules} cm ON cm.id = c.instanceid
                          JOIN {user} u ON u.id = f.userid
                          JOIN {course} AS co ON co.id = cm.course
                     LEFT JOIN {assign} a ON a.id = cm.instance AND cm.module = :assignmoduleid
                     LEFT JOIN {quiz} q ON q.id = cm.instance AND cm.module = :quizmoduleid
                     LEFT JOIN {data} d ON d.id = cm.instance AND cm.module = :databasemoduleid
                         WHERE f.filearea NOT LIKE 'draft' AND
                               (f.filename LIKE 'poodllfile%.mp3' OR f.filename LIKE '%.mp4' OR filename LIKE 'upfile_%.jpg') AND
                               f.component NOT IN ('assignsubmission_onlinepoodll', 'assignfeedback_poodll', 'question', 'mod_data')
                               {$sql_filter} {$sql_having} {$sql_order}
                       ) t
                    JOIN {course} c ON c.id = t.id
                GROUP BY coursename, activityname, contextid";
        $this->params['assignmoduleid'] = $modules['assign'];
        $this->params['quizmoduleid'] = $modules['quiz'];
        $this->params['databasemoduleid'] = $modules['data'];

        return $this->get_report_data($sql, $this->params);
    }
    public function report159($params) {
        $columns = array_merge(
            array(
                "co.fullname",
            ),
            $this->get_filter_columns($params, [null])
        );

        $sql_columns = $this->get_columns($params, [null]);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_having  = $this->get_filter_sql($params, $columns, false);
        $sql_filter  = $this->get_filterdate_sql($params, "f.timecreated");
        $sql_filter .= $this->get_teacher_sql($params, ["co.id" => "courses", "u.id" => "users"]);
        $sql_filter .= $this->get_filter_course_sql($params, "co.");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "co.id", true, true);

        $sql = "SELECT t.course, t.coursename,
                       SUM(CASE WHEN t.component = 'assignsubmission_onlinepoodll' THEN 1 ELSE 0 END) AS number_of_occurrences_sub,
                       SUM(CASE WHEN t.component = 'assignfeedback_poodll' THEN 1 ELSE 0 END) AS number_of_occurrences_feedback,
                       SUM(CASE WHEN t.component = 'question' THEN 1 ELSE 0 END) AS number_of_occurrences_question,
                       SUM(CASE WHEN t.component = 'mod_data' THEN 1 ELSE 0 END) AS number_of_occurrences_dbfield
                       {$sql_columns}
                  FROM (SELECT DISTINCT f.contenthash, f.component, co.id AS course, cm.module, cm.instance, co.fullname AS coursename
                          FROM {files} f
                          JOIN {context} c ON c.id = f.contextid
                          JOIN {course_modules} cm ON cm.id = c.instanceid
                          JOIN {user} u ON u.id = f.userid
                          JOIN {course} co ON co.id = cm.course
                         WHERE f.filearea NOT LIKE 'draft' AND
                               (f.filename LIKE 'poodllfile%.mp3' OR f.filename LIKE '%.mp4' OR filename LIKE 'upfile_%.jpg') AND
                               f.component IN ('assignsubmission_onlinepoodll', 'assignfeedback_poodll', 'question', 'mod_data')
                               {$sql_filter} {$sql_having} {$sql_order}
                       ) t
                  JOIN {course} c ON c.id = t.course
              GROUP BY t.course, t.coursename";

        return $this->get_report_data($sql, $this->params);
    }

    function report160($params)
    {
        global $CFG;
        $columns = array_merge(array("u.firstname","u.lastname","u.idnumber","c.shortname","ue.status","ue.timestart","lit.lastaccess","role_name"), $this->get_filter_columns($params));
        $modules = $this->get_course_modules($params);
        $sql_select = '';
        foreach($modules['modules'] as $module){
            $module = (object)$module;
            if($module->type == 'assign'){
                $sql_select .= ", (SELECT MAX(timemodified) FROM {assign_submission} ass WHERE userid = u.id AND assignment = $module->instance AND status='submitted') AS date_$module->id";
                $columns[] = "date_$module->id";
            }elseif($module->type == 'forum'){
                $sql_select .= ", (SELECT MAX(hp.modified) FROM {forum_discussions} hd LEFT JOIN {forum_posts} hp ON hp.discussion=hd.id WHERE hd.forum=$module->instance AND hp.userid=u.id) AS date_$module->id";
                $columns[] = "date_$module->id";
            }elseif($module->type == 'hsuforum'){
                $sql_select .= ", (SELECT MAX(hp.modified) FROM {hsuforum_discussions} hd LEFT JOIN {hsuforum_posts} hp ON hp.discussion=hd.id WHERE hd.forum=$module->instance AND hp.userid=u.id) AS date_$module->id";
                $columns[] = "date_$module->id";
            }elseif($module->type == 'quiz'){
                $sql_select .= ", (SELECT MAX(timefinish) FROM {quiz_attempts} WHERE quiz=$module->instance AND userid=u.id) AS date_$module->id";
                $columns[] = "date_$module->id";
            }
        }
        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid,'ctx.instanceid');
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql = $this->get_filter_in_sql($params->learner_roles, "ra.roleid");

        $data = $this->get_report_data("
            SELECT
                ra.id,
                u.email,
                u.idnumber,
                u.firstname,
                u.lastname,
                c.id AS course_id,
                c.shortname,
                ue.status AS enroll_status,
                ue.timestart AS enroll_started,
                lit.lastaccess,
                (CASE WHEN r.name='' THEN r.shortname ELSE r.name END) AS role_name
                $sql_columns
                $sql_select
            FROM {context} ctx
                JOIN {role_assignments} ra ON ctx.id = ra.contextid $sql
                JOIN {role} r ON r.id=ra.roleid
                JOIN {course} c ON c.id=ctx.instanceid
                JOIN {user} u ON u.id = ra.userid

                JOIN {enrol} e ON e.courseid=c.id
                JOIN {user_enrolments} ue ON ue.userid=u.id AND ue.enrolid=e.id
                LEFT JOIN {local_intelliboard_tracking} lit ON lit.userid=u.id AND lit.page='course' AND lit.param=c.id
            WHERE ctx.contextlevel = 50 AND u.id IS NOT NULL $sql_filter $sql_having $sql_order", $params,false);

        return array('modules' => $modules['modules'],
            'data'    => $data);
    }

    function report161($params)
    {
        global $CFG;
        require_once($CFG->libdir.'/gradelib.php');

        $columns = array_merge( array(
            "u.firstname",
            "u.lastname",
            "u.idnumber",
            "c.shortname",
            "enroll_status",
            "enroll_started",
            "role_name",
            "attendance_name",
            "last_taken",
            "numtakensessions",
            "points",
            "atd_percent"
        ), $this->get_filter_columns($params, ["u.id", "c", "cm1"]));

        $sql_columns = $this->get_columns($params, ["u.id", "c", "cm1"]);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_filter_in_sql($params->courseid,'c.id');
        $sql_filter .= $this->get_filter_in_sql($params->learner_roles,'ra.roleid');
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_teacher_sql($params, ["c.id" => "courses", "u.id" => "users"]);
        $sql_teacher_roles = $this->get_filter_in_sql($params->teacher_roles, "ra.roleid");
        $sql_join = $this->get_suspended_sql($params);

        return $this->get_report_data("
            SELECT
                   DISTINCT CONCAT(u.id,'_',c.id,'_',a.id) AS id,
                   u.id AS userid,
                   u.firstname,
                   u.lastname,
                   u.idnumber,
                   c.id AS course_id,
                   c.shortname AS course_name,
                   MIN(ue.status) AS enroll_status,
                   MIN(ue.timestart) AS enroll_started,
                   (CASE WHEN r.name='' THEN r.shortname ELSE r.name END) AS role_name,
                   a.name AS attendance_name,
                   MAX(atl.timetaken) AS last_taken,
                   COUNT(DISTINCT atl.id) AS numtakensessions,
                   SUM(stg.grade)         AS points,
                   MAX(stm.maxgrade)      AS maxpoints,
                   (100*SUM(stg.grade))/MAX(stm.maxgrade)      AS atd_percent,
                   (SELECT DISTINCT CONCAT(u.firstname,' ',u.lastname)
                                FROM {role_assignments} AS ra
                                    JOIN {user} u ON ra.userid = u.id
                                    JOIN {context} AS ctx ON ctx.id = ra.contextid
                                WHERE ctx.instanceid = c.id AND ctx.contextlevel = 50 $sql_teacher_roles LIMIT 1
                            ) AS teacher
                   $sql_columns

            FROM {course} c
               JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = 50
               JOIN {role_assignments} ra ON ra.contextid = ctx.id
               JOIN {role} r ON r.id=ra.roleid
               JOIN {user} u ON ra.userid = u.id
               JOIN {attendance} a ON a.course=c.id

               JOIN {enrol} e ON e.courseid=c.id
               JOIN {user_enrolments} ue ON ue.userid=u.id AND ue.enrolid=e.id

               LEFT JOIN {modules} m1 ON m1.name = 'attendance'
               LEFT JOIN {course_modules} cm1 ON cm1.module = m1.id AND cm1.course = c.id AND cm1.instance = a.id
               LEFT JOIN {attendance_sessions} ats ON ats.attendanceid=a.id
               LEFT JOIN {attendance_log} atl ON atl.studentid=u.id AND ats.id=atl.sessionid
               LEFT JOIN {attendance_statuses} stg ON stg.id = atl.statusid AND stg.deleted = 0 AND stg.visible = 1 AND stg.attendanceid=ats.attendanceid
               LEFT JOIN (SELECT
                              t.attendanceid,
                              t.studentid,
                              SUM(t.maxgrade) AS maxgrade
                            FROM (SELECT
                                    ass2.attendanceid,
                                    atl.studentid,
                                    MAX(ass2.grade) AS maxgrade
                                  FROM {attendance_log} atl
                                         JOIN {attendance_statuses} ass ON ass.id=atl.statusid
                                         JOIN {attendance_statuses} ass2 ON ass2.attendanceid=ass.attendanceid
                                         JOIN {attendance} att ON att.id=ass.attendanceid
                                  WHERE ass2.deleted = 0 AND ass2.visible = 1
                                  GROUP BY ass2.attendanceid,atl.studentid, atl.sessionid) t
                            GROUP BY t.attendanceid,t.studentid) stm ON stm.studentid=u.id AND stm.attendanceid = a.id
            $sql_join
            WHERE u.id > 0 $sql_filter
            GROUP BY u.id, c.id, a.id, ra.id, ue.id, r.name, r.shortname $sql_having $sql_order", $params);
    }

    public function report162($params) {
        global $DB;
        // get IDs of modules
        $modules = $DB->get_records_sql_menu(
            "SELECT name, id
                   FROM {modules}
                  WHERE name IN ('assign', 'data', 'quiz')"
        );

        $columns = array_merge(
            array(
                "userfullname",
            ),
            $this->get_filter_columns($params)
        );

        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_having  = $this->get_filter_sql($params, $columns, false);
        $sql_filter  = $this->get_filterdate_sql($params, "f.timecreated");
        $sql_filter .= $this->get_teacher_sql($params, ["co.id" => "courses", "u.id" => "users"]);
        $sql_filter .= $this->get_filter_course_sql($params, "co.");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");

        $sql = "SELECT t.contenthash, t.userfullname, t.timecreated, t.coursename, t.activityname, t.mimetype
                  FROM (SELECT DISTINCT f.contenthash, f.filesize, f.timecreated, f.mimetype, co.id,
                               co.fullname AS coursename, concat(u.firstname, ' ', u.lastname) as userfullname,
                               CASE
                                    WHEN a.name IS NOT NULL THEN a.name
                                    WHEN q.name IS NOT NULL THEN q.name
                                    WHEN d.name IS NOT NULL THEN d.name
                               END AS activityname
                               {$sql_columns}
                          FROM {files} f
                          JOIN {context} c ON c.id = f.contextid
                          JOIN {course_modules} cm ON cm.id = c.instanceid
                          JOIN {user} u ON u.id = f.userid
                          JOIN {course} co ON co.id = cm.course
                     LEFT JOIN {assign} a ON a.id = cm.instance AND cm.module = :assignmoduleid
                     LEFT JOIN {quiz} q ON q.id = cm.instance AND cm.module = :quizmoduleid
                     LEFT JOIN {data} d ON d.id = cm.instance AND cm.module = :databasemoduleid
                         WHERE f.filearea NOT LIKE 'draft' AND
                               (f.filename LIKE 'poodllfile%.mp3' OR f.filename LIKE '%.mp4' OR filename LIKE 'upfile_%.jpg') AND
                               f.component IN ('assignsubmission_onlinepoodll', 'assignfeedback_poodll', 'question', 'mod_data') AND
                               f.contextid = :contextidfilter
                               {$sql_filter} {$sql_having} {$sql_order}
                       ) t";
        $this->params['assignmoduleid'] = $modules['assign'];
        $this->params['quizmoduleid'] = $modules['quiz'];
        $this->params['databasemoduleid'] = $modules['data'];
        $this->params['contextidfilter'] = $params->custom;

        return $this->get_report_data($sql, $this->params);
    }

    public function report163($params)
    {
        $columns = array_merge(array(
            "u.username",
            "u.firstname",
            "u.lastname",
            "c.shortname",
            "c.fullname",
            "cc1.name",
            "tcrs.teachers",
            "gi.itemname",
            "grade",
            "grade_percent",
            "wg.firstname",
            "wg.lastname",
            "lit.firstaccess",
            "graduated",
            "ul.timeaccess",
        ), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_in_sql($params->custom, "m.id");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_teacher_sql($params, ["c.id" => "courses", "u.id" => "users"]);
        $grade_single = intelliboard_grade_sql(false, $params);
        $grade_percent = intelliboard_grade_sql(false, $params, 'g.', 0, 'gi.', true);
        $teachersselect = '';
        $teachersfilter = '';
        $teacherroles = get_config('local_intelliboard', 'filter10');

        if($teacherroles) {
            $teachersselect = 'tcrs.teachers as courseteachers,';
            $teachersgroupconcat = get_operator('GROUP_CONCAT', "CONCAT(u.firstname, ' ', u.lastname)", ['separator' => ', ']);
            $teachersfilter = "LEFT JOIN (SELECT cx.instanceid, {$teachersgroupconcat} as teachers
                                            FROM {context} cx
                                            JOIN {role_assignments} ra ON ra.contextid = cx.id AND
                                                                          ra.roleid IN ({$teacherroles})
                                            JOIN {user} u ON u.id = ra.userid
                                           WHERE cx.contextlevel = 50
                                        GROUP BY cx.instanceid
                                         ) tcrs ON tcrs.instanceid = c.id";
        }

        if($params->custom2 == 1) {
            $sql_filter .= $this->get_filterdate_sql($params, "g.timemodified");
        } elseif($params->custom2 == 2) {
            $sql_filter .= $this->get_filterdate_sql($params, "lit.firstaccess");
        }

        return $this->get_report_data("
                SELECT DISTINCT concat_ws('_', cm.id, u.id, c.id) AS unique_id,
                       gi.itemname AS activity_name,
                       g.userid,
                       u.username,
                       u.firstname,
                       u.lastname,
                       c.fullname,
                       cc1.name AS course_category,
                       c.shortname,
                       {$teachersselect}
                       g.timemodified AS graduated,
                       {$grade_single} AS grade,
                       {$grade_percent} AS grade_percent,
                       wg.firstname AS who_graded_first_name,
                       wg.lastname AS who_graded_last_name,
                       gi.itemtype,
                       CASE WHEN m.name='assign' THEN sg.timemodified ELSE 0 END AS need_grade_assignment,
                       lit.firstaccess,
                       ul.timeaccess AS course_lastaccess
                       {$sql_columns}

                FROM {course} c
           LEFT JOIN {course_categories} cc1 ON cc1.id = c.category
                     {$teachersfilter}
                JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = 50
                JOIN {role_assignments} ra ON ra.contextid = ctx.id
                JOIN {user} u ON u.id = ra.userid

           LEFT JOIN {grade_items} gi ON gi.courseid=c.id AND (gi.itemtype = 'mod' OR gi.itemtype = 'course')
           LEFT JOIN {grade_grades} g ON gi.id=g.itemid AND g.userid=u.id
           LEFT JOIN {user} wg ON wg.id = g.usermodified

           LEFT JOIN {modules} m ON m.name = gi.itemmodule
           LEFT JOIN {course_modules} cm ON cm.instance = gi.iteminstance AND cm.module = m.id
           LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id AND cmc.userid = u.id
           LEFT JOIN {assign_submission} ass ON ass.userid=u.id AND ass.assignment=cm.instance AND
                                                ass.status='submitted' AND ass.latest = 1
           LEFT JOIN {assign_grades} sg ON ass.assignment = sg.assignment AND ass.userid = sg.userid AND
                                           sg.attemptnumber = ass.attemptnumber AND
                                           (ass.timemodified >= sg.timemodified OR sg.timemodified IS NULL OR sg.grade IS NULL)

           LEFT JOIN {local_intelliboard_tracking} lit ON lit.userid=u.id AND
                                                          (
                                                           (lit.param=c.id AND lit.page='course' AND gi.itemtype = 'course') OR
                                                           (lit.param=cm.id AND lit.page='module' AND gi.itemtype = 'mod')
                                                          )
           LEFT JOIN {user_lastaccess} ul ON ul.courseid = c.id AND ul.userid = u.id
               WHERE c.id>0 {$sql_filter}
                     {$sql_having}
                     {$sql_order}",
            $params
        );
    }

    function report164($params)
    {
        global $CFG;
        $columns = array_merge(array(
            "c.fullname","u.firstname","u.lastname","u.email","u.username","cohort_name", "enrol_start_date"
        ), $this->get_filter_columns($params));
        $modules = $this->get_course_modules($params);
        $sql_select = '';
        foreach($modules['modules'] as $module){
            $completion = $this->get_completion($params, "");

            $module = (object)$module;
            $sql_select .= ", (SELECT timemodified FROM {course_modules_completion} WHERE userid = u.id AND coursemoduleid = $module->id $completion) AS completed_$module->id";
            $columns[] = "completed_$module->id";
        }
        $columns[] = "percent_completed";
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid,'ctx.instanceid');
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_in_sql($params->cohortid,'coh_m.cohortid');
        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql = $this->get_filter_in_sql($params->learner_roles, "ra.roleid");
        $grade_single = intelliboard_grade_sql(true, $params);
        $completion = $this->get_completion($params, "cmc.");
        $enrolfilter = $this->get_filter_enrol_sql($params, 'e1.');
        $enrolfilter .= $this->get_filter_enrol_sql($params, 'ue1.');

        if ($CFG->dbtype == 'pgsql') {
            $group_concat = "string_agg( DISTINCT CONCAT(coh.name), ', ')";
        } else {
            $group_concat = "GROUP_CONCAT(DISTINCT CONCAT(coh.name) SEPARATOR ', ')";
        }

        $data = $this->get_report_data("
            SELECT
              MIN(ra.id) AS id,
              u.email,
              u.username,
              u.idnumber,
              u.firstname,
              u.lastname,
              c.fullname AS course_name,
              c.id AS course_id,
              MIN(ue.enrol_start_date) AS enrol_start_date,
              $group_concat AS cohort_name,
              $grade_single AS grade,
              (COUNT( DISTINCT cmc.id)/COUNT(DISTINCT cm.id))*100 AS percent_completed
              $sql_select
              $sql_columns

            FROM {context} ctx
            JOIN {role_assignments} ra ON ctx.id = ra.contextid $sql
            JOIN {user} u ON u.id=ra.userid
            JOIN {course} c ON c.id=ctx.instanceid
       LEFT JOIN (SELECT e1.courseid, ue1.userid,
                         CASE WHEN MIN(ue1.timestart) > 0 THEN MIN(ue1.timestart) ELSE MIN(ue1.timecreated) END AS enrol_start_date
                    FROM {enrol} e1
                    JOIN {user_enrolments} ue1 ON ue1.enrolid = e1.id
                   WHERE e1.id > 0 {$enrolfilter}
                GROUP BY e1.courseid, ue1.userid
                 ) ue ON ue.courseid = c.id AND ue.userid = u.id

              LEFT JOIN {cohort_members} coh_m ON coh_m.userid=u.id
              LEFT JOIN {cohort} coh ON coh.id=coh_m.cohortid

              LEFT JOIN {grade_items} gi ON gi.courseid=c.id AND gi.itemtype = 'course'
              LEFT JOIN {grade_grades} g ON gi.id=g.itemid AND g.userid=u.id

              LEFT JOIN {course_modules} cm ON cm.completion>0 AND cm.course=c.id
              LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid=cm.id AND cmc.userid=u.id $completion
            WHERE ctx.contextlevel = 50 AND u.id IS NOT NULL AND ue.enrol_start_date IS NOT NULL $sql_filter
            GROUP BY u.id, c.id $sql_having $sql_order", $params,false);

        return array('modules' => $modules['modules'],
            'data'    => $data);
    }

    public function report165($params)
    {
        global $CFG;

        $columns = array_merge(array(
            "c.fullname",
            "cs.name",
            "teacher",
            "startdate",
            "enddate",
            "u.firstname",
            "u.lastname",
            "activity",
            "m.name",
            "cmc.timemodified",
            "grade",
            "g.timemodified"),
            $this->get_filter_columns($params)
        );
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_columns .= $this->get_modules_sql($params->custom);

        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_in_sql($params->custom, "m.id");
        $sql_filter .= $this->get_filterdate_sql($params, "g.timemodified");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_module_sql($params, "cm.");
        $sql_filter .= $this->get_filter_in_sql($params->learner_roles, "ra.roleid");
        $sql_teacher_roles = $this->get_filter_in_sql($params->teacher_roles, "ra.roleid");
        $grade_single = intelliboard_grade_sql(true, $params);
        $sql_join = $this->get_suspended_sql($params);

        if ($CFG->version < 2016120509) {
            $sql_columns .= ", '' AS startdate, '' AS enddate";
        } else {
            $sql_columns .= ", c.startdate AS startdate, c.enddate AS enddate";
        }

        return $this->get_report_data("
            SELECT
                   concat_ws('', cm.id, u.id) as unique_id,
                   c.id AS courseid,
                   c.fullname,
                   cs.section AS section_number,
                   cs.name AS section_name,
                   u.id AS userid,
                   u.firstname,
                   u.lastname,
                   m.name,
                   cm.id as cmid,
                   0 AS slot,
                   0 AS attempt,
                   MAX(cmc.completionstate) AS status,
                   MAX(cmc.timemodified) AS status_modified,
                   $grade_single AS grade,
                   MAX(g.timemodified) AS graded,
                   (SELECT DISTINCT CONCAT(u.firstname,' ',u.lastname)
                                FROM {role_assignments} AS ra
                                    JOIN {user} u ON ra.userid = u.id
                                    JOIN {context} AS ctx ON ctx.id = ra.contextid
                                WHERE ctx.instanceid = c.id AND ctx.contextlevel = 50 $sql_teacher_roles LIMIT 1
                            ) AS teacher
                   $sql_columns

            FROM {course} c
              JOIN {context} con ON con.contextlevel=50 AND con.instanceid=c.id
              JOIN {role_assignments} ra ON ra.contextid=con.id
              JOIN {user} u ON u.id=ra.userid

              JOIN {course_modules} cm ON cm.course = c.id
              JOIN {modules} m ON m.id = cm.module
              JOIN {course_sections} cs ON cs.id = cm.section AND cs.course = cm.course
              LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id AND cmc.userid=u.id

              LEFT JOIN {grade_items} gi ON gi.itemtype='mod' AND gi.itemmodule=m.name AND gi.iteminstance=cm.instance
              LEFT JOIN {grade_grades} g ON g.userid=u.id AND g.itemid=gi.id
              $sql_join
            WHERE c.id > 0 $sql_filter
            GROUP BY cm.id, u.id, c.id, cs.id, m.id  $sql_having $sql_order", $params);
    }

    public function report167($params)
    {
        global $CFG;
        $columns = array_merge(array(
            "u.firstname",
            "u.lastname",
            "q.name",
            "tags",
            "attempts"),
            $this->get_filter_columns($params)
        );
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_columns = $this->get_columns($params, ["u.id"]);

        $sql_filter = $this->get_filter_in_sql($params->custom2, "t.id");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_teacher_sql($params, ["u.id" => "users"]);

        if ($CFG->dbtype == 'pgsql') {
            $group_concat = "string_agg( DISTINCT t.rawname, ', ')";
        } else {
            $group_concat = "GROUP_CONCAT(DISTINCT t.rawname SEPARATOR ', ')";
        }
        return $this->get_report_data("
            SELECT
                  CONCAT(qua.questionid,u.id) AS uniqueid,
                  u.id AS userid,
                  u.firstname,
                  u.lastname,
                  q.name AS question,
                  COUNT(DISTINCT qua.id) AS attempts,
                  $group_concat AS tags
                  $sql_columns
            FROM {question_attempts} qua
              JOIN {question_attempt_steps} qas ON qas.questionattemptid=qua.id AND qas.fraction IS NOT NULL
              JOIN {question} q ON q.id=qua.questionid
              JOIN {user} u ON u.id=qas.userid

              LEFT JOIN {tag_instance} ti ON ti.itemid=q.id AND ti.component='core_question' AND ti.itemtype='question'
              LEFT JOIN {tag} t ON t.id=ti.tagid
            WHERE qas.state NOT LIKE '%partial' AND qas.state NOT LIKE '%right' $sql_filter
            GROUP BY qua.questionid,u.id,q.id $sql_having $sql_order", $params);
    }

    public function report168($params)
    {
        global $CFG, $DB;

        if(!$params->custom or !$params->courseid) {
            return [];
        }
        $columns = array_merge(array(
            "c.fullname",
            "ch.name",
            "u.firstname",
            "u.lastname",
            "u.email",
            "cohorts"),
            $this->get_filter_columns($params)
        );
        $sql_columns = $this->get_columns($params, ["u.id"]);
        $items = $DB->get_records('checklist_item', array('checklist'=> intval($params->custom), 'userid'=>0));
        foreach($items as $item){
            $sql_columns .= ", (SELECT CASE WHEN teachermark=1 THEN teachertimestamp ELSE usertimestamp END FROM {checklist_check} WHERE item = $item->id AND userid = u.id LIMIT 1) AS completed_item_$item->id";
            $columns[] = "completed_item_$item->id";
        }
        if ($CFG->dbtype == 'pgsql') {
            $cohorts = "string_agg( DISTINCT coh.name, ', ')";
            $sql_columns .= ", '' AS userinfo";
        } else {
            $cohorts = "GROUP_CONCAT( DISTINCT coh.name)";
            $sql_columns .= ", (SELECT GROUP_CONCAT(CASE WHEN d.data <> '' THEN CONCAT(f.name, ':', d.data) ELSE NULL END SEPARATOR '|||') FROM {user_info_data} d, {user_info_field} f WHERE f.id = d.fieldid AND d.userid = u.id) AS userinfo";
        }
        $columns[] = "userinfo";
        $columns[] = "chi.completed_items";
        $columns[] = "percent_completed";

        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_in_sql($params->learner_roles, "ra.roleid");
        $sql_filter .= $this->get_teacher_sql($params, ["c.id" => "courses", "u.id" => "users"]);

        $sql_columns .= ", coo.cohorts";
        $sql_cohort = $this->get_filter_in_sql($params->cohortid, "ch.cohortid");
        $sql_join = " LEFT JOIN (SELECT ch.userid, $cohorts AS cohorts FROM {cohort} coh, {cohort_members} ch WHERE coh.id = ch.cohortid $sql_cohort GROUP BY ch.userid) coo ON coo.userid = u.id";
        if ($params->cohortid) {
          $sql_filter .= " AND coo.cohorts IS NOT NULL";
        }

        $this->params['checklist'] = $params->custom;
        $this->params['checklist2'] = $params->custom;
        $this->params['checklist3'] = $params->custom;
        $this->params['course'] = $params->courseid;

        return $this->get_report_data("
            SELECT DISTINCT u.id,
              c.fullname,
              c.id AS courseid,
              ch.name AS checklist_name,
              u.id AS userid,
              u.firstname,
              u.lastname,
              u.email,
              che.all_items,
              chi.completed_items,
              (chi.completed_items/che.all_items)*100 AS percent_completed
              $sql_columns
            FROM {role_assignments} ra
                   JOIN {context} AS ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50
                   JOIN {course} c ON c.id = ctx.instanceid
                   JOIN {checklist} ch ON ch.course = c.id
              LEFT JOIN {modules} m ON m.name = 'checklist'
              LEFT JOIN {course_modules} cm ON cm.module = m.id AND cm.course = c.id AND cm.instance = ch.id
                   JOIN {user} u ON u.id = ra.userid
                   LEFT JOIN (SELECT checklist, COUNT(id) AS all_items FROM {checklist_item} WHERE checklist = :checklist2 AND userid = 0 GROUP BY checklist) che ON che.checklist = ch.id
                   LEFT JOIN (SELECT chk.userid, COUNT(DISTINCT chk.id) AS completed_items
                                FROM {checklist_item} chm, {checklist_check} chk
                                WHERE chm.userid = 0 AND chm.checklist = :checklist3 AND chk.item = chm.id AND ((chk.teachermark = 1 and chk.teachertimestamp > 0) OR chk.usertimestamp > 0)
                                GROUP BY chk.userid) chi ON chi.userid = u.id
                   $sql_join
            WHERE ch.id = :checklist AND c.id = :course $sql_filter $sql_having $sql_order", $params);
    }

    public function report169($params)
    {
        global $DB;
        $columns = array("qa.userid", "grade");
        $this->params['custom'] = $params->courseid;
        $questions = $DB->get_records_sql("
                                SELECT
                                  DISTINCT qua.questionid,
                                  que.name
                                FROM {quiz} q
                                  LEFT JOIN {quiz_attempts} qa ON qa.quiz=q.id
                                  LEFT JOIN {question_attempts} qua ON qua.questionusageid=qa.uniqueid
                                  LEFT JOIN {question} que ON que.id=qua.questionid
                                WHERE qua.questionid IS NOT NULL AND q.id=:custom", $this->params);
        $sql_columns = '';
        foreach($questions as $question){
            $sql_columns .= ",(SELECT responsesummary FROM {question_attempts} WHERE questionusageid=qa.uniqueid AND questionid=$question->questionid) AS useranswer_".$question->questionid;
            $columns[] = "useranswer_".$question->questionid;
            $sql_columns .= ",(SELECT rightanswer FROM {question_attempts} WHERE questionusageid=qa.uniqueid AND questionid=$question->questionid) AS rightanswer_".$question->questionid;
            $columns[] = "rightanswer_".$question->questionid;
        }
        $grade_single_sql = intelliboard_grade_sql(false, $params);


        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_filter_in_sql($params->learner_roles, "ra.roleid");
        $sql_filter .= $this->get_filterdate_sql($params, "qa.timefinish");
        $sql_filter .= $this->get_teacher_sql($params, ["con.instanceid" => "courses", "qa.userid" => "users"]);

        return $this->get_report_data("
                    SELECT
                           CONCAT(qa.userid, qa.id) as uniqueid,
                           qa.userid,
                           $grade_single_sql AS grade
                           $sql_columns
                    FROM {quiz} q
                      LEFT JOIN {quiz_attempts} qa ON qa.quiz=q.id

                      JOIN {context} con ON con.contextlevel=50 AND con.instanceid=q.course
                      JOIN {role_assignments} ra ON ra.contextid=con.id AND ra.userid=qa.userid
                      LEFT JOIN {grade_items} gi ON gi.itemtype='mod' AND gi.itemmodule='quiz' AND gi.iteminstance=q.id
                      LEFT JOIN {grade_grades} g ON g.itemid=gi.id AND g.userid=qa.userid AND g.rawgrade IS NOT NULL
                    WHERE q.id=:custom $sql_filter $sql_having $sql_order", $params);

    }

    public function report170($params)
    {
        global $CFG;
        $columns = array("que.name", "que.questiontext", "qua.rightanswer", "incorrect_answers");

        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_filter_in_sql($params->learner_roles, "ra.roleid");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "q.id");
        $sql_filter .= $this->get_filterdate_sql($params, "qa.timefinish");
        $sql_filter .= $this->get_teacher_sql($params, ["q.course" => "courses", "ra.userid" => "users"]);

        if ($CFG->dbtype == 'pgsql') {
            $group_concat = "string_agg( DISTINCT CASE WHEN qas.state LIKE '%wrong' THEN qua.responsesummary ELSE NULL END, ',</br>')";
        } else {
            $group_concat = "GROUP_CONCAT( DISTINCT CASE WHEN qas.state LIKE '%wrong' THEN qua.responsesummary ELSE NULL END SEPARATOR ',</br>')";
        }

        return $this->get_report_data("
                    SELECT
                           que.id,
                           que.name,
                           que.questiontext,
                           MAX(qua.rightanswer) AS rightanswer,
                           $group_concat as incorrect_answers

                    FROM {quiz} q
                      LEFT JOIN {quiz_attempts} qa ON qa.quiz=q.id
                      LEFT JOIN {question_attempts} qua ON qua.questionusageid=qa.uniqueid
                      LEFT JOIN {question} que ON que.id=qua.questionid
                      LEFT JOIN {question_attempt_steps} qas ON qas.questionattemptid=qua.id

                      JOIN {context} con ON con.contextlevel=50 AND con.instanceid=q.course
                      JOIN {role_assignments} ra ON ra.contextid=con.id AND ra.userid=qa.userid
                    WHERE q.id>0 $sql_filter
                    GROUP BY que.id $sql_having $sql_order", $params);

    }

    public function report171($params)
    {
        $columns = array_merge(array(
            "c.fullname", "f.name", "fd.name", "u.firstname", "u.lastname", "time_started", "students_accesed", "posts", "timespend"
        ), $this->get_filter_columns($params, [null]));

        $sql_columns = $this->get_columns($params, [null]);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter_roles = $this->get_filter_in_sql($params->learner_roles, "ra.roleid");
        $sql_filter = $this->get_filter_in_sql($params->courseid, "f.course");
        $sql_filter .= $this->get_teacher_sql($params, ["c.id" => "courses", "u.id" => "users"]);

        if(empty($sql_having) && $params->timestart && $params->timefinish){
            $this->prfx = $this->prfx + 1;
            $timestart = 'tmstart'.$this->prfx;
            $timefinish = 'tmfinish'.$this->prfx;
            $this->params[$timestart] = $params->timestart;
            $this->params[$timefinish] = $params->timefinish;


            $sql_having = " HAVING MIN(fp.created) BETWEEN :$timestart AND :$timefinish ";
        }else{
            $sql_having .= $this->get_filterdate_sql($params, 'MIN(fp.created)');
        }

        $forum_table = ($params->custom3 == 1)?'hsuforum':'forum';

        if ($params->custom2 == 1) {
            $sql_filter_roles2 = $this->get_filter_in_sql($params->learner_roles, "ra.roleid");
            $sql_select = "MIN(log.students_accesed)";
            $sql_join = "LEFT JOIN (SELECT l.objectid AS discussion,
                                           COUNT(DISTINCT l.userid) AS students_accesed
                                    FROM {context} ctx
                                           JOIN {role_assignments} ra ON ra.contextid=ctx.id
                                           LEFT JOIN {logstore_standard_log} l ON l.component='mod_".$forum_table."' AND l.action='viewed' AND l.target='discussion' AND l.userid=ra.userid AND l.courseid=ctx.instanceid
                                    WHERE ctx.contextlevel=50 $sql_filter_roles2
                                    GROUP BY l.objectid) log ON log.discussion=fd.id";
        } else {
          $sql_select = "MIN(stat.students_accesed)";
          $sql_join = "";
        }

        $sql_join .= $this->get_suspended_sql($params);

        return $this->get_report_data("
                    SELECT
                          fd.id,
                          c.id AS courseid,
                          c.fullname AS course_name,
                          f.name AS forum,
                          fd.name AS discusion,
                          u.id AS userid,
                          u.firstname,
                          u.lastname,
                          MIN(fp.created) AS time_started,
                          $sql_select AS students_accesed,
                          MIN(stat.timespend) AS timespend,
                          COUNT(DISTINCT CASE WHEN fp.parent>0 THEN fp.id ELSE NULL END) AS posts
                          {$sql_columns}
                    FROM {".$forum_table."} f
                      JOIN {".$forum_table."_discussions} fd ON fd.forum=f.id
                      JOIN {user} u ON u.id=fd.userid
                      JOIN {course} c ON c.id=f.course
                      LEFT JOIN {".$forum_table."_posts} fp ON fp.discussion=fd.id
                      JOIN {modules} m ON m.name='".$forum_table."'
                      JOIN {course_modules} cm ON cm.instance=f.id AND cm.module=m.id

                      LEFT JOIN (SELECT lit.param AS module, COUNT(DISTINCT lit.userid) AS students_accesed, SUM(lit.timespend) AS timespend
                                  FROM {context} ctx
                                    JOIN {role_assignments} ra ON ra.contextid=ctx.id
                                    LEFT JOIN {local_intelliboard_tracking} lit ON lit.page='module' AND lit.courseid=ctx.instanceid AND lit.userid=ra.userid
                                  WHERE ctx.contextlevel=50 $sql_filter_roles
                                  GROUP BY lit.param) stat ON stat.module=cm.id
                      $sql_join
                    WHERE f.id>0 $sql_filter
                    GROUP BY fd.id, c.id, f.name, u.id $sql_having $sql_order", $params);

    }
    function report172($params)
    {
        $columns = array_merge(array(
            "c.fullname",
            "u.firstname",
            "u.lastname",
            "u.email"),
            $this->get_filter_columns($params),
            array("completed",
            "not_completed",
            "not_accessed")
        );

        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, 'c.id');
        $sql_filter .= $this->get_filter_in_sql($params->learner_roles, "ra.roleid");
        $sql_filter .= $this->get_filterdate_sql($params, "ra.timemodified");
        $sql_join = $this->get_suspended_sql($params);
        $sql_vendor_filter = $this->vendor_filter('ra.userid', 'c.id', $params);
        $sql_vendor_filter1 = $this->vendor_filter('cmc.userid', 'cm.course', $params);

        return $this->get_report_data("
            SELECT CONCAT(c.id,'-',u.id) AS id,
                   c.id courseid,
                   c.fullname AS course_name,
                   u.id AS userid,
                   u.firstname,
                   u.lastname,
                   u.email,
                   CASE WHEN MIN(comp.userid) IS NOT NULL THEN 1 ELSE 0 END as completed,
                   CASE WHEN MIN(comp.userid) IS NULL AND MIN(lit.id) IS NOT NULL THEN 1 ELSE 0 END as not_completed,
                   CASE WHEN MIN(lit.id) IS NULL THEN 1 ELSE 0 END as not_accessed
                   $sql_columns
            FROM {role_assignments} ra
                   JOIN {context} AS ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50
                   JOIN {course} c ON c.id = ctx.instanceid
                   JOIN {user} u ON u.id = ra.userid

                   LEFT JOIN (SELECT cm.course, cmc.userid
                              FROM {course_modules} cm
                                 JOIN  {course_modules_completion} cmc ON cmc.coursemoduleid=cm.id AND cmc.completionstate=1
                               WHERE cmc.id > 0 {$sql_vendor_filter1}
                              GROUP BY cm.course,cmc.userid
                             ) comp ON comp.userid=u.id AND comp.course=c.id

                   LEFT JOIN {local_intelliboard_tracking} lit ON lit.page = 'course' AND lit.param = c.id AND lit.userid = ra.userid
                   $sql_join
            WHERE c.id > 0 $sql_filter {$sql_vendor_filter}
            GROUP BY c.id, u.id $sql_having $sql_order", $params);
    }

    function report173($params)
    {
        global $DB, $CFG;

        $columns = array_merge(array(
            "ue.userid",
            "u.username",
            "u.email",
            "u.firstname",
            "u.lastname"),
            $this->get_filter_columns($params), array(
            "ue.timecreated",
            "grade",
            "cmc.completionstate",
            "cmc.timemodified",
            "cmc2.completionstate",
            "cmc2.timemodified"
          ));

        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, 'c.id');

        $mods = ($params->custom) ? explode(",", $params->custom) : [];

        $questionnaire = isset($mods[0])?$mods[0]:0;
        $quizid = isset($mods[1])?$mods[1]:0;

        $this->params['quizid'] = intval($quizid);
        $this->params['quizid2'] = intval($quizid);

        $this->params['questionnaire'] = intval($questionnaire);

        $grade_single = intelliboard_grade_sql(false, $params);
        $sql_answer = $this->get_filter_in_sql($params->courseid, 'q.course');
        $sql_answer .= $this->get_filter_in_sql($questionnaire, "q.id");

        $responce_user_field = (get_config('mod_questionnaire', 'version')<2017050101)?'username':'userid';
        $responce_survey_id = (get_config('mod_questionnaire', 'version')<2017111101)?'survey_id':'surveyid';
        $responce_questionnaireid = (get_config('mod_questionnaire', 'version')<2018050102)?'survey_id':'questionnaireid';
        $responce_rank = (get_config('mod_questionnaire', 'version')<2018050104)?'rank':'rankvalue';

        if ($CFG->dbtype == 'pgsql') {
            $responce_user_field = 'id';
            $group_concat = "string_agg( DISTINCT CONCAT(g.name), ', ')";
            $group_concat2 = "string_agg( CONCAT(qua.questionid, '_intelli_sep_', qua.responsesummary), ' intelli_sep_c ')";
            $answers = "0";
        } else {
            $DB->execute("SET SESSION group_concat_max_len = 1000000");
            $group_concat = "GROUP_CONCAT(DISTINCT g.name)";
            $group_concat2 = "GROUP_CONCAT(CONCAT(qua.questionid, '_intelli_sep_', qua.responsesummary) SEPARATOR 'intelli_sep_c')";
            $answers = "GROUP_CONCAT(CONCAT ( q.question, 'intelli_sep_q', CASE WHEN q.response_table = 'response_text' THEN (SELECT a.response FROM {questionnaire_response_text} a WHERE a.response_id = r.id AND a.question_id = q.question) ELSE
                CASE WHEN q.response_table = 'response_bool' THEN (SELECT a.choice_id FROM {questionnaire_response_bool} a WHERE a.response_id = r.id AND a.question_id = q.question) ELSE
                CASE WHEN q.response_table = 'resp_single' THEN (SELECT h.content FROM {questionnaire_resp_single} a, {questionnaire_quest_choice} h WHERE a.response_id = r.id AND a.question_id = q.question AND h.id = a.choice_id AND h.question_id = q.question) ELSE
                    CASE WHEN q.response_table = 'response_rank' THEN (SELECT GROUP_CONCAT(CONCAT (h.content, ' - ', (a.{$responce_rank} + 1)) SEPARATOR 'intelli_sep_a') FROM {questionnaire_response_rank} a, {questionnaire_quest_choice} h WHERE a.response_id = r.id AND a.question_id = q.question AND h.id = a.choice_id AND h.question_id = q.question) ELSE
                    CASE WHEN q.response_table = 'resp_multiple' THEN (SELECT GROUP_CONCAT(h.content SEPARATOR 'intelli_sep_a') FROM {questionnaire_resp_multiple} a, {questionnaire_quest_choice} h WHERE a.response_id = r.id AND a.question_id = q.question AND h.id = a.choice_id AND h.question_id = q.question) ELSE '-' END
                        END
                    END
                END
            END) SEPARATOR 'intelli_sep_m')";
        }

        if ($params->custom3) {
          $sql_filter .= $this->get_filterdate_sql($params, "cmc2.timemodified");
        } else {
          $sql_filter .= $this->get_filterdate_sql($params, "cmc.timemodified");
        }

        if ($params->custom2) {
          if ($params->custom2 == 3) {
            $sql_filter .= " AND (cmc.completionstate = 0 OR cmc.completionstate = 3 OR cmc.completionstate IS NULL)";
          } else {
            $this->params['completionstate'] = intval($params->custom2);
            $sql_filter .= " AND cmc.completionstate = :completionstate";
          }
        }

        return $this->get_report_data("
            SELECT ue.id,
              ue.userid,
              ue.timecreated,
              e.courseid,
              c.fullname,
              u.firstname,
              u.lastname,
              u.username,
              u.email,
              cm.id AS coursemoduleid,
              cmc.completionstate,
              cmc.timemodified AS timecompleted,
              cmc2.completionstate AS completionstate2,
              cmc2.timemodified AS timecompleted2,
              $grade_single AS grade,
              g.timecreated AS graded,
              qz.quizanswers,
              r.answers
              $sql_columns
            FROM {user_enrolments} ue
              JOIN {enrol} e ON e.id = ue.enrolid
              JOIN {course} c ON c.id = e.courseid
              JOIN {user} u ON u.id = ue.userid

              JOIN {modules} m ON m.name = 'quiz'
              JOIN {course_modules} cm ON cm.module = m.id AND cm.instance = :quizid

              LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id AND cmc.userid = u.id
              LEFT JOIN {grade_items} gi ON gi.itemtype = 'mod' AND gi.itemmodule = 'quiz' AND gi.iteminstance = cm.instance
              LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.userid = u.id

              JOIN {modules} m2 ON m2.name = 'questionnaire'
              JOIN {course_modules} cm2 ON cm2.module = m2.id AND cm2.instance = :questionnaire

              LEFT JOIN {course_modules_completion} cmc2 ON cmc2.coursemoduleid = cm2.id AND cmc2.userid = u.id

              LEFT JOIN (SELECT q.id, qa.userid, $group_concat2 AS quizanswers
                FROM {quiz} q
                  LEFT JOIN {quiz_attempts} qa ON qa.quiz=q.id AND qa.attempt = (SELECT MAX(attempt) FROM {quiz_attempts} WHERE state='finished' AND userid = qa.userid and quiz = qa.quiz)
                  LEFT JOIN {question_attempts} qua ON qua.questionusageid=qa.uniqueid
                WHERE qua.questionid IS NOT NULL AND q.id=:quizid2 GROUP BY q.id, qa.userid) qz ON qz.id = cm.instance AND qz.userid = u.id

              LEFT JOIN (SELECT r.{$responce_user_field} AS userid, q.course, $answers AS answers
                  FROM (SELECT q.id AS questionnaire, q.name AS questionnairename, q.course, qq.id AS question, t.has_choices, t.response_table
                    FROM {questionnaire} q, {questionnaire_question} qq, {questionnaire_question_type} t
                    WHERE q.id = qq.{$responce_survey_id} AND qq.deleted = 'n' AND qq.type_id = t.typeid $sql_answer ORDER BY qq.position) q
                      JOIN {questionnaire_response} r ON r.{$responce_questionnaireid} = q.questionnaire
                      WHERE r.complete = 'y' GROUP BY r.{$responce_user_field}, q.course) r ON r.userid = u.id AND r.course = c.id
            WHERE ue.id > 0 $sql_filter $sql_having $sql_order", $params);

    }

    function report174($params)
    {
        $columns = array_merge(array(
            "u.username",
            "u.firstname",
            "u.lastname",
            "u.email",
            "c.fullname",
            "c.shortname",
            "cc.name",
            "ue.timecreated",
            "ce.name",
            "ci.code",
            "ci.timecreated"),
            $this->get_filter_columns($params)
        );

        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, 'c.id');
        $sql_filter .= $this->get_filterdate_sql($params, "ue.timemodified");

        return $this->get_report_data("SELECT ue.id,
                        u.username,
                        u.firstname,
                        u.lastname,
                        u.email,
                        ce.name as certificate,
                        ci.timecreated,
                        ci.code,
                        ue.userid,
                        e.courseid,
                        c.fullname as course,
                        c.shortname,
                        cc.name as category,
                        ue.timecreated as enrolled
                      $sql_columns
                    FROM {user_enrolments} ue
                      JOIN {user} u ON u.id = ue.userid
                      JOIN {enrol} e ON e.id = ue.enrolid
                      JOIN {course} c ON c.id = e.courseid
                      JOIN {course_categories} cc ON cc.id=c.category
                      LEFT JOIN {customcert} ce ON ce.course = c.id
                      LEFT JOIN {customcert_issues} ci ON ci.customcertid = ce.id AND ci.userid = u.id
                      LEFT JOIN {modules} m ON m.name='customcert'
                      LEFT JOIN {course_modules} cm ON cm.module=m.id AND cm.instance = ce.id
                      LEFT JOIN {grade_items} gi ON gi.courseid=c.id AND gi.itemtype='course'
                      LEFT JOIN {grade_grades} gg ON gg.itemid=gi.id AND gg.userid=u.id AND gg.overridden=0
                    WHERE ue.id > 0 $sql_filter $sql_having $sql_order", $params);
    }

    function report179($params)
    {
        global $DB, $CFG;

        $columns = array_merge(array(
            "ue.userid",
            "u.username",
            "u.email",
            "u.firstname",
            "u.lastname",
            "u.city",
            "u.country",
            "u.lastaccess",
            ),
            $this->get_filter_columns($params), array(
            "c.fullname",
            "cmc.timemodified",
            "grade",
            "cmc2.timemodified",
            "ue.timecreated",
          ));

        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filterdate_sql($params, "u.lastaccess");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, 'c.id');

        $mods = ($params->custom) ? explode(",", $params->custom) : [];

        $questionnaire = isset($mods[0])?$mods[0]:0;
        $quizid = isset($mods[1])?$mods[1]:0;

        $this->params['quizid'] = intval($quizid);
        $this->params['quizid2'] = intval($quizid);

        $this->params['questionnaire'] = intval($questionnaire);

        $grade_single = intelliboard_grade_sql(false, $params);
        $sql_answer = $this->get_filter_in_sql($params->courseid, 'q.course');
        $sql_answer .= $this->get_filter_in_sql($questionnaire, "q.id");

        $responce_user_field = (get_config('mod_questionnaire', 'version')<2017050101)?'username':'userid';
        $responce_survey_id = (get_config('mod_questionnaire', 'version')<2017111101)?'survey_id':'surveyid';
        $responce_questionnaireid = (get_config('mod_questionnaire', 'version')<2018050102)?'survey_id':'questionnaireid';
        $responce_rank = (get_config('mod_questionnaire', 'version')<2018050104)?'rank':'rankvalue';

        if ($CFG->dbtype == 'pgsql') {
            $responce_user_field = 'id';
            $group_concat = "string_agg( DISTINCT CONCAT(g.name), ', ')";
            $group_concat2 = "string_agg( CONCAT(qua.questionid, '_intelli_sep_', qua.responsesummary), ' intelli_sep_c ')";
            $answers = "0";
        } else {
            $DB->execute("SET SESSION group_concat_max_len = 1000000");
            $group_concat = "GROUP_CONCAT(DISTINCT g.name)";
            $group_concat2 = "GROUP_CONCAT(CONCAT(qua.questionid, '_intelli_sep_', qua.responsesummary) SEPARATOR 'intelli_sep_c')";
            $answers = "GROUP_CONCAT(CONCAT ( q.question, 'intelli_sep_q', CASE WHEN q.response_table = 'response_text' THEN (SELECT a.response FROM {questionnaire_response_text} a WHERE a.response_id = r.id AND a.question_id = q.question) ELSE
                CASE WHEN q.response_table = 'response_bool' THEN (SELECT a.choice_id FROM {questionnaire_response_bool} a WHERE a.response_id = r.id AND a.question_id = q.question) ELSE
                CASE WHEN q.response_table = 'resp_single' THEN (SELECT h.content FROM {questionnaire_resp_single} a, {questionnaire_quest_choice} h WHERE a.response_id = r.id AND a.question_id = q.question AND h.id = a.choice_id AND h.question_id = q.question) ELSE
                    CASE WHEN q.response_table = 'response_rank' THEN (SELECT GROUP_CONCAT(CONCAT (h.content, ' - ', (a.{$responce_rank} + 1)) SEPARATOR 'intelli_sep_a') FROM {questionnaire_response_rank} a, {questionnaire_quest_choice} h WHERE a.response_id = r.id AND a.question_id = q.question AND h.id = a.choice_id AND h.question_id = q.question) ELSE
                    CASE WHEN q.response_table = 'resp_multiple' THEN (SELECT GROUP_CONCAT(h.content SEPARATOR 'intelli_sep_a') FROM {questionnaire_resp_multiple} a, {questionnaire_quest_choice} h WHERE a.response_id = r.id AND a.question_id = q.question AND h.id = a.choice_id AND h.question_id = q.question) ELSE '-' END
                        END
                    END
                END
            END) SEPARATOR 'intelli_sep_m')";
        }

        if ($params->custom2) {
          if ($params->custom2 == 3) {
            $sql_filter .= " AND (cmc.completionstate = 0 OR cmc.completionstate = 3 OR cmc.completionstate IS NULL)";
          } else {
            $this->params['completionstate'] = intval($params->custom2);
            $sql_filter .= " AND cmc.completionstate = :completionstate";
          }
        }

        return $this->get_report_data("
            SELECT ue.id,
              ue.userid,
              ue.timecreated,
              e.courseid,
              c.fullname,
              u.firstname,
              u.lastname,
              u.username,
              u.email,
              u.city,
              u.country,
              u.department,
              u.institution,
              u.lastaccess,
              cm.id AS coursemoduleid,
              cmc.completionstate,
              cmc.timemodified AS timecompleted,
              cmc2.completionstate AS completionstate2,
              cmc2.timemodified AS timecompleted2,
              $grade_single AS grade,
              g.timecreated AS graded,
              r.answers
              $sql_columns
            FROM {user_enrolments} ue
              JOIN {enrol} e ON e.id = ue.enrolid
              JOIN {course} c ON c.id = e.courseid
              JOIN {user} u ON u.id = ue.userid

              JOIN {modules} m ON m.name = 'quiz'
              JOIN {course_modules} cm ON cm.module = m.id AND cm.instance = :quizid

              LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id AND cmc.userid = u.id
              LEFT JOIN {grade_items} gi ON gi.itemtype = 'mod' AND gi.itemmodule = 'quiz' AND gi.iteminstance = cm.instance
              LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.userid = u.id

              JOIN {modules} m2 ON m2.name = 'questionnaire'
              JOIN {course_modules} cm2 ON cm2.module = m2.id AND cm2.instance = :questionnaire

              LEFT JOIN {course_modules_completion} cmc2 ON cmc2.coursemoduleid = cm2.id AND cmc2.userid = u.id

              LEFT JOIN (SELECT r.{$responce_user_field} AS userid, q.course, $answers AS answers
                  FROM (SELECT q.id AS questionnaire, q.name AS questionnairename, q.course, qq.id AS question, t.has_choices, t.response_table
                    FROM {questionnaire} q, {questionnaire_question} qq, {questionnaire_question_type} t
                    WHERE q.id = qq.{$responce_survey_id} AND qq.deleted = 'n' AND qq.type_id = t.typeid $sql_answer ORDER BY qq.position) q
                      JOIN {questionnaire_response} r ON r.{$responce_questionnaireid} = q.questionnaire
                      WHERE r.complete = 'y' GROUP BY r.{$responce_user_field}, q.course) r ON r.userid = u.id AND r.course = c.id
            WHERE ue.id > 0 $sql_filter $sql_having $sql_order", $params);

    }


    function report181($params)
    {
        global $DB, $CFG;
        require_once($CFG->libdir . "/adminlib.php");

        $columns = array_merge(array(
            "u.username",
            "u.idnumber",
            "u.firstname",
            "u.lastname",
            "c.shortname",
            "c.fullname",
            "teacher",
            "m.name",
            "activity",
            "overal_submission_date",
            "overal_submission_graded",
            "overal_submission_grade",
            "ul.timeaccess"),
            $this->get_filter_columns($params)
        );

        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_module_sql($params, "cm.");
        $sql_filter .= $this->get_filterdate_sql($params, "GREATEST(COALESCE(ass.submitted,0), COALESCE(f.posted,0), COALESCE(q.started,0), COALESCE(gl.submitted,0), COALESCE(ch.submitted,0))");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, 'c.id');
        $sql_filter .= $this->get_filter_in_sql($params->custom, "m.id");
        $sql_filter .= $this->get_filter_in_sql($params->custom2, "cm.id");
        $sql_columns .= $this->get_modules_sql('');
        $grade_avg = intelliboard_grade_sql(true, $params);


        $sql_teacher_roles = $this->get_filter_in_sql($params->teacher_roles, "ra.roleid");
        $sql_inner_filter1 = $this->get_filter_in_sql($params->courseid, 'm.course');
        $sql_inner_filter2 = $this->get_filter_in_sql($params->courseid, 'm.course');
        $sql_inner_filter3 = $this->get_filter_in_sql($params->courseid, 'm.course');
        $sql_inner_filter4 = $this->get_filter_in_sql($params->courseid, 'm.course');
        $sql_inner_filter5 = $this->get_filter_in_sql($params->courseid, 'm.course');

        if ($CFG->dbtype == 'pgsql') {
            $typecast = '::TEXT';
        } else {
            $typecast = '';
        }

        $sql_select = $sql_overal_submission_date = $sql_overal_submission_graded = $sql_overal_submission_grade = '';
        if (get_component_version('mod_hsuforum')) {
            $sql_inner_filter6 = $this->get_filter_in_sql($params->courseid, 'm.course');
            $sql_select = "LEFT JOIN (SELECT
                            fd.forum,
                            fp.userid,
                            MAX(fp.modified) AS posted,
                            MAX(g.timemodified) AS graded,
                            $grade_avg AS grade
                        FROM {hsuforum_discussions} fd
                            JOIN {hsuforum} m ON m.id=fd.forum $sql_inner_filter6
                            LEFT JOIN {hsuforum_posts} fp ON fp.discussion=fd.id
                            LEFT JOIN {grade_items} gi ON gi.itemtype = 'mod' AND gi.itemmodule = 'hsuforum' AND gi.iteminstance = fd.forum
                            LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.userid = fp.userid
                        GROUP BY fd.forum, fp.userid) hf ON hf.userid=u.id AND hf.forum=cm.instance AND m.name='hsuforum'";

            $sql_overal_submission_date = ", COALESCE(hf.posted,0)";
            $sql_overal_submission_graded = ", COALESCE(hf.graded,0)";
            $sql_overal_submission_grade = ", COALESCE(hf.grade$typecast,'')";
        }

        return $this->get_report_data("
              SELECT
                CONCAT(u.id, '_', cm.id) AS id,
                u.username,
                u.idnumber,
                u.firstname,
                u.lastname,
                c.shortname,
                c.fullname,
                m.name AS mod_name,
                ass.submitted AS assignment_submission_date,
                ass.graded AS assignment_submission_graded,
                f.posted AS forum_posted_date,
                f.graded AS forum_graded,
                q.started AS quiz_started_date,
                q.graded AS quiz_graded,
                gl.submitted AS glossary_submission_date,
                gl.graded AS glossary_graded,
                ch.submitted AS choice_submission_date,
                ch.graded AS choice_graded,
                ul.timeaccess,
                GREATEST(COALESCE(ass.submitted,0), COALESCE(f.posted,0), COALESCE(q.started,0), COALESCE(gl.submitted,0), COALESCE(ch.submitted,0) $sql_overal_submission_date) AS overal_submission_date,
                GREATEST(COALESCE(ass.graded,0), COALESCE(f.graded,0), COALESCE(q.graded,0), COALESCE(gl.graded,0), COALESCE(ch.graded,0) $sql_overal_submission_graded) AS overal_submission_graded,
                GREATEST(COALESCE(ass.grade$typecast,''), COALESCE(f.grade$typecast,''), COALESCE(q.grade$typecast,''), COALESCE(gl.grade$typecast,''), COALESCE(ch.grade$typecast,'') $sql_overal_submission_grade) AS overal_submission_grade,
                 (SELECT DISTINCT CONCAT(u.firstname,' ',u.lastname)
                            FROM {role_assignments} AS ra
                                JOIN {user} u ON ra.userid = u.id
                                JOIN {context} AS ctx ON ctx.id = ra.contextid
                            WHERE ctx.instanceid = c.id AND ctx.contextlevel = 50 $sql_teacher_roles LIMIT 1
                        ) AS teacher
                $sql_columns
                FROM {user_enrolments} ue
                  LEFT JOIN {enrol} e ON e.id = ue.enrolid
                  LEFT JOIN {user} u ON u.id = ue.userid
                  LEFT JOIN {course} c ON c.id = e.courseid
                  LEFT JOIN {course_modules} cm ON cm.course = c.id
                  LEFT JOIN {modules} m ON m.id = cm.module
                  LEFT JOIN {user_lastaccess} ul ON ul.userid = u.id AND ul.courseid = c.id

                  LEFT JOIN (SELECT
                            ass.userid,
                            ass.assignment,
                            MAX(ass.timecreated) AS submitted,
                            MAX(g.timemodified) AS graded,
                            $grade_avg AS grade
                        FROM {assign_submission} ass
                            JOIN {assign} m ON m.id=ass.assignment $sql_inner_filter1
                            LEFT JOIN {grade_items} gi ON gi.itemtype = 'mod' AND gi.itemmodule = 'assign' AND gi.iteminstance = ass.assignment
                            LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.userid = ass.userid
                        WHERE ass.status='submitted'
                        GROUP BY ass.userid, ass.assignment) ass ON ass.userid=u.id AND ass.assignment=cm.instance AND m.name='assign'

                  LEFT JOIN (SELECT
                            fd.forum,
                            fp.userid,
                            MAX(fp.modified) AS posted,
                            MAX(g.timemodified) AS graded,
                            $grade_avg AS grade
                        FROM {forum_discussions} fd
                            JOIN {forum} m ON m.id=fd.forum $sql_inner_filter2
                            LEFT JOIN {forum_posts} fp ON fp.discussion=fd.id
                            LEFT JOIN {grade_items} gi ON gi.itemtype = 'mod' AND gi.itemmodule = 'forum' AND gi.iteminstance = fd.forum
                            LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.userid = fp.userid
                        GROUP BY fd.forum, fp.userid) f ON f.userid=u.id AND f.forum=cm.instance AND m.name='forum'

                  LEFT JOIN (SELECT
                            qa.quiz,
                            qa.userid,
                            MIN(qa.timestart) AS started,
                            MAX(g.timemodified) AS graded,
                            $grade_avg AS grade
                        FROM {quiz_attempts} qa
                            JOIN {quiz} m ON m.id=qa.quiz $sql_inner_filter3
                            LEFT JOIN {grade_items} gi ON gi.itemtype = 'mod' AND gi.itemmodule = 'quiz' AND gi.iteminstance = qa.quiz
                            LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.userid = qa.userid
                        GROUP BY qa.quiz, qa.userid) q ON q.userid=u.id AND q.quiz=cm.instance AND m.name='quiz'

                  LEFT JOIN (SELECT
                            ge.glossaryid,
                            ge.userid,
                            MAX(ge.timecreated) AS submitted,
                            MAX(g.timemodified) AS graded,
                            $grade_avg AS grade
                        FROM {glossary_entries} ge
                            JOIN {glossary} m ON m.id=ge.glossaryid $sql_inner_filter4
                            LEFT JOIN {grade_items} gi ON gi.itemtype = 'mod' AND gi.itemmodule = 'glossary' AND gi.iteminstance = ge.glossaryid
                            LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.userid = ge.userid
                        GROUP BY ge.glossaryid, ge.userid) gl ON gl.userid=u.id AND gl.glossaryid=cm.instance AND m.name='glossary'

                  LEFT JOIN (SELECT
                            ca.choiceid,
                            ca.userid,
                            MAX(ca.timemodified) AS submitted,
                            MAX(g.timemodified) AS graded,
                            $grade_avg AS grade
                        FROM {choice_answers} ca
                            JOIN {choice} m ON m.id=ca.choiceid $sql_inner_filter5
                            LEFT JOIN {grade_items} gi ON gi.itemtype = 'mod' AND gi.itemmodule = 'choice' AND gi.iteminstance = ca.choiceid
                            LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.userid = ca.userid
                        GROUP BY ca.choiceid, ca.userid) ch ON ch.userid=u.id AND ch.choiceid=cm.instance AND m.name='choice'

                  $sql_select

                WHERE ue.id > 0 $sql_filter $sql_having $sql_order", $params);
    }

    public function report182($params)
    {
        global $CFG;

        $columns = array_merge(array(
            "u.firstname",
            "u.lastname",
            "u.email",
            "questionnaire",
            "question",
            "answer",
            "qar.submitted",
            "c.fullname"),
            $this->get_filter_columns($params)
        );
        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "qa.course");
        $sql_filter .= $this->get_filterdate_sql($params, "qar.submitted");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_join = $this->get_suspended_sql($params);

        $rankanswergroup = get_operator(
            'GROUP_CONCAT', "CONCAT(qc.content, ' - ', rp.rankvalue)",
            ['separator' => ", "]
        );
        $multipleanswergroup = get_operator(
            'GROUP_CONCAT', "qc.content", ['separator' => ", "]
        );

        if ($CFG->dbtype == 'pgsql') {
            $typecast = '::TEXT';
        } else {
            $typecast = '';
        }

        $data = $this->get_report_data(
            "SELECT CONCAT(u.id, '_', qa.id, '_', qaq.id, '_', qar.submitted) AS id,
                    u.firstname,
                    u.lastname,
                    u.email,
                    qa.name AS questionnaire,
                    qaq.content AS question,
                    CASE WHEN qqt.response_table = 'response_bool' THEN bt.answer{$typecast}
                         WHEN qqt.response_table = 'resp_multiple' THEN mst.answer{$typecast}
                         WHEN qqt.response_table = 'response_text' THEN tt.answer{$typecast}
                         WHEN qqt.response_table = 'response_date' THEN dt.answer{$typecast}
                         WHEN qqt.response_table = 'resp_single' THEN sst.answer{$typecast}
                         WHEN qqt.response_table = 'response_rank' THEN rst.answer{$typecast}
                         ELSE '-'
                     END AS answer,
                    qar.submitted AS answer_time,
                    c.fullname AS course_name
                    {$sql_columns},
                    CASE WHEN qqt.response_table = 'response_bool' THEN 1 ELSE 0 END AS is_bool_val
               FROM {questionnaire} qa
          LEFT JOIN {questionnaire_survey} qs ON qs.id = qa.sid
          LEFT JOIN {questionnaire_question} qaq ON qaq.surveyid = qs.id AND
                                                    qaq.deleted = 'n'
          LEFT JOIN {questionnaire_question_type} qqt ON qqt.typeid = qaq.type_id
          LEFT JOIN {questionnaire_response} qar ON qar.questionnaireid = qa.id AND qar.complete = 'y'
          LEFT JOIN (SELECT response_id, question_id,
                            CASE WHEN choice_id = 'y' THEN TRUE ELSE FALSE END AS answer
                       FROM {questionnaire_response_bool}
                    ) bt ON bt.response_id = qar.id AND
                            qqt.response_table = 'response_bool' AND
                            bt.question_id = qaq.id
          LEFT JOIN (SELECT rp.response_id, rp.question_id,
                            {$multipleanswergroup} AS answer
                       FROM {questionnaire_resp_multiple} rp
                       JOIN {questionnaire_quest_choice} qc ON qc.id = rp.choice_id
                   GROUP BY rp.response_id, rp.question_id
                    ) mst ON mst.response_id = qar.id AND
                             qqt.response_table = 'resp_multiple' AND
                             mst.question_id = qaq.id
          LEFT JOIN (SELECT response_id, question_id, response AS answer
                       FROM {questionnaire_response_text}
                    ) tt ON tt.response_id = qar.id AND
                            qqt.response_table = 'response_text' AND
                            tt.question_id = qaq.id
          LEFT JOIN (SELECT response_id, question_id, response AS answer
                       FROM {questionnaire_response_date}
                    ) dt ON dt.response_id = qar.id AND
                            qqt.response_table = 'response_date' AND
                            dt.question_id = qaq.id
          LEFT JOIN (SELECT rp.response_id, rp.question_id, qc.content AS answer
                       FROM {questionnaire_resp_single} rp
                       JOIN {questionnaire_quest_choice} qc ON qc.id = rp.choice_id
                    ) sst ON sst.response_id = qar.id AND
                             qqt.response_table = 'resp_single' AND
                             sst.question_id = qaq.id
          LEFT JOIN (SELECT rp.response_id, rp.question_id,
                            {$rankanswergroup} AS answer
                       FROM {questionnaire_response_rank} rp
                       JOIN {questionnaire_quest_choice} qc ON qc.id = rp.choice_id
                   GROUP BY rp.response_id, rp.question_id
                    ) rst ON rst.response_id = qar.id AND
                             qqt.response_table = 'response_rank' AND
                             rst.question_id = qaq.id
          LEFT JOIN {user} u ON qar.userid = u.id
          LEFT JOIN {course} c ON c.id = qa.course
          {$sql_join}
              WHERE qa.id > 0 AND qqt.response_table != '' $sql_filter $sql_having $sql_order",
            $params, false
        );

        return ["data" => $data];
    }

    public function report183($params)
    {
        $columns = array_merge(array(
            "u.username", "u.firstname", "u.lastname", "c.shortname", "c.fullname", "cc1.name", "grader_name",
            "gi.itemname", "grade", "ass.timemodified", "graduated", "ul.timeaccess"
        ), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_in_sql($params->learner_roles, "ra.roleid");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_teacher_sql($params, ["c.id" => "courses", "u.id" => "users"]);
        $grade_single = intelliboard_grade_sql(false, $params);
        $grade_percent = intelliboard_grade_sql(false, $params, 'g.', 0, 'gi.', true);

        if($params->custom == 1) {
            $sql_filter .= $this->get_filterdate_sql($params, "g.timemodified");
        } elseif($params->custom == 2) {
            $sql_filter .= $this->get_filterdate_sql($params, "lit.firstaccess");
        }

        return $this->get_report_data(
            "SELECT DISTINCT(concat_ws('_', a.id, u.id, c.id)) AS unique_id,
                    a.name AS activity_name,
                    g.userid,
                    u.username,
                    u.firstname,
                    u.lastname,
                    c.fullname,
                    cc1.name AS course_category,
                    c.shortname,
                    CONCAT(gu.firstname, ' ', gu.lastname) AS grader_name,
                    g.timemodified AS graduated,
                    {$grade_single} AS grade,
                    {$grade_percent} AS grade_percent,
                    gi.itemtype,
                    sg.timemodified AS need_grade_assignment,
                    ass.timemodified AS submission_date,
                    lit.firstaccess,
                    ul.timeaccess AS course_lastaccess
                    {$sql_columns}
               FROM {course} c
               JOIN {course_categories} cc1 ON cc1.id = c.category
               JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = 50
               JOIN {role_assignments} ra ON ra.contextid = ctx.id
               JOIN {user} u ON u.id=ra.userid
               JOIN {assign} a ON a.course=c.id
          LEFT JOIN {grade_items} gi ON gi.iteminstance = a.id AND gi.itemtype='mod' AND gi.itemmodule='assign'
          LEFT JOIN {grade_grades} g ON gi.id=g.itemid AND g.userid=u.id
          LEFT JOIN {assign_submission} ass ON ass.userid=u.id AND ass.assignment=a.id AND ass.status='submitted' AND ass.latest = 1
          LEFT JOIN {assign_grades} sg ON ass.assignment = sg.assignment AND ass.userid = sg.userid AND sg.attemptnumber = ass.attemptnumber AND sg.grade > -1
          LEFT JOIN {user} gu ON gu.id=sg.grader
               JOIN {modules} m ON m.name='assign'
               JOIN {course_modules} cm ON cm.course=c.id AND cm.instance=a.id AND cm.module=m.id
          LEFT JOIN {local_intelliboard_tracking} lit ON lit.userid=u.id AND ((lit.param=c.id AND lit.page='course' AND gi.itemtype = 'course') OR (lit.param=cm.id AND lit.page='module' AND gi.itemtype = 'mod'))
          LEFT JOIN {user_lastaccess} ul ON ul.courseid = c.id AND ul.userid = u.id
              WHERE c.id>0 {$sql_filter}
                    {$sql_having}
                    {$sql_order}",
            $params
        );
    }

    public function report185($params)
    {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/quiz/locallib.php');
        require_once($CFG->dirroot . '/mod/quiz/report/reportlib.php');
        require_once($CFG->dirroot . '/mod/quiz/report/default.php');
        $file = $CFG->dirroot . '/mod/quiz/report/statistics/report.php';
        if (is_readable($file)) {
            include_once($file);
        }
        if (!class_exists('quiz_statistics_report')) {
            print_error('preprocesserror', 'quiz');
        }

        if (is_array($params->custom)) {
            $quizesids = $params->custom;
        } elseif ($params->custom) {
            $quizesids = explode(",", clean_param($params->custom, PARAM_SEQUENCE));
        } else {
            $quizesids = [];
        }

        if($quizesids) {
            list($sql, $params2) = $DB->get_in_or_equal($quizesids, SQL_PARAMS_NAMED, 'id');
            $quizes = $DB->get_records_sql(
                "SELECT * FROM {quiz} WHERE id $sql", $params2
            );
        } else {
            $quizes = $DB->get_records_sql(
                "SELECT qz.*
                   FROM {course} c
                   JOIN {quiz} qz ON qz.course = c.id
                  WHERE c.id = :courseid",
                ['courseid' => $params->courseid]
            );
        }

        $report = new quiz_statistics_report();
        $hash_codes = array();
        $temp_table = array();
        foreach($quizes as $quiz){
            $whichattempts = $quiz->grademethod;
            $whichtries = question_attempt::LAST_TRY;
            $groupstudentsjoins = new \core\dml\sql_join();

            $qubaids = quiz_statistics_qubaids_condition($quiz->id, $groupstudentsjoins, $whichattempts);
            $hash_codes[] = $hash_code = $qubaids->get_hash_code();

            $quizcalc = new \quiz_statistics\calculator();
            if($quizcalc->get_last_calculated_time($qubaids) === false){
                $DB->delete_records('quiz_statistics', array('hashcode' => $hash_code));
                $DB->delete_records('question_statistics', array('hashcode' => $hash_code));
                $DB->delete_records('question_response_analysis', array('hashcode' => $hash_code));
            }

            $questions = $report->load_and_initialise_questions_for_calculations($quiz);
            if(!empty($questions)){
                $report->get_all_stats_and_analysis($quiz, $whichattempts, $whichtries, $groupstudentsjoins, $questions);
            }
            $temp_table[] = "SELECT '$hash_code' AS hashcode, $quiz->id AS quiz";
        }


        $columns = array("c.fullname", "q.name", "ta_course.tags", "ta_module.tags", "qs.slot", "q.qtype", "q.name", "ta_question.tags", "qs.s", "qs.facility", "qs.sd", "qs.randomguessscore", "qs.maxmark", "qs.effectiveweight", "qs.discriminationindex", "qs.discriminativeefficiency");

        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_filter_in_sql($hash_codes, "qs.hashcode");
        if ($CFG->dbtype == 'pgsql') {
            $rawname = "string_agg( DISTINCT t.rawname, ', ')";
        } else {
            $rawname = "GROUP_CONCAT( DISTINCT t.rawname)";
        }

        return $this->get_report_data("
                SELECT
                    CONCAT(relations.hashcode,'_',qs.slot) AS id,
                    c.fullname AS course_name,
                    qz.name AS quiz_name,
                    ta_course.tags AS course_tags,
                    ta_module.tags AS activity_tags,
                    ta_question.tags AS question_tags,
                    qs.slot,
                    q.qtype,
                    q.name,
                    qs.s AS attempts,
                    qs.facility,
                    (qs.sd*100)/qs.maxmark AS sd,
                    qs.randomguessscore,
                    (qs.maxmark*100)/$quiz->sumgrades AS intended_weight,
                    qs.effectiveweight,
                    qs.discriminationindex,
                    qs.discriminativeefficiency
                FROM {question_statistics} qs
                    JOIN (".implode(' UNION ', $temp_table).") relations ON relations.hashcode=qs.hashcode
                    JOIN {question} q ON q.id=qs.questionid
                    JOIN {quiz} qz ON qz.id=relations.quiz
                    JOIN {course} c ON c.id=qz.course
                    JOIN {modules} m ON m.name='quiz'
                    JOIN {course_modules} cm ON cm.course=qz.course AND cm.module=m.id AND cm.instance=qz.id
                    LEFT JOIN (SELECT i.itemid, $rawname AS tags
                               FROM {tag_instance} i, {tag} t
                               WHERE t.id = i.tagid AND i.itemtype = 'course_modules' GROUP BY i.itemid) ta_module ON ta_module.itemid = cm.id
                    LEFT JOIN (SELECT i.itemid, $rawname AS tags
                               FROM {tag_instance} i, {tag} t
                               WHERE t.id = i.tagid AND i.itemtype = 'course' GROUP BY i.itemid) ta_course ON ta_course.itemid = c.id
                    LEFT JOIN (SELECT i.itemid, $rawname AS tags
                               FROM {tag_instance} i, {tag} t
                               WHERE t.id = i.tagid AND i.itemtype = 'question' GROUP BY i.itemid) ta_question ON ta_question.itemid = q.id
                WHERE qs.hashcode IS NOT NULL $sql_filter $sql_having $sql_order", $params);
    }

    public function report186($params)
    {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/quiz/locallib.php');
        require_once($CFG->dirroot . '/mod/quiz/report/reportlib.php');
        require_once($CFG->dirroot . '/mod/quiz/report/default.php');
        $file = $CFG->dirroot . '/mod/quiz/report/statistics/report.php';
        if (is_readable($file)) {
            include_once($file);
        }
        if (!class_exists('quiz_statistics_report')) {
            print_error('preprocesserror', 'quiz');
        }

        $courses = (is_array($params->courseid)) ? $params->courseid : explode(",", clean_param($params->courseid, PARAM_SEQUENCE));
        list($sql, $params2) = $DB->get_in_or_equal($courses, SQL_PARAMS_NAMED, 'courseid');
        $quizes = $DB->get_records_sql("SELECT * FROM {quiz} WHERE course $sql", $params2);

        $temp_table = array();
        foreach($quizes as $quiz){
            $report = new quiz_statistics_report();
            $whichattempts = $quiz->grademethod;
            $whichtries = question_attempt::LAST_TRY;
            $groupstudentsjoins = new \core\dml\sql_join();

            $qubaids = quiz_statistics_qubaids_condition($quiz->id, $groupstudentsjoins, $whichattempts);

            $hash_code = $qubaids->get_hash_code();
            $questions = $report->load_and_initialise_questions_for_calculations($quiz);
            $qcalc = new \core_question\statistics\questions\calculator($questions);
            $quizcalc = new \quiz_statistics\calculator();
            if ($quizcalc->get_last_calculated_time($qubaids) === false){
                $DB->delete_records('quiz_statistics', array('hashcode' => $hash_code));
                $DB->delete_records('question_statistics', array('hashcode' => $hash_code));
                $DB->delete_records('question_response_analysis', array('hashcode' => $hash_code));
            }

            if(count($questions)>0){
                $report->get_all_stats_and_analysis($quiz, $whichattempts, $whichtries, $groupstudentsjoins, $questions);
            }

            $temp_table[] = "SELECT '$hash_code' AS hashcode, $quiz->id AS quiz";
        }

        $columns = array("c.fullname", "q.name", "ta_course.tags", "ta_module.tags", "qs.firstattemptscount", "qs.allattemptscount", "qs.firstattemptsavg", "qs.allattemptsavg", "qs.lastattemptsavg", "qs.highestattemptsavg", "qs.median", "qs.standarddeviation", "qs.skewness", "qs.kurtosis");

        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);

        if ($CFG->dbtype == 'pgsql') {
            $rawname = "string_agg( DISTINCT t.rawname, ', ')";
        } else {
            $rawname = "GROUP_CONCAT( DISTINCT t.rawname)";
        }

        return $this->get_report_data("
                SELECT
                    relations.hashcode,
                    c.fullname AS course_name,
                    q.name AS quiz_name,
                    ta_course.tags AS course_tags,
                    ta_module.tags AS activity_tags,
                    qs.firstattemptscount,
                    qs.allattemptscount,
                    qs.firstattemptsavg*100/q.sumgrades AS firstattemptsavg,
                    qs.allattemptsavg*100/q.sumgrades AS allattemptsavg,
                    qs.lastattemptsavg*100/q.sumgrades AS lastattemptsavg,
                    qs.highestattemptsavg*100/q.sumgrades AS highestattemptsavg,
                    qs.median*100/q.sumgrades AS median,
                    qs.standarddeviation*100/q.sumgrades AS standarddeviation,
                    qs.skewness,
                    qs.kurtosis

                FROM {quiz_statistics} qs
                    JOIN (".implode(' UNION ', $temp_table).") relations ON relations.hashcode=qs.hashcode
                    JOIN {quiz} q ON q.id=relations.quiz
                    JOIN {course} c ON c.id=q.course
                    JOIN {modules} m ON m.name='quiz'
                    JOIN {course_modules} cm ON cm.course=q.course AND cm.module=m.id AND cm.instance=q.id
                    LEFT JOIN (SELECT i.itemid, $rawname AS tags
                               FROM {tag_instance} i, {tag} t
                               WHERE t.id = i.tagid AND i.itemtype = 'course_modules' GROUP BY i.itemid) ta_module ON ta_module.itemid = cm.id
                    LEFT JOIN (SELECT i.itemid, $rawname AS tags
                               FROM {tag_instance} i, {tag} t
                               WHERE t.id = i.tagid AND i.itemtype = 'course' GROUP BY i.itemid) ta_course ON ta_course.itemid = c.id
               WHERE q.id > 0 $sql_having $sql_order", $params);
    }

    public function report187($params)
    {
        global $CFG, $DB;

        $guide = $DB->get_records_sql("
                    SELECT
                        ggc.id,
                        ggc.shortname
                    FROM {modules} m
                        JOIN {course_modules} cm ON cm.module=m.id
                        JOIN {context} ctx ON ctx.instanceid=cm.id AND ctx.contextlevel=70
                        JOIN {grading_areas} ga ON ga.contextid=ctx.id
                        JOIN {grading_definitions} gd ON gd.areaid=ga.id AND gd.method='guide'
                        JOIN {gradingform_guide_criteria} ggc ON ggc.definitionid=gd.id
                    WHERE m.name='assign' AND cm.instance=:instance
                    ORDER BY ggc.sortorder", array('instance'=>(int)$params->custom));


        $columns = array();
        $select_sql = '';
        foreach($guide as $item){
            $select_sql .= ", (SELECT score FROM {gradingform_guide_fillings} WHERE instanceid=gi.id AND criterionid=$item->id) AS criterion_$item->id";
            $columns[] = "criterion_$item->id";
        }
        $columns = array_merge(array("u.firstname", "u.lastname", "c.fullname", "a.name"), $this->get_filter_columns($params), $columns);


        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->custom, "a.id");
        $sql_filter .= $this->get_filter_in_sql($params->learner_roles, "ra.roleid");

        $data = $this->get_report_data("
                SELECT
                    DISTINCT u.id,
                    u.firstname,
                    u.lastname,
                    c.fullname AS course,
                    a.name AS assignment
                    $select_sql
                    $sql_columns
                FROM {assign} a
                    JOIN {context} AS ctx_c ON ctx_c.instanceid = a.course AND ctx_c.contextlevel=50
                    JOIN {role_assignments} ra ON ra.contextid=ctx_c.id
                    JOIN {course} c ON c.id=a.course
                    JOIN {user} u ON u.id=ra.userid

                    LEFT JOIN {assign_grades} ag ON ag.assignment=a.id AND ag.userid=u.id
                    LEFT JOIN {modules} m ON m.name='assign'
                    LEFT JOIN {course_modules} cm ON cm.module=m.id AND cm.instance=a.id
                    LEFT JOIN {context} ctx ON ctx.instanceid=cm.id AND ctx.contextlevel=70
                    LEFT JOIN {grading_areas} ga ON ga.contextid=ctx.id
                    LEFT JOIN {grading_definitions} gd ON gd.areaid=ga.id AND gd.method='guide'

                    LEFT JOIN {grading_instances} gi ON gi.definitionid=gd.id AND gi.itemid=ag.id AND gi.status=1
                WHERE a.id>0 $sql_filter $sql_having $sql_order", $params);

        $data['guide'] = $guide;
        return $data;
    }

    public function report188($params) {
        global $CFG;

        // return empty data if IntelliCart is not installed or if IntelliCart is disabled in IntelliBoard plugin
        if(
            !file_exists($CFG->dirroot . '/local/intellicart/lib.php') ||
            !get_config('local_intelliboard', 'intellicart')
        ) {
            return ['data' => []];
        }

        $columns = array_merge(array(
            "orderid",
            "customer",
            "u.email",
            "products",
            "pc.courses",
            "ch.timeupdated",
            "ch.amount",
            "ch.subtotal",
            "ch.discount",
            "status",
            "s.quantity",
            "invoice"
        ), $this->get_filter_columns($params));
        $this->params['ltype'] = \local_intellicart\log::TYPE_PRODUCT;

        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_filterdate_sql($params, "ch.timeupdated");
        $currency = \local_intellicart\payment::get_currency('code');
        $coursesnames = get_operator('GROUP_CONCAT', 'DISTINCT c.fullname', ['separator' => ', ']);

        $coursefilter = '';
        if($params->courseid) {
            $courseidfilter = $this->get_filter_in_sql($params->courseid, 'c.id');
            $coursefilter = "JOIN (SELECT il.checkoutid
                                     FROM {local_intellicart_logs} il
                                     JOIN {local_intellicart_relations} lr ON lr.productid = il.instanceid AND
                                                                              lr.type = 'course'
                                     JOIN {course} c ON c.id = lr.instanceid
                                    WHERE il.type = 'product' {$courseidfilter}
                                 GROUP BY il.checkoutid
                                  ) pc1 ON pc1.checkoutid = ch.id";
        }

        return $this->get_report_data(
            "SELECT ch.id AS orderid,
                    ch.item_name AS products,
                    ch.timeupdated,
                    ch.amount,
                    ch.subtotal,
                    ch.discount,
                    ch.payment_status AS status,
                    u.email,
                    ch.userid,
                    ch.notes,
                    CONCAT(u.firstname, ' ', u.lastname) AS customer,
                    ch.id AS invoice,
                    ch.billingtype,
                    s.quantity,
                    pc.courses,
                    '{$currency}' AS currency
                    {$sql_columns}
               FROM {local_intellicart_checkout} ch
               JOIN {user} u ON u.id = ch.userid
                    {$coursefilter}
          LEFT JOIN (SELECT SUM(quantity) AS quantity, checkoutid
                       FROM {local_intellicart_logs}
                      WHERE type = :ltype
                   GROUP BY checkoutid
                    ) s ON s.checkoutid = ch.id
          LEFT JOIN (SELECT il.checkoutid, {$coursesnames} AS courses
                       FROM {local_intellicart_logs} il
                       JOIN {local_intellicart_relations} lr ON lr.productid = il.instanceid AND
                                                                lr.type = 'course'
                       JOIN {course} c ON c.id = lr.instanceid
                      WHERE il.type = 'product'
                   GROUP BY il.checkoutid
                    ) pc ON pc.checkoutid = ch.id
               WHERE ch.id > 0 {$sql_filter} {$sql_having} {$sql_order}",
            $params
        );
    }

    public function report193($params) {
        global $CFG;

        $columns = array(
            "uf.firstname",
            "uf.lastname",
            "uf.email",
            "rf.name",
            "m.timecreated",
            "ut.firstname",
            "ut.lastname",
            "ut.email",
            "rt.name"
        );

        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_filter_in_sql($params->custom2, "raf.roleid");
        $sql_filter .= $this->get_filterdate_sql($params, "m.timecreated");
        $role_names1 = get_operator('GROUP_CONCAT', 'DISTINCT rf.name', ['separator' => ', ']);
        $role_names2 = get_operator('GROUP_CONCAT', 'DISTINCT rt.name', ['separator' => ', ']);

        if($CFG->version < 2018032200){
            return $this->get_report_data(
                "SELECT
                    m.id,
                    uf.id AS userid_from,
                    uf.firstname AS firstname_from,
                    uf.lastname AS lastname_from,
                    uf.email AS email_from,
                    $role_names1 AS role_from,
                    m.timecreated,
                    ut.id AS userid_to,
                    ut.firstname AS firstname_to,
                    ut.lastname AS lastname_to,
                    ut.email AS email_to,
                    $role_names2 AS role_to
                FROM {message} m
                    JOIN {user} uf ON uf.id=m.useridfrom
                    JOIN {user} ut ON ut.id=m.useridto
                    JOIN {context} ctx ON ctx.contextlevel=10

                    LEFT JOIN {role_assignments} raf ON raf.contextid=ctx.id AND raf.userid=uf.id
                    LEFT JOIN {role} rf ON rf.id=raf.roleid
                    LEFT JOIN {role_assignments} rat ON rat.contextid=ctx.id AND rat.userid=ut.id
                    LEFT JOIN {role} rt ON rt.id=rat.roleid
                WHERE m.timecreated>0 $sql_filter
                GROUP BY m.id, uf.id, ut.id $sql_having $sql_order",
                $params
            );
        }else{
            return $this->get_report_data(
                "SELECT
                    m.id,
                    uf.id AS userid_from,
                    uf.firstname AS firstname_from,
                    uf.lastname AS lastname_from,
                    uf.email AS email_from,
                    $role_names1 AS role_from,
                    m.timecreated,
                    ut.id AS userid_to,
                    ut.firstname AS firstname_to,
                    ut.lastname AS lastname_to,
                    ut.email AS email_to,
                    $role_names2 AS role_to
                FROM {messages} m
                    JOIN {user} uf ON uf.id=m.useridfrom
                    JOIN {message_conversation_members} mcm ON mcm.conversationid=m.conversationid AND mcm.userid<>m.useridfrom
                    JOIN {user} ut ON ut.id=mcm.userid
                    JOIN {context} ctx ON ctx.contextlevel=10

                    LEFT JOIN {role_assignments} raf ON raf.contextid=ctx.id AND raf.userid=uf.id
                    LEFT JOIN {role} rf ON rf.id=raf.roleid
                    LEFT JOIN {role_assignments} rat ON rat.contextid=ctx.id AND rat.userid=ut.id
                    LEFT JOIN {role} rt ON rt.id=rat.roleid
                WHERE m.timecreated>0 $sql_filter
                GROUP BY m.id, uf.id, ut.id $sql_having $sql_order",
                $params
            );
        }

    }

    function report195($params)
    {
      global $CFG, $DB;

      $this->params['courseid'] = (int) $params->custom3;
      $this->params['cohortid'] = (int) $params->custom2;
      $this->params['hospitalid'] = (int) $params->custom;

      if (!$this->params['courseid'] or !$this->params['cohortid'] or !$this->params['hospitalid']) {
        return [];
      }

      $columns = array_merge(["u.firstname", "u.lastname", "u.email"], $this->get_filter_columns($params));
      $sql_columns = $this->get_columns($params, ["u.id"]);
      $sql_mod = $this->get_filter_module_sql($params, "");
      if ($modules = $DB->get_records_sql("SELECT id FROM {course_modules} WHERE completion > 0 AND course = :courseid $sql_mod", $this->params)) {
        foreach($modules as $module) {
            $completion = $this->get_completion($params, "");
            $sql_columns .= ", (SELECT timemodified FROM {course_modules_completion} WHERE userid = u.id AND coursemoduleid = $module->id $completion) AS completed_$module->id";
            $columns[] = "completed_$module->id";
        }
      }
      $sql_having = $this->get_filter_sql($params, $columns, false);
      $sql_order = $this->get_order_sql($params, $columns);
      $sql_filter = $this->get_filter_user_sql($params, "u.");
      $sql_filter .= $this->get_filterdate_sql($params, "ue.timecreated");
      $sql_totals = $this->get_completion($params, "cmc.");

      $data = $this->get_report_data("
          SELECT
            DISTINCT u.id,
            u.email,
            u.firstname,
            u.lastname,
            ul.timeaccess
            $sql_columns
          FROM {user_enrolments} ue
            JOIN {enrol} e ON e.id = ue.enrolid
            JOIN {user} u ON u.id = ue.userid
            LEFT JOIN {user_lastaccess} ul ON ul.courseid = e.courseid AND ul.userid = u.id
          WHERE e.courseid = :courseid AND e.customint1 = :cohortid AND e.enrol = 'cohort' $sql_filter $sql_having $sql_order", $params, false);

        $totals = $DB->get_record_sql("
              SELECT COUNT(DISTINCT u.id) AS learners, COUNT(DISTINCT ul.userid) AS registrants FROM {user_enrolments} ue
                JOIN {enrol} e ON e.id = ue.enrolid
                JOIN {user} u ON u.id = ue.userid
                LEFT JOIN {user_lastaccess} ul ON ul.courseid = e.courseid AND ul.userid = u.id
              WHERE e.courseid = :courseid AND e.customint1 = :cohortid AND e.enrol = 'cohort' $sql_filter $sql_having", $this->params);

        $completions = $DB->get_records_sql("
              SELECT cmc.coursemoduleid, COUNT(DISTINCT cmc.userid) AS users
              FROM {course_modules_completion} cmc, {course_modules} cm
              WHERE cm.course = :courseid AND cmc.coursemoduleid = cm.id AND cmc.userid IN(SELECT DISTINCT userid
              FROM {cohort_members} WHERE cohortid = :cohortid) $sql_totals GROUP BY cmc.coursemoduleid", $this->params);

        return [
          'data' => $data,
          'totals' => $totals,
          'completions' => $completions,
        ];
    }

    function report196($params)
    {
      global $CFG, $DB;

      $this->params['cohortid'] = (int) $params->custom2;
      $this->params['hospitalid'] = (int) $params->custom;

      if (!$this->params['cohortid'] or !$this->params['hospitalid']) {
        return [];
      }

      $columns = array_merge(["u.firstname", "u.lastname", "u.email"], $this->get_filter_columns($params));
      $sql_columns = $this->get_columns($params, ["u.id"]);
      $sql_course = $this->get_filter_enrol_sql($params, "");
      $sql_course .= $this->get_filter_in_sql($this->params['cohortid'], "customint1");

      if ($courses = $DB->get_records_sql("SELECT DISTINCT courseid AS id FROM {enrol} WHERE id > 0 AND enrol = 'cohort' $sql_course", $this->params)) {
        foreach($courses as $course) {
            $sql_columns .= ", (SELECT timecompleted FROM {course_completions} WHERE userid = u.id AND course = $course->id) AS completed_$course->id";
            $sql_columns .= ", (SELECT MAX(ue.id) FROM {user_enrolments} ue, {enrol} e WHERE ue.enrolid = e.id AND ue.userid = u.id AND e.courseid = $course->id) AS enrol_$course->id";
            $columns[] = "completed_$course->id";
        }
      }
      $sql_having = $this->get_filter_sql($params, $columns, false);
      $sql_having1 = $this->get_filter_sql($params, $columns);
      $sql_order = $this->get_order_sql($params, $columns);
      $sql_filter = $this->get_filter_user_sql($params, "u.");
      $sql_filter .= $this->get_filterdate_sql($params, "u.firstaccess");
      $sql_filter .= $this->get_filter_in_sql($this->params['cohortid'], "cm.cohortid");

      $data = $this->get_report_data("
          SELECT
            DISTINCT u.id,
            u.email,
            u.firstname,
            u.lastname,
            u.firstaccess
            $sql_columns
          FROM {user} u, {cohort_members} cm WHERE cm.userid = u.id $sql_filter $sql_having $sql_order", $params, false);

        $totals = $DB->get_record_sql("
              SELECT COUNT(DISTINCT cm.id) AS learners, COUNT(DISTINCT CASE WHEN u.firstaccess > 0 THEN u.id ELSE null END) AS registrants
              FROM {cohort_members} cm, {user} u
              WHERE u.id = cm.userid $sql_filter $sql_having", $this->params);

        $completions = $DB->get_records_sql("
              SELECT c.course, COUNT(DISTINCT c.userid) AS users
              FROM {course_completions} c, {cohort_members} cm,  {user} u
              WHERE c.userid = cm.userid AND u.id = c.userid AND c.timecompleted > 0 $sql_filter GROUP BY c.course $sql_having1", $this->params);

        return [
          'data' => $data,
          'totals' => $totals,
          'completions' => $completions,
        ];
    }

    public function report198($params) {
        global $CFG;

        if(empty($params->courseid)) {
            return ['data' => []];
        }

        $columns = array_merge([
            "c.fullname",
            "f.name",
            "fp.subject",
            "u1.firstname",
            "u1.lastname",
            "fp.created",
            "fp2.message",
            "fp2.created",
            "u2.firstname",
            "u2.lastname",
            "fp3.message",
            "fp3.created",
            "u3.firstname",
            "u3.lastname",
        ], $this->get_filter_columns($params));
        $sqlcolumns = $this->get_columns($params, [null]);
        $sqlfilter  = $this->get_filter_in_sql($params->courseid, 'c.id');
        $sqlfilter .= $this->get_filter_in_sql($params->custom, 'f.id');
        $sqlhaving  = $this->get_filter_sql($params, $columns);
        $sqlorder   = $this->get_order_sql($params, $columns);

        if ($CFG->dbtype == 'pgsql') {
            $randomnumber = "FLOOR(extract(epoch from now()) * random())";
        } else {
            $randomnumber = "FLOOR(RAND() * NOW())";
        }

        $forum_table = ($params->custom3 == 1)?'hsuforum':'forum';

        return $this->get_report_data(
            "SELECT {$randomnumber} as id,
                    c.fullname AS course,
                    f.name AS forum,
                    fp.subject AS discussion_post,
                    u1.firstname AS forum_started_by_first_name,
                    u1.lastname AS forum_started_by_last_name,
                    fp.created AS date_started,
                    fp2.message AS response,
                    fp2.created AS response_date,
                    u2.firstname AS response_first_name,
                    u2.lastname AS response_laste_name,
                    fp3.message AS response_immediatey_prior,
                    fp3.created AS response_immediatey_prior_date,
                    u3.firstname AS response_immediatey_prior_first_name,
                    u3.lastname AS response_immediatey_prior_last_name
                    {$sqlcolumns}
               FROM {".$forum_table."} f
               JOIN {course} c ON c.id = f.course
               JOIN {modules} m ON m.name = '".$forum_table."'
               JOIN {course_modules} cm ON cm.course = c.id AND cm.module = m.id AND cm.instance = f.id
               JOIN {".$forum_table."_discussions} fd ON fd.forum = f.id AND fd.course = c.id
               JOIN {".$forum_table."_posts} fp ON fp.discussion = fd.id AND fp.parent = 0
               JOIN {user} u1 ON u1.id = fp.userid
          LEFT JOIN {".$forum_table."_posts} fp2 ON fp2.discussion = fd.id AND fp2.parent <> 0
          LEFT JOIN {user} u2 ON u2.id = fp2.userid
          LEFT JOIN {".$forum_table."_posts} fp3 ON fp3.discussion = fd.id AND fp3.id = fp2.parent AND fp3.parent <> 0
          LEFT JOIN {user} u3 ON u3.id = fp3.userid
              WHERE c.id > 0 {$sqlfilter}
                    {$sqlhaving}
                    {$sqlorder}",
            $params
        );
    }

    function report200($params)
    {
        global $CFG;

        $this->params['hospitalid'] = (int) $params->custom;
        if (!$this->params['hospitalid']) {
          return [];
        }

        $columns = ["c.name", "enrolled_users", "completed_users", "percent_completed_users", "avg_rating", "registered_users", "percent_registered_users"];

        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["co.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->custom2, "c.id");
        $sql_filter .= $this->get_filter_course_sql($params, "co.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "e.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "ue.");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");

        $value = ($CFG->dbtype == 'pgsql') ? "fv.value::int" : "fv.value";

        return $this->get_report_data("
          SELECT
            c.id,
            c.name,
            COUNT(DISTINCT cm.userid) AS enrolled_users,
            COUNT(DISTINCT fc.userid) AS completed_users,
            COUNT(DISTINCT fc.userid)/COUNT(DISTINCT cm.userid)*100 AS percent_completed_users,
            AVG($value) AS avg_rating,
            COUNT(DISTINCT ul.userid) AS registered_users,
            COUNT(DISTINCT ul.userid)/COUNT(DISTINCT cm.userid)*100 AS percent_registered_users

        FROM {cohort} c
            JOIN {cohort_members} cm ON cm.cohortid=c.id
            JOIN {user_enrolments} ue ON ue.userid = cm.userid
            JOIN {enrol} e ON e.id = ue.enrolid AND e.customint1 = c.id AND e.enrol = 'cohort'
            JOIN {course} co ON co.id = e.courseid
            JOIN {user} u ON u.id = cm.userid
            JOIN {feedback} f ON f.course = co.id

            LEFT JOIN {feedback_completed} fc ON fc.feedback=f.id AND fc.userid=cm.userid
            LEFT JOIN {feedback_item} fi ON fi.feedback=f.id
            LEFT JOIN {feedback_value} fv ON fv.item=fi.id AND fv.completed=fc.id
            LEFT JOIN {user_lastaccess} ul ON ul.userid=cm.userid AND ul.courseid=co.id
        WHERE c.id IN (SELECT co.id FROM {local_management_cohort} c, {cohort} co WHERE co.id = c.cohortid AND co.visible = 1 AND c.status = 1  AND c.hospitalid = :hospitalid) $sql_filter
        GROUP BY c.id $sql_having $sql_order", $params);
    }

    function report202($params)
    {
      global $CFG, $DB;

      $this->params['hospitalid'] = (int) $params->custom;
      $this->params['cohortid'] = (int) $params->custom2;
      $this->params['courseid'] = (int) $params->custom3;


      if (!$this->params['courseid'] or !$this->params['cohortid'] or !$this->params['hospitalid']) {
        return [];
      }

      $columns = array_merge(["u.firstname", "u.lastname", "u.email"], $this->get_filter_columns($params));
      $sql_columns = $this->get_columns($params, ["u.id"]);

      if ($items = $DB->get_records_sql("SELECT DISTINCT i.id FROM {feedback} f, {feedback_item} i WHERE i.feedback = f.id AND f.course = :courseid", $this->params)) {
        foreach($items as $item) {
            $sql_columns .= ", (SELECT v.value FROM {feedback_value} v, {feedback_completed} c WHERE c.userid = u.id AND v.completed = c.id AND v.item = $item->id) AS completed_$item->id";
            $columns[] = "completed_$item->id";
        }
      }

      $sql_having = $this->get_filter_sql($params, $columns, false);
      $sql_order = $this->get_order_sql($params, $columns);
      $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users"]);
      $sql_filter .= $this->get_filter_user_sql($params, "u.");
      $sql_filter .= $this->get_filterdate_sql($params, "u.firstaccess");
      $sql_filter .= $this->get_filter_in_sql($this->params['courseid'], "cm.cohortid");

      return $this->get_report_data("
          SELECT
            DISTINCT u.id,
            u.email,
            u.firstname,
            u.lastname,
            u.firstaccess,
            (SELECT MAX(timemodified) FROM {feedback_completed} WHERE userid = u.id) AS completed_on
            $sql_columns
          FROM {user} u, {cohort_members} cm WHERE cm.userid = u.id $sql_filter $sql_having $sql_order", $params);
    }

    function report201($params)
    {
      global $DB;

      $this->params['hospitalid'] = (int) $params->custom;
      $this->params['cohortid'] = (int) $params->custom2;
      $this->params['courseid'] = (int) $params->custom3;


      if (!$this->params['courseid'] or !$this->params['cohortid'] or !$this->params['hospitalid']) {
        return [];
      }

      $sql_course = $this->get_filter_course_sql($params, "c.");
      $sql_course .= $this->get_filter_enrol_sql($params, "e.");
      $sql_course .= $this->get_filter_enrol_sql($params, "ue.");
      $sql_course .= $this->get_filter_in_sql($this->params['cohortid'], "cm.cohortid");

      $feedback = $DB->get_record_sql("SELECT MAX(f.id) AS id
        FROM {feedback} f, {feedback_item} i, {user_enrolments} ue, {enrol} e, {course} c, {cohort_members} cm
        WHERE f.course = c.id AND i.feedback = f.id AND c.id = e.courseid AND ue.userid = cm.userid AND e.id = ue.enrolid $sql_course", $this->params);

      $this->params['feedback'] = isset($feedback->id) ? $feedback->id : 0;

      $sql_cohort = $this->get_filter_in_sql($this->params['cohortid'], "cm.cohortid");
      $sql_cohort .= $this->get_filter_user_sql($params, "u.");

      return $this->get_report_data(
        "SELECT
          DISTINCT i.id,
          i.name,
          i.label,
          i.feedback,
          f.name AS feedback_name,
          i.position,
          v.value_0,
          v.value_1,
          v.value_2,
          v.value_3,
          v.value_4,
          v.value_5
        FROM {feedback_item} i
        LEFT JOIN {feedback} f ON f.id = i.feedback
        LEFT JOIN (
          SELECT
            v.item,
            SUM(CASE WHEN v.value = '0' OR v.value IS NULL THEN 1 ELSE 0 END) as value_0,
            SUM(CASE WHEN v.value = '1' THEN 1 ELSE 0 END) as value_1,
            SUM(CASE WHEN v.value = '2' THEN 1 ELSE 0 END) as value_2,
            SUM(CASE WHEN v.value = '3' THEN 1 ELSE 0 END) as value_3,
            SUM(CASE WHEN v.value = '4' THEN 1 ELSE 0 END) as value_4,
            SUM(CASE WHEN v.value = '5' THEN 1 ELSE 0 END) as value_5
          FROM
            {feedback_value} v,
            {feedback_completed} c
          WHERE v.completed = c.id AND c.userid IN (SELECT cm.userid FROM {cohort_members} cm, {user} u WHERE cm.userid = u.id $sql_cohort) GROUP BY v.item
        ) v ON v.item = i.id
        WHERE i.feedback = :feedback AND i.hasvalue=1
        ORDER BY i.position ASC", $params);
    }

    public function report207($params) {
        global $CFG;

        $columns = array_merge([
            "c.fullname",
            "e.name",
            "elt.secret",
            "",
            "",
            "",
            "elt.gradesync",
            "elt.gradesynccompletion",
        ], $this->get_filter_columns($params));

        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["c.id" => "courses"]);
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");

        if ($CFG->dbtype == 'pgsql') {
            $randomnumber = "FLOOR(extract(epoch from now()) * random())";
        } else {
            $randomnumber = "FLOOR(RAND() * NOW())";
        }

        $data = $this->get_report_data(
            "SELECT {$randomnumber} AS frst,
                    elt.id AS tool_id,
                    c.fullname AS course,
                    e.name,
                    elt.secret,
                    elt.gradesync,
                    elt.gradesynccompletion
               FROM {enrol_lti_tools} elt
               JOIN {enrol} e ON elt.enrolid = e.id
               JOIN {course} c ON c.id = e.courseid
              WHERE e.enrol = 'lti' {$sql_filter}
                    {$sql_having}
                    {$sql_order}",
            $params, false
        );

        foreach ($data as &$item) {
            $tool = (object) ['id' => $item->tool_id];
            $cartridge_url = \enrol_lti\helper::get_cartridge_url($tool);
            $item->cartridge_url = $cartridge_url->out();

            $launch_url = \enrol_lti\helper::get_launch_url($tool->id);
            $item->launch_url = $launch_url->out();

            $registration_url = \enrol_lti\helper::get_proxy_url($tool);
            $item->registration_url = $registration_url->out();
        }

        return ['data' => $data];
    }
    public function report208($params)
    {
        global $CFG;
        $columns = array_merge(array(
            "category",
            "parent_category",
            "c.fullname",
            "c.shortname",
            "c.idnumber",
            "c.visible",
            "e.learners",
            "modules",
            "e.completed",
            "visits",
            "timespend",
            "e.grade",
            "c.timecreated",
            "t.teachers",
            "t.teachers_count",
            "t.teachers_timespend",
            "s.avg_learners_timespend",
        ), $this->get_filter_columns($params, [null]));

        $sql_columns = $this->get_columns($params, [null]);
        $sql_filter = $this->get_teacher_sql($params, ["c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);
        $grade_avg = intelliboard_grade_sql(true, $params);
        $sql_vendor_filter = $this->vendor_filter(null, 'c.id', $params);
        $sql_vendor_filter1 = $this->vendor_filter('userid', 'courseid', $params);
        $sql_vendor_filter2 = $this->vendor_filter('ue.userid', 'e.courseid', $params);
        $sql_filter_tracking = '';

        $sql_compl = "";
        if ($params->custom == 1) {
            $sql_compl = $this->get_filterdate_sql($params, "cc.timecompleted");
        } elseif ($params->custom == 2) {
            $sql_filter .= $this->get_filterdate_sql($params, "ue.timecreated");
        } elseif ($params->custom == 3) {
            $sql_filter_tracking = $this->get_filterdate_sql($params, "firstaccess");
        } else {
            $sql_filter .= $this->get_filterdate_sql($params, "c.timecreated");
        }

        if ($CFG->dbtype == 'pgsql') {
            $group_concat = "string_agg( DISTINCT CONCAT(u.firstname,' ',u.lastname), ', ')";
        } else {
            $group_concat = "GROUP_CONCAT(DISTINCT CONCAT(u.firstname,' ',u.lastname) SEPARATOR ', ')";
        }

        if($params->sizemode){
            $sql_filter2 = $this->get_filter_in_sql($params->courseid, "ctx.instanceid");
            $sql_filter2 .= $this->get_filter_in_sql($params->teacher_roles, "ra.roleid");

            $sql_join = "LEFT JOIN (SELECT
                                   ctx.instanceid,
                                   $group_concat AS teachers,
                                   COUNT(DISTINCT u.id) AS teachers_count
                               FROM {role_assignments} AS ra
                                    JOIN {user} u ON ra.userid = u.id
                                    JOIN {context} AS ctx ON ctx.id = ra.contextid
                               WHERE ctx.contextlevel = 50 {$sql_filter2}
                               GROUP BY ctx.instanceid) t ON t.instanceid=c.id";
            $sql_select = ",
                '0' AS teachers_timespend,
                '0' AS avg_learners_timespend";
        }else{
            $sql_filter2 = $this->get_filter_in_sql($params->courseid, "ctx.instanceid");
            $sql_filter2 .= $this->get_filter_in_sql($params->teacher_roles, "ra.roleid");

            $sql_filter3 = $this->get_filter_in_sql($params->courseid, "ctx.instanceid");
            $sql_filter3 .= $this->get_filter_in_sql($params->learner_roles, "ra.roleid");

            $sql_join = "LEFT JOIN (SELECT
                                   ctx.instanceid,
                                   $group_concat AS teachers,
                                   COUNT(DISTINCT u.id) AS teachers_count,
                                   SUM(timespend) AS teachers_timespend
                               FROM {role_assignments} AS ra
                                    JOIN {user} u ON ra.userid = u.id
                                    JOIN {context} AS ctx ON ctx.id = ra.contextid
                                    JOIN {local_intelliboard_tracking} lit ON lit.courseid=ctx.instanceid AND lit.userid=u.id
                               WHERE ctx.contextlevel = 50 {$sql_filter2}
                               GROUP BY ctx.instanceid) t ON t.instanceid=c.id
                        LEFT JOIN (SELECT
                                   ctx.instanceid,
                                   SUM(lit.timespend)/COUNT(ra.userid) AS avg_learners_timespend
                               FROM {role_assignments} AS ra
                                    JOIN {context} AS ctx ON ctx.id = ra.contextid
                                    JOIN {local_intelliboard_tracking} lit ON lit.courseid=ctx.instanceid AND lit.userid=ra.userid
                               WHERE ctx.contextlevel = 50 {$sql_filter3}
                               GROUP BY ctx.instanceid) s ON s.instanceid=c.id";
            $sql_select = ",
                MAX(t.teachers_timespend) AS teachers_timespend,
                MAX(s.avg_learners_timespend) AS avg_learners_timespend";
        }
        $sql_filter4 = $this->get_teacher_sql($params, ["ue.userid" => "users"]);
        $sql_filter4 .= $this->get_filter_in_sql($params->courseid, "e.courseid");
        $sql_filter4 .= $this->get_filter_enrol_sql($params, "ue.");
        $sql_filter4 .= $this->get_filter_enrol_sql($params, "e.");
        $sqlfiltermodule = $this->get_filter_module_sql($params, "");

        return $this->get_report_data("
            SELECT c.id,
                c.fullname AS course,
                c.shortname,
                c.timecreated AS created,
                c.idnumber,
                c.enablecompletion,
                c.visible,
                MAX(e.grade) AS grade,
                MAX(l.timespend) AS timespend,
                MAX(l.visits) AS visits,
                MAX(e.completed) AS completed,
                MAX(e.learners) AS learners,
                (SELECT COUNT(id) FROM {course_modules} WHERE course = c.id AND deletioninprogress = 0 {$sqlfiltermodule}) AS modules,
                MAX(cat.name) AS category,
                (SELECT name FROM {course_categories} WHERE id = MAX(cat.parent)) AS parent_category,
                MAX(t.teachers) AS teachers,
                MAX(t.teachers_count) AS teachers_count
                $sql_select
                $sql_columns
            FROM {course} c
                JOIN(SELECT
                         e.courseid,
                         $grade_avg AS grade,
                         COUNT(DISTINCT cc.userid) AS completed,
                         COUNT(DISTINCT ue.userid) AS learners
                    FROM {enrol} e
                         JOIN {user_enrolments} ue ON ue.enrolid=e.id
                         LEFT JOIN {course_completions} cc ON cc.timecompleted > 0 AND cc.course = e.courseid AND cc.userid = ue.userid $sql_compl
                         JOIN {grade_items} gi ON gi.itemtype = 'course' AND gi.courseid = e.courseid
                         LEFT JOIN {grade_grades} g ON g.userid = ue.userid AND g.itemid = gi.id AND g.finalgrade IS NOT NULL
                    WHERE e.courseid>0 {$sql_filter4} {$sql_vendor_filter2}
                    GROUP BY e.courseid) e ON e.courseid=c.id
                LEFT JOIN (SELECT courseid, SUM(timespend) AS timespend, SUM(visits) AS visits
                             FROM {local_intelliboard_tracking}
                            WHERE id > 0 {$sql_filter_tracking} {$sql_vendor_filter1}
                         GROUP BY courseid
                          ) l ON l.courseid = c.id
                LEFT JOIN {course_categories} cat ON cat.id = c.category
                $sql_join
            WHERE c.id > 0 $sql_filter {$sql_vendor_filter} GROUP BY c.id $sql_having $sql_order", $params);
    }
    public function report209($params)
    {
        global $CFG;
        $columns = array_merge(array(
            "c.id",
            "c.shortname",
            "c.fullname",
            "u.idnumber",
            "u.email",
            "u.firstname",
            "u.lastname",
            "ats.sessdate",
            "stg.description",
            "atl.timetaken",
            "teacher",
        ), $this->get_filter_columns($params, [null]));

        $sql_columns = $this->get_columns($params, [null]);
        $sql_filter = $this->get_teacher_sql($params, ["c.id" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filterdate_sql($params, "atl.timetaken");
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_order = $this->get_order_sql($params, $columns);

        if(!empty($params->custom2)){
            $sql_filter .= $this->get_filter_in_sql(explode(",", $params->custom2), "stg.acronym");
        }
        $sql_teacher_roles = $this->get_filter_in_sql($params->teacher_roles, "ra.roleid");
        if ($CFG->dbtype == 'pgsql') {
            $group_concat = "string_agg( DISTINCT CONCAT(u.firstname,' ',u.lastname), ', ')";
        } else {
            $group_concat = "GROUP_CONCAT(DISTINCT CONCAT(u.firstname,' ',u.lastname) SEPARATOR ', ')";
        }


        return $this->get_report_data("
            SELECT CONCAT(u.id,'_',c.id,'_',atl.id) AS id,
                u.id AS userid,
                u.idnumber,
                u.email,
                u.firstname,
                u.lastname,
                c.id AS course_id,
                c.fullname AS course_name,
                c.shortname AS course_shortname,
                ats.sessdate,
                stg.description AS status,
                atl.timetaken,
                (SELECT $group_concat
                    FROM {role_assignments} AS ra
                             JOIN {user} u ON ra.userid = u.id
                             JOIN {context} AS ctx ON ctx.id = ra.contextid
                    WHERE ctx.instanceid = c.id AND ctx.contextlevel = 50 $sql_teacher_roles
                   ) AS teacher
                $sql_columns
            FROM {attendance} a
                JOIN {course} c ON c.id = a.course
                JOIN {attendance_sessions} ats ON ats.attendanceid = a.id
                JOIN {attendance_log} atl ON ats.id = atl.sessionid
                JOIN {attendance_statuses} stg ON stg.id = atl.statusid AND stg.deleted = 0 AND stg.visible = 1 AND stg.attendanceid = ats.attendanceid
                JOIN {user} u ON atl.studentid = u.id
            WHERE c.id > 0 $sql_filter $sql_having $sql_order", $params);
    }

    public function report212($params) {
        global $DB;

        if (!$DB->get_manager()->table_exists('local_intellicart')) {
            return ["data" => []];
        }

        $columns = array_merge(array(
            "icmp.companies",
            "CONCAT(u.firstname, ' ', u.lastname)",
            "u.email",
            "p.name",
            "l.timemodified",
            "lis.expiration",
            "ase.assignees",
        ), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_filterdate_sql($params, "l.timemodified");
        $sql_manager_filter = $this->vendor_manager_filter("u.id", $params);

        $assigneesfield = get_operator(
            "GROUP_CONCAT", "CONCAT(u1.firstname, ' ', u1.lastname)", ["separator" => ", "]
        );
        $companiesfield = get_operator(
            "GROUP_CONCAT", "liv.name", ["separator" => ", "]
        );

        return $this->get_report_data(
            "SELECT l.id, icmp.companies, CONCAT(u.firstname, ' ', u.lastname) AS purchaser, u.email,
                    u.firstname, u.lastname,
                    p.name AS license, l.timemodified AS purchase_date,
                    lis.expiration AS expiration_date, ase.assignees {$sql_columns}
               FROM {local_intellicart_logs} l
               JOIN {user} u ON u.id = l.userid
               JOIN {local_intellicart_products} p ON p.id = l.instanceid
               JOIN {local_intellicart_seats} lis ON lis.userid = u.id AND
                                                     lis.productid = p.id AND
                                                     lis.checkoutid = l.checkoutid
          LEFT JOIN (SELECT l1.instanceid,
                            {$assigneesfield} AS assignees
                       FROM {local_intellicart_logs} l1
                       JOIN {user} u1 ON u1.id = l1.userid
                      WHERE l1.type = 'usedseat'
                   GROUP BY l1.instanceid
                    ) ase ON ase.instanceid = lis.id
          LEFT JOIN (SELECT liu.userid, {$companiesfield} AS companies
                       FROM {local_intellicart_users} liu
                       JOIN {local_intellicart_vendors} liv ON liv.id = liu.instanceid
                      WHERE liu.type = 'vendor' AND liu.role = 'manager'
                   GROUP BY liu.userid
                    ) icmp ON icmp.userid = u.id
              WHERE l.status = 'completed' AND l.type = 'seat' {$sql_manager_filter} {$sql_filter}
                    {$sql_having}
                    {$sql_order}",
            $params
        );
    }

    public function report213($params) {
        global $DB;
        if (!$params->custom) {
            return [];
        }

        $columns = array_merge(array(
            "u.firstname",
            "u.lastname",
            "u.email",
            "st.attempt",
            "sa.startdate",
            "sa.enddate",
            "sc.score",
            "status",
        ), $this->get_filter_columns($params));

        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_scorm_filter1 = $this->get_filter_in_sql($params->custom, "scormid");
        $sql_scorm_filter2 = $this->get_filter_in_sql($params->custom, "scormid", false);
        $sql_scorm_filter3 = $this->get_filter_in_sql($params->custom, "scormid", false);
        $sql_scorm_filter4 = $this->get_filter_in_sql($params->custom, "scormid", false);
        $sql_scorm_filter5 = $this->get_filter_in_sql($params->custom, "s.id", false);

        if ($items = $DB->get_records_sql("SELECT DISTINCT SUBSTRING_INDEX(SUBSTRING_INDEX(element, '.', 2), '.', -1) AS element  FROM {scorm_scoes_track} WHERE element LIKE 'cmi.interactions_%.id' $sql_scorm_filter1", $this->params)) {
            foreach($items as $item) {
                $sql_columns .= ", (SELECT value FROM {scorm_scoes_track} WHERE userid=st.userid AND attempt=st.attempt AND element='cmi.".$item->element.".student_response') AS ".$item->element;
                $columns[] = $item->element;
            }
        }

        $sql_having = $this->get_filter_sql($params, $columns, false);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users"]);
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "e.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "ue.");
        $sql_filter .= $this->get_filterdate_sql($params, "sa.enddate");
        $sql_manager_filter = $this->vendor_manager_filter("u.id", $params);

        return $this->get_report_data("
          SELECT DISTINCT CONCAT(u.id, '_', COALESCE(st.attempt, 0)) AS uniqueid,
                    st.scormid AS scormid,
                    st.attempt AS attempt,
                    u.id AS userid,
                    u.firstname,
                    u.lastname,
                    u.email,
                    sa.startdate,
                    sa.enddate,
                    sc.score,
                    CASE WHEN ss.status IS NULL THEN 'notattempted' ELSE ss.status END AS status
                    $sql_columns
            FROM {scorm} s
                JOIN {enrol} e ON e.courseid=s.course
                JOIN {user_enrolments} ue ON ue.enrolid=e.id
                JOIN {user} u ON ue.userid=u.id
                LEFT JOIN {scorm_scoes_track} st ON st.scormid=s.id AND st.element='x.start.time' AND u.id=st.userid
                LEFT JOIN (SELECT userid, attempt, MIN(timemodified) AS startdate, MAX(timemodified) AS enddate FROM {scorm_scoes_track} WHERE $sql_scorm_filter2 GROUP BY userid, attempt) sa ON sa.userid=st.userid AND sa.attempt=st.attempt
                LEFT JOIN (SELECT userid, attempt, MAX(value) AS score FROM {scorm_scoes_track} WHERE $sql_scorm_filter3 AND element='cmi.core.score.raw' GROUP BY userid, attempt) sc ON sc.userid=st.userid AND sc.attempt=st.attempt
                LEFT JOIN (SELECT userid, attempt, MAX(value) AS status FROM {scorm_scoes_track} WHERE $sql_scorm_filter4 AND element='cmi.core.lesson_status' GROUP BY userid, attempt) ss ON ss.userid=st.userid AND ss.attempt=st.attempt
            WHERE $sql_scorm_filter5 $sql_filter $sql_manager_filter $sql_having $sql_order", $params);
    }

    public function report213_header($params) {
        global $DB;

        if (!$params->custom) {
            return [];
        }
        $sql_scorm_filter1 = $this->get_filter_in_sql($params->custom, "scormid");
        $sql_scorm_filter2 = $this->get_filter_in_sql($params->custom, "id", false);

        $data = [];
        $data['questions'] = $DB->get_records_sql("SELECT DISTINCT SUBSTRING_INDEX(SUBSTRING_INDEX(element, '.', 2), '.', -1) AS question, value AS name FROM {scorm_scoes_track} WHERE element LIKE 'cmi.interactions_%.id' $sql_scorm_filter1", $this->params);
        $data['scorm_name'] = $DB->get_record_sql("SELECT name FROM {scorm} WHERE $sql_scorm_filter2", $this->params);

        return $data;
    }

    function get_cohort_stats($params)
    {
        global $DB, $CFG;

        if (!$params->cohortid) {
          return [];
        }
        $sql_filter = $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_in_sql($params->cohortid, "cm.cohortid");

        $sql_course = $this->get_filter_course_sql($params, "c.");
        $sql_course .= $this->get_filter_enrol_sql($params, "e.");
        $sql_course .= $this->get_filter_enrol_sql($params, "ue.");
        $sql_course .= $this->get_filter_in_sql($params->cohortid, "cm.cohortid");

        if ($CFG->dbtype == 'pgsql') {
            $cohorts = "string_agg( DISTINCT coh.name, ', ')";
        } else {
            $cohorts = "GROUP_CONCAT( DISTINCT coh.name)";
        }

        $data = $DB->get_record_sql("SELECT
          $cohorts AS cohorts,
          COUNT(DISTINCT cm.userid) AS cohort_size,
          COUNT(DISTINCT CASE WHEN u.lastaccess > 0 THEN u.id ELSE null END) AS registered,
          COUNT(DISTINCT f.userid) AS completed
          FROM {cohort} coh, {cohort_members} cm
            LEFT JOIN {user} u ON u.id = cm.userid
            LEFT JOIN {feedback_completed} f ON f.userid = cm.userid
          WHERE coh.id = cm.cohortid $sql_filter", $this->params);

        $data->courses = $DB->get_records_sql("SELECT DISTINCT c.id, c.fullname FROM {user_enrolments} ue, {enrol} e, {course} c, {cohort_members} cm
          WHERE c.id = e.courseid AND ue.userid = cm.userid AND e.id = ue.enrolid $sql_course", $this->params);

        return $data;
    }

    function get_cohort_feedbacks($params)
    {
        global $DB;

        if (!$params->cohortid) {
          return [];
        }
        $sql_course = $this->get_filter_course_sql($params, "c.");
        $sql_course .= $this->get_filter_enrol_sql($params, "e.");
        $sql_course .= $this->get_filter_enrol_sql($params, "ue.");
        $sql_course .= $this->get_filter_in_sql($params->cohortid, "cm.cohortid");

        return $DB->get_records_sql("SELECT
          DISTINCT f.id, f.name
          FROM {feedback} f, {feedback_item} i, {user_enrolments} ue, {enrol} e, {course} c, {cohort_members} cm
          WHERE f.course = c.id AND i.feedback = f.id AND c.id = e.courseid AND ue.userid = cm.userid AND e.id = ue.enrolid $sql_course", $this->params);
    }

    function get_cohort_feedback_items($params)
    {
        global $DB;

        if (!$params->cohortid) {
          return [];
        }
        $sql_course = $this->get_filter_course_sql($params, "c.");
        $sql_course .= $this->get_filter_enrol_sql($params, "e.");
        $sql_course .= $this->get_filter_enrol_sql($params, "ue.");
        $sql_course .= $this->get_filter_in_sql($params->cohortid, "cm.cohortid");

        return $DB->get_records_sql("SELECT
          DISTINCT i.id, i.name, i.label
          FROM {feedback} f, {feedback_item} i, {user_enrolments} ue, {enrol} e, {course} c, {cohort_members} cm
          WHERE f.course = c.id AND i.feedback = f.id AND c.id = e.courseid AND ue.userid = cm.userid AND e.id = ue.enrolid $sql_course", $this->params);
    }

    function get_course_feedback_items($params)
    {
        global $DB;

        if (!$params->courseid) {
          return [];
        }

        return $DB->get_records_sql("SELECT DISTINCT i.id, i.name, i.label
          FROM {feedback} f, {feedback_item} i
          WHERE i.feedback = f.id AND f.course = :course", ['course' => $params->courseid]);
    }


    function get_assignment_grading_definitions($params){
        global $DB;

        return $DB->get_records_sql("
                    SELECT
                        ggc.id,
                        ggc.shortname
                    FROM {modules} m
                        JOIN {course_modules} cm ON cm.module=m.id
                        JOIN {context} ctx ON ctx.instanceid=cm.id AND ctx.contextlevel=70
                        JOIN {grading_areas} ga ON ga.contextid=ctx.id
                        JOIN {grading_definitions} gd ON gd.areaid=ga.id AND gd.method='guide'
                        JOIN {gradingform_guide_criteria} ggc ON ggc.definitionid=gd.id
                    WHERE m.name='assign' AND cm.instance=:instance
                    ORDER BY ggc.sortorder", array('instance'=>(int)$params->custom));
    }

    function get_question_tags($params){
        global $DB;

        return $DB->get_records_sql("
                            SELECT
                                  DISTINCT t.id,
                                  t.rawname
                            FROM {tag_instance} ti
                              JOIN {tag} t ON t.id=ti.tagid
                            WHERE ti.component='core_question' AND ti.itemtype='question'");
    }

    function get_course_feedback($params){
        global $DB;

        $sql_filter = $this->get_filter_in_sql($params->courseid,'f.course',false);

        return $DB->get_records_sql("SELECT f.id,f.name
                                      FROM {feedback} f
                                      WHERE $sql_filter",$this->params);
    }

    function get_course_checklists($params){
        global $DB;

        $sql_filter = $this->get_filter_in_sql($params->courseid,'ch.course',false);

        return $DB->get_records_sql("SELECT ch.id,ch.name
                                      FROM {checklist} ch
                                      WHERE $sql_filter",$this->params);
    }

    function get_course_checklist_items($params){
        global $DB;

        return ($params->custom) ? $DB->get_records('checklist_item', array('checklist'=>(int)$params->custom, 'userid'=>0), '', 'id,displaytext') : [];
    }

    function get_role_users($params){
        global $DB;

        $sql_filter = $this->get_filter_in_sql($params->custom,'ra.roleid');
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_teacher_sql($params, ["u.id" => "users"]);

        return $DB->get_records_sql("
            SELECT u.id, CONCAT(u.firstname,' ',u.lastname) AS name
            FROM {role_assignments} ra
              INNER JOIN {user} u ON u.id=ra.userid
            WHERE u.id IS NOT NULL $sql_filter",$this->params);
    }

    function get_course_grade_categories($params)
    {
        global $CFG;
        require($CFG->libdir.'/gradelib.php');
        $display_types = array(
            GRADE_DISPLAY_TYPE_REAL => 'real',
            GRADE_DISPLAY_TYPE_PERCENTAGE => 'percentage',
            GRADE_DISPLAY_TYPE_LETTER => 'letter',
            GRADE_DISPLAY_TYPE_REAL_PERCENTAGE => 'realpercentage',
            GRADE_DISPLAY_TYPE_REAL_LETTER => 'realletter',
            GRADE_DISPLAY_TYPE_LETTER_REAL => 'letterreal',
            GRADE_DISPLAY_TYPE_LETTER_PERCENTAGE => 'letterpercentage',
            GRADE_DISPLAY_TYPE_PERCENTAGE_LETTER => 'percentageletter',
            GRADE_DISPLAY_TYPE_PERCENTAGE_REAL => 'percentagereal'
        );

        $courses = explode(',', $params->courseid);
        $items = array();
        foreach($courses as $course){
            $items += (array)grade_item::fetch_all(array('itemtype' => 'category', 'courseid' => $course));
        }
        foreach($courses as $course){
            $items += (array)grade_item::fetch_all(array('itemtype' => 'course', 'courseid' => $course));
        }

        $names = array();
        $ranges = array();
        foreach($items as $item){
            if(!$item){
                continue;
            }

            $parts = [];
            if ($item->is_course_item()) {
                $parts[] = ['translate' => true, 'key' => 'course'];
                $parts[] = ['translate' => true, 'key' => 'total'];
                $format = '%s %s (%s)';

            } else if ($item->is_category_item()) {
                $category = $item->load_parent_category();
                $value = $category->get_name();
                $parts[] = ['translate' => false, 'value' => $value];
                $parts[] = ['translate' => true, 'key' => 'total'];
                $format = '%s %s (%s)';
            } else {
                $parts[] = ['translate' => true, 'key' => 'grade'];
                $format = '%s (%s)';
            }

            $parts[] = ['translate' => true, 'key' => $display_types[$CFG->grade_displaytype]];
            $names[$item->get_name(true)] = ['parts' => $parts, 'format' => $format];
            $ranges[$item->get_name(true)] = $item->get_formatted_range(
                $CFG->grade_displaytype, $CFG->grade_decimalpoints
            );
        }

        return array('data'=>$names,'ranges'=>$ranges);

    }

    function get_data_question_answers($params)
    {
        $sql_filter = $this->get_filter_in_sql($params->filter,'dc.fieldid', false);

        return $this->get_report_data("
            SELECT
              MAX(dc.id) AS id,
              dc.content,
              COUNT(1) AS count_answers
            FROM {data_content} dc
            WHERE $sql_filter
            GROUP BY dc.content", $params, false);

    }
    function get_course_databases($params)
    {
        global $DB;

        $sql = $this->get_teacher_sql($params, ["c.id" => "courses"]);
        if($params->courseid){
            $sql .= " WHERE ".$this->get_filter_in_sql($params->courseid,'d.course',false);
        }

        return $DB->get_records_sql("
            SELECT
                d.id,
                d.name,
                c.fullname as course,
                c.id as courseid
            FROM {data} d
                JOIN {course} c ON c.id = d.course $sql", $this->params);
    }
    function get_databases_question($params)
    {
        global $DB;

        $sql = $this->get_filter_in_sql($params->custom3,'df.dataid');
        $sql .= $this->get_filter_in_sql($params->custom2,'df.id');
        return $DB->get_records_sql("
                SELECT
                  df.id,
                  df.name,
                  d.name as data_name,
                  d.id as data_id
                FROM {data_fields} df
                  LEFT JOIN {data} d ON d.id=df.dataid
                WHERE df.id > 0 $sql", $this->params);
    }
    function get_history_items($params)
    {
        global $DB;

        $sql = $this->get_filter_in_sql($params->courseid, 'courseid');

        return $this->get_report_data("SELECT id, oldid, courseid, itemtype FROM {grade_items_history} WHERE id > 0 $sql", $params);
    }
    function get_history_grades($params)
    {
        global $DB;

        $sql = $this->get_filter_in_sql($params->custom, 'itemid');

        return $this->get_report_data("SELECT id, oldid, timemodified, itemid, userid, finalgrade FROM {grade_grades_history} WHERE id > 0 $sql", $params);
    }
    function get_competency($params)
    {
        global $DB;
        return $DB->get_records('competency',array(),'sortorder ASC','id,shortname');
    }

    function get_competency_templates($params) {
        global $DB;

        $sqlfilter = $this->get_filter_in_sql($params->cohortid, "ctc.cohortid");

        return $DB->get_records_sql(
            "SELECT ct.id, ct.shortname
               FROM {competency_template} ct
          LEFT JOIN {competency_templatecohort} ctc ON ctc.templateid = ct.id
              WHERE ct.visible = 1 {$sqlfilter}
           ORDER BY ct.shortname ASC", $this->params
        );
    }

    function get_attendance_statuses($params)
    {
        return $this->get_report_data("SELECT acronym, MAX(description) AS description FROM {attendance_statuses} WHERE visible=1 AND deleted=0 GROUP BY acronym", $params, false);
    }


    public function analytic1($params){
        global $DB,$CFG;

        $where_sql = "";
        $select_sql = $this->get_suspended_sql($params, 'log.courseid', 'log.userid');
        $sql_vendor_filter = $this->vendor_filter('log.userid', 'log.courseid', $params);

        $params->custom2 = clean_param($params->custom2, PARAM_TEXT);

        if(!empty($params->custom) || $params->custom === 0){
            $select_sql .= " LEFT JOIN {role_assignments} ra ON log.contextid=ra.contextid and ra.userid=log.userid ";
            $params->custom = clean_param($params->custom, PARAM_SEQUENCE);

            $sql_enabled = $this->get_filter_in_sql($params->custom,'ra.roleid',false);
            if(in_array(0,explode(',', $params->custom))){
                $where_sql = "AND ($sql_enabled OR ra.roleid IS NULL)";
            }else{
                $where_sql = "AND $sql_enabled";
            }
        }

        if(empty($params->courseid)){
            return array("data" => array());
        }

        $where_sql .= $this->get_filter_in_sql($params->courseid,'log.courseid');
        $where_sql .= $this->get_filterdate_sql($params, 'log.timecreated');
        $where_sql .= $this->get_teacher_sql($params, ["log.courseid" => "courses"]);

        if ($CFG->dbtype == 'pgsql') {
            $DB->execute("SET SESSION TIME ZONE '$params->custom2'");
            $data = $DB->get_records_sql("
                  SELECT MIN(log.id) AS id,
                       COUNT(log.id) AS count,
                       (CASE WHEN extract(dow from to_timestamp(log.timecreated))=0 THEN 6 ELSE extract(dow from to_timestamp(log.timecreated))-1 END) AS day,

                       (CASE WHEN extract(hour from to_timestamp(log.timecreated))>=6 AND extract(hour from to_timestamp(log.timecreated))<12 THEN '1' ELSE (
                             CASE WHEN extract(hour from to_timestamp(log.timecreated))>=12 AND extract(hour from to_timestamp(log.timecreated))<17 THEN '2' ELSE (
                                  CASE WHEN extract(hour from to_timestamp(log.timecreated))>=17 AND extract(hour from to_timestamp(log.timecreated))<=23 THEN '3' ELSE (
                                       CASE WHEN extract(hour from to_timestamp(log.timecreated))>=0 AND extract(hour from to_timestamp(log.timecreated))<6 THEN '4' ELSE 'undef' END
                                  ) END
                             ) END
                       ) END) AS time_of_day
                  FROM {logstore_standard_log} log
                    $select_sql
                  WHERE log.id > 0 $where_sql {$sql_vendor_filter}
                  GROUP BY day,time_of_day
                  ORDER BY time_of_day, day
                ", $this->params);
        } else {
            $DB->execute("SET @@session.time_zone = :timezone", array('timezone'=>$params->custom2));
            $data = $DB->get_records_sql("
                  SELECT MIN(log.id) AS id,
                         COUNT(log.id) AS count,
                         WEEKDAY(FROM_UNIXTIME(log.timecreated,'%Y-%m-%d %T')) AS day,
                         IF(FROM_UNIXTIME(log.timecreated,'%H')>=6 && FROM_UNIXTIME(log.timecreated,'%H')<12,'1',
                             IF(FROM_UNIXTIME(log.timecreated,'%H')>=12 && FROM_UNIXTIME(log.timecreated,'%H')<17,'2',
                             IF(FROM_UNIXTIME(log.timecreated,'%H')>=17 && FROM_UNIXTIME(log.timecreated,'%H')<=23,'3',
                             IF(FROM_UNIXTIME(log.timecreated,'%H')>=0 && FROM_UNIXTIME(log.timecreated,'%H')<6,'4','undef')))) AS time_of_day
                  FROM {logstore_standard_log} log
                    $select_sql
                  WHERE log.id > 0 $where_sql {$sql_vendor_filter}
                  GROUP BY day,time_of_day
                  ORDER BY time_of_day, day
                ", $this->params);
        }

        return array("data" => $data);
    }
    public function analytic2($params){
        global $DB;
        $fields = explode(',',$params->custom2);

        $field_ids = array(0);
        foreach($fields as $field){
            if(strpos($field,'=') > 0){
                list($id,$name) = explode('=',$field);
                $field_ids[] = $id;
            }
        }

        $sql_enabled = $this->get_filter_in_sql(implode(',',$field_ids),'uif.id',false);

        if(empty($sql_enabled)){
            return array("data" => array(), 'user' => array());
        }

        $data = $DB->get_records_sql("
                  SELECT MAX(uid.id),
                         uif.id AS fieldid,
                         uif.name,
                         COUNT(uid.userid) AS users,
                         uid.data
                  FROM {user_info_field} uif
                     LEFT JOIN {user_info_data} uid ON uif.id=uid.fieldid
                  WHERE $sql_enabled
                  GROUP BY uid.data,uif.id
                ", $this->params);

        if(isset($params->custom) && !empty($params->custom)){
            $params->custom = json_decode($params->custom);
            $params->custom->field_id = clean_param($params->custom->field_id,PARAM_INT);
            $params->custom->field_value = clean_raw($params->custom->field_value,false);

            $join_sql = $select_sql = $where_sql = '';
            $where = array();
            $coll = array("u.firstname", "u.lastname", "u.email");
            $grade_avg_sql = intelliboard_grade_sql(true,$params, 'g.', 0, 'gi.',true);
            $enabled_tracking = false;
            foreach($fields as $field){
                if($field == 'average_grade'){
                    $join_sql .= " JOIN {grade_items} gi ON gi.itemtype = 'course'
                                      LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.userid = u.id ";
                    $select_sql .= " $grade_avg_sql AS average_grade, ";
                    $coll[] = "average_grade";
                }elseif($field == 'courses_enrolled'){
                    $join_sql .= " JOIN {user_enrolments} ue ON ue.userid=u.id
                                      LEFT JOIN {enrol} e ON e.id = ue.enrolid AND e.courseid>1";
                    $select_sql .= " COUNT(DISTINCT e.courseid) AS courses_enrolled, ";
                    $coll[] = "courses_enrolled";
                }elseif($field == 'total_visits' || $field == 'time_spent'){
                    $join_sql .= (!$enabled_tracking)?" LEFT JOIN (SELECT lit.userid, SUM(lit.timespend) as timespend, SUM(lit.visits) as visits FROM {local_intelliboard_tracking} lit GROUP BY lit.userid) lit ON lit.userid = u.id ":'';
                    $select_sql .= ($field == 'total_visits')?' MAX(lit.visits) as total_visits, ':' MAX(lit.timespend) as time_spent, ';
                    $coll[] = $field;
                    $enabled_tracking = true;
                }else{
                    if(empty($field)) continue;
                    list($id,$name) = explode('=',$field);
                    $join_sql .= " LEFT JOIN {user_info_data} uid{$id} ON uid{$id}.userid=u.id AND uid{$id}.fieldid={$id} ";
                    $select_sql .= " MAX(uid{$id}.data) as field_{$id}, ";
                    if($params->custom->field_id != 0){
                        $where[] = " (uid{$id}.fieldid=:field_id{$id} AND uid{$id}.data=:field_value{$id}) ";
                        $this->params["field_id{$id}"] = $params->custom->field_id;
                        $this->params["field_value{$id}"] = $params->custom->field_value;
                    }
                    $coll[] = "field_{$id}";
                }
            }

            if(!empty($where)) {
                $where_sql = 'AND ('.implode('OR',$where).')';
            }

            if(!empty($params->custom3) || $params->custom3 === 0 || $params->custom3 === '0'){
                $join_sql .= " LEFT JOIN (SELECT ra.userid, ra.roleid FROM {role_assignments} ra GROUP BY ra.userid, ra.roleid)ra ON ra.userid=u.id ";
                $params->custom3 = clean_param($params->custom3, PARAM_SEQUENCE);

                $sql_enabled = $this->get_filter_in_sql($params->custom3,'ra.roleid',false);
                if(in_array(0,explode(',', $params->custom3))){
                    $where_sql .= ($params->custom3 === 0 || $params->custom3 === '0')?"AND ra.roleid IS NULL":"AND ($sql_enabled OR ra.roleid IS NULL)";
                }else{
                    $where_sql .= "AND $sql_enabled";
                }
            }

            $order_sql = $this->get_order_sql($params, $coll);
            $limit_sql = $this->get_limit_sql($params);
            $where_sql .= $this->get_teacher_sql($params, ["u.id" => "users"]);

            $sql = "SELECT DISTINCT u.id,
                           u.firstname,
                           u.lastname,
                           u.email,
                           $select_sql
                           u.id AS userid
                    FROM {user} u
                        $join_sql
                    WHERE u.id>1 $where_sql
                    GROUP BY u.id $order_sql";

            $users = $DB->get_records_sql($sql.$limit_sql,$this->params);
            return array('users'=>$users,'data'=>$data);
        }

        $join_sql = $select_sql = '';
        $sql_params = array();
        foreach($fields as $field){
            if($field == 'average_grade' || $field == 'total_visits' || $field == 'time_spent' || $field == 'courses_enrolled'){
                $select_sql .= " 0 as {$field}, ";
            }else{
                if(empty($field)) continue;
                list($id,$name) = explode('=',$field);
                $join_sql .= " LEFT JOIN {user_info_data} uid{$id} ON uid{$id}.userid=u.id AND uid{$id}.fieldid=:fieldid{$id} ";
                $select_sql .= " uid{$id}.data as field_{$id}, ";
                $sql_params["fieldid{$id}"] = $id;
            }

        }
        $where_sql = $this->get_teacher_sql($params, ["u.id" => "users"]);

        if(!empty($params->custom3) || $params->custom3 === 0 || $params->custom3 === '0'){
            $join_sql .= " LEFT JOIN (SELECT ra.userid, ra.roleid FROM {role_assignments} ra GROUP BY ra.userid, ra.roleid)ra ON ra.userid=u.id ";
            $params->custom3 = clean_param($params->custom3, PARAM_SEQUENCE);

            $sql_enabled = $this->get_filter_in_sql($params->custom3,'ra.roleid',false);
            if(in_array(0,explode(',', $params->custom3))){
                $where_sql .= ($params->custom3 === 0 || $params->custom3 === '0')?"AND ra.roleid IS NULL":"AND ($sql_enabled OR ra.roleid IS NULL)";
            }else{
                $where_sql .= "AND $sql_enabled";
            }
            $sql_params = array_merge($this->params, $sql_params);
        }

        $user = $DB->get_record_sql("SELECT DISTINCT u.id,
                                            u.firstname,
                                            u.lastname,
                                            u.email,
                                            $select_sql
                                            u.id AS userid
                                     FROM {user} u
                                        $join_sql
                                     WHERE u.id>0 $where_sql
                                     LIMIT 1
                                    ",$sql_params);
        return array("data" => $data, 'user'=>$user);
    }

    public function get_quizes($params){
        global $DB;

        $sql = $this->get_teacher_sql($params, ["c.id" => "courses"]);
        $sql .= $this->get_filter_in_sql($params->courseid,'q.course');
        $sql .= $this->vendor_filter(null, 'q.course', $params);
        $sql .= $this->get_filter_course_sql($params, 'c.');

        $data = $DB->get_records_sql(
            "SELECT q.id,
                    q.name,
                    c.id AS courseid,
                    c.fullname AS coursename
               FROM {quiz} q
               JOIN {course} c ON c.id = q.course
              WHERE c.id > 0 {$sql}",
            $this->params
        );

        return array('data'=>$data);
    }

    public function analytic3($params){
        global $DB, $CFG;
        $data = array();
        $sql_vendor_filter = $this->vendor_filter('qa.userid', null, $params);
        $sql_vendor_filter1 = $this->vendor_filter('g.userid', 'q.course', $params);
        if(is_numeric($params->custom)){
            $join_sql = $this->get_suspended_sql($params, 'q.course', 'qa.userid');
            $where = '';
            if($params->custom > 0){
                $where .= ' AND q.id=:custom';
                $this->params['custom'] = $params->custom;
            }
            if($params->courseid > 0){
                $where .= " AND q.course=:courseid";
                $this->params['courseid'] = $params->courseid;
            }

            $data = $DB->get_records_sql("
                      SELECT MAX(qas.id) AS id,
                             que.id,
                             que.name,
                             SUM(CASE WHEN qas.state LIKE '%partial' OR qas.state LIKE '%right' THEN 1 ELSE 0 END) AS rightanswer,
                             COUNT(qas.id) AS allanswer
                      FROM {quiz} q
                        LEFT JOIN {quiz_attempts} qa ON qa.quiz=q.id {$sql_vendor_filter}
                        LEFT JOIN {question_attempts} qua ON qua.questionusageid=qa.uniqueid
                        LEFT JOIN {question_attempt_steps} qas ON qas.questionattemptid=qua.id AND qas.fraction IS NOT NULL
                        LEFT JOIN {question} que ON que.id=qua.questionid
                        $join_sql
                      WHERE que.id IS NOT NULL $where
                      GROUP BY que.id
                     ", $this->params);

            if ($CFG->dbtype == 'pgsql') {
                $time = $DB->get_records_sql("
						  SELECT MAX(qa.id) AS id,
								COUNT(qa.id) AS count,
								(CASE WHEN extract(dow from to_timestamp(qa.timefinish))=0 THEN 6 ELSE extract(dow from to_timestamp(qa.timefinish))-1 END) AS day,
							   (CASE WHEN extract(hour from to_timestamp(qa.timefinish))>=6 AND extract(hour from to_timestamp(qa.timefinish))<12 THEN '1' ELSE (
									 CASE WHEN extract(hour from to_timestamp(qa.timefinish))>=12 AND extract(hour from to_timestamp(qa.timefinish))<17 THEN '2' ELSE (
										  CASE WHEN extract(hour from to_timestamp(qa.timefinish))>=17 AND extract(hour from to_timestamp(qa.timefinish))<=23 THEN '3' ELSE (
											   CASE WHEN extract(hour from to_timestamp(qa.timefinish))>=0 AND extract(hour from to_timestamp(qa.timefinish))<6 THEN '4' ELSE 'undef' END
										  ) END
									 ) END
							   ) END) AS time_of_day
						 FROM {quiz} q
							LEFT JOIN {quiz_attempts} qa ON qa.quiz=q.id AND qa.state='finished' AND qa.sumgrades IS NOT NULL {$sql_vendor_filter}
							$join_sql
						 WHERE q.id>0 $where
						 GROUP BY day,time_of_day
						 ORDER BY time_of_day, day
						", $this->params);
            }else{
                $time = $DB->get_records_sql("
                      SELECT qa.id,
                             COUNT(qa.id) AS count,
                             WEEKDAY(FROM_UNIXTIME(qa.timefinish,'%Y-%m-%d %T')) AS day,
                             IF(FROM_UNIXTIME(qa.timefinish,'%H')>=6 && FROM_UNIXTIME(qa.timefinish,'%H')<12,'1',
                                 IF(FROM_UNIXTIME(qa.timefinish,'%H')>=12 && FROM_UNIXTIME(qa.timefinish,'%H')<17,'2',
                                 IF(FROM_UNIXTIME(qa.timefinish,'%H')>=17 && FROM_UNIXTIME(qa.timefinish,'%H')<=23,'3',
                                 IF(FROM_UNIXTIME(qa.timefinish,'%H')>=0 && FROM_UNIXTIME(qa.timefinish,'%H')<6,'4','undef')))) AS time_of_day
                     FROM {quiz} q
                        LEFT JOIN {quiz_attempts} qa ON qa.quiz=q.id AND qa.state='finished' AND qa.sumgrades IS NOT NULL {$sql_vendor_filter}
                        $join_sql
                     WHERE q.id>0 $where
                     GROUP BY day,time_of_day
                     ORDER BY time_of_day, day
                    ", $this->params);
            }

            $join_sql = $this->get_suspended_sql($params, 'q.course', 'g.userid');
            $grade_single_sql = intelliboard_grade_sql(false,$params, 'g.', 0, 'gi.', true);
            $grades = $DB->get_records_sql("
                        SELECT MAX(g.id) AS id,
                               q.id AS quiz_id,
                               q.name AS quiz_name,
                               MAX(ROUND(((gi.gradepass - gi.grademin)/(gi.grademax - gi.grademin))*100,0)) AS gradepass,
                               COUNT(DISTINCT g.userid) AS users,
                               $grade_single_sql AS grade
                        FROM {quiz} q
                           LEFT JOIN {grade_items} gi ON gi.itemtype='mod' AND gi.itemmodule='quiz' AND gi.iteminstance=q.id
                           LEFT JOIN {grade_grades} g ON g.itemid=gi.id AND g.userid != 2 AND g.rawgrade IS NOT NULL {$sql_vendor_filter1}
                           $join_sql
                        WHERE g.rawgrade IS NOT NULL $where
                        GROUP BY $grade_single_sql,quiz_id
                       ", $this->params);
        }

        return array("data" => $data, "time"=>$time, "grades"=>$grades);
    }
    public function get_incorrect_answers($params){
        global $DB, $CFG;

        $join_sql = $this->get_suspended_sql($params, 'q.course', 'u.id');
        $this->params = array('questionid'=>$params->filter, 'quiz'=>$params->custom);
        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_vendor_filter = $this->vendor_filter('qa.userid', 'q.course', $params);
        if ($CFG->dbtype == 'pgsql') {
            $cohorts = "string_agg( DISTINCT coh.name, ', ')";
        } else {
            $cohorts = "GROUP_CONCAT( DISTINCT coh.name)";
        }

        return $DB->get_records_sql("
                    SELECT
                      qas.id,
                      que.name,
                      qua.responsesummary,
                      u.id AS userid,
                      u.firstname,
                      u.lastname,
                      (SELECT $cohorts FROM {cohort} coh, {cohort_members} ch WHERE coh.id = ch.cohortid AND ch.userid=u.id) AS cohort_name
                      $sql_columns
                    FROM {quiz_attempts} qa
                      JOIN {quiz} q ON qa.quiz=q.id
                      JOIN {question_attempts} qua ON qua.questionusageid=qa.uniqueid
                      JOIN {question_attempt_steps} qas ON qas.questionattemptid=qua.id AND qas.fraction IS NOT NULL
                      JOIN {question} que ON que.id=qua.questionid
                      JOIN {user} u ON u.id=qas.userid
                      $join_sql
                    WHERE qa.quiz=:quiz {$sql_vendor_filter} AND qua.questionid = :questionid AND qas.state NOT LIKE '%partial' AND qas.state NOT LIKE '%right'"
                , $this->params);
    }
    public function analytic4($params){
        global $DB, $CFG;

        if(!empty($params->custom)){
            if($params->custom == 'get_countries'){
                $filter_sql = $this->get_teacher_sql($params, ["u.id" => "users"]);
                $countries = $DB->get_records_sql("
                               SELECT u.id,
                                      u.country,
                                      uid.data AS state,
                                      COUNT(DISTINCT u.id) AS users
                               FROM {user} u
                                  LEFT JOIN {user_info_field} uif ON uif.shortname LIKE 'state'
                                  LEFT JOIN {user_info_data} uid ON uid.fieldid=uif.id AND uid.userid=u.id
                               WHERE u.country NOT LIKE '' $filter_sql
                               GROUP BY u.country,uid.data");
                return array("countries" => $countries);
            }else{
                $columns = array_merge(array(
                    "u.firstname", "u.lastname", "u.email", "u.country", "state", "course", "MIN(e.enrol)", "grade", "MAX(l.timespend)", "complete"
                ), $this->get_filter_columns($params));

                $where = array();
                $where_str = '';
                $custom = unserialize($params->custom);
                if(!empty($custom['country'])){
                    $this->params['country'] = clean_param($custom['country'],PARAM_ALPHANUMEXT);
                    $where[] = "u.country=:country";
                }
                if(isset($custom['state']) && !empty($custom['state'])){
                    $custom['state'] = clean_param($custom['state'],PARAM_ALPHANUMEXT);
                    $where[] = $DB->sql_like('uid.data', ":state", false, false);
                    $this->params['state'] = "%".$custom['state']."%";
                }
                if(isset($custom['enrol']) && !empty($custom['enrol'])){
                    $custom['enrol'] = clean_param($custom['enrol'],PARAM_ALPHANUMEXT);
                    $where[] = "e.enrol = :enrol";
                    $this->params['enrol'] = $custom['enrol'];
                }
                if(!empty($where))
                    $where_str = " AND ".implode(' AND ',$where);

                $where_sql = "WHERE ue.id IS NOT NULL ".$where_str;
                $where_sql .= $this->get_teacher_sql($params, ["u.id" => "users"]);
                $order_sql = $this->get_order_sql($params, $columns);
                $limit_sql = $this->get_limit_sql($params);
                $sql_columns = $this->get_columns($params, ["u.id"]);
                $join_sql = $this->get_suspended_sql($params, 'c.id', 'u.id');
                $grade_avg_sql = intelliboard_grade_sql(true,$params, 'g.', 0, 'gi.',true);
                if ($CFG->dbtype == 'pgsql') {
                    $group_concat = "string_agg( DISTINCT e.enrol, ', ')";
                } else {
                    $group_concat = "GROUP_CONCAT( DISTINCT e.enrol)";
                }
                $sql = "SELECT MIN(ue.id) AS id,
                               $grade_avg_sql AS grade,
                               c.enablecompletion,
                               MAX(cc.timecompleted) AS complete,
                               u.id AS uid,
                               u.email,
                               u.country,
                               MIN(uid.data) AS state,
                               u.firstname,
                               u.lastname,
                               $group_concat AS enrols,
                               c.id AS cid,
                               c.fullname AS course,
                               MAX(l.timespend) AS timespend
                               $sql_columns
                        FROM {user} u
                           JOIN {user_enrolments} ue ON u.id = ue.userid
                           JOIN {enrol} e ON e.id = ue.enrolid

                           JOIN {course} c ON c.id = e.courseid
                           LEFT JOIN {course_completions} cc ON cc.course = e.courseid AND cc.userid = ue.userid
                           LEFT JOIN {grade_items} gi ON gi.itemtype = 'course' AND gi.courseid=c.id
                           LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.userid=u.id
                           LEFT JOIN (SELECT lit.userid,
                                             lit.courseid,
                                             sum(lit.timespend) AS timespend
                                      FROM {local_intelliboard_tracking} lit
                                      GROUP BY lit.courseid, lit.userid) l ON l.courseid = c.id AND l.userid = u.id
                           LEFT JOIN {user_info_field} uif ON uif.shortname LIKE 'state'
                           LEFT JOIN {user_info_data} uid ON uid.fieldid=uif.id AND uid.userid=ue.userid
                           $join_sql
                        $where_sql
                        GROUP BY u.id, c.id $order_sql $limit_sql
                        ";

                $users = $DB->get_records_sql($sql, $this->params);
                return array("users" => $users);
            }
        }

        $filter_sql = $this->get_teacher_sql($params, ["ue.userid" => "users"]);
        $join_sql = $this->get_suspended_sql($params, 'e.courseid', 'ue.userid');
        $methods = $DB->get_records_sql("
                     SELECT MAX(e.id) as id,
                            e.enrol,
                            COUNT(DISTINCT ue.id) AS users
                     FROM {enrol} e
                        LEFT JOIN {user_enrolments} ue ON ue.enrolid=e.id
                        $join_sql
                     WHERE e.id>0 $filter_sql
                     GROUP BY e.enrol");

        $filter_sql = $this->get_teacher_sql($params, ["u.id" => "users"]);
        $countries = $DB->get_records_sql("
                       SELECT MAX(u.id) as id,
                              u.country,
                              uid.data AS state,
                              COUNT(DISTINCT u.id) AS users
                       FROM {user} u
                          LEFT JOIN {user_info_field} uif ON uif.shortname LIKE 'state'
                          LEFT JOIN {user_info_data} uid ON uid.fieldid=uif.id AND uid.userid=u.id
                       WHERE u.country NOT LIKE '' $filter_sql
                       GROUP BY u.country,uid.data");

        return array("methods" => $methods, "countries" => $countries);
    }
    public function analytic5($params){
        global $DB, $CFG;
        $join_sql = $this->get_suspended_sql($params, 'q.course', 'qa.userid');
        $sql_vendor_filter = $this->vendor_filter('qa.userid', 'q.course', $params);
        $params->custom = clean_param($params->custom,PARAM_INT);
        $this->params['custom1'] = $params->custom;
        $this->params['custom2'] = $params->custom;
        $this->params['custom3'] = $params->custom;
        $this->params['custom4'] = $params->custom;
        $this->params['custom5'] = $params->custom;

        if ($CFG->dbtype == 'pgsql') {
            $range_col = 'range';
        }else{
            $range_col = '`range`';
        }
        $data = $DB->get_records_sql("
                  SELECT MAX(qa.id) AS id,
                         CASE WHEN (qa.userid=max_att.userid AND qa.attempt=max_att.attempt) AND (qa.userid=min_att.userid AND qa.attempt=min_att.attempt) THEN 'first-last' ELSE
                            CASE WHEN qa.userid=min_att.userid AND qa.attempt=min_att.attempt THEN 'first' ELSE 'last' END
                         END AS attempt_category,

                         CONCAT(10*FLOOR(((((q.grade/q.sumgrades)*qa.sumgrades)/q.grade)*100)/10),
                                '-',
                                10*FLOOR(((((q.grade/q.sumgrades)*qa.sumgrades)/q.grade)*100)/10) + 10,
                                '%'
                            ) AS $range_col,
                         COUNT(qa.sumgrades) AS count_att
                  FROM {quiz_attempts} qa
                    JOIN (SELECT userid, MAX(attempt) AS attempt
                            FROM {quiz_attempts}
                          WHERE quiz=:custom1 GROUP BY userid ) AS max_att ON max_att.userid=qa.userid
                    JOIN (SELECT userid, MIN(attempt) AS attempt
                            FROM {quiz_attempts}
                          WHERE quiz=:custom2 GROUP BY userid ) AS min_att ON max_att.userid=min_att.userid
                    JOIN {quiz} q ON q.id=qa.quiz
                    $join_sql
                  WHERE qa.userid != 2 {$sql_vendor_filter} AND qa.quiz=:custom3 AND qa.sumgrades IS NOT NULL AND ((qa.userid=max_att.userid AND qa.attempt=max_att.attempt) OR (qa.userid=min_att.userid AND qa.attempt=min_att.attempt))
                  GROUP BY attempt_category,$range_col
                 ", $this->params);

        $overall_info = $DB->get_record_sql("
                          SELECT
                                (SELECT AVG((((q.grade/q.sumgrades)*qa.sumgrades)/q.grade)*100) AS average
                                    FROM {quiz_attempts} qa
                                        JOIN {quiz} q ON q.id=qa.quiz
                                        $join_sql
                                    WHERE qa.userid<>2 AND qa.quiz=:custom1 AND qa.attempt=1 AND qa.state='finished' {$sql_vendor_filter}
                                ) AS average_first_att,
                                (SELECT AVG((((q.grade/q.sumgrades)*qa.sumgrades)/q.grade)*100) AS average
                                    FROM {quiz_attempts} qa
                                     JOIN {quiz} q ON q.id=qa.quiz
                                     JOIN (SELECT userid, MAX(attempt) AS attempt
                                           FROM {quiz_attempts}
                                           WHERE quiz=:custom5 GROUP BY userid ) AS max_att ON max_att.userid=qa.userid
                                     $join_sql
                                    WHERE qa.userid<>2 {$sql_vendor_filter} AND qa.quiz=:custom4 AND qa.attempt=max_att.attempt AND qa.userid=max_att.userid AND qa.state='finished'
                                ) AS average_last_att,
                                (SELECT COUNT(qa.id)
                                    FROM {quiz_attempts} qa
                                      JOIN {quiz} q ON q.id=qa.quiz
                                      $join_sql
                                    WHERE qa.userid<>2 AND qa.quiz=:custom2 AND qa.state='finished' {$sql_vendor_filter}
                                ) AS count_att,
                                (SELECT CASE WHEN COUNT(DISTINCT qa.userid)>0 THEN COUNT(qa.attempt)/COUNT(DISTINCT qa.userid) ELSE 0 END
                                    FROM {quiz_attempts} qa
                                      JOIN {quiz} q ON q.id=qa.quiz
                                      $join_sql
                                    WHERE qa.userid<>2 AND qa.quiz=:custom3 {$sql_vendor_filter}
                                ) AS avg_att
                       ", $this->params);

        return array("data" => $data, 'overall_info'=>$overall_info);
    }
    public function analytic5table($params){
        global $DB;
        $columns = array("que.id", "que.name", "que.questiontext");
        $order_sql = $this->get_order_sql($params, $columns);
        $join_sql = $this->get_suspended_sql($params, 'q.course', 'qa.userid');
        $sql_vendor_filter = $this->vendor_filter('qa.userid', 'q.course', $params);
        $params->custom = clean_param($params->custom,PARAM_INT);
        $this->params['custom1'] = $params->custom;
        $this->params['custom2'] = $params->custom;
        $this->params['custom3'] = $params->custom;

        $sql = "SELECT MIN(qas.id) as id,
                    CASE WHEN (qa.userid=max_att.userid AND qa.attempt=max_att.attempt) AND (qa.userid=min_att.userid AND qa.attempt=min_att.attempt) THEN 'first-last' ELSE
                       CASE WHEN qa.userid=min_att.userid AND qa.attempt=min_att.attempt THEN 'first' ELSE 'last' END
                    END AS attempt_category,
                    que.id AS questionid,
                    que.name,
                    que.questiontext,
                    AVG(((qas.fraction-qua.minfraction)/(qua.maxfraction-qua.minfraction))*100) as scale,
                    COUNT(qa.id) AS count_users
                FROM {quiz} q
                 JOIN (SELECT qa.userid, MAX(qa.attempt) AS attempt, qa.quiz
                       FROM {quiz_attempts} qa
                       JOIN {quiz} q ON q.id = qa.quiz
                        WHERE qa.quiz=:custom1 AND qa.userid != 2 {$sql_vendor_filter} GROUP BY qa.userid, qa.quiz ) AS max_att ON max_att.quiz=q.id
                 JOIN (SELECT qa.userid, MIN(qa.attempt) AS attempt
                       FROM {quiz_attempts} qa
                       JOIN {quiz} q ON q.id = qa.quiz
                        WHERE qa.quiz=:custom2 AND qa.userid != 2 {$sql_vendor_filter} GROUP BY qa.userid ) AS min_att ON max_att.userid=min_att.userid
                 LEFT JOIN {quiz_attempts} qa ON qa.quiz=q.id AND ((qa.userid=max_att.userid AND qa.attempt=max_att.attempt) OR (qa.userid=min_att.userid AND qa.attempt=min_att.attempt))
                 LEFT JOIN {question_attempts} qua ON qua.questionusageid=qa.uniqueid
                 LEFT JOIN {question_attempt_steps} qas ON qas.questionattemptid=qua.id AND qas.sequencenumber = (SELECT MAX(sequencenumber) FROM {question_attempt_steps} WHERE questionattemptid = qua.id)
                 LEFT JOIN {question} que ON que.id=qua.questionid
                 $join_sql
                WHERE q.id=:custom3
                GROUP BY attempt_category,que.id $order_sql";

        $question_info = $DB->get_records_sql($sql, $this->params);
        $size = $this->count_records($sql, 'questionid', $this->params);

        return array('question_info'=>$question_info,"recordsTotal" => $size,"recordsFiltered" => $size);
    }

    public function analytic6($params){
        global $DB;

        $params->custom = clean_param($params->custom,PARAM_INT);

        $sql_enabled_learner_roles = $this->get_filter_in_sql($params->learner_roles,'ra.roleid');
        $join_sql1 = $this->get_suspended_sql($params, 'log.courseid', 'log.userid');
        $join_sql2 = $this->get_suspended_sql($params, 'c.instanceid', 'ra.userid');
        $join_sql3 = $this->get_suspended_sql($params, 'gi.courseid', 'g.userid');
        $this->params['custom1'] = $params->custom;
        $this->params['courseid'] = $params->courseid;
        $this->params['timestart'] = $params->timestart;
        $this->params['timefinish'] = $params->timefinish;
        $this->params['custom1'] = $params->custom;
        $this->params['custom2'] = $params->custom;
        $this->params['courseid2'] = $params->courseid;
        $this->params['courseid3'] = $params->courseid;

        $interactions = $DB->get_records_sql(
            "SELECT MAX(log.id) AS id,
                    COUNT(log.id) AS all_interactions,
                    SUM(CASE WHEN log.userid=:custom1 THEN 1 ELSE 0 END) AS user_interactions,
                    " . DBHelper::group_by_date_val("monthdayyear", "log.timecreated") . " AS day_interactions
               FROM {context} c
          LEFT JOIN {role_assignments} ra ON ra.contextid=c.id {$sql_enabled_learner_roles}
          LEFT JOIN {logstore_standard_log} log ON c.instanceid=log.courseid AND ra.userid = log.userid
                    {$join_sql1}
              WHERE c.instanceid = :courseid AND c.contextlevel = 50 AND log.timecreated BETWEEN :timestart AND :timefinish
           GROUP BY day_interactions
           ORDER BY MIN(log.timecreated) DESC",
            $this->params
        );

        array_map(function(&$item) {
            $item->all = $item->all_interactions;
            $item->user = $item->user_interactions;
            $item->day = $item->day_interactions;
            unset($item->all_interactions);
            unset($item->user_interactions);
            unset($item->day_interactions);
        }, $interactions);

        $access = $DB->get_records_sql(
            "SELECT MAX(log.id) AS id,
                    COUNT(log.id) AS all_interactions,
                    SUM(CASE WHEN log.userid = :custom1 THEN 1 ELSE 0 END) AS user_interactions,
                    " . DBHelper::group_by_date_val("monthdayyear", "log.timecreated") . " AS day_interactions
               FROM {context} c
          LEFT JOIN {role_assignments} ra ON ra.contextid = c.id {$sql_enabled_learner_roles}
          LEFT JOIN {logstore_standard_log} log ON c.instanceid=log.courseid AND ra.userid=log.userid
                    {$join_sql1}
              WHERE c.instanceid = :courseid AND c.contextlevel = 50 AND log.target = 'course' AND log.action = 'viewed' AND
                    log.timecreated BETWEEN :timestart AND :timefinish
           GROUP BY day_interactions
           ORDER BY MIN(log.timecreated) DESC",
            $this->params
        );

        array_map(function(&$item) {
            $item->all = $item->all_interactions;
            $item->user = $item->user_interactions;
            $item->day = $item->day_interactions;
            unset($item->all_interactions);
            unset($item->user_interactions);
            unset($item->day_interactions);
        }, $access);

        $user_quiz = $DB->get_records_sql(
            "SELECT MAX(qa.id) AS id,
                    COUNT(qa.id) AS all_interactions,
                    SUM(CASE WHEN qa.userid = :custom1 THEN 1 ELSE 0 END) AS user_interactions,
                    " . DBHelper::group_by_date_val("monthdayyear", "qa.timefinish") . " AS day_interactions
               FROM {context} c
          LEFT JOIN {role_assignments} ra ON ra.contextid = c.id $sql_enabled_learner_roles
          LEFT JOIN {quiz} q ON q.course = c.instanceid
          LEFT JOIN {quiz_attempts} qa ON qa.quiz = q.id AND qa.userid = ra.userid AND qa.state = 'finished'
                    {$join_sql2}
              WHERE c.instanceid = :courseid AND c.contextlevel = 50 AND qa.id IS NOT NULL AND qa.timefinish BETWEEN :timestart AND :timefinish
           GROUP BY day_interactions",
            $this->params
        );

        array_map(function(&$item) {
            $item->all = $item->all_interactions;
            $item->user = $item->user_interactions;
            $item->day = $item->day_interactions;
            unset($item->all_interactions);
            unset($item->user_interactions);
            unset($item->day_interactions);
        }, $user_quiz);

        $user_assign = $DB->get_records_sql(
            "SELECT MAX(asub.id) AS id,
                    COUNT(asub.id) AS all_interactions,
                    SUM(CASE WHEN asub.userid = :custom1 THEN 1 ELSE 0 END) AS user_interactions,
                    " . DBHelper::group_by_date_val("monthdayyear", "asub.timemodified") . " AS day_interactions
               FROM {context} c
          LEFT JOIN {role_assignments} ra ON ra.contextid = c.id $sql_enabled_learner_roles
          LEFT JOIN {assign} a ON a.course = c.instanceid
          LEFT JOIN {assign_submission} asub ON asub.assignment = a.id AND asub.userid = ra.userid AND asub.status = 'submitted'
                    {$join_sql2}
              WHERE c.instanceid = :courseid AND c.contextlevel = 50 AND asub.id IS NOT NULL AND
                    asub.timemodified BETWEEN :timestart AND :timefinish
           GROUP BY day_interactions",
            $this->params
        );

        array_map(function(&$item) {
            $item->all = $item->all_interactions;
            $item->user = $item->user_interactions;
            $item->day = $item->day_interactions;
            unset($item->all_interactions);
            unset($item->user_interactions);
            unset($item->day_interactions);
        }, $user_assign);

        $timespend = $DB->get_record_sql(
            "SELECT SUM(t.timespend) AS all_interactions,
                    MAX(tu.timespend) AS user_interactions
              FROM {context} c
         LEFT JOIN {role_assignments} ra ON ra.contextid = c.id {$sql_enabled_learner_roles}
         LEFT JOIN (SELECT lit.userid, SUM(lit.timespend) AS timespend
                      FROM {local_intelliboard_tracking} lit
                     WHERE lit.courseid = :courseid
                  GROUP BY lit.userid
                   ) t ON t.userid = ra.userid
         LEFT JOIN (SELECT MAX(lit.userid) AS userid, SUM(lit.timespend) AS timespend
                      FROM {local_intelliboard_tracking} lit
                     WHERE lit.courseid = :courseid2 AND lit.userid = :custom1
                   ) tu ON tu.userid = :custom2
                   {$join_sql2}
             WHERE c.instanceid = :courseid3 AND c.contextlevel = 50",
            $this->params
        );

        $timespend->all = $timespend->all_interactions;
        $timespend->user = $timespend->user_interactions;
        unset($timespend->all_interactions);
        unset($timespend->user_interactions);

        $count_students = $DB->get_record_sql(
            "SELECT COUNT(DISTINCT ra.userid) AS students
               FROM {context} c
          LEFT JOIN {role_assignments} ra ON ra.contextid = c.id $sql_enabled_learner_roles
                    {$join_sql2}
              WHERE c.instanceid = :courseid AND c.contextlevel = 50",
            $this->params
        );

        $grade_single = intelliboard_grade_sql(false, $params, 'g.', 0, 'gi.', true);
        $grade_avg = intelliboard_grade_sql(true, $params, 'g.', 0, 'gi.', true);

        $score = $DB->get_record_sql(
            "SELECT (SELECT $grade_avg
                       FROM {grade_items} gi
                       JOIN {grade_grades} g ON g.itemid = gi.id
                            {$join_sql3}
                      WHERE gi.itemtype = 'course' AND gi.courseid = :courseid
                    ) AS avg,
                    (SELECT {$grade_single}
                       FROM {grade_items} gi, {grade_grades} g
                      WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND gi.courseid = :courseid2 AND g.userid = :custom1
                    ) AS user",
            $this->params
        );

        return [
            "interactions"   => $interactions,
            "access"         => $access,
            "timespend"      => $timespend,
            "user_quiz"      => $user_quiz,
            "user_assign"    => $user_assign,
            "score"          => $score,
            "count_students" => $count_students
        ];
    }

    public function analytic7($params){
        global $DB, $CFG;

        $sql_enabled_learner_roles = $this->get_filter_in_sql($params->learner_roles,'ra.roleid');
        $sql_enabled_courseid = $this->get_filter_in_sql($params->courseid,'c.instanceid');

        $filter_sql = $this->get_teacher_sql($params, ["u.id" => "users", "c.instanceid" => "courses"]);
        $join_sql = $this->get_suspended_sql($params, 'c.instanceid', 'u.id');
        $countries = $DB->get_records_sql("
                       SELECT MAX(u.id) as id,
                              u.country,
                              uid.data AS state,
                              COUNT(DISTINCT u.id) AS users
                        FROM {context} c
                            JOIN {role_assignments} ra ON ra.contextid=c.id $sql_enabled_learner_roles
                            JOIN {user} u ON u.id=ra.userid
                            LEFT JOIN {user_info_field} uif ON uif.shortname LIKE 'state'
                            LEFT JOIN {user_info_data} uid ON uid.fieldid=uif.id AND uid.userid=ra.userid
                            $join_sql
                        WHERE c.contextlevel=50 $sql_enabled_courseid $filter_sql
                        GROUP BY u.country,uid.data
                       ", $this->params);

        if($params->custom == 'get_countries'){
            return array("countries" => $countries);
        }

        $join_sql = $this->get_suspended_sql($params, 'e.courseid', 'ue.userid');
        $filter_sql = $this->get_teacher_sql($params, ["ue.userid" => "users", "e.courseid" => "courses"]);
        $sql_course = $this->get_filter_in_sql($params->courseid,'e.courseid');
        $enroll_methods = $DB->get_records_sql("
                            SELECT MAX(e.id) as id,
                                   e.enrol,
                                   COUNT(DISTINCT ue.id) AS users
                            FROM {enrol} e
                                LEFT JOIN {user_enrolments} ue ON ue.enrolid=e.id
                                $join_sql
                            WHERE e.id>0 $sql_course $filter_sql
                            GROUP BY e.enrol
                         ", $this->params);

        $join_sql = $this->get_suspended_sql($params, 'c.instanceid', 'ra.userid');
        $filter_sql = $this->get_teacher_sql($params, ["ra.userid" => "users", "c.instanceid" => "courses"]);
        $complettions = $DB->get_record_sql("
                         SELECT SUM(CASE WHEN gg.finalgrade>gi.grademin AND cc.timecompleted IS NULL THEN 1 ELSE 0 END) AS not_completed,
                                SUM(CASE WHEN cc.timecompleted>0 THEN 1 ELSE 0 END) AS completed,
                                SUM(CASE WHEN cc.timestarted>0 AND cc.timecompleted IS NULL AND (gg.finalgrade=gi.grademin OR gg.finalgrade IS NULL) THEN 1 ELSE 0 END) AS in_progress
                         FROM {context} c
                            JOIN {role_assignments} ra ON ra.contextid=c.id $sql_enabled_learner_roles
                            LEFT JOIN {course_completions} cc ON cc.course=c.instanceid AND cc.userid=ra.userid
                            LEFT JOIN {grade_items} gi ON gi.itemtype = 'course' AND gi.courseid=c.instanceid
                            LEFT JOIN {grade_grades} gg ON gg.itemid=gi.id AND gg.userid=ra.userid
                            $join_sql
                         WHERE c.contextlevel=50 $sql_enabled_courseid $filter_sql
                        ", $this->params);

        $grade_single_sql = intelliboard_grade_sql(false,$params, 'g.', 0, 'gi.',true);
        $filter_sql = $this->get_teacher_sql($params, ["g.userid" => "users", "c.instanceid" => "courses"]);

        if ($CFG->dbtype == 'pgsql') {
            $scope = '';
        } else {
            $scope = '`';
        }
        $grade_range = $DB->get_records_sql("
                         SELECT CONCAT(10*FLOOR($grade_single_sql/10),
                                         '-',
                                         10*FLOOR($grade_single_sql/10) + 10,
                                         '%'
                                  ) as {$scope}range{$scope},
                                COUNT(DISTINCT g.userid) AS users
                         FROM {context} c
                            LEFT JOIN {role_assignments} ra ON ra.contextid=c.id $sql_enabled_learner_roles
                            LEFT JOIN {grade_items} gi ON gi.courseid=c.instanceid AND gi.itemtype='course'
                            LEFT JOIN {grade_grades} g ON g.itemid=gi.id AND g.userid=ra.userid
                            $join_sql
                         WHERE c.contextlevel=50 $sql_enabled_courseid AND g.rawgrademax IS NOT NULL $filter_sql
                         GROUP BY {$scope}range{$scope}
                        ", $this->params);

        return array("countries"      => $countries,
            "enroll_methods" => $enroll_methods,
            "complettions"   => $complettions,
            "grade_range"    => $grade_range);
    }

    public function analytic7table($params){
        global $DB, $CFG;

        $columns = array_merge(array(
            "u.firstname",
            "u.lastname",
            "u.email",
            "c.fullname",
            "u.country",
            "MIN(uid.data)",
            "MIN(e.enrol)",
            "visits",
            "timespend",
            "grade",
            "cc.timecompleted",
            "ue.timecreated"
        ), $this->get_filter_columns($params));

        $sql_filter = $this->get_filter_in_sql($params->courseid,'c.id');
        $sql_filter .= $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses"]);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_columns = $this->get_columns($params, ["u.id"]);
        $sql_limit = $this->get_limit_sql($params);

        $sql_enabled = $this->get_filter_in_sql($params->learner_roles,'ra.roleid',false);
        $join_sql = $this->get_suspended_sql($params, 'c.id', 'ra.userid');
        $where = array($sql_enabled);
        $having = array();
        $where_str = '';
        $having_str = '';
        $custom = unserialize($params->custom);
        $grade_avg_sql = intelliboard_grade_sql(true,$params, 'g.', 0, 'gi.',true);
        if(!empty($custom['country']) && $custom['country'] != 'world'){
            $this->params['country'] = clean_param($custom['country'],PARAM_ALPHANUMEXT);
            $where[] = "u.country=:country";
        }
        if(isset($custom['state']) && !empty($custom['state'])){
            $custom['state'] = clean_param($custom['state'],PARAM_ALPHANUMEXT);
            $where[] = $DB->sql_like('uid.data', ":state", false, false);
            $this->params['state'] = "%".$custom['state']."%";

        }
        if(isset($custom['enrol']) && !empty($custom['enrol'])){
            $custom['enrol'] = clean_param($custom['enrol'],PARAM_ALPHANUMEXT);
            $where[] = $DB->sql_like('e.enrol', ":enrol", false, false);
            $this->params['enrol'] = "%".$custom['enrol']."%";

        }
        if(isset($custom['grades']) && !empty($custom['grades'])){
            $custom['grades'] = clean_param($custom['grades'],PARAM_ALPHANUMEXT);
            $grades = explode('-',$custom['grades']);
            $grades[1] = (empty($grades[1]))?110:$grades[1];
            $having[] = "$grade_avg_sql BETWEEN :grade_min AND :grade_max";
            $this->params['grade_min'] = $grades[0];
            $this->params['grade_max'] = $grades[1]-0.001;
        }
        if(isset($custom['user_status']) && !empty($custom['user_status'])){
            $custom['user_status'] = clean_param($custom['user_status'],PARAM_INT);
            if($custom['user_status'] == 1){
                $having[] = "($grade_avg_sql>0 AND (MAX(cc.timecompleted)=0 OR MAX(cc.timecompleted) IS NULL))";
            }elseif($custom['user_status'] == 2){
                $where[] = "cc.timecompleted>0";
            }elseif($custom['user_status'] == 3){
                $where[] = "(MAX(cc.timestarted)>0 AND ($grade_avg_sql=0 OR MAX(g.finalgrade) IS NULL) AND (MAX(cc.timecompleted)=0 OR MAX(cc.timecompleted) IS NULL))";
            }
        }
        if(!empty($where))
            $where_str = " AND ".implode(' AND ',$where);

        if(!empty($having))
            $having_str = " HAVING ".implode(' AND ',$having);

        $where_sql = "WHERE u.id IS NOT NULL ".$where_str;

        if ($CFG->dbtype == 'pgsql') {
            $group_concat = "string_agg( DISTINCT e.enrol, ', ')";
        } else {
            $group_concat = "GROUP_CONCAT( DISTINCT e.enrol)";
        }

        $sql = "SELECT MAX(CONCAT(ue.id,'_',ra.id)) as id,
                           MAX(ue.timecreated) AS enrolled,
                           $grade_avg_sql AS grade,
                           c.enablecompletion,
                           MAX(cc.timecompleted) AS complete,
                           u.id AS uid,
                           u.email,
                           u.country,
                           MAX(uid.data) AS state,
                           u.firstname,
                           u.lastname,
                           $group_concat AS enrols,
                           MAX(l.timespend) as timespend,
                           MAX(l.visits) as visits,
                           c.id AS cid,
                           c.fullname AS course,
                           c.timemodified AS start_date
                           $sql_columns
                    FROM {user_enrolments} ue
                        JOIN {enrol} e ON e.id = ue.enrolid
                        JOIN {context} ctx ON ctx.instanceid = e.courseid
                        JOIN {role_assignments} ra ON ra.contextid = ctx.id AND ue.userid = ra.userid

                        JOIN {user} AS u ON u.id = ue.userid
                        JOIN {course} AS c ON c.id = e.courseid
                        LEFT JOIN {course_completions} cc ON cc.course = e.courseid AND cc.userid = ue.userid

                        LEFT JOIN {grade_items} gi ON gi.itemtype = 'course' AND gi.courseid=c.id
                        LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.userid=u.id

                        LEFT JOIN (SELECT lit.userid,
                                       lit.courseid,
                                       sum(lit.timespend) AS timespend,
                                       sum(lit.visits) AS visits
                                     FROM
                                       {local_intelliboard_tracking} lit
                                     GROUP BY lit.courseid, lit.userid) l ON l.courseid = c.id AND l.userid = u.id

                        LEFT JOIN {user_info_field} uif ON uif.shortname LIKE 'state'
                        LEFT JOIN {user_info_data} uid ON uid.fieldid=uif.id AND uid.userid=ue.userid
                        $join_sql
                    $where_sql $sql_filter
                    GROUP BY u.id, c.id
                    $having_str
                    $sql_order $sql_limit";

        $data = $DB->get_records_sql($sql, $this->params);

        $size = $this->count_records($sql, 'id', $this->params);
        return array(
            "recordsTotal"    => $size,
            "recordsFiltered" => $size,
            "data"            => $data);
    }


    public function analytic8($params){
        global $DB;

        $columns = array_merge(array(
            "coursename", "cohortname", "learners_completed", "learners_not_completed", "learners_overdue", "avg_grade", "timespend"
        ), $this->get_filter_columns($params, [null]));

        $sql_columns = $this->get_columns($params, [null]);
        $sql_filter = $this->get_filter_in_sql($params->learner_roles,'ra.roleid');
        $sql_filter .= $this->get_filter_in_sql($params->courseid,'c.id');
        $sql_filter .= $this->get_filter_in_sql($params->cohortid,'cm.cohortid');
        $sql_filter .= $this->get_teacher_sql($params, ["ra.userid" => "users", "ctx.instanceid" => "courses", "cm.cohortid"=>"cohorts"]);
        $sql_filter .= $this->vendor_filter('ra.userid', 'c.id', $params);
        $join_sql = $this->get_suspended_sql($params, 'ctx.instanceid', 'ra.userid');

        $sql_order = $this->get_order_sql($params, $columns);
        $sql_limit = $this->get_limit_sql($params);
        $params->custom = clean_param($params->custom, PARAM_INT);
        $this->params['custom'] = ($params->custom)?$params->custom:time();
        $this->params['timestart'] = $params->timestart;
        $this->params['timefinish'] = $params->timefinish;
        $grade_avg_sql = intelliboard_grade_sql(true,$params, 'g.', 0, 'gi.',true);

        $sql = "SELECT MAX(concat_ws('_', ra.id, cm.cohortid, c.id)) AS id,
                       c.id AS courseid,
                       c.fullname AS coursename,
                       cm.cohortid,
                       MIN(coh.name) AS cohortname,
                       $grade_avg_sql AS avg_grade,
                       COUNT(DISTINCT CASE WHEN cr.completion IS NOT NULL AND cc.timecompleted>0 THEN cc.id ELSE NULL END) AS learners_completed,
                       COUNT(DISTINCT CASE WHEN cr.completion IS NOT NULL AND (cc.timecompleted=0 OR cc.timecompleted IS NULL) THEN cc.id ELSE NULL END) AS learners_not_completed,
                       COUNT(DISTINCT CASE WHEN cr.completion IS NOT NULL AND cc.timecompleted>:custom THEN cc.id ELSE NULL END) AS learners_overdue,
                       AVG(l.timespend) AS timespend
                       {$sql_columns}
                FROM {role_assignments} ra
                  JOIN {context} ctx ON ctx.id = ra.contextid
                  JOIN {course} c ON c.id=ctx.instanceid

                  LEFT JOIN {course_completions} cc ON cc.course = c.id AND cc.userid = ra.userid

                  LEFT JOIN {grade_items} gi ON gi.itemtype = 'course' AND gi.courseid=c.id
                  LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.userid=ra.userid

                  LEFT JOIN (SELECT lit.userid, lit.courseid, SUM(lit.timespend) AS timespend
                             FROM
                               {local_intelliboard_tracking} lit
                             GROUP BY lit.courseid, lit.userid) l ON l.courseid = c.id AND l.userid = ra.userid
                  LEFT JOIN {cohort_members} cm ON cm.userid = ra.userid
                  LEFT JOIN {cohort} coh ON coh.id=cm.cohortid
                  LEFT JOIN (SELECT COUNT(id) AS completion ,course FROM {course_completion_criteria} GROUP BY course) cr ON cr.course=c.id
                  $join_sql
                WHERE ra.userid>0 $sql_filter
                GROUP BY c.id,cm.cohortid $sql_order $sql_limit";

        $data = $DB->get_records_sql($sql, $this->params);

        return array("data" => $data);
    }

    public function analytic8details($params){
        global $DB;
        $custom = json_decode($params->custom);

        if($params->cohortid === 0 && $params->courseid === 0){
            return array("data" => array());
        }

        $sql_where = '';
        if($custom->user_status == 1){
            $sql_where = " AND cc.timecompleted>0 ";
        }elseif($custom->user_status == 2){
            $sql_where = " AND (cc.timecompleted=0 OR cc.timecompleted IS NULL) ";
        }elseif($custom->user_status == 3){
            $sql_where = " AND cc.timecompleted>:duedate";
            $this->params['duedate'] = $custom->duedate;
        }

        $columns = array_merge(array("coursename", "cohortname", "learnername", "u.email", "grade", "l.timespend","cc.timecompleted"), $this->get_filter_columns($params));
        $sql_filter = $this->get_filter_in_sql($params->learner_roles,'ra.roleid');
        $sql_filter .= $this->get_filter_in_sql($params->courseid,'c.id');
        $sql_filter .= $this->get_filter_in_sql($params->cohortid,'cm.cohortid');
        $sql_filter .= ($params->cohortid == 0 && $params->custom2 == 1)?" AND cm.cohortid IS NULL ":'';
        $sql_filter .= $this->get_teacher_sql($params, ["u.id" => "users", "c.id" => "courses", "cm.cohortid"=>"cohorts"]);
        $sql_filter .= $this->vendor_filter('ra.userid', 'c.id', $params);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_limit = $this->get_limit_sql($params);
        $sql_columns = $this->get_columns($params, ["u.id"]);
        $join_sql = $this->get_suspended_sql($params, 'c.id', 'u.id');

        if($params->filter){
            $sql_filter .= " AND (" . $DB->sql_like('u.firstname', ":firstname", false, false);
            $sql_filter .= " OR " . $DB->sql_like('u.lastname', ":lastname", false, false);
            $sql_filter .= ")";
            $this->params['firstname'] = "%$params->filter%";
            $this->params['lastname'] = "%$params->filter%";
        }
        $grade_avg_sql = intelliboard_grade_sql(true,$params, 'g.', 0, 'gi.',true);

        $sql = "SELECT MIN(ra.id) AS id,
                       MIN(c.id) AS courseid,
                       MIN(c.fullname) AS coursename,
                       MIN(cm.cohortid) AS cohortid,
                       MIN(coh.name) AS cohortname,
                       $grade_avg_sql AS grade,
                       MAX(l.timespend) AS timespend,
                       CONCAT(u.firstname, ' ', u.lastname) AS learnername,
                       u.email,
                       MAX(cc.timecompleted) AS timecompleted
                       $sql_columns
                FROM {role_assignments} ra
                  JOIN {context} ctx ON ctx.id = ra.contextid
                  JOIN {user} AS u ON u.id = ra.userid
                  JOIN {course} AS c ON c.id = ctx.instanceid
                  LEFT JOIN {course_completions} cc ON cc.course = c.id AND cc.userid = u.id

                  LEFT JOIN {grade_items} gi ON gi.itemtype = 'course' AND gi.courseid=c.id
                  LEFT JOIN {grade_grades} g ON g.itemid = gi.id AND g.userid=u.id

                  LEFT JOIN (SELECT lit.userid,
                               lit.courseid,
                               sum(lit.timespend) AS timespend
                             FROM
                               {local_intelliboard_tracking} lit
                             GROUP BY lit.courseid, lit.userid) l ON l.courseid = c.id AND l.userid = u.id

                  LEFT JOIN {cohort_members} cm ON cm.userid = u.id
                  LEFT JOIN {cohort} coh ON coh.id=cm.cohortid
                  LEFT JOIN (SELECT COUNT(id) AS completion ,course FROM {course_completion_criteria} GROUP BY course) cr ON cr.course=ctx.instanceid
                  $join_sql
                WHERE cr.completion IS NOT NULL $sql_where $sql_filter
                GROUP BY u.id $sql_order $sql_limit";


        $data = $DB->get_records_sql($sql, $this->params);

        return array("data" => $data);
    }
    public function analytic9($params){
        global $CFG,$DB;

        $custom = json_decode($params->custom);
        $sql_filter = $this->get_filter_in_sql($params->courseid,'c.id',false);
        $sql_filter .= $this->get_teacher_sql($params, ["c.id" => "courses"]);
        $sql_log_course = $this->get_filter_in_sql($params->courseid,'l.courseid', false);
        $join_sql1 = $this->get_suspended_sql($params, 'c.id', 'ue.userid');
        $join_sql2 = $this->get_suspended_sql($params, 'c.id', 'cc.userid');
        $join_sql3 = $this->get_suspended_sql($params, 'c.id', 'log.userid');

        $this->params['time_current_week'] = strtotime('this week 00:00:00');
        $this->params['time_current_week_end'] = time();
        $this->params['time_current_month'] = mktime(0, 0, 0, date('m'), 1, date("Y"));
        $this->params['time_current_month_end'] = time();
        $this->params['time_current_year'] = mktime(0, 0, 0, 1, 1, date("Y"));
        $this->params['time_current_year_end'] = time();
        $this->params['time_filter_second'] = $custom->timestart;
        $this->params['time_filter_second_end'] = ($custom->timefinish)?$custom->timefinish:time();

        $this->params['time_last_week'] = strtotime('last week 00:00:00');
        $this->params['time_last_week_end'] = $this->params['time_current_week'];
        $this->params['time_last_month'] = mktime(0, 0, 0, date('m')-1, 1, date("Y"));
        $this->params['time_last_month_end'] = $this->params['time_current_month'];
        $this->params['time_last_year'] = mktime(0, 0, 0, 1, 1, date("Y")-1);
        $this->params['time_last_year_end'] = $this->params['time_current_year'];
        $this->params['time_filter'] = $params->timestart;
        $this->params['time_filter_end'] = ($params->timefinish)?$params->timefinish:time();

        $this->params['timestart'] = min($this->params);
        $this->params['timefinish'] = max($this->params);
        $sql_vendor_filter = $this->vendor_filter('ue.userid', 'c.id', $params);
        $sql_vendor_filter1 = $this->vendor_filter('l.userid', 'l.courseid', $params);
        $sql_vendor_filter2 = $this->vendor_filter('cc.userid', 'c.id', $params);

        $enroll = $DB->get_record_sql("
                    SELECT
                      COUNT(DISTINCT CASE WHEN ue.timestart BETWEEN :time_current_week AND :time_current_week_end THEN ue.userid ELSE null END) AS enroll_current_week,
                      COUNT(DISTINCT CASE WHEN ue.timestart BETWEEN :time_current_month AND :time_current_month_end THEN ue.userid ELSE null END) AS enroll_current_month,
                      COUNT(DISTINCT CASE WHEN ue.timestart BETWEEN :time_current_year AND :time_current_year_end THEN ue.userid ELSE null END) AS enroll_current_year,
                      COUNT(DISTINCT CASE WHEN ue.timestart BETWEEN :time_filter_second AND :time_filter_second_end THEN ue.userid ELSE null END) AS enroll_time_filter_second,

                      COUNT(DISTINCT CASE WHEN ue.timestart BETWEEN :time_last_week AND :time_last_week_end THEN ue.userid ELSE null END) AS enroll_last_week,
                      COUNT(DISTINCT CASE WHEN ue.timestart BETWEEN :time_last_month AND :time_last_month_end THEN ue.userid ELSE null END) AS enroll_last_month,
                      COUNT(DISTINCT CASE WHEN ue.timestart BETWEEN :time_last_year AND :time_last_year_end THEN ue.userid ELSE null END) AS enroll_last_year,
                      COUNT(DISTINCT CASE WHEN ue.timestart BETWEEN :time_filter AND :time_filter_end THEN ue.userid ELSE null END) AS enroll_time_filter
                    FROM {course} c
                      JOIN {enrol} e ON e.courseid=c.id
                      JOIN {user_enrolments} ue ON ue.enrolid=e.id
                      $join_sql1
                    WHERE $sql_filter {$sql_vendor_filter}", $this->params);

        $complete = $DB->get_record_sql("
                    SELECT
                      COUNT(DISTINCT CASE WHEN cc.timecompleted BETWEEN :time_current_week AND :time_current_week_end THEN cc.userid ELSE null END) AS completed_current_week,
                      COUNT(DISTINCT CASE WHEN cc.timecompleted BETWEEN :time_current_month AND :time_current_month_end THEN cc.userid ELSE null END) AS completed_current_month,
                      COUNT(DISTINCT CASE WHEN cc.timecompleted BETWEEN :time_current_year AND :time_current_year_end THEN cc.userid ELSE null END) AS completed_current_year,
                      COUNT(DISTINCT CASE WHEN cc.timecompleted BETWEEN :time_filter_second AND :time_filter_second_end THEN cc.userid ELSE null END) AS completed_time_filter_second,

                      COUNT(DISTINCT CASE WHEN cc.timecompleted BETWEEN :time_last_week AND :time_last_week_end THEN cc.userid ELSE null END) AS completed_last_week,
                      COUNT(DISTINCT CASE WHEN cc.timecompleted BETWEEN :time_last_month AND :time_last_month_end THEN cc.userid ELSE null END) AS completed_last_month,
                      COUNT(DISTINCT CASE WHEN cc.timecompleted BETWEEN :time_last_year AND :time_last_year_end THEN cc.userid ELSE null END) AS completed_last_year,
                      COUNT(DISTINCT CASE WHEN cc.timecompleted BETWEEN :time_filter AND :time_filter_end THEN cc.userid ELSE null END) AS completed_time_filter
                    FROM {course} c
                      JOIN {course_completions} cc ON cc.course=c.id
                      $join_sql2
                    WHERE $sql_filter {$sql_vendor_filter2}", $this->params);

        $act_access = $DB->get_record_sql("
                    SELECT
                      COUNT(DISTINCT CASE WHEN log.first_access BETWEEN :time_current_week AND :time_current_week_end THEN log.userid ELSE null END) AS act_access_current_week,
                      COUNT(DISTINCT CASE WHEN log.first_access BETWEEN :time_current_month AND :time_current_month_end THEN log.userid ELSE null END) AS act_access_current_month,
                      COUNT(DISTINCT CASE WHEN log.first_access BETWEEN :time_current_year AND :time_current_year_end THEN log.userid ELSE null END) AS act_access_current_year,
                      COUNT(DISTINCT CASE WHEN log.first_access BETWEEN :time_filter_second AND :time_filter_second_end THEN log.userid ELSE null END) AS act_access_time_filter_second,

                      COUNT(DISTINCT CASE WHEN log.first_access BETWEEN :time_last_week AND :time_last_week_end THEN log.userid ELSE null END) AS act_access_last_week,
                      COUNT(DISTINCT CASE WHEN log.first_access BETWEEN :time_last_month AND :time_last_month_end THEN log.userid ELSE null END) AS act_access_last_month,
                      COUNT(DISTINCT CASE WHEN log.first_access BETWEEN :time_last_year AND :time_last_year_end THEN log.userid ELSE null END) AS act_access_last_year,
                      COUNT(DISTINCT CASE WHEN log.first_access BETWEEN :time_filter AND :time_filter_end THEN log.userid ELSE null END) AS act_access_time_filter
                    FROM {course} c
                      JOIN (SELECT
                              l.userid,
                              MIN(l.firstaccess) AS first_access,
                              MIN(l.courseid) AS courseid
                            FROM {local_intelliboard_tracking} l
                            WHERE $sql_log_course {$sql_vendor_filter1} AND l.page='module'
                            GROUP BY l.userid
                        ) log ON log.courseid=c.id
                      $join_sql3
                    WHERE $sql_filter", $this->params);

        $act_never_access = $DB->get_record_sql("
                    SELECT
                      COUNT(DISTINCT CASE WHEN ue.timestart BETWEEN :time_current_week AND :time_current_week_end THEN ue.userid ELSE null END) AS enroll_current_week,
                      COUNT(DISTINCT CASE WHEN ue.timestart BETWEEN :time_current_month AND :time_current_month_end THEN ue.userid ELSE null END) AS enroll_current_month,
                      COUNT(DISTINCT CASE WHEN ue.timestart BETWEEN :time_current_year AND :time_current_year_end THEN ue.userid ELSE null END) AS enroll_current_year,
                      COUNT(DISTINCT CASE WHEN ue.timestart BETWEEN :time_filter_second AND :time_filter_second_end THEN ue.userid ELSE null END) AS enroll_time_filter_second,

                      COUNT(DISTINCT CASE WHEN ue.timestart BETWEEN :time_last_week AND :time_last_week_end THEN ue.userid ELSE null END) AS enroll_last_week,
                      COUNT(DISTINCT CASE WHEN ue.timestart BETWEEN :time_last_month AND :time_last_month_end THEN ue.userid ELSE null END) AS enroll_last_month,
                      COUNT(DISTINCT CASE WHEN ue.timestart BETWEEN :time_last_year AND :time_last_year_end THEN ue.userid ELSE null END) AS enroll_last_year,
                      COUNT(DISTINCT CASE WHEN ue.timestart BETWEEN :time_filter AND :time_filter_end THEN ue.userid ELSE null END) AS enroll_time_filter
                    FROM {course} c
                      JOIN {enrol} e ON e.courseid=c.id
                      JOIN {user_enrolments} ue ON ue.enrolid=e.id
                      LEFT JOIN {local_intelliboard_tracking} l ON l.courseid=c.id AND l.userid=ue.userid AND l.page='module'
                      $join_sql1
                    WHERE $sql_filter {$sql_vendor_filter} AND l.id IS NULL ", $this->params);

        return array(
            "enroll"    => $enroll,
            "act_access"=> $act_access,
            "act_never_access"=> $act_never_access,
            "complete"  => $complete);
    }

    public function analytic10($params){
        global $DB;
        $data = array();
        $grades = array();
        $questions = array();
        if(is_numeric($params->custom)){
            $join_sql1 = $this->get_suspended_sql($params, 'q.course', 'qa.userid');
            $join_sql2 = $this->get_suspended_sql($params, 'q.course', 'g.userid');
            $where = '';
            if($params->custom > 0){
                $where .= ' AND q.id=:custom';
                $this->params['custom'] = $params->custom;
            }
            if($params->courseid > 0){
                $where .= " AND q.course=:courseid";
                $this->params['courseid'] = $params->courseid;
            }

            $questions = $DB->get_records_sql("
                                SELECT
                                  DISTINCT qua.questionid,
                                  que.name
                                FROM {quiz} q
                                  LEFT JOIN {quiz_attempts} qa ON qa.quiz=q.id
                                  LEFT JOIN {question_attempts} qua ON qua.questionusageid=qa.uniqueid
                                  LEFT JOIN {question} que ON que.id=qua.questionid
                                WHERE qua.questionid IS NOT NULL $where", $this->params);

            $data = $DB->get_records_sql("
                      SELECT MAX(qas.id) AS id,
                             que.id AS question_id,
                             que.name,
                             SUM(CASE WHEN qas.state LIKE '%partial' OR qas.state LIKE '%right' THEN 1 ELSE 0 END) AS rightanswer,
                             COUNT(qas.id) AS allanswer
                      FROM {quiz} q
                        LEFT JOIN {quiz_attempts} qa ON qa.quiz=q.id
                        LEFT JOIN {question_attempts} qua ON qua.questionusageid=qa.uniqueid
                        LEFT JOIN {question_attempt_steps} qas ON qas.questionattemptid=qua.id AND qas.fraction IS NOT NULL
                        LEFT JOIN {question} que ON que.id=qua.questionid
                        $join_sql1
                      WHERE que.id IS NOT NULL $where
                      GROUP BY que.id
                     ", $this->params);

            $grade_single_sql = intelliboard_grade_sql(true,$params);
            $grades = $DB->get_records_sql("
                        SELECT MAX(g.id) AS id,
                               q.id AS quiz_id,
                               q.name AS quiz_name,
                               MIN(ROUND(((gi.gradepass - gi.grademin)/(gi.grademax - gi.grademin))*100,0)) AS gradepass,
                               COUNT(DISTINCT g.userid) AS users,
                               $grade_single_sql AS grade
                        FROM {quiz} q
                           LEFT JOIN {grade_items} gi ON gi.itemtype='mod' AND gi.itemmodule='quiz' AND gi.iteminstance=q.id
                           LEFT JOIN {grade_grades} g ON g.itemid=gi.id AND g.userid != 2 AND g.rawgrade IS NOT NULL
                           $join_sql2
                        WHERE g.rawgrade IS NOT NULL $where
                        GROUP BY grade,quiz_id
                       ", $this->params);
        }

        return array("data" => $data, "grades"=>$grades, "questions"=>$questions);
    }

    public function analytic10table($params){
        global $DB;
        $data = array();
        $questions = array();
        $size = new stdClass();
        $size->count = 0;
        if(is_numeric($params->custom)){
            $where = '';
            if($params->custom > 0){
                $where .= ' AND q.id=:custom';
                $this->params['custom'] = $params->custom;
            }
            if($params->courseid > 0){
                $where .= " AND q.course=:courseid";
                $this->params['courseid'] = $params->courseid;
            }

            $questions = $DB->get_records_sql("
                                SELECT
                                  DISTINCT qua.questionid,
                                  que.name
                                FROM {quiz} q
                                  LEFT JOIN {quiz_attempts} qa ON qa.quiz=q.id
                                  LEFT JOIN {question_attempts} qua ON qua.questionusageid=qa.uniqueid
                                  LEFT JOIN {question} que ON que.id=qua.questionid
                                WHERE qua.questionid IS NOT NULL $where", $this->params);

            $grade_single_sql = intelliboard_grade_sql(false, $params);
            $columns = array_merge(array("u.firstname", "u.lastname", "u.email", "grade"), $this->get_filter_columns($params));

            $sql_columns = '';
            foreach($questions as $question){
                $sql_columns .= ",(SELECT responsesummary FROM {question_attempts} WHERE questionusageid=qa.uniqueid AND questionid=$question->questionid) AS useranswer_".$question->questionid;
                $columns[] = "useranswer_".$question->questionid;
                $sql_columns .= ",(SELECT rightanswer FROM {question_attempts} WHERE questionusageid=qa.uniqueid AND questionid=$question->questionid) AS rightanswer_".$question->questionid;
                $columns[] = "rightanswer_".$question->questionid;
            }

            $order_sql = $this->get_order_sql($params, $columns);
            $sql_limit = $this->get_limit_sql($params);
            $sql_columns .= $this->get_columns($params, ["u.id"]);
            $join_sql = $this->get_suspended_sql($params, 'q.course', 'qa.userid');

            $sql = "SELECT
                           CONCAT(u.id, qa.id) as uniqueid,
                           u.id AS userid,
                           u.firstname,
                           u.lastname,
                           u.email,
                           $grade_single_sql AS grade
                           $sql_columns
                    FROM {quiz} q
                      LEFT JOIN {quiz_attempts} qa ON qa.quiz=q.id
                      LEFT JOIN {user} u ON u.id=qa.userid
                      LEFT JOIN {grade_items} gi ON gi.itemtype='mod' AND gi.itemmodule='quiz' AND gi.iteminstance=q.id
                      LEFT JOIN {grade_grades} g ON g.itemid=gi.id AND g.userid=u.id AND g.rawgrade IS NOT NULL
                      $join_sql
                    WHERE u.id IS NOT NULL $where $order_sql $sql_limit";

            $data = $DB->get_records_sql($sql, $this->params);

            $sql = "SELECT
                           COUNT(u.id) AS count
                    FROM {quiz} q
                      LEFT JOIN {quiz_attempts} qa ON qa.quiz=q.id
                      LEFT JOIN {user} u ON u.id=qa.userid
                      $join_sql
                    WHERE u.id IS NOT NULL $where";
            $size = $DB->get_record_sql($sql, $this->params);
        }

        return array("data" => $data, "recordsTotal"=>$size->count, "questions"=>$questions);
    }

    public function analytic11($params){
        global $DB;

        $where = 'c.id > 0';
        $where .= $this->get_filterdate_sql($params, 'c.timestart');

        if($params->courseid) {
            $where .= $this->get_filter_in_sql($params->courseid, "c.course", true, true);
        }

        $cohortfilter = '';
        if($params->cohortid){
            $cohortidfilter = $DB->get_in_or_equal(
                explode(',', $params->cohortid),
                SQL_PARAMS_NAMED,
                'chrt'
            );
            $cohortfilter = "JOIN {cohort} chrt ON chrt.id {$cohortidfilter[0]}
                             JOIN {cohort_members} crtm ON crtm.cohortid = chrt.id AND
                                                           crtm.userid = libp.external_user_id";
            $this->params += $cohortidfilter[1];
        }

        $sql = "SELECT c.id, c.name, sp.spent
                  FROM {collaborate} c
             LEFT JOIN (SELECT libp.sessionuid, SUM(libp.duration) as spent
                          FROM {local_intelliboard_bb_partic} libp
                               {$cohortfilter}
                      GROUP BY libp.sessionuid
                       ) sp ON sp.sessionuid = c.sessionuid
                 WHERE {$where}";

        return $DB->get_records_sql($sql, $this->params);
    }

    public function analytic11Table($params){
        global $DB, $CFG;

        $columns = [
            'course', 'session', 'clbr.timestart', 'clbr.timeend', 'participant', 'user_cohorts',
            'role', 'join_time', 'left_time', 'duration', 'rejoins', 'grade', ''
        ];
        $sql_order = $this->get_order_sql($params, $columns);
        $sqlcolumns = $this->get_columns($params, [null]);
        $where = 'libp.id > 0';
        $where .= $this->get_filterdate_sql($params, 'clbr.timestart');
        $sql_limit = $this->get_limit_sql($params);

        if($params->courseid) {
            $where .= $this->get_filter_in_sql($params->courseid, "clbr.course", true, true);
        }

        $cohortName = get_operator('GROUP_CONCAT', 'chrt1.name', ['separator' => ', ']);

        $cohortfilter = '';
        if($params->cohortid){
            $cohortidfilter = $DB->get_in_or_equal(
                explode(',', $params->cohortid),
                SQL_PARAMS_NAMED,
                'chrt'
            );
            $cohortfilter = "JOIN {cohort} chrt ON chrt.id {$cohortidfilter[0]}
                             JOIN {cohort_members} crtm ON crtm.cohortid = chrt.id AND
                                                           crtm.userid = libp.external_user_id";
            $this->params += $cohortidfilter[1];
        }

        if ($CFG->dbtype == 'pgsql') {
            $randomnumber = "FLOOR(extract(epoch from now()) * random())";
        } else {
            $randomnumber = "FLOOR(RAND() * NOW())";
        }

        $sql = "SELECT {$randomnumber} frst, c.fullname as course,
                       clbr.name as session, clbr.timestart, clbr.timeend,
                       libp.display_name as participant, libp.role,
                       libp.first_join_time as join_time,
                       libp.last_left_time as left_time,
                       libp.duration, libp.rejoins,
                       CONCAT(
                           ROUND(gg.finalgrade, 1), '/', ROUND(gg.rawgrademax, 1)
                       ) as grade, clbr.id as sessionid, clbr.course as courseid,
                       (SELECT {$cohortName}
                          FROM {cohort_members} crtm1
                          JOIN {cohort} chrt1 ON chrt1.id = crtm1.cohortid
                         WHERE crtm1.userid = libp.external_user_id
                      GROUP BY crtm1.userid
                       ) AS user_cohorts
                       {$sqlcolumns}
                  FROM {local_intelliboard_bb_partic} libp
                  JOIN {collaborate} clbr ON clbr.sessionuid = libp.sessionuid
                  JOIN {course} c ON c.id = clbr.course
                  {$cohortfilter}
             LEFT JOIN {grade_items} gi ON gi.itemmodule = 'collaborate' AND
                                           gi.iteminstance = clbr.id
             LEFT JOIN {grade_grades} gg ON gg.itemid = gi.id AND
                                            gg.userid = libp.external_user_id
                 WHERE {$where} {$sql_order} {$sql_limit}";

        return $DB->get_records_sql($sql, $this->params);
    }

    public function report175($params) {
        global $DB;

        $columns = [
            'course', 'tcrs.teachers', 'session', 'particip_num', 'absent',
            'late', 'on_time'
        ];
        $sqlcolumns = $this->get_columns($params, [null]);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_filter = $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, 'c.id');
        $sql_filter .= $this->get_filterdate_sql($params, "clb.timestart");
        $rolefilter = $this->get_filter_in_sql($params->teacher_roles, 'ra.roleid');
        $cohortmember = '';
        $numbernotcohortusers = '0';

        if($params->cohortid){
            $cohortidfilter = $DB->get_in_or_equal(
                explode(',', $params->cohortid), SQL_PARAMS_NAMED
            );
            $cohortmembers = array_keys($DB->get_records_sql(
                "SELECT DISTINCT cm.userid
                   FROM {cohort} ch
                   JOIN {cohort_members} cm ON cm.cohortid = ch.id
                  WHERE ch.id {$cohortidfilter[0]}",
                $cohortidfilter[1]
            ));
            $cohortmember = " AND libp.external_user_id IN(".implode(',', $cohortmembers).")";
            $notcohortmember = "libp.external_user_id NOT IN(".implode(',', $cohortmembers).")";
            $numbernotcohortusers = "SUM(CASE WHEN {$notcohortmember} THEN 1 ELSE 0 END)";
        }

        $namegroupconcat = get_operator('GROUP_CONCAT', 'CONCAT(u.firstname, \' \', u.lastname)', ['separator' => ', ']);

        $data = $this->get_report_data(
            "SELECT clb.sessionuid,
                    c.id AS courseid,
                    c.fullname AS course,
                    tcrs.teachers,
                    clb.name AS session,
                    MIN(prtcp.particip_num) AS particip_num,
                    SUM(CASE WHEN libp.first_join_time <= clb.timestart
                                  {$cohortmember} THEN 1 ELSE 0 END) on_time,
                    SUM(CASE WHEN libp.first_join_time > clb.timestart AND
                                  libp.first_join_time <= clb.timeend
                                  {$cohortmember} THEN 1 ELSE 0 END) late,
                    MIN(prtcp.particip_num) - (
                        SUM(CASE WHEN libp.first_join_time <= clb.timestart
                                      {$cohortmember} THEN 1 ELSE 0 END) +
                        SUM(CASE WHEN libp.first_join_time > clb.timestart AND
                                      libp.first_join_time <= clb.timeend
                                      {$cohortmember} THEN 1 ELSE 0 END) +
                        {$numbernotcohortusers}
                    ) AS absent
                    {$sqlcolumns}
               FROM {collaborate} clb
          LEFT JOIN {course} c ON c.id = clb.course
          LEFT JOIN {local_intelliboard_bb_partic} libp ON libp.sessionuid = clb.sessionuid
          LEFT JOIN (SELECT cx.instanceid as courseid,
                            {$namegroupconcat} as teachers
                       FROM {context} cx
                  LEFT JOIN {role_assignments} ra ON ra.contextid = cx.id {$rolefilter}
                  LEFT JOIN {user} u ON u.id = ra.userid
                      WHERE cx.contextlevel = 50
                   GROUP BY cx.instanceid
                    ) tcrs ON tcrs.courseid = c.id
          LEFT JOIN (SELECT cx.instanceid as crs, COUNT(ra.id) particip_num
                       FROM {context} cx
                  LEFT JOIN {role_assignments} ra ON ra.contextid = cx.id
                      WHERE cx.contextlevel = 50
                   GROUP BY cx.instanceid
                    ) as prtcp ON prtcp.crs = c.id
              WHERE clb.id > 0 {$sql_filter}
           GROUP BY clb.sessionuid, c.id, c.fullname, tcrs.teachers, clb.name
                    {$sql_having}
                    {$sql_order}",
            $params, false
        );

        /** Data for comparing */
        // time params for compare period
        $params->timefinish = $params->timestart - 1;
        $params->timestart = $params->timestart - 604800;
        // do not paginate data for comparing
        $params->length = -1;
        $sql_filter = $this->get_filterdate_sql($params, "clb.timestart");
        $comparedata = $this->get_report_data(
            "SELECT t.course AS courseid,
                    AVG(t.on_time) on_time,
                    AVG(t.late) late,
                    AVG(t.absent) absent
               FROM (SELECT clb.sessionuid,
                            clb.course,
                            SUM(CASE WHEN libp.first_join_time <= clb.timestart
                                          {$cohortmember} THEN 1 ELSE 0 END) on_time,
                            SUM(CASE WHEN libp.first_join_time > clb.timestart AND
                                          libp.first_join_time <= clb.timeend
                                          {$cohortmember} THEN 1 ELSE 0 END) late,
                            MIN(prtcp.particip_num) - (
                                SUM(CASE WHEN libp.first_join_time <= clb.timestart
                                              {$cohortmember} THEN 1 ELSE 0 END) +
                                SUM(CASE WHEN libp.first_join_time > clb.timestart AND
                                              libp.first_join_time <= clb.timeend
                                              {$cohortmember} THEN 1 ELSE 0 END) +
                                {$numbernotcohortusers}
                            ) AS absent
                       FROM {collaborate} as clb
                       JOIN {course} c ON c.id = clb.course
                  LEFT JOIN {local_intelliboard_bb_partic} libp ON libp.sessionuid = clb.sessionuid
                  LEFT JOIN (SELECT cx.instanceid as crs, COUNT(ra.id) particip_num
                               FROM {context} cx
                          LEFT JOIN {role_assignments} ra ON ra.contextid = cx.id
                              WHERE cx.contextlevel = 50
                           GROUP BY cx.instanceid
                            ) as prtcp ON prtcp.crs = c.id
                      WHERE clb.id > 0 {$sql_filter}
                   GROUP BY clb.sessionuid, clb.course
                   ) t
            GROUP BY t.course",
            $params, false
        );

        foreach($data as &$row) {
            if(isset($comparedata[$row->courseid])) {
                $row->compare_on_time = $comparedata[$row->courseid]->on_time;
                $row->compare_late = $comparedata[$row->courseid]->late;
                $row->compare_absent = $comparedata[$row->courseid]->absent;
            } else {
                $row->compare_on_time = 0;
                $row->compare_late = 0;
                $row->compare_absent = 0;
            }
        }

        return ['data' => $data];
    }

    public function report176($params) {
        $columns = [
            'course', 'tcrs.teachers', 'num_sessions', 'particip_num', 'absent',
            'late', 'on_time'
        ];
        $sqlcolumns = $this->get_columns($params, [null]);
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_having = $this->get_filter_sql($params, $columns);
        $sql_filter = $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, 'c.id');
        $sql_date_filter = $this->get_filterdate_sql($params, "clb.timestart");
        $teacherrolesids = explode(
            ',', get_config('local_intelliboard', 'filter10')
        );
        $rolefilter = $this->get_filter_in_sql($teacherrolesids, 'ra.roleid');
        $namegroupconcat = get_operator('GROUP_CONCAT', 'CONCAT(u.firstname, \' \', u.lastname)', ['separator' => ', ']);

        $data = $this->get_report_data(
            "SELECT c.id AS courseid, c.fullname AS course, tcrs.teachers,
                    COUNT(DISTINCT clb.sessionuid) AS num_sessions, MIN(prtcp.particip_num) AS particip_num,
                    SUM(CASE WHEN libp.first_join_time <= clb.timestart THEN 1 ELSE 0 END) AS on_time,
                    SUM(CASE WHEN libp.first_join_time > clb.timestart AND
                                  libp.first_join_time <= clb.timeend THEN 1 ELSE 0 END) AS late,
                    MIN(prtcp.particip_num) * COUNT(DISTINCT clb.sessionuid) - (
                        SUM(CASE WHEN libp.first_join_time <= clb.timestart THEN 1 ELSE 0 END) +
                        SUM(CASE WHEN libp.first_join_time > clb.timestart AND
                                      libp.first_join_time <= clb.timeend THEN 1 ELSE 0 END)
                    ) AS absent
                    {$sqlcolumns}
               FROM {course} c
          LEFT JOIN {collaborate} clb ON c.id = clb.course {$sql_date_filter}
          LEFT JOIN {local_intelliboard_bb_partic} libp ON libp.sessionuid = clb.sessionuid
          LEFT JOIN (SELECT cx.instanceid AS courseid,
                            {$namegroupconcat} AS teachers
                       FROM {context} cx
                  LEFT JOIN {role_assignments} ra ON ra.contextid = cx.id {$rolefilter}
                  LEFT JOIN {user} u ON u.id = ra.userid
                      WHERE cx.contextlevel = 50
                   GROUP BY cx.instanceid
                    ) tcrs ON tcrs.courseid = c.id
          LEFT JOIN (SELECT cx.instanceid as crs, COUNT(ra.id) particip_num
                       FROM {context} cx
                  LEFT JOIN {role_assignments} ra ON ra.contextid = cx.id
                      WHERE cx.contextlevel = 50
                   GROUP BY cx.instanceid
                    ) as prtcp ON prtcp.crs = c.id
              WHERE c.id > 0 {$sql_filter}
           GROUP BY c.id, c.fullname, tcrs.teachers
                    {$sql_having} {$sql_order}",
            $params, false
        );

        /** Data for comparing */
        // do not paginate data for comparing
        $params->length = -1;
        // time params for compare period
        $params->timefinish = $params->timestart - 1;
        $params->timestart = $params->timestart - 604800;
        $sql_date_filter = $this->get_filterdate_sql($params, "clb.timestart");
        $comparedata = $this->get_report_data(
            "SELECT c.id AS courseid, MIN(prtcp.particip_num) AS particip_num,
                    SUM(CASE WHEN libp.first_join_time <= clb.timestart THEN 1 ELSE 0 END) AS on_time,
                    SUM(CASE WHEN libp.first_join_time > clb.timestart AND
                                  libp.first_join_time <= clb.timeend THEN 1 ELSE 0 END) AS late,
                    MIN(prtcp.particip_num) * COUNT(DISTINCT clb.sessionuid) - (
                        SUM(CASE WHEN libp.first_join_time <= clb.timestart THEN 1 ELSE 0 END) +
                        SUM(CASE WHEN libp.first_join_time > clb.timestart AND
                                      libp.first_join_time <= clb.timeend THEN 1 ELSE 0 END)
                    ) AS absent
               FROM {course} c
               JOIN {collaborate} clb ON c.id = clb.course {$sql_date_filter}
          LEFT JOIN {local_intelliboard_bb_partic} libp ON libp.sessionuid = clb.sessionuid
          LEFT JOIN (SELECT cx.instanceid AS crs, COUNT(ra.id) AS particip_num
                       FROM {context} cx
                  LEFT JOIN {role_assignments} ra ON ra.contextid = cx.id
                      WHERE cx.contextlevel = 50
                   GROUP BY cx.instanceid
                    ) as prtcp ON prtcp.crs = c.id
              WHERE c.id > 0
           GROUP BY c.id",
            $params, false
        );

        foreach($data as &$row) {
            if(isset($comparedata[$row->courseid])) {
                $row->compare_on_time = $comparedata[$row->courseid]->on_time;
                $row->compare_late = $comparedata[$row->courseid]->late;
                $row->compare_absent = $comparedata[$row->courseid]->absent;
            } else {
                $row->compare_on_time = 0;
                $row->compare_late = 0;
                $row->compare_absent = 0;
            }
        }

        return ['data' => $data];
    }

    public function report177Chart($params) {
        global $DB, $CFG;

        $sql_filter = $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, 'c.id');
        $sql_date_filter = $this->get_filterdate_sql($params, "cb.timestart");

        $cohortfilter = '';
        if($params->cohortid){
            $cohortidfilter = $DB->get_in_or_equal(
                explode(',', $params->cohortid), SQL_PARAMS_NAMED
            );
            $cohortmembers = array_keys($DB->get_records_sql(
                "SELECT DISTINCT cm.userid
                   FROM {cohort} ch
                   JOIN {cohort_members} cm ON cm.cohortid = ch.id
                  WHERE ch.id {$cohortidfilter[0]}",
                $cohortidfilter[1]
            ));
            $cohortfilter = $this->get_filter_in_sql(
                $cohortmembers, 'lb.external_user_id'
            );
        }

        $datefiled = DBHelper::group_by_date_val('monthyearday', 'cb.timestart');

        return $DB->get_records_sql(
            "SELECT {$datefiled} as rep_date,
                    COUNT(lb.id) as num_participants
               FROM {course} c
               JOIN {collaborate} cb ON cb.course = c.id {$sql_date_filter}
          LEFT JOIN {local_intelliboard_bb_partic} lb ON lb.sessionuid = cb.sessionuid
                                                         {$cohortfilter}
              WHERE c.id > 0 {$sql_filter}
           GROUP BY 1
           ORDER BY MIN(cb.timestart)",
            $this->params
        );
    }

    public function report177Table($params) {
        global $DB;

        $columns = [
            'learner',
            'u.email',
            'user_cohorts',
            'participated_in_sess',
            'missed_sessions',
            '',
            'last_attended'
        ];
        $sql_order = $this->get_order_sql($params, $columns);
        $sql_filter = $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, 'c.id');
        $rolefilter = $this->get_filter_in_sql($params->learner_roles, 'ra.roleid');
        $sql_date_filter = $this->get_filterdate_sql($params, "clb.timestart");
        $sql_limit = $this->get_limit_sql($params);
        $cohortNames = get_operator('GROUP_CONCAT', 'ch.name', ['separator' => ', ']);

        $cohortfilter = '';
        if($params->cohortid){
            $cohortidfilter = $DB->get_in_or_equal(
                explode(',', $params->cohortid), SQL_PARAMS_NAMED
            );
            $cohortmembers = array_keys($DB->get_records_sql(
                "SELECT DISTINCT cm.userid
                   FROM {cohort} ch
                   JOIN {cohort_members} cm ON cm.cohortid = ch.id
                  WHERE ch.id {$cohortidfilter[0]}",
                $cohortidfilter[1]
            ));
            $cohortfilter = $this->get_filter_in_sql(
                $cohortmembers, 'ra.userid'
            );
        }

        return $this->get_report_data(
            "SELECT t.userid AS id,
                    CONCAT(u.firstname, ' ', u.lastname) AS learner,
                    u.email,
                    SUM(CASE WHEN libp.id != 0 THEN 1 ELSE 0 END) AS participated_in_sess,
                    COUNT(DISTINCT clb.id) - SUM(CASE WHEN libp.id != 0 THEN 1 ELSE 0 END) AS missed_sessions,
                    COUNT(DISTINCT clb.id) AS total_sessions,
                    MAX(libp.first_join_time) AS last_attended,
                    (SELECT {$cohortNames}
                       FROM {cohort} ch
                       JOIN {cohort_members} cm ON cm.cohortid = ch.id
                      WHERE cm.userid = t.userid
                    ) AS user_cohorts
               FROM (SELECT c.id, ra.userid
                       FROM {course} c
                       JOIN {context} as ctx ON ctx.contextlevel = 50 AND
                                                ctx.instanceid = c.id
                       JOIN {role_assignments} as ra ON ra.contextid = ctx.id {$rolefilter} {$cohortfilter}
                      WHERE c.id > 0 {$sql_filter}
                   GROUP BY c.id, ra.userid
                    ) as t
               JOIN {user} u ON u.id = t.userid
               JOIN {collaborate} as clb ON clb.course = t.id  {$sql_date_filter}
          LEFT JOIN {local_intelliboard_bb_partic} as libp ON libp.sessionuid = clb.sessionuid AND
                                                              libp.external_user_id = t.userid
           GROUP BY t.userid, u.email, u.firstname, u.lastname {$sql_order} {$sql_limit}",
            $this->params
        );
    }

    public function report177StudentInfo($params) {
        $user_filter = $this->get_filter_in_sql($params->userid, "u.id", false);
        $course_sessions_filter = $this->get_filter_in_sql(
            $params->courseid, "cb.course", false
        );
        $date_filter = $this->get_filterdate_sql($params, 'cb.timestart');

        return $this->get_report_data(
            "SELECT t.id, CONCAT(t.firstname, ' ', t.lastname) as fullname,
                    SUM(CASE WHEN libp.id != 0 THEN 1 ELSE 0 END) as participated_in_sess,
                    COUNT(DISTINCT cb.id) - SUM(CASE WHEN libp.id != 0 THEN 1 ELSE 0 END) as missed_sessions,
                    MAX(cb.timestart) as most_recent_attended
               FROM (SELECT u.*
                       FROM {user} u
                      WHERE {$user_filter}
                    ) t
          LEFT JOIN {collaborate} cb ON {$course_sessions_filter} {$date_filter}
          LEFT JOIN {local_intelliboard_bb_partic} libp ON libp.sessionuid = cb.sessionuid AND
                                                           libp.external_user_id = t.id
           GROUP BY t.id, t.firstname, t.lastname",
            $params
        );
    }

    public function report178Charts($params) {
        global $DB;

        $user_filter = $this->get_filter_in_sql($params->userid, "u.id", false);
        $course_sessions_filter = $this->get_filter_in_sql(
            $params->courseid, "cb.course", false
        );
        $date_filter = $this->get_filterdate_sql($params, 'cb.timestart');

        $piechart = $this->get_report_data(
            "SELECT t.id,
                    SUM(CASE WHEN libp.id != 0 THEN 1 ELSE 0 END) AS sessions_attended,
                    COUNT(DISTINCT cb.id) - SUM(CASE WHEN libp.id != 0 THEN 1 ELSE 0 END) AS sessions_missed
               FROM (SELECT u.*
                       FROM {user} u
                      WHERE {$user_filter}
                    ) t
          LEFT JOIN {collaborate} cb ON {$course_sessions_filter} {$date_filter}
          LEFT JOIN {local_intelliboard_bb_partic} libp ON libp.sessionuid = cb.sessionuid AND
                                                           libp.external_user_id = t.id
           GROUP BY t.id, t.firstname, t.lastname",
            $params, false
        );

        $numbers = $DB->get_record_sql(
            "SELECT AVG(t1.sessions_attended) AS avg_attendned, AVG(t1.sessions_missed) AS avg_missed
               FROM (SELECT cb.course,
                            SUM(CASE WHEN lb.id != 0 THEN 1 ELSE 0 END) AS sessions_attended,
                            COUNT(DISTINCT cb.id) - SUM(CASE WHEN lb.id != 0 THEN 1 ELSE 0 END) AS sessions_missed
                       FROM (SELECT cx.instanceid
                               FROM {context} cx
                               JOIN {role_assignments} ra ON ra.contextid = cx.id AND
                                                       ra.userid = :userid
                              WHERE cx.contextlevel = 50
                           GROUP BY cx.instanceid
                            ) t
                       JOIN {collaborate} cb ON cb.course = t.instanceid
                  LEFT JOIN {local_intelliboard_bb_partic} lb ON lb.sessionuid = cb.sessionuid AND
                                                                 lb.external_user_id = :userid1
                   GROUP BY cb.course
                     ) t1",
            [
                'userid' => $params->userid,
                'userid1' => $params->userid,
                'coursecontextlevel' => CONTEXT_COURSE
            ]
        );

        $datefiled = DBHelper::group_by_date_val('monthyearday', 'cb.timestart');

        $columnchart = $this->get_report_data(
            "SELECT {$datefiled} AS sess_date,
                    SUM(libp.duration) AS spent
               FROM (SELECT u.*
                       FROM {user} u
                      WHERE {$user_filter}
                    ) t
               JOIN {collaborate} cb ON {$course_sessions_filter} {$date_filter}
               JOIN {local_intelliboard_bb_partic} libp ON libp.sessionuid = cb.sessionuid AND
                                                           libp.external_user_id = t.id
           GROUP BY 1
           ORDER BY MIN(cb.timestart)",
            $params, false
        );

        return [
            'pie_chart' => array_shift($piechart),
            'column_chart' => $columnchart,
            'numbers' => $numbers
        ];
    }

    public function report178Table($params) {
        $columns = [
            'course', 'cb.name', 'tcrs.teachers', 'libp.first_join_time',
            'libp.last_left_time', 'libp.duration', 'grade'
        ];
        $sqlcolumns = $this->get_columns($params, [null]);
        $user_filter = $this->get_filter_in_sql($params->userid, "u.id", false);
        $course_filter = $this->get_filter_in_sql($params->courseid, "cx.instanceid");
        $course_sessions_filter = $this->get_filter_in_sql(
            $params->courseid, "cb.course", false
        );
        $date_filter = $this->get_filterdate_sql($params, 'cb.timestart');
        $rolefilter = $this->get_filter_in_sql($params->teacher_roles, 'ra.roleid');
        $sql_order = $this->get_order_sql($params, $columns);
        $namesgrouping = get_operator('GROUP_CONCAT', 'CONCAT(u.firstname, \' \', u.lastname)', ['separator' => ', ']);
        $texttypecast = DBHelper::get_typecast("text");

        return $this->get_report_data(
            "SELECT libp.id, c.fullname AS course, cb.name, tcrs.teachers,
                    libp.first_join_time, libp.last_left_time, libp.duration,
                    CONCAT(
                        CASE WHEN gg.finalgrade IS NOT NULL THEN ROUND(gg.finalgrade){$texttypecast} ELSE '-' END,
                        '/',
                        CASE WHEN gi.grademax IS NOT NULL THEN ROUND(gi.grademax){$texttypecast} ELSE '-' END
                    ) AS grade
                    {$sqlcolumns}
               FROM (SELECT u.*
                       FROM {user} u
                      WHERE {$user_filter}
                    ) t
               JOIN {collaborate} cb ON {$course_sessions_filter} {$date_filter}
               JOIN {course} c ON c.id = cb.course
               JOIN {local_intelliboard_bb_partic} libp ON libp.sessionuid = cb.sessionuid AND
                                                           libp.external_user_id = t.id
          LEFT JOIN (SELECT cx.instanceid as courseid,
                            {$namesgrouping} AS teachers
                       FROM {context} cx
                  LEFT JOIN {role_assignments} ra ON ra.contextid = cx.id {$rolefilter}
                  LEFT JOIN {user} u ON u.id = ra.userid
                      WHERE cx.contextlevel = 50 {$course_filter}
                   GROUP BY cx.instanceid
                    ) tcrs ON tcrs.courseid = c.id
          LEFT JOIN {grade_items} gi ON gi.itemmodule = 'collaborate' AND
                                        gi.iteminstance = cb.id
          LEFT JOIN {grade_grades} gg ON gg.itemid = gi.id AND
                                         gg.userid = libp.external_user_id
          {$sql_order}",
            $params
        );
    }

    public function report180Table($params) {
        global $DB;

        $columns = [
            'lbp.display_name',
            'u.email',
            'user_cohorts',
            'lbp.role',
            'type',
            'lbp.first_join_time',
            'lbp.last_left_time',
            'lbp.duration',
            'lbp.rejoins'
        ];
        $sessionfilter = $this->get_filter_in_sql(
            $params->custom, 'cb.id', false
        );
        $sqlcolumns = $this->get_columns($params, [null]);
        $sql_order = $this->get_order_sql($params, $columns);
        $cohortNames = get_operator('GROUP_CONCAT', 'ch.name', ['separator' => ', ']);
        $cohortfilter = '';

        if ($params->cohortid){
            $cohortidfilter = $DB->get_in_or_equal(
                explode(',', $params->cohortid), SQL_PARAMS_NAMED
            );
            $cohortmembers = array_keys($DB->get_records_sql(
                "SELECT DISTINCT cm.userid
                   FROM {cohort} ch
                   JOIN {cohort_members} cm ON cm.cohortid = ch.id
                  WHERE ch.id {$cohortidfilter[0]}",
                $cohortidfilter[1]
            ));
            $cohortfilter = $this->get_filter_in_sql(
                $cohortmembers, 'lbp.external_user_id'
            );
        }

        return $this->get_report_data(
            "SELECT lbp.id,
                    lbp.display_name,
                    u.email,
                    lbp.role,
                    CASE WHEN lbp.role = 'moderator'
                         THEN 'organizer'
                         ELSE 'invitee'
                    END AS type,
                    lbp.first_join_time,
                    lbp.last_left_time,
                    lbp.duration,
                    lbp.rejoins,
                    (SELECT {$cohortNames}
                       FROM {cohort} ch
                       JOIN {cohort_members} cm ON cm.cohortid = ch.id
                      WHERE cm.userid = lbp.external_user_id
                    ) AS user_cohorts
                    {$sqlcolumns}
               FROM {collaborate} cb
               JOIN {local_intelliboard_bb_partic} lbp ON lbp.sessionuid = cb.sessionuid
                                                          {$cohortfilter}
               JOIN {course} c ON c.id = cb.course
               JOIN {user} u ON u.id = lbp.external_user_id
              WHERE {$sessionfilter} {$sql_order}",
            $params
        );
    }

    function report180SessionDetails($params) {
        global $DB;

        $sessionfilter = $this->get_filter_in_sql(
            $params->custom, 'cb.id', false
        );

        $data = array_shift($this->get_report_data(
            "SELECT t.id, t.sessionuid, t.name, t.timestart, t.timeend, t.intro,
                    COUNT(lbp.id) as attendees, AVG(lbp.duration) as avg_time_in_sess
               FROM (SELECT cb.*
                       FROM {collaborate} cb
                      WHERE{$sessionfilter}
                    ) t
          LEFT JOIN {local_intelliboard_bb_partic} lbp ON lbp.sessionuid = t.sessionuid
           GROUP BY t.id, t.sessionuid, t.name, t.timestart, t.timeend, t.intro",
            $params, false
        ));

        if($data) {
            $data->timestart = date('c', $data->timestart);
            $data->timeend = date('c', $data->timeend);

            try {
                $recordingslist = [];
                $adapter = \local_intelliboard\tools\bb_collaborate_tool::adapter();
                $recordings = array_slice(
                    $adapter->get_session_recordings($data->sessionuid, ['CURLOPT_TIMEOUT_MS' => 5000]), 0, 5
                );

                foreach ($recordings as $item) {
                    $recordingurl = $adapter->get_recording_url(
                        $item['id'], ['CURLOPT_TIMEOUT_MS' => 5000]
                    );

                    if (!$recordingurl) {
                        continue;
                    }

                    $recordingslist[] = (object) [
                        "sessionuid" => $data->sessionuid,
                        "record_name" => $item['name'],
                        "record_url" => $recordingurl,
                    ];
                }

                $data->recordings = $recordingslist;
            } catch (\Exception $e) {
                $data->recordings = [];
            }
        }

        return $data;
    }

    function monitor57($params) {
        global $DB;

        $sql_filter = $this->get_filter_in_sql($params->courseid, "cb.course");
        $sql_filter .= $this->get_filterdate_sql($params, 'cb.timestart');

        $sql = "SELECT cb.id, cb.name, COUNT(lb.id) as total_users,
                       SUM(CASE WHEN lb.role = 'participant' THEN 1 ELSE 0 END) as students,
                       SUM(CASE WHEN lb.role = 'moderator' THEN 1 ELSE 0 END) as teachers
                  FROM {collaborate} cb
                  JOIN {local_intelliboard_bb_partic} lb ON lb.sessionuid = cb.sessionuid
                 WHERE cb.id > 0 {$sql_filter}
              GROUP BY cb.id, cb.name";

        return $DB->get_records_sql($sql, $this->params);
    }

    function monitor58($params) {
        global $DB;

        $sql_filter = $this->get_filterdate_sql($params, 'cb.timestart');

        $chartdata = $DB->get_records_sql(
            "SELECT c.id, c.fullname, COUNT(cb.id) as num_sessions
                  FROM {course} c
                  JOIN {collaborate} cb ON cb.course = c.id
                 WHERE c.id > 0 {$sql_filter}
              GROUP BY c.id, c.fullname",
            $this->params
        );

        $labelsdata = $DB->get_record_sql(
            "SELECT SUM(CASE WHEN timecreated BETWEEN :t1 AND :t2 THEN 1 ELSE 0 END) scheduled_today,
                    SUM(CASE WHEN timeend BETWEEN :t3 AND :t4 THEN 1 ELSE 0 END) finished_today,
                    SUM(CASE WHEN timecreated BETWEEN :t5 AND :t6 THEN 1 ELSE 0 END) scheduled_selected_period,
                    SUM(CASE WHEN timeend BETWEEN :t7 AND :t8 THEN 1 ELSE 0 END) finished_selected_period
               FROM {collaborate}
              WHERE (timecreated BETWEEN :timestart AND :timeend) OR
                    (timeend BETWEEN :timestart1 AND :timeend1)",
            [
                'timestart' => min($params->timestart, strtotime('today midnight')),
                'timeend' => max(
                    $params->timefinish, strtotime('today midnight') + 86400
                ),
                'timestart1' => min($params->timestart, strtotime('today midnight')),
                'timeend1' => max(
                    $params->timefinish, strtotime('today midnight') + 86400
                ),
                't1' => strtotime('today midnight'),
                't2' => strtotime('today midnight') + 86400,
                't3' => strtotime('today midnight'),
                't4' => strtotime('today midnight') + 86400,
                't5' => $params->timestart,
                't6' => $params->timefinish,
                't7' => $params->timestart,
                't8' => $params->timefinish
            ]
        );

        return [
            'monitor' => 'monitor58',
            'chart_data' => $chartdata,
            'labels_data' => $labelsdata
        ];
    }

    function monitor59($params) {
        global $CFG, $DB;

        if ($CFG->dbtype == 'pgsql') {
            $tsformat = "extract(dow from to_timestamp(cb.timestart))";
        } else {
            $tsformat = "FROM_UNIXTIME(cb.timestart, '%w')";
        }

        $coursefilter = $this->get_filter_in_sql($params->courseid, 'cb.course');
        $datefilter = $this->get_filterdate_sql($params, 'cb.timestart');
        $sql_filter = $this->get_teacher_sql($params, ["lb.external_user_id" => "users", "cb.course" => "courses"]);

        $data = $DB->get_records_sql(
            "SELECT {$tsformat} day_number,
                    SUM(CASE WHEN lb.first_join_time = t.first_join THEN 1 ELSE 0 END) as first_time,
                    SUM(CASE WHEN lb.first_join_time != t.first_join THEN 1 ELSE 0 END) as returning
               FROM {local_intelliboard_bb_partic} lb
               JOIN (SELECT external_user_id, MIN(first_join_time) first_join
                       FROM {local_intelliboard_bb_partic}
                   GROUP BY external_user_id
                    ) t ON t.external_user_id = lb.external_user_id
               JOIN {collaborate} cb ON cb.sessionuid = lb.sessionuid
                                        {$coursefilter} {$datefilter}
              WHERE lb.id > 0 {$sql_filter}
           GROUP BY {$tsformat}",
            $this->params
        );

        // monday - 1, sunday = 7
        foreach($data as &$item) {
            if($item->day_number === 0) {
                $item->day_number = 7;
            }
        }

        return $data;
    }

    function monitor60($params) {
        global $DB;

        $coursefilter = $this->get_filter_in_sql($params->courseid, 'cb.course');
        $datefilter = $this->get_filterdate_sql($params, 'cb.timestart');

        return $DB->get_records_sql(
            "SELECT t.id, t.name,
                    SUM(CASE WHEN lb.duration < 1800 THEN 1 ELSE 0 END) as less_30,
                    SUM(CASE WHEN lb.duration BETWEEN 1800 AND 3600 THEN 1 ELSE 0 END) as more_than_30,
                    SUM(CASE WHEN lb.duration > 3600 THEN 1 ELSE 0 END) as more_than_60
               FROM (SELECT cb.*
                       FROM {collaborate} cb
                      WHERE cb.id > 0 {$coursefilter} {$datefilter}
                    ) t
          LEFT JOIN {local_intelliboard_bb_partic} lb ON lb.sessionuid = t.sessionuid
           GROUP BY t.id, t.name",
            $this->params
        );
    }

    public function get_quiz_questions($params)
    {
        global $DB;

        $this->params['custom'] = (int)$params->custom;
        return $DB->get_records_sql("
                                SELECT
                                  DISTINCT qua.questionid,
                                  que.questiontext,
                                  que.name
                                FROM {quiz} q
                                  LEFT JOIN {quiz_attempts} qa ON qa.quiz=q.id
                                  LEFT JOIN {question_attempts} qua ON qua.questionusageid=qa.uniqueid
                                  LEFT JOIN {question} que ON que.id=qua.questionid
                                WHERE qua.questionid IS NOT NULL AND q.id=:custom", $this->params);
    }

    public function get_tracking_logs($params)
    {
        global $DB;

        $this->params['custom'] = (int)$params->custom;
        $datefilter = $this->get_filterdate_sql($params, 'timepoint');

        return $DB->get_records_sql("SELECT * FROM {local_intelliboard_logs} WHERE trackid=:custom $datefilter ORDER BY timepoint ASC", $this->params);
    }
    public function get_tracking_details($params)
    {
        global $DB;

        $this->params['custom'] = (int)$params->custom;

        return $DB->get_records_sql("SELECT * FROM {local_intelliboard_details} WHERE logid=:custom", $this->params);
    }

    public function get_course_sections($params)
    {
        global $CFG;
        require_once($CFG->dirroot .'/course/lib.php');

        $modinfo = get_fast_modinfo($params->courseid);

        $course_topics = array();
        foreach($modinfo->get_section_info_all() as $number => $section){
            $course_topics[$number] = get_section_name($params->courseid,$section);
        }

        return array("data" => $course_topics);
    }

    public function get_course_user_groups($params)
    {
        global $DB;

        $data = $DB->get_records('groups', array('courseid'=>$params->courseid),'','id,name');

        return array("data" => $data);
    }

    public function get_course_instructors($params)
    {
        global $DB;

        $sql = $this->get_filter_in_sql($params->teacher_roles, 'ra.roleid');
        $sql .= $this->get_filter_in_sql($params->courseid, 'ctx.instanceid');
        $sql .= $this->get_filter_user_sql($params, "u.");

        return $DB->get_records_sql("
            SELECT DISTINCT u.id, CONCAT(u.firstname,' ',u.lastname) AS name, u.email
            FROM {role_assignments} AS ra
                JOIN {user} AS u ON ra.userid = u.id
                JOIN {context} AS ctx ON ctx.id = ra.contextid
            WHERE ctx.contextlevel = 50 $sql", $this->params);
    }
    public function get_course_discussions($params)
    {
        global $DB;

        $forum_table = ($params->custom3 == 1)?'hsuforum':'forum';

        $sql_filter = $this->get_teacher_sql($params, ["course" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid,'course');

        return $DB->get_records_sql("SELECT id, name FROM {".$forum_table."} WHERE id > 0 $sql_filter", $this->params);
    }
    public function get_course_questionnaire($params)
    {
        global $DB;

        $sql_filter = $this->get_teacher_sql($params, ["course" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid,'course');

        return $DB->get_records_sql("SELECT id, name FROM {questionnaire} WHERE id > 0 $sql_filter", $this->params);
    }
    public function get_course_survey($params)
    {
        global $DB;

        $sql_filter = $this->get_teacher_sql($params, ["course" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid,'course');

        return $DB->get_records_sql("SELECT id, name FROM {survey} WHERE id > 0 $sql_filter", $this->params);
    }
    public function get_course_survey_questions($params)
    {
        global $DB;

        $sql =  $this->get_filter_in_sql($params->custom, 'a.survey');

        return $DB->get_records_sql("SELECT DISTINCT q.id, q.text, q.shorttext FROM {survey_answers} a, {survey_questions} q WHERE q.id = a.question $sql", $this->params);
    }
    public function get_course_questionnaire_questions($params)
    {
        global $DB;

        $responce_survey_id = (get_config('mod_questionnaire', 'version') < 2017111101) ? 'survey_id' : 'surveyid';

        $sql =  $this->get_filter_in_sql($params->custom, 'q.id');

        return $DB->get_records_sql(
            "SELECT qq.id, qq.name, qq.content
               FROM {questionnaire} q
               JOIN {questionnaire_survey} qs ON qs.id = q.sid
               JOIN {questionnaire_question} qq ON qs.id = qq.{$responce_survey_id} AND qq.deleted = 'n'
              WHERE q.id > 0 {$sql}
           ORDER BY qq.position",
           $this->params
        );
    }

    public function get_course_scorms($params)
    {
        global $DB;

        $sql_filter = $this->get_teacher_sql($params, ["course" => "courses"]);
        $sql_filter .= $this->get_filter_in_sql($params->courseid,'course');

        return $DB->get_records_sql("SELECT id, name FROM {scorm} WHERE id > 0 $sql_filter", $this->params);
    }

    public function get_cohort_users($params){
        global $DB;

        $sql = "";
        if($params->custom2){
            if($params->cohortid){
                $sql = " AND (a.clusterid = :cohortid1 or a.clusterid IN (SELECT id FROM {local_elisprogram_uset} WHERE parent = :cohortid2))";
                $this->params['cohortid1'] = $params->cohortid;
                $this->params['cohortid2'] = $params->cohortid;
            }
            return $DB->get_records_sql("SELECT DISTINCT b.muserid AS id, CONCAT(u.firstname,' ',u.lastname) AS name
                                          FROM {local_elisprogram_uset_asign} a,{local_elisprogram_usr_mdl} b, {local_elisprogram_usr} u
                                          WHERE a.userid = u.id AND b.cuserid = a.userid AND b.muserid IN (
                                            SELECT DISTINCT userid FROM {quiz_attempts} WHERE state = 'finished') $sql", $this->params);
        }else{
            $sql = $this->get_filter_in_sql($params->cohortid,'cm.cohortid');
            if($params->courseid){
                $sql_enabled = $this->get_filter_in_sql($params->courseid,'e.courseid',false);
                $sql .= " AND u.id IN(SELECT distinct ue.userid FROM {user_enrolments} ue, {enrol} e WHERE $sql_enabled  and ue.enrolid = e.id)";
            }
            return $DB->get_records_sql("SELECT DISTINCT u.id, CONCAT(u.firstname,' ',u.lastname) AS name
                                         FROM {user} u, {cohort_members} cm
                                         WHERE cm.userid = u.id AND u.deleted = 0 AND u.suspended = 0 AND u.id IN (
                                            SELECT DISTINCT userid FROM {quiz_attempts} WHERE state = 'finished') $sql", $this->params);
        }
    }
    public function get_users($params)
    {
        global $DB;

        $custom = clean_param($params->custom, PARAM_SEQUENCE);
        $sql = $this->get_filter_in_sql($custom,'us.id');
        $sql .= $this->get_filter_user_sql($params, "us.");
        $sql .= $this->get_teacher_sql($params, ["us.id" => "users", "c.instanceid" => "courses"]);
        $sql_enabled = $this->get_filter_in_sql($params->learner_roles,'ra.roleid');

        $this->params['courseid'] = $params->courseid;
        $data = $DB->get_records_sql("
            SELECT us.id, CONCAT(us.firstname,' ',us.lastname) AS name
            FROM {context} c
                LEFT JOIN {role_assignments} ra ON ra.contextid=c.id $sql_enabled
                LEFT JOIN {user} us ON us.id=ra.userid
            WHERE us.id IS NOT NULL AND c.contextlevel=50 AND c.instanceid = :courseid $sql",$this->params);

        return array("data" => $data);
    }

    public function get_bb_collaborate_sessions($params)
    {
        global $DB;

        $where = 'cb.id > 0';
        $where .= $this->get_filter_in_sql($params->courseid, 'cb.course');

        $data = $DB->get_records_sql("
            SELECT cb.id, cb.name
            FROM {collaborate} cb
            WHERE {$where}",
            $this->params,
            $params->start,
            $params->length
        );

        return ["data" => $data];
    }

    public function get_grade_letters($params)
    {
        global $DB;

        $data = $DB->get_records_sql("
            SELECT id,lowerboundary,letter
            FROM {grade_letters}
            WHERE contextid = 1");

        return array("letters" => $data);
    }

    public function get_questions($params){
        global $CFG, $DB;

        if($CFG->version < 2012120301){
            $sql_extra = "q.questions";
        }else{
            $sql_extra = "qat.layout";
        }

        if ($CFG->dbtype == 'pgsql') {
            $extra = "ROUND(((LENGTH($sql_extra) - LENGTH(REPLACE($sql_extra, ',', '')) + 1)/2), 0)";
        } else {
            $extra = "FORMAT(((LENGTH($sql_extra) - LENGTH(REPLACE($sql_extra, ',', '')) + 1)/2), 0)";
        }

        $this->params['filter'] = intval($params->filter);
        return $DB->get_records_sql("
             SELECT qa.id,
                ROUND(((qa.maxmark * qas.fraction) * q.grade / q.sumgrades),2) as grade,
                qa.slot,
                qu.id as attempt,
                q.name as quiz,
                que.name as question,
                que.questiontext,
                qas.userid,
                qas.state,
                qas_completed.timecreated,
                $extra as questions
             FROM
                {question_attempts} qa,
                {question_attempt_steps} qas,
                {question_attempt_steps} qas_completed,
                {question_usages} qu,
                {question} que,
                {quiz} q,
                {quiz_attempts} qat,
                {context} cx,
                {course_modules} cm
             WHERE qat.id = :filter
               AND q.id = qat.quiz
               AND cm.instance = q.id
               AND cx.instanceid = cm.id
               AND qu.contextid = cx.id
               AND qa.questionusageid = qu.id
               AND qas.questionattemptid = qa.id
               AND que.id = qa.questionid
               AND qas.state <> 'todo'
               AND qas.state <> 'complete'
               AND qas.userid = qat.userid
               AND qas_completed.questionattemptid=qas.questionattemptid
               AND qas_completed.state='complete'
             ORDER BY qas.timecreated DESC",$this->params);
    }

    public function get_live_info($params)
    {
        global $DB;

        $datefilter_1 = $this->get_filterdate_sql($params, 'l.timepoint');
        $datefilter_2 = $this->get_filterdate_sql($params, 'cc.timecompleted');
        $datefilter_4 = $this->get_filterdate_sql($params, 'cc.timemodified');
        $datefilter_5 = $this->get_filterdate_sql($params, 'timecreated');
        $datefilter_6 = $this->get_filterdate_sql($params, 'ue.timecreated');
        $datefilter_7 = $this->get_filterdate_sql($params, 'l.timepoint');

        $this->params["to1"] = strtotime('-10 minutes');
        $this->params["to2"] = strtotime('-10 minutes');
        $this->params["to3"] = strtotime('-10 minutes');
        $filter_completion = $this->get_completion($params, "cc.");
        $filter_teacher = $this->get_filter_in_sql($params->teacher_roles, "roleid");
        $filter_learner = $this->get_filter_in_sql($params->learner_roles, 'roleid');

        if ($params->externalid) {
            $sql = $this->get_teacher_sql($params, ["t.userid" => "users", "t.courseid" => "courses"]);
            $data = array();
            $data['activity'] = $DB->get_records_sql("
                SELECT d.timepoint, SUM(d.visits) AS visits
                FROM
                    {local_intelliboard_tracking} t,
                    {local_intelliboard_logs} l,
                    {local_intelliboard_details} d
                WHERE l.trackid = t.id AND d.logid = l.id $datefilter_1 $sql
                GROUP BY d.timepoint", $this->params);

            $sql1 = $this->get_teacher_sql($params, ["cc.userid" => "users", "c.id" => "courses"]);
            $sql2 = $this->get_teacher_sql($params, ["cc.userid" => "users", "cm.course" => "courses"]);
            $sql3 = $this->get_teacher_sql($params, ["id" => "users"]);
            $sql4 = $this->get_teacher_sql($params, ["ue.userid" => "users", "e.courseid" => "courses"]);
            $sql5 = $this->get_teacher_sql($params, ["r.userid" => "users", "ctx.instanceid" => "courses"]);
            $sql6 = $this->get_teacher_sql($params, ["r.userid" => "users", "ctx.instanceid" => "courses"]);
            $sql7 = $this->get_teacher_sql($params, ["id" => "users"]);

            $data['info'] = $DB->get_record_sql("
                SELECT
                    (SELECT COUNT(cc.id) FROM {course_completions} cc, {course} c WHERE cc.course = c.id $datefilter_2 $sql1) AS completions,
                    (SELECT COUNT(cc.id) FROM {course_modules_completion} cc, {course_modules} cm WHERE cm.id = cc.coursemoduleid $datefilter_4 $filter_completion $sql2) AS completed_activities,
                    (SELECT COUNT(id) FROM {user} WHERE 1 $datefilter_5 $sql3) AS users,
                    (SELECT COUNT(ue.id) FROM {user_enrolments} ue, {enrol} e WHERE e.id = ue.enrolid $datefilter_6 $sql4) AS enrolments,
                    (SELECT COUNT(DISTINCT r.userid) FROM {role_assignments} r, {user} u, {context} ctx WHERE ctx.id = r.contextid AND ctx.contextlevel = 50 AND r.userid = u.id AND u.lastaccess >= :to1 $filter_learner $sql5) AS learners,
                    (SELECT COUNT(DISTINCT r.userid) FROM {role_assignments} r, {user} u, {context} ctx WHERE ctx.id = r.contextid AND ctx.contextlevel = 50 AND r.userid = u.id AND u.lastaccess >= :to2 $filter_teacher $sql6) AS teachers,
                    (SELECT COUNT(id) FROM {user} WHERE lastaccess >= :to3 $sql7) AS online,
                    t.sessions,
                    t.timespend,
                    t.visits
                FROM (SELECT COUNT(DISTINCT t.userid) AS sessions, SUM(l.timespend) AS timespend, SUM(l.visits) AS visits FROM {local_intelliboard_tracking} t, {local_intelliboard_logs} l WHERE l.trackid = t.id $datefilter_7 $sql) t", $this->params);
        } else {
            $data = array();
            $data['activity'] = $DB->get_records_sql("
                SELECT d.timepoint, SUM(d.visits) AS visits
                FROM
                    {local_intelliboard_logs} l,
                    {local_intelliboard_details} d
                WHERE d.logid = l.id $datefilter_1
                GROUP BY d.timepoint", $this->params);

            $data['info'] = $DB->get_record_sql("
                SELECT
                    (SELECT COUNT(cc.id) FROM {course_completions} cc WHERE 1 $datefilter_2) AS completions,
                    (SELECT COUNT(cc.id) FROM {course_modules_completion} cc WHERE 1 $datefilter_4 $filter_completion) AS completed_activities,
                    (SELECT COUNT(id) FROM {user} WHERE 1 $datefilter_5) AS users,
                    (SELECT COUNT(ue.id) FROM {user_enrolments} ue WHERE 1 $datefilter_6) AS enrolments,
                    (SELECT COUNT(DISTINCT r.userid) FROM {role_assignments} r, {user} u WHERE r.userid = u.id AND u.lastaccess >= :to1 $filter_learner) AS learners,
                    (SELECT COUNT(DISTINCT r.userid) FROM {role_assignments} r, {user} u WHERE r.userid = u.id AND u.lastaccess >= :to2 $filter_teacher) AS teachers,
                    (SELECT COUNT(id) FROM {user} WHERE lastaccess >= :to3) AS online,
                    t.sessions,
                    t.timespend,
                    t.visits
                FROM (SELECT SUM(l.sessions) AS sessions, SUM(l.timespend) AS timespend, SUM(l.visits) AS visits FROM {local_intelliboard_totals} l WHERE 1 $datefilter_7) t", $this->params);
        }

        return $data;
    }
    public function get_total_info($params)
    {
        global $DB;

        $sql1 = $this->get_teacher_sql($params, ["id" => "users"]);
        $sql3 = $this->get_teacher_sql($params, ["id" => "courses"]);
        $sql4 = $this->get_teacher_sql($params, ["course" => "courses"]);
        $sql5 = $this->get_teacher_sql($params, ["id" => "categories"]);
        $sql1 .= $this->get_filter_enrolled_users_sql($params, "id");
        $sql1 .= $this->get_filter_user_sql($params, "");
        $sql3 .= $this->get_filter_course_sql($params, "");
        $sql4 .= $this->get_filter_module_sql($params, "");

        if($params->sizemode){
            $sql_files = ",
                '0' as space,
                '0' as userspace,
                '0' as coursespace";
        }else{
            $sql11 = $this->get_teacher_sql($params, ["userid" => "users"]);
            $sql22 = $this->get_teacher_sql($params, ["userid" => "users"]);
            $sql33 = $this->get_teacher_sql($params, ["userid" => "users"]);

            $sql_files = ",
                (SELECT SUM(filesize) FROM {files} WHERE id > 0 $sql11) as space,
                (SELECT SUM(filesize) FROM {files} WHERE component='user' $sql22) as userspace,
                (SELECT SUM(filesize) FROM {files} WHERE filearea='content' $sql33) as coursespace";
        }
        return $DB->get_record_sql("
            SELECT
                (SELECT COUNT(*) FROM {user} WHERE id > 0 $sql1) AS users,
                (SELECT COUNT(*) FROM {course} WHERE category > 0 $sql3) AS courses,
                (SELECT COUNT(*) FROM {course_modules} WHERE id > 0 $sql4) AS modules,
                (SELECT COUNT(*) FROM {course_categories} WHERE visible = 1 {$sql5}) AS categories
                $sql_files", $this->params);
    }
    public function get_moodle_size($params)
    {
      global $DB;

      return $DB->get_record_sql("
              SELECT
                (SELECT COUNT(*) FROM {local_intelliboard_logs}) AS local_intelliboard_logs,
                (SELECT COUNT(*) FROM {local_intelliboard_details}) AS local_intelliboard_details,
                (SELECT COUNT(*) FROM {local_intelliboard_totals}) AS local_intelliboard_totals,
                (SELECT COUNT(*) FROM {local_intelliboard_tracking}) AS local_intelliboard_tracking,
                (SELECT COUNT(*) FROM {logstore_standard_log}) AS logstore_standard_log,
                (SELECT COUNT(*) FROM {course_completions}) AS course_completions,
                (SELECT COUNT(*) FROM {course_modules}) AS course_modules,
                (SELECT COUNT(*) FROM {course_modules_completion}) AS course_modules_completion,
                (SELECT COUNT(*) FROM {context}) AS context,
                (SELECT COUNT(*) FROM {role_assignments}) AS role_assignments,
                (SELECT COUNT(*) FROM {user_enrolments}) AS user_enrolments,
                (SELECT COUNT(*) FROM {grade_items}) AS grade_items,
                (SELECT COUNT(*) FROM {grade_grades}) AS grade_grades,
                (SELECT COUNT(*) FROM {quiz_attempts}) AS quiz_attempts,
                (SELECT COUNT(*) FROM {quiz_grades}) AS quiz_grades");
    }
    public function get_system_users($params){
        global $DB;

        $sql1 = $this->get_teacher_sql($params, ["id" => "users"]);
        $sql2 = $this->get_teacher_sql($params, ["id" => "users"]);
        $sql3 = $this->get_teacher_sql($params, ["id" => "users"]);
        $sql4 = $this->get_teacher_sql($params, ["id" => "users"]);
        $sql5 = $this->get_teacher_sql($params, ["id" => "users"]);
        $sql6 = $this->get_teacher_sql($params, ["ue.userid" => "users", "e.courseid" => "courses"]);
        $sql7 = $this->get_teacher_sql($params, ["ue.userid" => "users", "e.courseid" => "courses"]);
        $sql8 = $this->get_teacher_sql($params, ["ue.userid" => "users", "e.courseid" => "courses"]);
        $sql9 = $this->get_teacher_sql($params, ["ue.userid" => "users", "e.courseid" => "courses"]);
        $sql10 = $this->get_teacher_sql($params, ["ue.userid" => "users", "e.courseid" => "courses"]);

        return $DB->get_record_sql(
            "SELECT (SELECT COUNT(u.id) FROM {user} u WHERE u.username <> 'guest' AND u.deleted = 0 $sql1) AS users,
                    (SELECT COUNT(u.id) FROM {user} u WHERE u.username <> 'guest' AND u.deleted = 1 $sql2) AS deleted,
                    (SELECT COUNT(u.id) FROM {user} u WHERE u.username <> 'guest' AND u.deleted = 0 AND u.suspended = 0 AND u.lastaccess > 0 $sql3) AS active,
                    (SELECT COUNT(u.id) FROM {user} u WHERE u.username <> 'guest' AND (u.confirmed = 0) $sql4) AS deactive,
                    (SELECT COUNT(u.id) FROM {user} u WHERE u.username <> 'guest' AND u.suspended = 1 $sql5) AS suspended,
                    (SELECT SUM(CASE WHEN diff = 0 THEN 1 ELSE 0 END)
                       FROM (SELECT COUNT(ue.id) - SUM(CASE WHEN cc.timecompleted > 0 THEN 1 ELSE 0 END) AS diff
                               FROM {user_enrolments} ue
                               JOIN {enrol} e ON e.id = ue.enrolid
                               JOIN {course_completions} cc ON cc.userid = ue.userid AND cc.course = e.courseid
                              WHERE ue.status = 0 {$sql6}
                           GROUP BY ue.userid
                             ) t
                    ) AS graduated,
                    (SELECT COUNT(DISTINCT ue.userid) FROM {enrol} e, {user_enrolments} ue WHERE e.id = ue.enrolid $sql7) AS enrolled,
                    (SELECT COUNT(DISTINCT ue.userid) FROM {enrol} e, {user_enrolments} ue WHERE e.enrol = 'cohort' AND ue.enrolid = e.id $sql8) AS enrol_cohort,
                    (SELECT COUNT(DISTINCT ue.userid) FROM {enrol} e, {user_enrolments} ue WHERE e.enrol = 'manual' AND ue.enrolid = e.id $sql9) AS enrol_manual,
                    (SELECT COUNT(DISTINCT ue.userid) FROM {enrol} e, {user_enrolments} ue WHERE e.enrol = 'self' AND ue.enrolid = e.id $sql10) AS enrol_self",
            $this->params
        );
    }

    public function get_system_courses($params)
    {
        global $DB;

        $sql1 = $this->get_teacher_sql($params, ["userid" => "users", "course" => "courses"]);
        $sql1 .= $this->vendor_filter("userid", "course", $params);
        $sql2 = $this->get_teacher_sql($params, ["course" => "courses"]);
        $sql2 .= $this->vendor_filter(null, "course", $params);
        $sql3 = $this->get_teacher_sql($params, ["id" => "courses"]);
        $sql3 .= $this->vendor_filter(null, "id", $params);
        $sql4 = $this->get_teacher_sql($params, ["id" => "courses"]);
        $sql4 .= $this->vendor_filter(null, "id", $params);
        $sql5 = $this->get_teacher_sql($params, ["ue.userid" => "users", "e.courseid" => "courses"]);
        $sql5 .= $this->vendor_filter("ue.userid", "e.courseid", $params);
        $sql6 = $this->get_filter_in_sql($params->learner_roles,'roleid',false);
        $sql6 .= $this->get_teacher_sql($params, ["userid" => "users"]);
        $sql6 .= $this->vendor_filter("userid", null, $params);
        $sql7 = $this->get_filter_in_sql($params->teacher_roles,'roleid',false);
        $sql7 .= $this->get_teacher_sql($params, ["userid" => "users"]);
        $sql7 .= $this->vendor_filter("userid", null, $params);
        $sql8 = $this->get_teacher_sql($params, ["userid" => "users", "courseid" => "courses"]);
        $sql8 .= $this->vendor_filter("userid", "courseid", $params);
        $sql9 = $this->get_teacher_sql($params, ["cm.course" => "courses"]);
        $sql9 .= $this->vendor_filter(null, "cm.course", $params);
        $sql10 = $this->get_teacher_sql($params, ["cmc.userid" => "users", "cm.course" => "courses"]);
        $sql10 .= $this->get_completion($params, "cmc.");
        $sql10 .= $this->get_filter_module_sql($params, "cm.");
        $sql10 .= $this->vendor_filter("cmc.userid", "cm.course", $params);

        $data = $DB->get_record_sql(
            "SELECT (SELECT COUNT(*) FROM {course_completions} WHERE timecompleted > 0 {$sql1}) AS completed_courses,
                    (SELECT COUNT(*) FROM {course_modules} WHERE visible = 1 {$sql2}) AS modules,
                    (SELECT COUNT(*) FROM {course} WHERE visible = 1 AND category > 0 {$sql3}) AS visible,
                    (SELECT COUNT(*) FROM {course} WHERE visible = 0 AND category > 0 {$sql4}) AS hidden,
                    (SELECT COUNT(DISTINCT ue.userid) FROM {enrol} e, {user_enrolments} ue WHERE e.id = ue.enrolid AND ue.status = 1 {$sql5}) AS expired,
                    (SELECT COUNT(DISTINCT (userid)) FROM {role_assignments} WHERE {$sql6}) AS learners,
                    (SELECT COUNT(DISTINCT (userid)) FROM {role_assignments} WHERE {$sql7}) AS teachers,
                    (SELECT COUNT(DISTINCT (param)) FROM {local_intelliboard_tracking} WHERE page = 'module' {$sql8}) AS reviewed,
                    (SELECT COUNT(cmc.id) FROM {course_modules_completion} cmc, {course_modules} cm WHERE cmc.coursemoduleid = cm.id {$sql10}) AS completed_activities,
                    (SELECT COUNT(cm.id) FROM {course_modules} cm, {modules} m WHERE m.name = 'certificate' AND cm.module = m.id {$sql9}) AS certificates",
            $this->params
        );

        if ($data->certificates) {
            $data->certificates_issued = $DB->count_records('certificate_issues');
        } else {
            $data->certificates_issued = 0;
        }
        return $data;
    }

    public function get_system_load($params){
        global $DB;

        $sql1 = $this->get_teacher_sql($params, ["userid" => "users", "courseid" => "courses"]);
        $sql2 = $this->get_teacher_sql($params, ["userid" => "users", "courseid" => "courses"]);
        $sql3 = $this->get_teacher_sql($params, ["userid" => "users", "courseid" => "courses"]);
        $sql4 = $this->get_teacher_sql($params, ["userid" => "users", "courseid" => "courses"]);
        $sql5 = $this->get_teacher_sql($params, ["userid" => "users", "courseid" => "courses"]);
        $sql6 = $this->get_teacher_sql($params, ["userid" => "users", "courseid" => "courses"]);

        return $DB->get_record_sql("
            SELECT
                (SELECT SUM(timespend) FROM {local_intelliboard_tracking} WHERE id > 0 $sql1) AS sitetimespend,
                (SELECT SUM(timespend) FROM {local_intelliboard_tracking} WHERE courseid > 0 $sql2) AS coursetimespend,
                (SELECT SUM(timespend) FROM {local_intelliboard_tracking} WHERE page = 'module' $sql3) AS activitytimespend,
                (SELECT SUM(visits) FROM {local_intelliboard_tracking} WHERE id > 0 $sql4) AS sitevisits,
                (SELECT SUM(visits) FROM {local_intelliboard_tracking} WHERE courseid > 0 $sql5) AS coursevisits,
                (SELECT SUM(visits) FROM {local_intelliboard_tracking} WHERE page = 'module' $sql6) AS activityvisits", $this->params);
    }

    public function get_module_visits($params){
        global $DB;

        $sql = $this->get_teacher_sql($params, ["lit.userid" => "users", "lit.courseid" => "courses"]);
        $sql .= $this->get_filter_module_sql($params, "cm.");

        return $DB->get_records_sql("
            SELECT m.id, m.name, SUM(lit.visits) AS visits
            FROM {local_intelliboard_tracking} lit, {course_modules} cm, {modules} m
            WHERE lit.page = 'module' AND cm.id = lit.param AND m.id = cm.module $sql
            GROUP BY m.id", $this->params);
    }
    public function get_useragents($params){
        global $DB;

        $sql = $this->get_teacher_sql($params, ["userid" => "users", "courseid" => "courses"]);

        return $DB->get_records_sql("
            SELECT MAX(id) AS id, useragent AS name, COUNT(DISTINCT userid) AS amount
            FROM {local_intelliboard_tracking}
            WHERE useragent != '' $sql
            GROUP BY useragent", $this->params);
    }
    public function get_useros($params){
        global $DB;

        $sql = $this->get_teacher_sql($params, ["userid" => "users", "courseid" => "courses"]);

        return $DB->get_records_sql("
            SELECT MAX(id) AS id, useros AS name, COUNT(DISTINCT userid) AS amount
            FROM {local_intelliboard_tracking}
            WHERE useros != '' $sql
            GROUP BY useros", $this->params);
    }
    public function get_userlang($params){
        global $DB;

        $sql = $this->get_teacher_sql($params, ["userid" => "users", "courseid" => "courses"]);

        return $DB->get_records_sql("
            SELECT MAX(id) AS id, userlang AS name, COUNT(DISTINCT userid) AS amount
            FROM {local_intelliboard_tracking}
            WHERE userlang != '' $sql
            GROUP BY userlang", $this->params);
    }

    public function get_users_count($params){
        global $DB;

        $sql = $this->get_teacher_sql($params, ["id" => "users"]);
        $sql .= $this->get_filter_user_sql($params, "");
        $sql .= $this->get_filterdate_sql($params, "timecreated");

        return $DB->get_records_sql("
            SELECT MAX(id) AS id, auth, COUNT(*) AS users
            FROM {user}
            WHERE id > 0 $sql
            GROUP BY auth", $this->params);
    }
    public function get_most_visited_courses($params){
        global $DB;

        $sql = $this->get_teacher_sql($params, ["l.userid" => "users", "l.courseid" => "courses"]);
        $sql .= $this->get_filter_course_sql($params, "c.");
        $sql .= $this->vendor_filter("l.userid", "c.id", $params);

        return $DB->get_records_sql(
            "SELECT c.id,
                    c.fullname,
                    SUM(l.visits) AS visits
               FROM {local_intelliboard_tracking} l
               JOIN {course} c ON c.id = l.courseid
              WHERE c.id > 0 {$sql}
           GROUP BY c.id
           ORDER BY visits DESC",
            $this->params, 0, 100
        );
    }

    public function get_visits_perweek($params){
        global $DB, $CFG;


        $month_func = ($CFG->dbtype == 'pgsql') ? "EXTRACT(MONTH FROM to_timestamp(l.timepoint))" : "MONTH(FROM_UNIXTIME(l.timepoint))";
        $week_func = ($CFG->dbtype == 'pgsql') ? "EXTRACT(DAY from date_trunc('week', to_timestamp(l.timepoint)) -
                   date_trunc('week', date_trunc('month', to_timestamp(l.timepoint)))) / 7 + 1" : "FLOOR(((DAY(FROM_UNIXTIME(l.timepoint)) - 1) / 7) + 1)";

        $sql = $this->get_filterdate_sql($params, "l.timepoint");

        if ($params->externalid) {
            $sql .= $this->get_teacher_sql($params, ["t.userid" => "users", "t.courseid" => "courses"]);

            return $DB->get_records_sql("
                SELECT max(l.id),
                    $month_func AS monthpoint,
                    $week_func AS weekpoint,
                    COUNT(DISTINCT t.userid) AS sessions, SUM(t.visits) AS visits
                FROM {local_intelliboard_tracking} t, {local_intelliboard_logs} l
                WHERE l.trackid = t.id $sql
                GROUP BY monthpoint, weekpoint", $this->params);
        } else {
            return $DB->get_records_sql("
                SELECT max(l.id),
                    $month_func AS monthpoint,
                    $week_func AS weekpoint,
                    SUM(l.sessions) AS sessions, SUM(l.visits) AS visits
                FROM {local_intelliboard_totals} l WHERE l.id > 0 $sql
                GROUP BY monthpoint, weekpoint", $this->params);
        }
    }

    public function get_visits_perday($params){
        global $DB, $CFG;

        $function = ($CFG->dbtype == 'pgsql') ? 'EXTRACT(isodow FROM to_timestamp(l.timepoint))-1' : 'WEEKDAY(FROM_UNIXTIME(l.timepoint))';

        $sql = $this->get_filterdate_sql($params, "l.timepoint");

        if ($params->externalid) {
            $sql .= $this->get_teacher_sql($params, ["t.userid" => "users", "t.courseid" => "courses"]);

            return $DB->get_records_sql("
                SELECT max(d.id) as id,
                    $function as daypoint,
                    d.timepoint as hourpoint,
                    SUM(d.visits) AS visits
                FROM {local_intelliboard_tracking} t, {local_intelliboard_logs} l, {local_intelliboard_details} d
                WHERE d.logid = l.id AND l.trackid = t.id $sql
                GROUP BY daypoint, hourpoint", $this->params);
        } else {
            return $DB->get_records_sql("
                SELECT max(d.id) as id,
                    $function as daypoint,
                    d.timepoint as hourpoint,
                    SUM(d.visits) AS visits
                FROM {local_intelliboard_logs} l, {local_intelliboard_details} d
                WHERE d.logid = l.id $sql
                GROUP BY daypoint, hourpoint", $this->params);
        }
    }

    public function get_visits_per_day_by_entity($params){
        global $DB;
        $sql = $this->get_filter_in_sql($params->userid, 't.userid');
        $sql .= $this->get_filter_in_sql($params->courseid, 't.courseid');
        $sql .= $this->get_filterdate_sql($params, "l.timepoint");

        return $DB->get_records_sql("
                SELECT CONCAT(t.daypoint, '_',t.monthpoint, '_',t.yearpoint) as id,
                     t.daypoint,
                     t.monthpoint,
                     t.yearpoint,
                     SUM(t.visits) AS visits
                     FROM
                     (SELECT " . get_operator('DAY', get_operator('FROM_UNIXTIME', 'l.timepoint')) . " AS daypoint,
                    " . get_operator('MONTH', get_operator('FROM_UNIXTIME', 'l.timepoint')) . " AS monthpoint,
                    " . get_operator('YEAR', get_operator('FROM_UNIXTIME', 'l.timepoint')) . " AS yearpoint,
                     l.visits as visits
                     FROM {local_intelliboard_tracking} t
                     INNER JOIN {local_intelliboard_logs} l ON t.id = l.trackid
                     WHERE t.userid <> 0 $sql
                     ) as t
                     GROUP BY t.daypoint, t.monthpoint, t.yearpoint
        ", $this->params);
    }


    public function get_enrollments_per_course($params){
        global $DB;

        $sql = $this->get_teacher_sql($params, ["ue.userid" => "users", "c.id" => "courses"]);
        $sql .= $this->get_filter_course_sql($params, "c.");
        $sql .= $this->get_filter_enrol_sql($params, "e.");
        $sql .= $this->get_filter_enrol_sql($params, "ue.");
        $sql .= $this->get_filterdate_sql($params, "ue.timemodified");
        $sql .= $this->get_filter_in_sql($params->courseid, 'c.id');
        $sql .= $this->vendor_filter("ue.userid", "c.id", $params);

        return $DB->get_records_sql(
            "SELECT c.id,
                    c.fullname,
                    COUNT(DISTINCT ue.userid ) AS enrolled,
                    COUNT(DISTINCT cc.userid ) AS completed
               FROM {course} c
          LEFT JOIN {enrol} e ON e.courseid = c.id
          LEFT JOIN {user_enrolments} ue ON ue.enrolid = e.id
          LEFT JOIN {course_completions} cc ON cc.course = e.courseid AND cc.userid = ue.userid AND cc.timecompleted > 0
              WHERE c.id > 0 {$sql}
           GROUP BY c.id",
            $this->params, 0, 100
        ); // maximum
    }


    public function get_active_courses_per_day($params){
        global $DB;

        $datediff = $params->timefinish - $params->timestart;
        $days = floor($datediff/(60*60*24)) + 1;
        $sql_filter = $this->get_teacher_sql($params, ["tr.userid" => "users", "tr.courseid" => "courses"]);

        if($days <= 30){
            $ext = 86400; //by day
        }elseif($days <= 90){
            $ext = 604800; //by week
        }elseif($days <= 365){
            $ext = 2592000; //by month
        }else{
            $ext = 31556926; //by year
        }

        $this->params['timestart'] = $params->timestart;
        $this->params['timefinish'] = $params->timefinish;

        return $DB->get_records_sql(
            "SELECT FLOOR(il.timepoint / $ext) * $ext AS timepointval, COUNT(DISTINCT tr.courseid) AS courses
               FROM {local_intelliboard_tracking} tr
               JOIN {local_intelliboard_logs} il ON il.trackid = tr.id
              WHERE FLOOR(il.timepoint / $ext) * $ext BETWEEN :timestart AND :timefinish AND
                    tr.page = 'course' {$sql_filter}
           GROUP BY timepointval", $this->params
        );
    }
    public function get_unique_sessions($params){
        global $DB;

        $datediff = $params->timefinish - $params->timestart;
        $days = floor($datediff/(60*60*24)) + 1;
        $sql_filter = $this->get_teacher_sql($params, ["lit.userid" => "users", "lit.courseid" => "courses"]);
        $userfilter = '';

        if($days <= 1){
            $ext = 3600; //by hour
        }elseif($days <= 45){
            $ext = 86400; //by day
        }elseif($days <= 90){
            $ext = 604800; //by week
        }elseif($days <= 365){
            $ext = 2592000; //by month
        }else{
            $ext = 31556926; //by year
        }

        $this->params['timestart'] = $params->timestart;
        $this->params['timefinish'] = $params->timefinish;

        if ($params->custom4) {
            $users = $DB->get_records_sql(
                "SELECT userid
                   FROM {role_assignments}
                  WHERE contextid = :context AND roleid = :role",
                ['role' => $params->custom4, 'context' => context_system::instance()->id]
            );

            if (count($users)) {
                $userfilter = $this->get_filter_in_sql(array_keys($users), 'lit.userid');
            } else {
                $userfilter = ' AND lit.userid = -1';
            }
        }

        return $DB->get_records_sql(
            "SELECT FLOOR(t.timepoint / $ext) * $ext AS timepointval, SUM(t.users) AS users
               FROM (SELECT lil.timepoint, COUNT(DISTINCT lit.userid) AS users
                       FROM {local_intelliboard_tracking} lit
                       JOIN {local_intelliboard_logs} lil ON lit.id = lil.trackid {$userfilter}
                      WHERE lit.id > 0 {$sql_filter}
                   GROUP BY lil.timepoint
                    ) t
              WHERE FLOOR(t.timepoint / $ext) * $ext BETWEEN :timestart AND :timefinish
           GROUP BY timepointval",
            $this->params
        );
    }
    public function get_new_courses_per_day($params){
        global $DB;

        $sql = $this->get_teacher_sql($params, ["id" => "courses"]);
        $datediff = $params->timefinish - $params->timestart;
        $days = floor($datediff/(60*60*24)) + 1;

        if($days <= 1){
            $ext = 3600; //by hour
        }elseif($days <= 45){
            $ext = 86400; //by day
        }elseif($days <= 90){
            $ext = 604800; //by week
        }elseif($days <= 365){
            $ext = 2592000; //by month
        }else{
            $ext = 31556926; //by year
        }

        $this->params['timestart'] = $params->timestart;
        $this->params['timefinish'] = $params->timefinish;


        return $DB->get_records_sql("SELECT FLOOR(timecreated / $ext) * $ext AS timeval, COUNT(id) AS courses
            FROM {course}
            WHERE category > 0 AND FLOOR(timecreated / $ext) * $ext BETWEEN :timestart AND :timefinish $sql
            GROUP BY timeval", $this->params);
    }
    public function get_users_per_day($params){
        global $DB, $CFG;

        $datediff = $params->timefinish - $params->timestart;
        $days = floor($datediff/(60*60*24)) + 1;

        if($days <= 1){
            $ext = 3600; //by hour
            $group_time = ($CFG->dbtype == 'pgsql')?"to_char(date(to_timestamp(timecreated)),'YYYYDDDHH24')":"DATE_FORMAT(FROM_UNIXTIME(timecreated), '%Y%j%H')";
        }elseif($days <= 45){
            $ext = 86400; //by day
            $group_time = ($CFG->dbtype == 'pgsql')?"to_char(date(to_timestamp(timecreated)),'YYYYDDD')":"DATE_FORMAT(FROM_UNIXTIME(timecreated), '%Y%j')";
        }elseif($days <= 90){
            $ext = 604800; //by week
            $group_time = ($CFG->dbtype == 'pgsql')?"to_char(date(to_timestamp(timecreated)),'WW')":"YEARWEEK(FROM_UNIXTIME(timecreated))";
        }elseif($days <= 365){
            $ext = 2592000; //by month
            $group_time = ($CFG->dbtype == 'pgsql')?"to_char(date(to_timestamp(timecreated)),'YYYYMM')":"DATE_FORMAT(FROM_UNIXTIME(timecreated), '%Y%m')";
        }else{
            $ext = 31556926; //by year
            $group_time = ($CFG->dbtype == 'pgsql')?"to_char(date(to_timestamp(timecreated)),'YYYY')":"DATE_FORMAT(FROM_UNIXTIME(timecreated), '%Y')";
        }
        $sql = $this->get_teacher_sql($params, ["id" => "users"]);
        $sql .= $this->get_filter_user_sql($params, "");

        $this->params['timestart'] = $params->timestart;
        $this->params['timefinish'] = $params->timefinish;

        return $DB->get_records_sql("SELECT $group_time AS group_time, MIN(timecreated) AS timepoint, COUNT(id) AS users
            FROM {user}
            WHERE timecreated BETWEEN :timestart AND :timefinish $sql
            GROUP BY group_time
            ORDER BY timepoint", $this->params);
    }
    public function get_relation_users_per_day($params){
        global $DB, $CFG;

        $datediff = $params->timefinish - $params->timestart;
        $days = floor($datediff/(60*60*24)) + 1;

        if($days <= 1){
            $ext = 3600; //by hour
            $group_time = ($CFG->dbtype == 'pgsql')?"to_char(date(to_timestamp(u.timecreated)),'YYYYDDDHH24')":"DATE_FORMAT(FROM_UNIXTIME(u.timecreated), '%Y%j%H')";
        }elseif($days <= 45){
            $ext = 86400; //by day
            $group_time = ($CFG->dbtype == 'pgsql')?"to_char(date(to_timestamp(u.timecreated)),'YYYYDDD')":"DATE_FORMAT(FROM_UNIXTIME(u.timecreated), '%Y%j')";
        }elseif($days <= 90){
            $ext = 604800; //by week
            $group_time = ($CFG->dbtype == 'pgsql')?"to_char(date(to_timestamp(u.timecreated)),'WW')":"YEARWEEK(FROM_UNIXTIME(u.timecreated))";
        }elseif($days <= 365){
            $ext = 2592000; //by month
            $group_time = ($CFG->dbtype == 'pgsql')?"to_char(date(to_timestamp(u.timecreated)),'YYYYMM')":"DATE_FORMAT(FROM_UNIXTIME(u.timecreated), '%Y%m')";
        }else{
            $ext = 31556926; //by year
            $group_time = ($CFG->dbtype == 'pgsql')?"to_char(date(to_timestamp(u.timecreated)),'YYYY')":"DATE_FORMAT(FROM_UNIXTIME(u.timecreated), '%Y')";
        }
        $sql = $this->get_teacher_sql($params, ["u.id" => "users"]);
        $sql .= $this->get_filter_user_sql($params, "u.");
        $sql2 = $this->get_teacher_sql($params, ["id" => "users"]);
        $sql2 .= $this->get_filter_user_sql($params, "");

        $this->params['timestart'] = $params->timestart;
        $this->params['timefinish'] = $params->timefinish;

        return $DB->get_records_sql("SELECT
                $group_time AS group_time,
                MIN(u.timecreated) AS timepoint,
                COUNT(u.id) AS users,
                (SELECT COUNT(id) FROM {user} WHERE timecreated>0 AND timecreated<=MAX(u.timecreated) $sql2) AS all_users
            FROM {user} u
            WHERE u.timecreated>0 AND u.timecreated BETWEEN :timestart AND :timefinish $sql
            GROUP BY group_time
            ORDER BY timepoint", $this->params);
    }
    public function get_active_users_per_day($params){
        global $DB;

        $datediff = $params->timefinish - $params->timestart;
        $days = floor($datediff/(60*60*24)) + 1;
        $sql_filter = $this->get_teacher_sql($params, ["tr.userid" => "users", "tr.courseid" => "courses"]);

        if($days <= 45){
            $ext = 86400; //by day
        }elseif($days <= 90){
            $ext = 604800; //by week
        }elseif($days <= 365){
            $ext = 2592000; //by month
        }else{
            $ext = 31556926; //by year
        }

        $this->params['timestart'] = $params->timestart;
        $this->params['timefinish'] = $params->timefinish;

        return $DB->get_records_sql(
            "SELECT FLOOR(il.timepoint / $ext) * $ext AS timepointval, SUM(il.visits) AS users
               FROM {local_intelliboard_tracking} tr
               JOIN {local_intelliboard_logs} il ON il.trackid = tr.id
              WHERE FLOOR(il.timepoint / $ext) * $ext BETWEEN :timestart AND :timefinish {$sql_filter}
           GROUP BY timepointval",
           $this->params
        );
    }


    public function get_cohorts($params){
        global $DB;

        $sql = $this->get_teacher_sql($params, ["ch.id" => "cohorts"]);
        $sqljoin = "";

        if ($params->userid) {
            $sqljoin = "JOIN {cohort_members} cm ON cm.cohortid = ch.id AND cm.userid = :userid";
            $this->params["userid"] = $params->userid;
        }

        return $DB->get_records_sql(
            "SELECT DISTINCT ch.id, ch.name
               FROM {cohort} ch
               {$sqljoin}
              WHERE ch.visible = 1 {$sql}
           ORDER BY ch.name",
            $this->params
        );
    }
    public function get_elisuset($params){
        global $DB;

        return $DB->get_records_sql("SELECT id, name FROM {local_elisprogram_uset} ORDER BY name");
    }
    public function get_totara_pos($params){
        global $DB;

        return $DB->get_records_sql("SELECT id, fullname FROM {pos} WHERE visible = 1 ORDER BY fullname");
    }
    public function get_scorm_user_attempts($params){
        global $DB;

        $this->params['courseid'] = $params->courseid;
        $this->params['userid'] = (int) $params->userid;
        if($params->userid){
            $sql = " AND b.userid = :userid";
        }else{
            $sql = "";
        }
        return $DB->get_records_sql("SELECT DISTINCT b.attempt
                                     FROM {scorm} a, {scorm_scoes_track} b
                                     WHERE a.course = :courseid AND b.scormid = a.id $sql", $this->params);
    }
    public function get_course_users($params){
        global $DB;

        $sql_filter = $this->get_teacher_sql($params, ["u.id" => "users", "e.courseid" => "courses"]);
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "ue.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "e.");
        $sql_enabled = $this->get_filter_in_sql($params->courseid,'e.courseid');

        return $DB->get_records_sql("
            SELECT DISTINCT u.id, u.firstname, u.lastname
            FROM {user_enrolments} ue, {enrol} e, {user} u
            WHERE ue.enrolid = e.id AND u.id = ue.userid $sql_enabled $sql_filter", $this->params);
    }


    public function get_courses($params){
        global $DB;

        $sql = $this->get_teacher_sql($params, ["c.id" => "courses"]);

        $sql_filter = $this->get_filter_course_sql($params, "c.");

        if ($params->filter) {
            $sql_filter .= " AND " . $DB->sql_like('c.fullname', ":fullname", false, false);
            $this->params['fullname'] = "%$params->filter%";
        }


        $params->custom = clean_param($params->custom, PARAM_SEQUENCE);
        if ($params->custom) {
            $sql_enabled = $this->get_filter_in_sql($params->custom,'ue.userid');
            $sql_filter .= " AND c.id IN(SELECT DISTINCT(e.courseid) FROM {user_enrolments} ue, {enrol} e WHERE e.id = ue.enrolid $sql_enabled)";
        }



        if ($params->cohortid) {
            $this->params['cohortid'] = (int) $params->cohortid;

            $sql_filter .= " AND c.id IN(SELECT DISTINCT(courseid) FROM {enrol} WHERE customint1 = :cohortid)";
        }

        return $this->get_report_data("SELECT c.id,
                                            c.shortname,
                                            c.fullname,
                                            c.idnumber,
                                            ca.id AS cid,
                                            ca.idnumber AS cidnumber,
                                            ca.name AS category
                                        FROM {course} c, {course_categories} ca
                                        WHERE c.category = ca.id $sql $sql_filter
                                        ORDER BY c.fullname", $params, false);
    }
    public function get_modules($params){
        global $DB;

        $sql = "";
        if($params->custom){
            $sql = " AND name IN (SELECT itemmodule FROM {grade_items} GROUP BY itemmodule)";
        }
        return $DB->get_records_sql("SELECT id, name FROM {modules} WHERE visible = 1 $sql");
    }
    public function get_userids($params){
        global $DB;

        $emails = array();
        $usernames = array();
        $values = explode(",", $params->filter);
        foreach($values as $val){
            $emails[] = clean_param($val, PARAM_EMAIL);
            $usernames[] = clean_param($val, PARAM_USERNAME);
        }
        if(empty($emails) and empty($usernames)){
            return array();
        }

        $filter = "";
        if ($emails) {
            list($sql1, $sqlparams) = $DB->get_in_or_equal($usernames, SQL_PARAMS_NAMED, 'username');
            $this->params = array_merge($this->params, $sqlparams);
            $filter .= " AND username $sql1";
        }

        if ($usernames) {
            list($sql2, $sqlparams) = $DB->get_in_or_equal($emails, SQL_PARAMS_NAMED, 'email');
            $this->params = array_merge($this->params, $sqlparams);
            $filter .= ($filter) ? " OR email $sql2" : " AND email $sql2";
        }


        return $DB->get_records_sql("SELECT id FROM {user} WHERE id > 0 $filter", $this->params);
    }
    public function get_outcomes($params){
        global $DB;

        return $DB->get_records_sql("SELECT id, shortname, fullname FROM {grade_outcomes} WHERE courseid > 0");
    }
    public function get_roles($params){
        global $DB;

        if($params->filter){
            $sql = "'none'";
        }else{
            $sql = "'student', 'user', 'frontpage'";
        }

        return $DB->get_records_sql("
            SELECT id, name, shortname
            FROM {role}
            WHERE archetype NOT IN ($sql)
            ORDER BY sortorder");
    }
    public function get_roles_fix_name($params){
        $roles = role_fix_names(get_all_roles());
        return $roles;
    }
    public function get_system_roles_fix_names($params){
        global $DB;

        $roles = $DB->get_records_sql("
            SELECT r.*
            FROM {role} r
                JOIN {role_context_levels} rcl ON rcl.roleid=r.id AND rcl.contextlevel=10
            ORDER BY sortorder");

        return role_fix_names($roles);
    }
    public function get_tutors($params){
        global $DB;

        $params->filter = clean_param($params->filter, PARAM_INT);

        if($params->filter){
            $filter = "a.roleid = :roleid";
            $this->params['roleid'] = $params->filter;
        }else{
            $filter = $this->get_filter_in_sql($params->teacher_roles,'a.roleid',false);
        }

        return $DB->get_records_sql("
            SELECT u.id,  CONCAT(u.firstname, ' ', u.lastname) AS name, u.email
            FROM {user} u
            LEFT JOIN {role_assignments} a ON a.userid = u.id
            WHERE $filter AND u.deleted = 0 AND u.confirmed = 1
            GROUP BY u.id",$this->params);
    }


    public function get_cminfo($params){
        global $DB;

        $module = $DB->get_record_sql("
            SELECT cm.id, cm.instance, m.name
           FROM {course_modules} cm, {modules} m
           WHERE m.id = cm.module AND cm.id = :id",array('id'=>intval($params->custom)));

        return $DB->get_record($module->name, array('id'=>$module->instance));
    }
    public function get_course_items($params) {
        global $DB;
        $sql = $this->get_filter_in_sql($params->courseid, 'id');
        return $DB->get_records_sql("SELECT * FROM {course} WHERE id > 0 $sql", $this->params);
    }
    public function get_cohort_items($params)
    {
        global $DB;
        $sql = $this->get_filter_in_sql($params->cohortid, 'id');
        return $DB->get_records_sql("SELECT * FROM {cohort} WHERE id > 0 $sql", $this->params);
    }
    public function get_learner($params)
    {
        global $DB;

        if($params->userid){
            $this->params['userid1'] = $params->userid;
            $this->params['userid2'] = $params->userid;
            $this->params['userid3'] = $params->userid;
            $this->params['userid4'] = $params->userid;
            $this->params['userid5'] = $params->userid;
            $grade_avg = intelliboard_grade_sql(true, $params);

            $user = $DB->get_record_sql("
                SELECT
                   u.*,
                    MAX(cx.id) AS context,
                    COUNT(c.id) AS completed,
                    MAX(gc.grade) as grade,
                    MAX(lit.timespend_site) as timespend_site, MAX(lit.visits_site) as visits_site,
                    MAX(lit2.timespend_courses) as timespend_courses, MAX(lit2.visits_courses) as visits_courses,
                    MAX(lit3.timespend_modules) as timespend_modules, MAX(lit3.visits_modules) as visits_modules,
                    MAX((SELECT COUNT(*) FROM {course} WHERE visible = 1 AND category > 0)) AS available_courses
                 FROM {user} u
                    LEFT JOIN {course_completions} c ON c.timecompleted > 0 AND c.userid = u.id
                    LEFT JOIN {context} cx ON cx.instanceid = u.id AND contextlevel = 30
                    LEFT JOIN (SELECT g.userid, $grade_avg AS grade FROM {grade_items} gi, {grade_grades} g WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL AND g.userid = :userid1 GROUP BY g.userid) as gc ON gc.userid = u.id
                    LEFT JOIN (SELECT userid, SUM(timespend) AS timespend_site, SUM(visits) AS visits_site FROM {local_intelliboard_tracking} WHERE userid = :userid2  GROUP BY userid) lit ON lit.userid = u.id
                    LEFT JOIN (SELECT userid, SUM(timespend) AS timespend_courses, SUM(visits) AS visits_courses FROM {local_intelliboard_tracking} WHERE courseid > 0 AND userid = :userid3  GROUP BY userid) lit2 ON lit2.userid = u.id
                    LEFT JOIN (SELECT userid, SUM(timespend) AS timespend_modules, SUM(visits) AS visits_modules FROM {local_intelliboard_tracking} WHERE page = 'module' AND userid = :userid4  GROUP BY userid) lit3 ON lit3.userid = u.id
                 WHERE u.id = :userid5
                 GROUP BY u.id
                ", $this->params);

            if($user->id){
                $filter1 = $this->get_filter_in_sql($params->learner_roles, 'roleid', false);
                $filter2 = $this->get_filter_in_sql($params->learner_roles, 'roleid', false);

                $this->params['userid1'] = $user->id;
                $this->params['userid2'] = $user->id;
                $grade_avg = intelliboard_grade_sql(true, $params);

                $user->avg = $DB->get_record_sql("SELECT a.timespend_site, a.visits_site, c.grade_site
                                                  FROM
                                                    (SELECT ROUND(AVG(b.timespend_site),0) as timespend_site,
                                                            ROUND(AVG(b.visits_site),0) as visits_site
                                                        FROM (SELECT SUM(timespend) as timespend_site, SUM(visits) as visits_site
                                                            FROM {local_intelliboard_tracking}
                                                            WHERE userid NOT IN (SELECT DISTINCT userid FROM {role_assignments} WHERE $filter1) AND userid != :userid1
                                                            GROUP BY userid) AS b) a,
                                                    (SELECT ROUND(AVG(b.grade),0) AS grade_site FROM (SELECT $grade_avg AS grade
                                                        FROM {grade_items} gi, {grade_grades} g
                                                        WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL AND g.userid NOT IN (
                                                          SELECT distinct userid FROM {role_assignments} WHERE $filter2) AND g.userid != :userid2 GROUP BY g.userid) b) c
                                                ",$this->params);


                $this->params['userid'] = $user->id;
                $user->data = $DB->get_records_sql("SELECT uif.id, uif.name, uid.data
                                                    FROM {user_info_field} uif,
                                                         {user_info_data} uid
                                                    WHERE uif.id = uid.fieldid AND uid.userid = :userid
                                                    ORDER BY uif.name
                                                   ",$this->params);

                $user->grades = $DB->get_records_sql("SELECT MAX(g.id) as id, gi.itemmodule, $grade_avg AS grade
                                                      FROM {grade_items} gi,
                                                             {grade_grades} g
                                                      WHERE  gi.itemtype = 'mod' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL AND g.userid = :userid
                                                      GROUP BY gi.itemmodule

                                                     ",$this->params);

                $completion = $this->get_completion($params, "cmc.");
                $user->courses = $DB->get_records_sql("SELECT DISTINCT(c.id),
                                                              ue.userid,
                                                              ROUND(((cmc.completed/cmm.modules)*100), 0) AS completion,
                                                              c.id AS cid,
                                                              c.fullname
                                                       FROM {user_enrolments} ue
                                                           LEFT JOIN {enrol} e ON e.id = ue.enrolid
                                                           LEFT JOIN {course} c ON c.id = e.courseid
                                                           LEFT JOIN {course_completions} cc ON cc.timecompleted > 0 AND cc.course = e.courseid AND cc.userid = ue.userid
                                                           LEFT JOIN (SELECT cm.course, COUNT(cm.id) AS modules FROM {course_modules} cm WHERE cm.visible = 1 AND cm.completion > 0 GROUP BY cm.course) AS cmm ON cmm.course = c.id
                                                           LEFT JOIN (SELECT cm.course, cmc.userid, COUNT(cmc.id) AS completed FROM {course_modules} cm, {course_modules_completion} cmc WHERE cmc.coursemoduleid = cm.id AND cm.visible  =  1 $completion GROUP BY cm.course, cmc.userid) as cmc ON cmc.course = c.id AND cmc.userid = ue.userid
                                                       WHERE ue.userid = :userid

                                                       ORDER BY c.fullname
                                                      ",$this->params, 0, 100);
            }else{
                return false;
            }
        }else{
            return false;
        }
        return $user;
    }

    public function get_learners($params){
        global $DB;

        $params->filter = clean_param($params->filter, PARAM_SEQUENCE);
        $filter = $this->get_filter_in_sql($params->filter,'u.id');
        $sql1 = $this->get_filter_in_sql($params->learner_roles, "ra.roleid");
        $grade_avg = intelliboard_grade_sql(true, $params);

        return $DB->get_records_sql("
           SELECT u.id,u.firstname,u.lastname,u.email,u.firstaccess,u.lastaccess,cx.id AS context, gc.average, ue.courses, c.completed, ROUND(((c.completed/ue.courses)*100), 0) as progress
           FROM {user} u
                LEFT JOIN (SELECT g.userid, $grade_avg AS average FROM {grade_items} gi, {grade_grades} g WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL GROUP BY g.userid) AS gc ON gc.userid = u.id
                LEFT JOIN {context} cx ON cx.instanceid = u.id AND contextlevel = 30
                LEFT JOIN (SELECT ra.userid, COUNT(DISTINCT ctx.instanceid) AS courses FROM {role_assignments} ra, {context} ctx WHERE ctx.id = ra.contextid AND ctx.contextlevel = 50 $sql1 GROUP BY ra.userid) as ue ON ue.userid = u.id
                LEFT JOIN (SELECT userid, COUNT(id) AS completed FROM {course_completions} WHERE timecompleted > 0 GROUP BY userid) AS c ON c.userid = u.id
           WHERE u.deleted = 0 $filter", $this->params);
    }
    public function get_learner_courses($params){
        global $DB;

        $this->params['userid'] = $params->userid;

        return $DB->get_records_sql("
            SELECT c.id, c.fullname
            FROM {user_enrolments} AS ue
                LEFT JOIN {enrol} AS e ON e.id = ue.enrolid
                LEFT JOIN {course} AS c ON c.id = e.courseid
            WHERE ue.userid = :userid
            GROUP BY c.id
            ORDER BY c.fullname ASC", $this->params);

    }
    public function get_course($params){
        global $DB;

        $filter = $this->get_filter_in_sql($params->courseid, 'c.id', false);
        $sql1 = $this->get_filter_in_sql($params->learner_roles, "ra.roleid");
        $grade_avg = intelliboard_grade_sql(true, $params);
        $sqlfiltermodule = $this->get_filter_module_sql($params, "");

        $course = $DB->get_record_sql("
            SELECT c.id,
                c.fullname,
                c.timecreated,
                c.enablecompletion,
                c.format,
                c.startdate,
                ca.name AS category,
                l.learners,
                cc.completed,
                gc.grade,
                gr.grades,
                cm.modules,
                s.sections,
                lit.timespend,
                lit.visits,
                lit2.timespend AS timespend_modules,
                lit2.visits AS visits_modules
            FROM {course} c
                LEFT JOIN {course_categories} ca ON ca.id = c.category
                LEFT JOIN (SELECT course, COUNT(id) AS modules FROM {course_modules} WHERE deletioninprogress = 0 {$sqlfiltermodule} GROUP BY course) cm ON cm.course = c.id
                LEFT JOIN (SELECT gi.courseid, COUNT(g.id) AS grades FROM {grade_items} gi, {grade_grades} g WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL GROUP BY gi.courseid) AS gr ON gr.courseid = c.id
                LEFT JOIN (SELECT course, COUNT(*) AS sections FROM {course_sections} WHERE visible = 1 GROUP BY course) AS s ON s.course = c.id
                LEFT JOIN (SELECT gi.courseid, $grade_avg AS grade
                            FROM {grade_items} gi, {grade_grades} g
                            WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL
                            GROUP BY gi.courseid) AS gc ON gc.courseid = c.id
                LEFT JOIN (SELECT ctx.instanceid, COUNT(DISTINCT ra.userid) as learners FROM {role_assignments} ra, {context} ctx WHERE ctx.id = ra.contextid AND ctx.contextlevel = 50 $sql1 GROUP BY ctx.instanceid) AS l ON l.instanceid = c.id
                LEFT JOIN (SELECT course, COUNT(DISTINCT userid) AS completed FROM {course_completions} WHERE timecompleted > 0 GROUP BY course) AS cc ON cc.course = c.id
                LEFT JOIN (SELECT courseid, SUM(timespend) AS timespend, SUM(visits) AS visits FROM {local_intelliboard_tracking} GROUP BY courseid) AS lit ON lit.courseid = c.id
                LEFT JOIN (SELECT courseid, SUM(timespend) AS timespend, SUM(visits) AS visits FROM {local_intelliboard_tracking} WHERE page = 'module' GROUP BY courseid) AS lit2 ON lit2.courseid = c.id
                WHERE $filter", $this->params);

        if($course->id){
            $filter = $this->get_filter_in_sql($params->learner_roles, 'roleid', false, false);
            $this->params['courseid1'] = $course->id;
            $this->params['courseid2'] = $course->id;

            $course->avg = $DB->get_record_sql("SELECT a.timespend_site, a.visits_site, c.grade_site
                                                FROM
                                                    (SELECT ROUND(AVG(b.timespend_site),0) AS timespend_site,
                                                            ROUND(AVG(b.visits_site),0) AS visits_site
                                                        FROM (SELECT SUM(timespend) AS timespend_site, SUM(visits) AS visits_site
                                                            FROM {local_intelliboard_tracking}
                                                            WHERE userid NOT IN (SELECT DISTINCT userid FROM {role_assignments} WHERE $filter) AND courseid != :courseid1
                                                            GROUP BY courseid) AS b) a,
                                                    (SELECT round(AVG(b.grade),0) AS grade_site FROM (SELECT $grade_avg AS grade
                                                        FROM {grade_items} gi, {grade_grades} g
                                                        WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL AND gi.courseid != :courseid2 GROUP BY gi.courseid) b) c
                                               ", $this->params);

            $sqlfiltermodule1 = $this->get_filter_module_sql($params, "cm.");
            $course->mods = $DB->get_records_sql(
                "SELECT MAX(m.id) as id, MAX(m.name) as name, COUNT( cm.id ) AS size
                   FROM {course_modules} cm, {modules} m
                  WHERE m.id = cm.module AND cm.course = :course AND deletioninprogress = 0 {$sqlfiltermodule1}
               GROUP BY cm.module",
               ['course' => $course->id]
            );


            $filter1 = $this->get_filter_in_sql($params->teacher_roles, 'ra.roleid');
            $filter1 .= $this->get_filter_in_sql($params->courseid, 'c.id');

            $course->teachers = $DB->get_records_sql("SELECT DISTINCT(u.id), CONCAT(u.firstname, ' ', u.lastname) AS name, u.email, cx.id AS context
                                                      FROM {user} u
                                                        LEFT JOIN {context} cx ON cx.instanceid = u.id AND contextlevel = 30
                                                        LEFT JOIN {role_assignments} AS ra ON u.id = ra.userid
                                                        LEFT JOIN {context} ctx ON ra.contextid = ctx.id
                                                        LEFT JOIN {course} c ON c.id = ctx.instanceid
                                                      WHERE ctx.instanceid = c.id $filter1

                                                     ", $this->params);
        }
        return $course;
    }

    public function monitor27($params){
        global $DB;

        $ext = 2592000; //by month

        $this->params['timestart'] = $params->timestart;
        $this->params['timefinish'] = ($params->timefinish) ? $params->timefinish : time();

        $sql = $this->get_teacher_sql($params, ["u.id" => "users"]);
        if($params->cohortid){
            $sql_join = $this->get_filter_in_sql($params->cohortid, "cohortid");
            $sql .= " AND u.id IN (SELECT userid FROM {cohort_members} WHERE id > 0 $sql_join)";
        }

        return $DB->get_records_sql("
            SELECT FLOOR(u.timecreated / $ext) * $ext AS timepoint, COUNT(u.id) AS users
            FROM {user} u
            WHERE FLOOR(u.timecreated / $ext) * $ext BETWEEN :timestart AND :timefinish $sql
            GROUP BY timepoint", $this->params);
    }
    public function monitor28($params){
        global $DB;

        $ext = 86400;
        $this->params['timestart'] = $params->timestart;
        $this->params['timefinish'] = ($params->timefinish) ? $params->timefinish : time();


        if ($params->custom2) {
            $sql = $this->get_filter_in_sql($params->custom2, 'id');
            $result = $DB->get_records_sql("SELECT data FROM {user_info_data} WHERE data != '' $sql", $this->params);

            $params->custom2 = array();
            foreach ($result as $item){
                $params->custom2[] = $item->data;
            }
        }

        $sql = $this->get_teacher_sql($params, ["u.id" => "users"]);
        $sql .= $this->get_filter_in_sql($params->custom, "f.id");
        $sql .= $this->get_filter_in_sql($params->custom2, "d.data");

        return $DB->get_records_sql("
            SELECT FLOOR(l.timecreated / $ext) * $ext AS timepointval, COUNT(DISTINCT l.id) AS logins
            FROM {logstore_standard_log} AS l
                JOIN {user} u ON l.userid = u.id
                JOIN {user_info_data} d ON u.id = d.userid
                JOIN {user_info_field} f ON d.fieldid = f.id
            WHERE l.action LIKE '%loggedin%' AND floor(l.timecreated / $ext) * $ext BETWEEN :timestart AND :timefinish $sql
            GROUP BY timepointval", $this->params);
    }
    public function monitor29($params){
        global $DB;

        $ext = 2592000; //by month
        $this->params['timestart'] = $params->timestart;
        $this->params['timefinish'] = ($params->timefinish) ? $params->timefinish : time();


        if ($params->custom2) {
            $sql = $this->get_filter_in_sql($params->custom2, 'id');
            $result = $DB->get_records_sql("SELECT data FROM {user_info_data} WHERE data != '' $sql", $this->params);

            $params->custom2 = array();
            foreach ($result as $item){
                $params->custom2[] = $item->data;
            }
        }
        $sql = $this->get_teacher_sql($params, ["u.id" => "users"]);
        $sql .= $this->get_filter_in_sql($params->custom, "f.id");
        $sql .= $this->get_filter_in_sql($params->custom2, "d.data");

        return $DB->get_records_sql("
            SELECT FLOOR(l.timecreated / $ext) * $ext AS timepointval, COUNT(DISTINCT u.id) as users
            FROM {user} AS u
                JOIN {user_info_data} d ON u.id = d.userid
                JOIN {user_info_field} f ON d.fieldid = f.id
                JOIN {logstore_standard_log} l on u.id = l.userid
            WHERE u.firstaccess <> 0 AND floor(l.timecreated / $ext) * $ext BETWEEN :timestart AND :timefinish $sql
            GROUP BY timepointval", $this->params);
    }
    public function monitor30($params){
        global $DB;

        $ext = 2592000; //by month
        $this->params['timestart'] = strtotime('-4 month');
        $this->params['timefinish'] = time();

        if ($params->custom) {
            $sql = $this->get_filter_in_sql($params->custom, 'id');
            $result = $DB->get_records_sql("SELECT enrol FROM {enrol} WHERE enrol != '' $sql", $this->params);

            $params->custom = array();
            foreach ($result as $item){
                $params->custom[] = $item->enrol;
            }
        }
        $sql_user = $this->get_teacher_sql($params,  ["ue.userid" => "users"]);
        $sql = $this->get_filter_in_sql($params->custom, "en.enrol");


        $sql_join = "";
        if ($params->custom2) {
            if($totara =  $DB->get_record("config", array('name'=>'totara_build'))){
                $sql_join = "JOIN {prog_courseset_course} d ON d.courseid = c.id
                JOIN {prog_courseset} courseset ON courseset.id = d.coursesetid";
            } else {
                return null;
            }
        }

        return $DB->get_records_sql("
            SELECT (max(ue.timecreated) / c.id) as id, c.id as courseid, c.fullname, FLOOR(ue.timecreated / $ext) * $ext AS timepointval, COUNT(DISTINCT ue.id) AS enrolled
            FROM {course} c
                $sql_join
                JOIN {enrol} en ON en.courseid = c.id $sql
                JOIN {user_enrolments} ue ON ue.enrolid = en.id
            WHERE floor(ue.timecreated / $ext) * $ext BETWEEN :timestart AND :timefinish $sql_user
            GROUP BY c.id, timepointval", $this->params);
    }
    public function monitor31($params){
        global $DB;

        $ext = 2592000; //by month

        if (!$params->custom or !$params->custom2) {
            return null;
        }

        if ($params->custom2) {
            $sql = $this->get_filter_in_sql($params->custom2, 'id');
            $result = $DB->get_records_sql("SELECT data FROM {user_info_data} WHERE data != '' $sql", $this->params);

            $params->custom2 = array();
            foreach ($result as $item){
                $params->custom2[] = $item->data;
            }
        }

        $sql = $this->get_teacher_sql($params, ["u.id" => "users"]);
        $sql .= $this->get_filter_in_sql($params->custom, "f.id");
        $sql .= $this->get_filter_in_sql($params->custom2, "d.data");

        return $DB->get_records_sql("
            SELECT (max(u.timecreated) / u.id) as id, FLOOR(u.timecreated / $ext) * $ext AS timepointval, d.data, COUNT(DISTINCT u.id) users
            FROM {user} u
                JOIN {user_info_data} AS d ON u.id = d.userid
                JOIN {user_info_field} AS f ON d.fieldid = f.id
            WHERE d.data <> 'null' AND d.data <> '' $sql
            GROUP BY timepointval, d.data", $this->params);
    }
    public function monitor32($params) {
        global $DB;

        $sql_filter  = $this->get_filterdate_sql($params, "bm.starttime");
        $sql_filter .= $this->get_teacher_sql($params, ["c.id" => "courses"]);
        $sql_filter .= $this->get_filter_course_sql($params, "c.");

        $sql = "SELECT bm.*, COUNT(bm.id) AS numberofparticipants
                  FROM {local_intelliboard_bbb_meet} AS bm
            INNER JOIN {course} AS c ON c.id = bm.courseid
             LEFT JOIN {local_intelliboard_bbb_atten} AS bat ON bat.localmeetingid = bm.id
                 WHERE bm.id > 0 {$sql_filter}
              GROUP BY bm.id";

        return $DB->get_records_sql($sql, $this->params);
    }
    public function monitor33($params) {
        global $DB;

        $sql_filter  = $this->get_filterdate_sql($params, "m.starttime");
        $sql_filter .= $this->get_teacher_sql($params, ["c.id" => "courses"]);
        $sql_filter .= $this->get_filter_course_sql($params, "c.");

        $sql = "SELECT m.id,
                       m.meetingname,
                       m.courseid,
                       SUM(CASE
                                WHEN ba.islisteningonly = 'false' THEN 1 ELSE 0
                       END) AS activeusers,
                       SUM(CASE
                               WHEN ba.islisteningonly = 'true' THEN 1 ELSE 0
                       END) AS passiveusers,
                       (SELECT count(u.id)
                          FROM {user} AS u
                          JOIN {user_enrolments} ej1_ue ON ej1_ue.userid = u.id
                          JOIN {enrol} ej1_e ON (ej1_e.id = ej1_ue.enrolid)
                         WHERE u.deleted = 0 AND ej1_e.courseid = m.courseid
                       ) AS totalusers
                  FROM {local_intelliboard_bbb_meet} AS m
            INNER JOIN {course} AS c ON c.id = m.courseid
             LEFT JOIN {local_intelliboard_bbb_atten} AS ba ON ba.localmeetingid = m.id
                 WHERE m.id > 0 {$sql_filter}
              GROUP BY m.id, m.meetingname, m.courseid";

        return $DB->get_records_sql($sql, $this->params);
    }
    public function monitor34($params) {
        global $DB;

        $sql_filter  = $this->get_filterdate_sql($params, "m.starttime");
        $sql_filter .= $this->get_teacher_sql($params, ["c.id" => "courses"]);
        $sql_filter .= $this->get_filter_course_sql($params, "c.");

        $result['monitortype'] = 'bbbreport_courses_statistic';

        $sql = "SELECT (SELECT count(id)
                          FROM {local_intelliboard_bbb_meet}
                         WHERE endtime IS NULL)
                       AS activemeetings,
                       (SELECT count(id)
                          FROM {local_intelliboard_bbb_meet}
                         WHERE starttime BETWEEN ? AND ?)
                       AS organizedtoday,
                       (SELECT count(id)
                          FROM {local_intelliboard_bbb_meet}
                         WHERE endtime BETWEEN ? AND ?)
                       AS finishedthisweek";

        $todaystart = strtotime('today');
        $todayend = strtotime('today + 1 day');
        $startofweek = strtotime("this week monday");
        $endofweek = strtotime("this week sunday + 1 day");

        $res = $DB->get_record_sql(
            $sql,
            [$todaystart, $todayend, $startofweek, $endofweek]
        );

        $result['numberofactivemeetings'] = $res->activemeetings;
        $result['organizedtoday'] = $res->organizedtoday;
        $result['finishedthisweek'] = $res->finishedthisweek;

        // get organized meetings filtered by range of time and grouped by course
        $sql = "SELECT c.fullname,
                       c.id,
                       (SELECT count(m.id)
                          FROM {local_intelliboard_bbb_meet} AS m
                         WHERE m.courseid = c.id
                       ) AS numberofmeetings
                  FROM {course} AS c";

        $meetings = $DB->get_records_sql($sql, $this->params);
        $result['numofmeetingsbycourse'] = $meetings;

        return $result;
    }
    public function monitor35($params) {
        global $DB, $CFG;

        $sql_filter  = $this->get_filterdate_sql($params, "ba.arrivaltime");
        $sql_filter .= $this->get_teacher_sql($params, ["c.id" => "courses", "u.id" => "users"]);
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter_on = ($CFG->dbtype == 'pgsql') ? "u.id::text LIKE ba.userid::text" : "u.id = ba.userid";

        $sql = "SELECT ba.id, ba.arrivaltime,
                       (
                       CASE
                           WHEN ba.userid NOT IN (SELECT ba1.userid
                                                    FROM {local_intelliboard_bbb_atten} AS ba1
                                                   WHERE ba1.arrivaltime < ba.arrivaltime
                                                  )
                           THEN 'firsttime'
                           ELSE 'returning'
                       END
                       ) AS visit
                  FROM {local_intelliboard_bbb_atten} AS ba
            INNER JOIN {local_intelliboard_bbb_meet} AS m ON m.id = ba.localmeetingid
            INNER JOIN {course} AS c ON c.id = m.courseid
            INNER JOIN {user} AS u ON $sql_filter_on
                 WHERE ba.id > 0 {$sql_filter}";

        return $DB->get_records_sql($sql, $this->params);
    }
    public function monitor36($params) {
        global $DB;

        $sql_filter  = $this->get_filterdate_sql($params, "m.starttime");
        $sql_filter .= $this->get_teacher_sql($params, ["c.id" => "courses"]);
        $sql_filter .= $this->get_filter_course_sql($params, "c.");

        // meetings info
        $sql = "SELECT m.id AS meetingid,
                       m.meetingname,
                       SUM(
                           CASE WHEN ba.hasjoinedvoice = 'true' THEN 1 ELSE 0 END
                       ) AS participantsspeaking,
                       SUM(
                           CASE WHEN ba.hasvideo = 'true' THEN 1 ELSE 0 END
                       ) AS participantshasvideo,
                       SUM(
                           CASE WHEN ba.islisteningonly = 'true' THEN 1 ELSE 0 END
                       ) AS participantslisteningonly,
                       COUNT(ba.id) AS numberofparticipans,
                       SUM(
                            CASE WHEN ba.islisteningonly <> 'true' THEN 1 ELSE 0 END
                       ) AS numberofactiveparticipants
                  FROM {local_intelliboard_bbb_meet} AS m
            INNER JOIN {course} AS c ON c.id = m.courseid
             LEFT JOIN {local_intelliboard_bbb_atten} AS ba ON ba.localmeetingid = m.id
                 WHERE m.endtime IS NOT NULL{$sql_filter}
              GROUP BY m.id, m.meetingname";
        return $DB->get_records_sql($sql, $this->params);
    }
    public function monitor37($params) {
        global $DB, $CFG;

        $sql_filter  = $this->get_filterdate_sql($params, "ba.arrivaltime");
        $sql_filter .= $this->get_teacher_sql($params, ["c.id" => "courses"]);
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");
        $sql_filter_on = ($CFG->dbtype == 'pgsql') ? "u.id::text LIKE ba.userid::text" : "u.id = ba.userid";
        $sql_filter_on2 = ($CFG->dbtype == 'pgsql') ? "it.userid::text LIKE ba.userid::text" : "it.userid = ba.userid";

        $sql = "SELECT it.useros, COUNT(DISTINCT ba.userid) AS amountofuse
                  FROM {local_intelliboard_bbb_atten} AS ba
            INNER JOIN {local_intelliboard_tracking} AS it ON $sql_filter_on2 AND it.page = 'site'
            INNER JOIN {local_intelliboard_bbb_meet} AS m ON m.id = ba.localmeetingid
            INNER JOIN {course} AS c ON c.id = m.courseid
            INNER JOIN {user} AS u ON $sql_filter_on
                 WHERE ba.id > 0{$sql_filter}
              GROUP BY it.useros";

        return $DB->get_records_sql($sql, $this->params);
    }
    public function monitor38($params) {
        global $DB, $CFG;

        $sql_filter  = $this->get_filterdate_sql($params, "ba.arrivaltime");
        $sql_filter .= $this->get_teacher_sql($params, ["c.id" => "courses"]);
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter_on = ($CFG->dbtype == 'pgsql') ? "ba.userid::text LIKE gm.userid::text" : "ba.userid = gm.userid";

        $sql = "SELECT g.id,
                       g.name,
                       CASE WHEN t.speaking IS NULL THEN 0 ELSE t.speaking END AS speaking,
                       CASE WHEN t.hasvideo IS NULL THEN 0 ELSE t.hasvideo END AS hasvideo,
                       CASE WHEN t.listeningonly IS NULL THEN 0 ELSE t.listeningonly END AS listeningonly,
                       CASE WHEN t.presenting IS NULL THEN 0 ELSE t.presenting END AS presenting,
                       CASE WHEN t.totalusers IS NULL THEN 0 ELSE t.totalusers END AS totalusers
                  FROM {groups} AS g
             LEFT JOIN
                       (SELECT gm.groupid,
                               SUM(CASE WHEN ba.islisteningonly = 'true' THEN 1 ELSE 0 END) as listeningonly,
                               SUM(CASE WHEN ba.ispresenter = 'true' THEN 1 ELSE 0 END) as presenting,
                               SUM(CASE WHEN ba.hasjoinedvoice = 'true' THEN 1 ELSE 0 END) as speaking,
                               SUM(CASE WHEN ba.hasvideo = 'true' THEN 1 ELSE 0 END) as hasvideo,
                               COUNT(ba.id) AS totalusers
                          FROM {local_intelliboard_bbb_atten} AS ba
                    INNER JOIN {local_intelliboard_bbb_meet} AS m ON m.id = ba.localmeetingid
                    INNER JOIN {course} AS c ON c.id = m.courseid
                    INNER JOIN {groups_members} AS gm ON $sql_filter_on
                      GROUP BY gm.groupid
                       ) AS t ON g.id = t.groupid";

        return $DB->get_records_sql($sql, $this->params);
    }
    public function monitor39($params) {
        global $DB, $CFG;

        $sql_filter  = $this->get_filterdate_sql($params, "ba.arrivaltime");
        $sql_filter .= $this->get_teacher_sql($params, ["c.id" => "courses", "u.id" => "users"]);
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_user_sql($params, "u.");

        $sql_filter_on = ($CFG->dbtype == 'pgsql') ? "u.id::text LIKE ba.userid::text" : "u.id = ba.userid";
        $sql_filter_on2 = ($CFG->dbtype == 'pgsql') ? "it.userid::text LIKE ba.userid::text" : "it.userid = ba.userid";

        $sql = "SELECT it.useros, COUNT(DISTINCT ba.userid) AS amountofuse
                  FROM {local_intelliboard_bbb_atten} AS ba
            INNER JOIN {local_intelliboard_tracking} AS it ON $sql_filter_on2 AND it.page = 'site'
            INNER JOIN {local_intelliboard_bbb_meet} AS m ON m.id = ba.localmeetingid
            INNER JOIN {course} AS c ON c.id = m.courseid
            INNER JOIN {user} AS u ON $sql_filter_on
                 WHERE ba.id > 0{$sql_filter}
              GROUP BY it.useros";

        $osinfo = $DB->get_records_sql($sql, $this->params);

        return [
            'monitortype' => 'bbbreport_os_info',
            'osinfo' => $osinfo,
        ];
    }
    public function monitor53($params) {
        global $DB;

        $sql_filter_user = $this->get_filter_in_sql($params->custom2, "gh.userid");
        $sql_filter = $this->get_filter_in_sql($params->courseid, "gi.courseid");
        $sql_filter .= $this->get_teacher_sql($params, ["gi.courseid" => "courses", "gh.userid" => "users"]);
        $sql_filter .= $this->get_filterdate_sql($params, "gh.timemodified");

        $grade_sql = intelliboard_grade_sql(false, $params,'gh.');
        $grade_percent = intelliboard_grade_sql(false,null, 'gh.',0, 'gi.',true);

        $sql = "SELECT
                  gh.timemodified,
                  $grade_percent AS finalgrade,
                  $grade_sql AS grade_real,
                  gh.rawgrademax
                FROM {grade_items} gi
                  JOIN {grade_grades_history} gh ON gh.itemid=gi.id AND gh.finalgrade IS NOT NULL $sql_filter_user
                WHERE gi.itemtype='course' {$sql_filter}";

        return $DB->get_records_sql($sql, $this->params);
    }

    public function monitor54($params) {
        global $DB;

        $sql_filter = $this->get_filter_enrol_sql($params, "ue.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "e.");
        $sql_filter .= $this->get_teacher_sql($params, ["e.courseid" => "courses", "ue.userid" => "users"]);
        $sql_role_filter = $this->get_filter_in_sql($params->learner_roles, 'ra.roleid');

        $sql = "SELECT SUM(CASE WHEN e.enrolled >  5 THEN 1 ELSE 0 END) AS more_than_five_courses,
                       SUM(CASE WHEN e.enrolled >= 5 THEN 1 ELSE 0 END) AS five_courses,
                       SUM(CASE WHEN e.enrolled >= 4 THEN 1 ELSE 0 END) AS four_courses,
                       SUM(CASE WHEN e.enrolled >= 3 THEN 1 ELSE 0 END) AS three_courses,
                       SUM(CASE WHEN e.enrolled >= 2 THEN 1 ELSE 0 END) AS two_courses,
                       SUM(CASE WHEN e.enrolled >= 1 THEN 1 ELSE 0 END) AS one_courses
                  FROM (SELECT COUNT(DISTINCT e.courseid) AS enrolled
                          FROM {user_enrolments} ue
                          JOIN {enrol} e ON ue.enrolid=e.id
                          JOIN {context} cx ON cx.instanceid = e.courseid AND cx.contextlevel = :contextlevel
                          JOIN {role_assignments} ra ON ra.contextid = cx.id AND ra.userid = ue.userid
                                                        {$sql_role_filter}
                         WHERE ue.id>0 {$sql_filter}
                      GROUP BY ue.userid
                       ) e";

        return $DB->get_record_sql($sql, array_merge($this->params, ['contextlevel' => CONTEXT_COURSE]));
    }

    public function monitor62($params) {
        global $DB;

        $sql_time_filter = $this->get_filterdate_sql($params, "lg1.timecreated");
        $sql_filter = $this->get_teacher_sql($params, ["lg.userid" => "users"]);
        $groupdate = DBHelper::group_by_date_val("daymonth", "lg.timecreated");

        $sql = "SELECT MIN(lg.id) AS id, {$groupdate} AS timepoint, COUNT(*) AS all_login,
                       SUM(CASE WHEN lg.action <> 'loggedin'
                                THEN 1 ELSE 0 END
                       ) AS mobile_app_login
                  FROM (SELECT lg1.*
                          FROM {logstore_standard_log} lg1
                         WHERE lg1.id > 0 {$sql_time_filter}
                       ) lg
                 WHERE lg.action = 'loggedin' OR lg.other LIKE '%\"tool_mobile_get_config\";}' {$sql_filter}
              GROUP BY 2
              ORDER BY MAX(lg.timecreated)";

        return $DB->get_records_sql($sql, $this->params);
    }

    public function monitor69($params) {
        global $DB;

        $sql_filter = $this->get_filter_in_sql($params->courseid, "e.courseid");
        $sql_filter .= $this->get_teacher_sql($params, ["ue.userid" => "users", "e.courseid" => "courses"]);
        $sql_filter .= $this->get_filter_enrol_sql($params, "ue.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "e.");
        $datefilter = $this->get_filterdate_sql($params, "t.enrol_date");
        $datestr = DBHelper::group_by_date_val('year', 't.enrol_date');

        if ($params->custom4) {
            $rolefilter = $this->get_filter_in_sql($params->custom4, "ra.roleid");
        } else {
            $rolefilter = "";
        }

        $sql = "SELECT {$datestr} AS date, COUNT(t.userid) AS enr_num, SUM(t.compl) AS successful_num
                  FROM (SELECT ue.userid, e.courseid,
                               CASE WHEN MIN(ue.timestart) > 0 THEN MIN(ue.timestart) ELSE MIN(ue.timecreated) END AS enrol_date,
                               CASE WHEN MIN(cc.timecompleted) IS NOT NULL AND MIN(cc.timecompleted) > 0 THEN 1 ELSE 0 END as compl
                          FROM {user_enrolments} ue
                          JOIN {enrol} e ON e.id = ue.enrolid
                          JOIN (SELECT ra.userid, cx.instanceid AS course_id
                                  FROM {role_assignments} ra
                                  JOIN {context} cx ON cx.id = ra.contextid AND cx.contextlevel = 50
                                 WHERE ra.id > 0 {$rolefilter}
                              GROUP BY ra.userid, cx.instanceid
                               ) ras ON ras.userid = ue.userid AND ras.course_id = e.courseid
                     LEFT JOIN {course_completions} cc ON cc.course = e.courseid AND cc.userid = ue.userid
                         WHERE ue.id > 0 {$sql_filter}
                      GROUP BY ue.userid, e.courseid
                       ) t
                 WHERE t.userid > 0 {$datefilter}
              GROUP BY 1
              ORDER BY MAX(t.enrol_date)";

        return $DB->get_records_sql($sql, $this->params);
    }

    public function monitor71($params) {
        global $DB;

        if (isset($params->courseid) && !$params->courseid) {
            return [];
        }

        $this->params["courseid"] = $params->courseid;
        $sql_filter = $this->get_teacher_sql($params, ["ue.userid" => "users", "e.courseid" => "courses"]);
        $sql_filter .= $this->get_filter_enrol_sql($params, "ue.");
        $sql_filter .= $this->get_filter_enrol_sql($params, "e.");
        $datefilter = $this->get_filterdate_sql($params, "t.enrol_date");
        $datestr = DBHelper::group_by_date_val('year', 't.enrol_date');
        $rolefilter = $this->get_filter_in_sql($params->learner_roles, "ra.roleid");

        $sql = "SELECT {$datestr} AS cdate,
                       SUM(CASE WHEN t.compl = 1 THEN 1 ELSE 0 END) AS total_success,
                       SUM(CASE WHEN t.compl = 0 THEN 1 ELSE 0 END) AS total_unsuccess,
                       SUM(CASE WHEN (t.compl = 1 AND t.is_qatari = 1) THEN 1 ELSE 0 END) AS qatari_success,
                       SUM(CASE WHEN (t.compl = 0 AND t.is_qatari = 1) THEN 1 ELSE 0 END) AS qatari_unsuccess
                  FROM (SELECT ue.userid, e.courseid,
                               CASE WHEN MIN(ue.timestart) > 0 THEN MIN(ue.timestart) ELSE MIN(ue.timecreated) END AS enrol_date,
                               CASE WHEN MIN(cc.timecompleted) IS NOT NULL AND MIN(cc.timecompleted) > 0 THEN 1 ELSE 0 END AS compl,
                               CASE WHEN (uid.data IS NOT NULL AND uid.data = 'Qatari') THEN 1 ELSE 0 END AS is_qatari
                          FROM {user_enrolments} ue
                          JOIN {enrol} e ON e.id = ue.enrolid AND e.courseid = :courseid
                          JOIN (SELECT ra.userid, cx.instanceid AS course_id
                                  FROM {role_assignments} ra
                                  JOIN {context} cx ON cx.id = ra.contextid AND cx.contextlevel = 50
                                 WHERE ra.id > 0 {$rolefilter}
                              GROUP BY ra.userid, cx.instanceid
                               ) ras ON ras.userid = ue.userid AND ras.course_id = e.courseid
                     LEFT JOIN {course_completions} cc ON cc.course = e.courseid AND cc.userid = ue.userid
                     LEFT JOIN {user_info_field} uif ON uif.shortname = 'Qatari_NonQatari'
                     LEFT JOIN {user_info_data} uid ON uid.fieldid = uif.id AND uid.userid = ue.userid
                         WHERE ue.id > 0 {$sql_filter}
                      GROUP BY ue.userid, e.courseid, uid.data
                       ) t
                 WHERE t.userid > 0 {$datefilter}
              GROUP BY 1
              ORDER BY MAX(t.enrol_date)";

        return $DB->get_records_sql($sql, $this->params);
    }

    public function monitor72($params) {
        global $DB;

        $sql_filter = $this->get_teacher_sql($params, ["ue.userid" => "users", "ue.courseid" => "courses"]);
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filterdate_sql($params, "ue.enrol_date");
        $rolefilter = $this->get_filter_in_sql($params->learner_roles, "ra1.roleid");
        $sqlenrolfilter = $this->get_filter_enrol_sql($params, "ue1.");
        $sqlenrolfilter .= $this->get_filter_enrol_sql($params, "e1.");
        $datestr = DBHelper::group_by_date_val('year', 'ue.enrol_date');

        $sql = "SELECT CONCAT(ccat.id, '_', {$datestr}), {$datestr} AS cdate, ccat.name, ccat.path,
                       SUM(CASE WHEN cco.timecompleted IS NOT NULL AND cco.timecompleted > 0 THEN 1 ELSE 0 END) AS num_success
                  FROM (SELECT cc.id, cc.path, cc.name, cc.depth
                          FROM {course_categories} cc
                         WHERE cc.path LIKE '%/5/%'
                       ) ccat
             LEFT JOIN {course} c ON c.category = ccat.id
             LEFT JOIN (SELECT e1.courseid, ue1.userid AS userid,
                               CASE WHEN MIN(ue1.timestart) > 0 THEN MIN(ue1.timestart) ELSE MIN(ue1.timecreated) END AS enrol_date
                          FROM {enrol} e1
                          JOIN {user_enrolments} ue1 ON  ue1.enrolid = e1.id
                         WHERE e1.id > 0 {$sqlenrolfilter}
                      GROUP BY e1.courseid, ue1.userid
                       ) ue ON c.id = ue.courseid
             LEFT JOIN (SELECT ra1.userid, cx.instanceid AS course_id
                          FROM {role_assignments} ra1
                          JOIN {context} cx ON cx.id = ra1.contextid AND cx.contextlevel = 50
                         WHERE ra1.id > 0 {$rolefilter}
                      GROUP BY ra1.userid, cx.instanceid
                       ) ra ON ra.userid = ue.userid AND ra.course_id = ue.courseid
             LEFT JOIN {course_completions} cco ON cco.course = ra.course_id AND cco.userid = ra.userid
                 WHERE ccat.id > 0 {$sql_filter}
              GROUP BY ccat.id, ccat.name, ccat.path, ccat.depth, 2
              ORDER BY ccat.depth";

        $data = $DB->get_records_sql($sql, $this->params);
        $result = [];

        $firstlevelcategories = $DB->get_records_sql(
            "SELECT cc1.id, cc1.name, cc1.path, 0 AS num_success
               FROM {course_categories} cc
               JOIN {course_categories} cc1 ON cc1.path LIKE '%/5/%' AND cc1.depth = cc.depth + 1
              WHERE cc.id = 5"
        );

        // Create an array where a key is a year and a value is an array of first level categories
        // in relation to the base category
        for($i = strtotime("-10 years"); $i <= strtotime("+10 years"); $i+= YEARSECS) {
            $categories = [];

            foreach ($firstlevelcategories as $category) {
                $categories[] = clone $category;
            }

            $result[date("Y", $i)] = $categories;
        }

        // data grouping. Here we summarize data for the first level categories.
        // If a category has the path /a/b/c, we search for category /a and add a value to that category.
        // A data is also grouped by year.
        foreach ($data as $item) {
            if (!isset($result[$item->cdate])) {
                continue;
            }

            $yearcategories = &$result[$item->cdate];

            foreach ($yearcategories as &$category) {
                if (strpos($item->path, $category->path) !== false) {
                    $category->num_success += $item->num_success;
                    continue 2;
                }
            }
        }

        // delete year if all categories inside have no data
        $result = array_filter($result, function($item) {
            $isnotempty = false;

            foreach ($item as $cat) {
                if ($cat->num_success != 0) {
                    $isnotempty = true;
                    break;
                }
            }

            return $isnotempty;
        });

        return ["data" => $result];
    }

    public function monitor73($params)
    {
        global $DB;

        $sql_filter = $this->get_teacher_sql($params, ["ue.userid" => "users", "ue.courseid" => "courses"]);
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filterdate_sql($params, "ue.enrol_date");
        $rolefilter = $this->get_filter_in_sql($params->learner_roles, "ra1.roleid");
        $sqlenrolfilter = $this->get_filter_enrol_sql($params, "ue1.");
        $sqlenrolfilter .= $this->get_filter_enrol_sql($params, "e1.");
        $datestr = DBHelper::group_by_date_val('year', 'ue.enrol_date');

        $sql = "SELECT {$datestr} AS cdate,
                       SUM(CASE WHEN cco.timecompleted IS NOT NULL AND
                                     cco.timecompleted > 0 AND
                                     uid.data = 'Male'
                                THEN 1 ELSE 0
                            END
                       ) AS num_success_male,
                       SUM(CASE WHEN cco.timecompleted IS NOT NULL AND
                                     cco.timecompleted > 0 AND
                                     uid.data = 'Female'
                                THEN 1 ELSE 0
                            END
                       ) AS num_success_female,
                       SUM(CASE WHEN cco.timecompleted IS NOT NULL AND
                                     cco.timecompleted > 0 AND
                                     ((uid.data <> 'Female' AND uid.data <> 'Male') OR uid.data IS NULL)
                                THEN 1 ELSE 0
                            END
                       ) AS num_success_unknown
                  FROM (SELECT cc.id, cc.path, cc.name, cc.depth
                          FROM {course_categories} cc
                         WHERE cc.path LIKE '%/5/%'
                       ) ccat
                  JOIN {course} c ON c.category = ccat.id
                  JOIN (SELECT e1.courseid, ue1.userid AS userid,
                               CASE WHEN MIN(ue1.timestart) > 0 THEN MIN(ue1.timestart) ELSE MIN(ue1.timecreated) END AS enrol_date
                          FROM {enrol} e1
                          JOIN {user_enrolments} ue1 ON  ue1.enrolid = e1.id
                         WHERE e1.id > 0 {$sqlenrolfilter}
                      GROUP BY e1.courseid, ue1.userid
                       ) ue ON c.id = ue.courseid
                  JOIN (SELECT ra1.userid, cx.instanceid AS course_id
                          FROM {role_assignments} ra1
                          JOIN {context} cx ON cx.id = ra1.contextid AND cx.contextlevel = 50
                         WHERE ra1.id > 0 {$rolefilter}
                      GROUP BY ra1.userid, cx.instanceid
                       ) ra ON ra.userid = ue.userid AND ra.course_id = ue.courseid
                  JOIN {course_completions} cco ON cco.course = ra.course_id AND cco.userid = ra.userid
             LEFT JOIN {user_info_field} uif ON uif.shortname = 'gender'
             LEFT JOIN {user_info_data} uid ON uid.fieldid = uif.id AND uid.userid = ue.userid
                 WHERE ccat.id > 0 {$sql_filter}
              GROUP BY 1
              ORDER BY MIN(ue.enrol_date)";

        return $DB->get_records_sql($sql, $this->params);
    }

    public function monitor74($params)
    {
        global $DB;

        $sql_filter = $this->get_teacher_sql($params, ["ue.userid" => "users", "c.id" => "courses"]);
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filterdate_sql($params, "ue.enrol_date");
        $sql_filter .= $this->get_filter_in_sql($params->courseid, "c.id");
        $rolefilter = $this->get_filter_in_sql($params->learner_roles, "ra1.roleid");
        $sqlenrolfilter = $this->get_filter_enrol_sql($params, "ue1.");
        $sqlenrolfilter .= $this->get_filter_enrol_sql($params, "e1.");
        $datestr = DBHelper::group_by_date_val('year', 'ue.enrol_date');

        $sql = "SELECT {$datestr} AS cdate,
                       SUM(CASE WHEN cco.timecompleted IS NOT NULL AND
                                     cco.timecompleted > 0
                                THEN 1 ELSE 0
                            END
                       ) AS qp_success,
                       COUNT(uid.id) AS total_participants
                  FROM {course} c
                  JOIN (SELECT e1.courseid, ue1.userid AS userid,
                               CASE WHEN MIN(ue1.timestart) > 0 THEN MIN(ue1.timestart) ELSE MIN(ue1.timecreated) END AS enrol_date
                          FROM {enrol} e1
                          JOIN {user_enrolments} ue1 ON  ue1.enrolid = e1.id
                         WHERE e1.id > 0 {$sqlenrolfilter}
                      GROUP BY e1.courseid, ue1.userid
                       ) ue ON c.id = ue.courseid
                  JOIN (SELECT ra1.userid, cx.instanceid AS course_id
                          FROM {role_assignments} ra1
                          JOIN {context} cx ON cx.id = ra1.contextid AND cx.contextlevel = 50
                         WHERE ra1.id > 0 {$rolefilter}
                      GROUP BY ra1.userid, cx.instanceid
                       ) ra ON ra.userid = ue.userid AND ra.course_id = ue.courseid
                  JOIN {user_info_field} uif ON uif.shortname = 'qp_othercompanies'
                  JOIN {user_info_data} uid ON uid.fieldid = uif.id AND uid.userid = ue.userid
                                               AND uid.data = 'Qatar Petroleum'
             LEFT JOIN {course_completions} cco ON cco.course = ra.course_id AND cco.userid = ra.userid
                 WHERE c.id > 0 {$sql_filter}
              GROUP BY 1
              ORDER BY MIN(ue.enrol_date)";

        return $DB->get_records_sql($sql, $this->params);
    }

    public function monitor75($params) {
        global $DB;

        $sql_filter = $this->get_teacher_sql($params, ["ue.userid" => "users", "ue.courseid" => "courses"]);
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filterdate_sql($params, "ue.enrol_date");
        $rolefilter = $this->get_filter_in_sql($params->learner_roles, "ra1.roleid");
        $sqlenrolfilter = $this->get_filter_enrol_sql($params, "ue1.");
        $sqlenrolfilter .= $this->get_filter_enrol_sql($params, "e1.");

        $sql = "SELECT ccat.id, ccat.name, ccat.path,
                       SUM(CASE WHEN cco.timecompleted IS NOT NULL AND
                                     cco.timecompleted > 0 AND
                                     uid.data = 'Qatar Petroleum'
                                THEN 1 ELSE 0 END
                       ) AS success_qp,
                       SUM(CASE WHEN cco.timecompleted IS NOT NULL AND
                                     cco.timecompleted > 0 AND
                                     uid.data = 'Other companies'
                                THEN 1 ELSE 0 END
                       ) AS success_other
                  FROM (SELECT cc.id, cc.path, cc.name, cc.depth
                          FROM {course_categories} cc
                         WHERE cc.path LIKE '%/5/%'
                       ) ccat
                  JOIN {course} c ON c.category = ccat.id
                  JOIN (SELECT e1.courseid, ue1.userid AS userid,
                               CASE WHEN MIN(ue1.timestart) > 0 THEN MIN(ue1.timestart) ELSE MIN(ue1.timecreated) END AS enrol_date
                          FROM {enrol} e1
                          JOIN {user_enrolments} ue1 ON  ue1.enrolid = e1.id
                         WHERE e1.id > 0 {$sqlenrolfilter}
                      GROUP BY e1.courseid, ue1.userid
                       ) ue ON c.id = ue.courseid
                  JOIN (SELECT ra1.userid, cx.instanceid AS course_id
                          FROM {role_assignments} ra1
                          JOIN {context} cx ON cx.id = ra1.contextid AND cx.contextlevel = 50
                         WHERE ra1.id > 0 {$rolefilter}
                      GROUP BY ra1.userid, cx.instanceid
                       ) ra ON ra.userid = ue.userid AND ra.course_id = ue.courseid
                  JOIN {user_info_field} uif ON uif.shortname = 'qp_othercompanies'
                  JOIN {user_info_data} uid ON uid.fieldid = uif.id AND uid.userid = ue.userid
             LEFT JOIN {course_completions} cco ON cco.course = ra.course_id AND cco.userid = ra.userid
                 WHERE ccat.id > 0 {$sql_filter}
              GROUP BY ccat.id, ccat.name, ccat.path, ccat.depth
              ORDER BY ccat.depth";

        $data = $DB->get_records_sql($sql, $this->params);

        $firstlevelcategories = $DB->get_records_sql(
            "SELECT cc1.id, cc1.name, cc1.path, 0 AS success_qp, 0 AS success_other
               FROM {course_categories} cc
               JOIN {course_categories} cc1 ON cc1.path LIKE '%/5/%' AND cc1.depth = cc.depth + 1
              WHERE cc.id = 5"
        );

        foreach ($data as $row) {
            foreach ($firstlevelcategories as &$category) {
                if (strpos($row->path, $category->path) !== false) {
                    $category->success_qp += $row->success_qp;
                    $category->success_other += $row->success_other;
                    break;
                }
            }
        }

        return $firstlevelcategories;
    }

    public function monitor76($params) {
        global $DB;

        $coursefilter = $this->get_filter_course_sql($params, "c.");
        $sqlfilter = $this->get_teacher_sql($params, ["ue1.userid" => "users", "e1.courseid" => "courses"]);
        $sqlfilter .= $this->get_filterdate_sql($params, "ue1.enrol_date");
        $sqlfilter .= $this->get_filter_enrol_sql($params, "ue1.");
        $sqlfilter .= $this->get_filter_enrol_sql($params, "e1.");
        $rolefilter = $this->get_filter_in_sql($params->learner_roles, "ra1.roleid");
        $datestr = DBHelper::group_by_date_val(
            'year', 'CASE WHEN ue1.timestart > 0 THEN ue1.timestart ELSE ue1.timecreated END'
        );

        $sql = "SELECT CONCAT(ccat.id, '_', ce.cdate), ce.cdate, ccat.name, ccat.path,
                       AVG(ce.number_enroll) AS avg_enr_num
                  FROM (SELECT cc.id, cc.path, cc.name, cc.depth
                          FROM {course_categories} cc
                         WHERE cc.path LIKE '%/5/%'
                       ) ccat
             LEFT JOIN {course} c ON c.category = ccat.id
             LEFT JOIN (SELECT e1.courseid,
                               {$datestr} AS cdate,
                               COUNT(ra.userid) AS number_enroll
                          FROM {enrol} e1
                          JOIN {user_enrolments} ue1 ON  ue1.enrolid = e1.id
                          JOIN (SELECT ra1.userid, cx.instanceid AS course_id
                                  FROM {role_assignments} ra1
                                  JOIN {context} cx ON cx.id = ra1.contextid AND cx.contextlevel = 50
                                 WHERE ra1.id > 0 {$rolefilter}
                              GROUP BY ra1.userid, cx.instanceid
                               ) ra ON ra.userid = ue1.userid AND ra.course_id = e1.courseid
                         WHERE e1.id > 0 {$sqlfilter}
                      GROUP BY e1.courseid, 2
                       ) ce ON c.id = ce.courseid
                 WHERE ccat.id > 0 {$coursefilter}
              GROUP BY ccat.id, ccat.name, ccat.path, ccat.depth, 2
              ORDER BY ccat.depth";

        $data = $DB->get_records_sql($sql, $this->params);
        $result = [];

        $firstlevelcategories = $DB->get_records_sql(
            "SELECT cc1.id, cc1.name, cc1.path, 0 AS avg_enr_num
               FROM {course_categories} cc
               JOIN {course_categories} cc1 ON cc1.path LIKE '%/5/%' AND cc1.depth = cc.depth + 1
              WHERE cc.id = 5"
        );

        // Create an array where a key is a year and a value is an array of first level categories
        for($i = strtotime("-10 years"); $i <= strtotime("+10 years"); $i+= YEARSECS) {
            $categories = [];

            foreach ($firstlevelcategories as $category) {
                $firstlvlcat = clone $category;
                $firstlvlcat->data = [];
                $categories[] = $firstlvlcat;
            }

            $result[date("Y", $i)] = $categories;
        }

        // data grouping. Here we fill field "data" for the first level categories.
        // If a category has the path /a/b/c, we search for category /a and add a value to that category to field "data".
        // A data is also grouped by year.
        foreach ($data as $item) {
            // skip if we do not have year for the row
            if (!isset($result[$item->cdate])) {
                continue;
            }

            $yearcategories = &$result[$item->cdate];

            foreach ($yearcategories as &$category) {
                if (strpos($item->path, $category->path) !== false) {
                    $category->data[] = $item->avg_enr_num;
                    continue 2;
                }
            }
        }

        // count avg value for first level categories by year
        foreach($result as $year) {
            foreach ($year as &$category) {
                $category->avg_enr_num = !count($category->data) ? 0 : array_sum($category->data) / count($category->data);
                unset($category->data);
            }
        }

        // delete year if all categories inside have no data
        $result = array_filter($result, function($item) {
            $isnotempty = false;

            foreach ($item as $cat) {
                if ($cat->avg_enr_num != 0) {
                    $isnotempty = true;
                    break;
                }
            }

            return $isnotempty;
        });

        return ["data" => $result];
    }

    public function monitor77($params) {
        global $DB;

        $sql_filter = $this->get_teacher_sql($params, ["ue.userid" => "users", "ue.courseid" => "courses"]);
        $sql_filter .= $this->get_filter_course_sql($params, "c.");
        $rolefilter = $this->get_filter_in_sql($params->learner_roles, "ra1.roleid");
        $sqlenrolfilter = $this->get_filter_enrol_sql($params, "ue1.");
        $sqlenrolfilter .= $this->get_filter_enrol_sql($params, "e1.");

        $sql = "SELECT uid.data AS nationality, COUNT(ue.userid) AS numb_stud
                  FROM (SELECT cc.id, cc.path, cc.name, cc.depth
                          FROM {course_categories} cc
                         WHERE cc.path LIKE '%/5/%'
                       ) ccat
                  JOIN {course} c ON c.category = ccat.id
                  JOIN (SELECT e1.courseid, ue1.userid AS userid
                          FROM {enrol} e1
                          JOIN {user_enrolments} ue1 ON  ue1.enrolid = e1.id
                         WHERE e1.id > 0 {$sqlenrolfilter}
                      GROUP BY e1.courseid, ue1.userid
                       ) ue ON c.id = ue.courseid
                  JOIN (SELECT ra1.userid, cx.instanceid AS course_id
                          FROM {role_assignments} ra1
                          JOIN {context} cx ON cx.id = ra1.contextid AND cx.contextlevel = 50
                         WHERE ra1.id > 0 {$rolefilter}
                      GROUP BY ra1.userid, cx.instanceid
                       ) ra ON ra.userid = ue.userid AND ra.course_id = ue.courseid
                  JOIN {user_info_field} uif ON uif.shortname = 'nationality'
                  JOIN {user_info_data} uid ON uid.fieldid = uif.id AND uid.userid = ue.userid
                 WHERE c.id > 0 {$sql_filter}
              GROUP BY uid.data
              ORDER BY 2 DESC";

        return ['data' => $DB->get_records_sql($sql, $this->params)];
    }

    public function monitor78($params) {
        global $DB;

        $sqlfilter = $this->get_teacher_sql($params, ["ue.userid" => "users", "ue.courseid" => "courses"]);
        $sqlfilter .= $this->get_filter_course_sql($params, "c.");
        $sqlfilter .= $this->get_filterdate_sql($params, "ue.enrol_date");
        $rolefilter = $this->get_filter_in_sql($params->learner_roles, "ra1.roleid");
        $sqlenrolfilter = $this->get_filter_enrol_sql($params, "ue1.");
        $sqlenrolfilter .= $this->get_filter_enrol_sql($params, "e1.");

        $sql = "SELECT uid.data AS company, COUNT(ue.userid) AS numb_stud
                  FROM (SELECT cc.id, cc.path, cc.name, cc.depth
                          FROM {course_categories} cc
                         WHERE cc.path LIKE '%/5/%'
                       ) ccat
                  JOIN {course} c ON c.category = ccat.id
                  JOIN (SELECT e1.courseid, ue1.userid AS userid,
                               CASE WHEN MIN(ue1.timestart) > 0 THEN MIN(ue1.timestart) ELSE MIN(ue1.timecreated) END AS enrol_date
                          FROM {enrol} e1
                          JOIN {user_enrolments} ue1 ON  ue1.enrolid = e1.id
                         WHERE e1.id > 0 {$sqlenrolfilter}
                      GROUP BY e1.courseid, ue1.userid
                       ) ue ON c.id = ue.courseid
                  JOIN (SELECT ra1.userid, cx.instanceid AS course_id
                          FROM {role_assignments} ra1
                          JOIN {context} cx ON cx.id = ra1.contextid AND cx.contextlevel = 50
                         WHERE ra1.id > 0 {$rolefilter}
                      GROUP BY ra1.userid, cx.instanceid
                       ) ra ON ra.userid = ue.userid AND ra.course_id = ue.courseid
                  JOIN {user_info_field} uif ON uif.shortname = 'Company'
                  JOIN {user_info_data} uid ON uid.fieldid = uif.id AND uid.userid = ue.userid
                 WHERE c.id > 0 {$sqlfilter}
              GROUP BY uid.data
              ORDER BY 2 DESC";

        return ['data' => $DB->get_records_sql($sql, $this->params)];
    }

    public function get_userinfo($params)
    {
        global $DB;

        $this->params['userid'] = clean_param($params->filter, PARAM_INT);
        return $DB->get_record_sql("
            SELECT u.*, cx.id AS context
            FROM {user} u
                LEFT JOIN {context} cx ON cx.instanceid = u.id AND contextlevel = 30
            WHERE u.id = :userid", $this->params);
    }
    public function get_user_info_fields_data($params)
    {
        global $DB;

        $sql = $this->get_filter_in_sql($params->filter, 'fieldid');
        $sql .= $this->get_filter_in_sql($params->custom, 'userid');

        if ($params->custom2) {
            $items = explode(',', $params->custom2);

            for ($i = 0; $i < count($items); $i++) {
                $rawitem = explode('|', $items[$i]);

                if (count($rawitem) === 2) {
                    $sql .= " AND (fieldid <> :fieldid{$i} OR data <> :dataval{$i})";
                    $this->params["fieldid{$i}"] = $rawitem[0];
                    $this->params["dataval{$i}"] = $rawitem[1];
                }
            }
        }

        return $DB->get_records_sql(
            "SELECT MAX(id) AS id, MAX(fieldid) AS fieldid, data, COUNT(id) AS items
               FROM {user_info_data}
              WHERE data != '' $sql
           GROUP BY data
           ORDER BY data ASC",
            $this->params
        );
    }
    public function get_user_info_fields($params)
    {
        global $DB;

        return $DB->get_records_sql("
            SELECT uif.id, uif.name, uif.datatype AS type, uif.shortname, uic.name AS category
            FROM {user_info_field} uif, {user_info_category} uic
            WHERE uif.categoryid = uic.id
            ORDER BY uif.name");
    }
    public function get_site_avg($params)
    {
        global $DB;

        $sql_enabled = $this->get_filter_in_sql($params->learner_roles,'roleid',false);
        $grade_avg = intelliboard_grade_sql(true, $params);

        return $DB->get_record_sql("
            SELECT a.timespend_site, a.visits_site, c.grade_site
            FROM
                (SELECT ROUND(AVG(b.timespend_site),0) as timespend_site, ROUND(AVG(b.visits_site),0) as visits_site
                    FROM (SELECT SUM(timespend) as timespend_site, SUM(visits) as visits_site
                        FROM {local_intelliboard_tracking}
                        WHERE userid NOT IN (SELECT DISTINCT userid FROM {role_assignments} WHERE $sql_enabled) AND userid != 2 GROUP BY userid) AS b) a,
                (SELECT round(AVG(b.grade),0) AS grade_site FROM (SELECT $grade_avg AS grade
                    FROM {grade_items} gi, {grade_grades} g
            WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL GROUP BY g.userid) b) c", $this->params);
    }
    public function get_countries($params)
    {
        global $DB;

        $sql = $this->get_teacher_sql($params, ["id" => "users"]);
        $sql .= $this->get_filter_user_sql($params, "");
        $sql .= $this->get_filterdate_sql($params, "timecreated");

        return $DB->get_records_sql("SELECT MAX(id) AS id, country, count(*) AS users
            FROM {user}
            WHERE country <> '' $sql
            GROUP BY country", $this->params);
    }

    public function get_enrols($params)
    {
        global $DB;

        $sql = $this->get_teacher_sql($params, ["ue.userid" => "users", "e.courseid" => "courses"]);
        $sql .= $this->get_filter_enrol_sql($params, "ue.");
        $sql .= $this->get_filter_enrol_sql($params, "e.");
        $sql .= $this->get_filterdate_sql($params, "ue.timecreated");

        return $DB->get_records_sql("SELECT MAX(e.id) AS id, e.enrol, COUNT(ue.id) AS enrols
            FROM {user_enrolments} ue, {enrol} e
            WHERE e.id = ue.enrolid $sql
            GROUP BY e.enrol", $this->params);
    }
    protected function time_ext($params) {
        $datediff = $params->timefinish - $params->timestart;
        $days = floor($datediff/(60*60*24)) + 1;

        if ($days <= 45) {
            $ext = 86400; //by day
        } elseif ($days <= 90){
            $ext = 604800; //by week
        } elseif ($days <= 730) {
            $ext = 2592000; //by month
        } else {
            $ext = 31556926; //by year
        }
        return $ext;
    }
    public function get_site_activity($params)
    {
        global $DB, $CFG;

        $sql = $this->get_teacher_sql($params, ["userid" => "users"]);
        $sql2 = $this->get_teacher_sql($params, ["userid" => "users"]);
        $sql3 = $this->get_teacher_sql($params, ["t.userid" => "users", "t.courseid" => "courses"]);
        $ext = $this->time_ext($params);
        $this->params['timestart'] = $params->timestart;
        $this->params['timefinish'] = $params->timefinish;
        $data = new stdClass();

        if ($CFG->dbtype == 'mysqli' && !$params->sizemode) {
            $sql_cache = ' SQL_NO_CACHE ';
        } else {
            $sql_cache = '';
        }

        if ($params->externalid) {
            $data->sessions = $DB->get_records_sql("
                SELECT $sql_cache floor(l.timepoint / $ext) * $ext AS timepointval, COUNT(DISTINCT t.userid) AS pointval
                FROM {local_intelliboard_logs} l, {local_intelliboard_tracking} t
                WHERE l.trackid = t.id AND l.timepoint BETWEEN :timestart AND :timefinish $sql3
                GROUP BY timepointval", $this->params);
        } else {
            $data->sessions = $DB->get_records_sql("
                SELECT $sql_cache floor(timepoint / $ext) * $ext AS timepointval, SUM(sessions) AS pointval
                FROM {local_intelliboard_totals}
                WHERE timepoint BETWEEN :timestart AND :timefinish
                GROUP BY timepointval", $this->params);
        }
        $data->enrolments = $DB->get_records_sql("
            SELECT $sql_cache floor(timecreated / $ext) * $ext AS timepointval, COUNT(DISTINCT (userid)) AS pointval
            FROM {user_enrolments}
            WHERE timecreated BETWEEN :timestart AND :timefinish $sql
            GROUP BY timepointval", $this->params);

        $data->completions = $DB->get_records_sql("
            SELECT $sql_cache floor(timecompleted / $ext) * $ext AS timepointval, COUNT(DISTINCT (userid)) AS pointval
            FROM {course_completions}
            WHERE timecompleted BETWEEN :timestart AND :timefinish $sql2
            GROUP BY timepointval", $this->params);

        return $data;
    }

    public function get_intellicart_vendors($params)
    {
        global $DB, $CFG;
        require_once($CFG->libdir . "/adminlib.php");

        if (get_component_version('local_intellicart')) {
        return $DB->get_records("local_intellicart_vendors");
        }
        return [];
    }

    public function get_assign_users($params)
    {
        global $DB, $CFG;

        $type = "";
        if ($CFG->dbtype == 'pgsql') {
          $type = "::text";
        }

        $sql_filter = $this->get_filter_user_sql($params, "");
        $sql_filter .= $this->get_filter_in_sql($params->custom, "id");

        $opr = ($params->custom2) ? "" :"NOT";
        $this->params['userid'] = (int) $params->userid;
        $sql_filter .= " AND id{$type} $opr IN(SELECT instance FROM {local_intelliboard_assign} WHERE userid = :userid AND rel = 'external' AND type='users')";
        $sql_arr = array();
        if ($params->filter) {
            $sql_arr[] = $DB->sql_like('firstname', ":firstname", false, false);
            $sql_arr[] = $DB->sql_like('lastname', ":lastname", false, false);
            $sql_arr[] = $DB->sql_like('username', ":username", false, false);
            $sql_arr[] = $DB->sql_like('email', ":email", false, false);
            $this->params['firstname'] = "%$params->filter%";
            $this->params['lastname'] = "%$params->filter%";
            $this->params['username'] = "%$params->filter%";
            $this->params['email'] = "%$params->filter%";
            $sql_filter .= " AND (".implode(" OR ", $sql_arr) .")";
        }

        $count = $DB->count_records_sql("SELECT COUNT(*) FROM {user} WHERE id > 0 $sql_filter", $this->params);

        if ($count > 300) {
            return array('length' => $count);
        } else {
            return $DB->get_records_sql("SELECT id, firstname, lastname, email FROM {user} WHERE id > 0 $sql_filter", $this->params);
        }
    }
    public function get_assign_courses($params)
    {
        global $DB, $CFG;

        $type = "";
        if ($CFG->dbtype == 'pgsql') {
          $type = "::text";
        }

        $sql_filter = $this->get_filter_course_sql($params, "c.");
        $sql_filter .= $this->get_filter_in_sql($params->custom, "c.id");

        $opr = ($params->custom2) ? "" :"NOT";
        $this->params['userid'] = (int) $params->userid;
        $sql_filter .= " AND c.id{$type} $opr IN(SELECT instance FROM {local_intelliboard_assign} WHERE userid = :userid AND rel = 'external' AND type='courses')";
        $sql_arr = array();
        if ($params->filter) {
            $sql_arr[] = $DB->sql_like('c.fullname', ":fullname", false, false);
            $sql_arr[] = $DB->sql_like('ca.name', ":name", false, false);
            $this->params['fullname'] = "%$params->filter%";
            $this->params['name'] = "%$params->filter%";
            $sql_filter .= " AND (".implode(" OR ", $sql_arr) .")";
        }

        $count = $DB->count_records_sql("SELECT COUNT(*) FROM {course} c, {course_categories} ca WHERE ca.id = c.category $sql_filter", $this->params);

        if ($count > 300) {
            return array('length' => $count);
        } else {
            return $DB->get_records_sql("SELECT c.id, c.fullname, c.shortname, ca.name FROM {course} c, {course_categories} ca WHERE ca.id = c.category $sql_filter", $this->params);
        }
    }
    public function get_assign_categories($params)
    {
        global $DB, $CFG;
        require_once($CFG->libdir.'/coursecatlib.php');

        $type = "";
        if ($CFG->dbtype == 'pgsql') {
          $type = "::text";
        }

        $sql_filter = $this->get_filter_in_sql($params->custom, "id");

        $opr = ($params->custom2) ? "" :"NOT";
        $this->params['userid'] = (int) $params->userid;
        $sql_filter .= " AND id{$type} $opr IN(SELECT instance FROM {local_intelliboard_assign} WHERE userid = :userid AND rel = 'external' AND type='categories')";
        $sql_arr = array();
        if ($params->filter) {
            $sql_arr[] = $DB->sql_like('name', ":name", false, false);
            $this->params['name'] = "%$params->filter%";
            $sql_filter .= " AND (".implode(" OR ", $sql_arr) .")";
        }

        $count = $DB->count_records_sql("SELECT COUNT(*) FROM {course_categories} WHERE visible > 0 $sql_filter", $this->params);

        if ($count > 300) {
            return array('length' => $count);
        } else {
            $records = $DB->get_records_sql("SELECT id, name, idnumber FROM {course_categories} WHERE visible > 0 $sql_filter", $this->params);

            foreach($records as &$record){
                $coursecat = coursecat::get($record->id);
                $record->full_path = $this->get_category_nested_name($coursecat, false);
            }

            return $records;
        }
    }

    /**
    * Get the nested name of this category, with all of it's parents.
    *
    * @param   bool    $includelinks Whether to wrap each name in the view link for that category.
    * @param   string  $separator The string between each name.
    * @param   array   $options Formatting options.
    * @return  string
    */
    protected function get_category_nested_name($coursecat, $includelinks = true, $separator = ' / ', $options = []) {
       // Get the name of hierarchical name of this category.
       $parents = $coursecat->get_parents();
       $categories = coursecat::get_many($parents);
       $categories[] = $coursecat;

       $names = array_map(function($category) use ($options, $includelinks) {
           if ($includelinks) {
               return html_writer::link($category->get_view_link(), $category->get_formatted_name($options));
           } else {
               return $category->get_formatted_name($options);
           }

       }, $categories);

       return implode($separator, $names);
   }

    public function get_assign_fields($params)
    {
        global $DB;

        $this->params['userid'] = (int) $params->userid;
        $sql_filter = "";
        $sql_arr = array();
        if ($params->filter) {
            $sql_arr[] = $DB->sql_like('instance', ":instance", false, false);
            $this->params['instance'] = "%$params->filter%";
            $sql_filter .= " AND (".implode(" OR ", $sql_arr) .")";
        }

        $count = $DB->count_records_sql("SELECT COUNT(*) FROM {local_intelliboard_assign} WHERE userid = :userid AND rel = 'external' AND type='fields' $sql_filter", $this->params);

        if ($count > 300) {
            return array('length' => $count);
        } else {
            return $DB->get_records_sql("SELECT id, instance FROM {local_intelliboard_assign} WHERE userid = :userid AND rel = 'external' AND type='fields' $sql_filter", $this->params);
        }
    }

    public function get_assign_cohorts($params)
    {
        global $DB, $CFG;

        $type = "";
        if ($CFG->dbtype == 'pgsql') {
          $type = "::text";
        }

        $sql_filter .= $this->get_filter_in_sql($params->custom, "id");

        $opr = ($params->custom2) ? "" :"NOT";
        $this->params['userid'] = (int) $params->userid;
        $sql_filter .= " AND id{$type} $opr IN(SELECT instance FROM {local_intelliboard_assign} WHERE userid = :userid AND rel = 'external' AND type='cohorts')";
        $sql_arr = array();
        if ($params->filter) {
            $sql_arr[] = $DB->sql_like('name', ":name", false, false);
            $this->params['name'] = "%$params->filter%";
            $sql_filter .= " AND (".implode(" OR ", $sql_arr) .")";
        }

        $count = $DB->count_records_sql("SELECT COUNT(*) FROM {cohort} WHERE id > 0 $sql_filter", $this->params);

        if ($count > 300) {
            return array('length' => $count);
        } else {
            return $DB->get_records_sql("SELECT id, name FROM {cohort} WHERE id > 0 $sql_filter", $this->params);
        }
    }
     public function get_dashboard_stats($params)
    {
        global $DB;
        $sql = $this->get_teacher_sql($params, "userid", "users");
        $this->params['timeyesterday1'] = strtotime('yesterday');
        $this->params['timeyesterday2'] = strtotime('yesterday');
        $this->params['timeyesterday3'] = strtotime('yesterday');
        $this->params['timelastweek1'] = strtotime('last week');
        $this->params['timelastweek2'] = strtotime('last week');
        $this->params['timelastweek3'] = strtotime('last week');
        $this->params['timetoday1'] = strtotime('today');
        $this->params['timetoday2'] = strtotime('today');
        $this->params['timetoday3'] = strtotime('today');
        $this->params['timeweek1'] = strtotime('previous monday');
        $this->params['timeweek2'] = strtotime('previous monday');
        $this->params['timeweek3'] = strtotime('previous monday');
        $this->params['timefinish1'] = time();
        $this->params['timefinish2'] = time();
        $this->params['timefinish3'] = time();
        $this->params['timefinish4'] = time();
        $this->params['timefinish5'] = time();
        $this->params['timefinish6'] = time();
        $data = array();
        if($params->sizemode){
            $data[] = array();
        }else{
            $data[] = $DB->get_record_sql("SELECT
            (SELECT SUM(sessions) FROM {local_intelliboard_totals} WHERE timepoint BETWEEN :timeyesterday1 AND :timetoday1) as sessions_today,
            (SELECT SUM(sessions) FROM {local_intelliboard_totals} WHERE timepoint BETWEEN :timelastweek1 AND :timeweek1) as sessions_week,
            (SELECT COUNT(DISTINCT (userid)) FROM {user_enrolments} WHERE timecreated BETWEEN :timeyesterday2 AND :timetoday2 $sql) as enrolments_today,
            (SELECT COUNT(DISTINCT (userid)) FROM {user_enrolments} WHERE timecreated BETWEEN :timelastweek2 AND :timeweek2 $sql) as enrolments_week,
            (SELECT COUNT(DISTINCT (userid)) FROM {course_completions} WHERE timecompleted BETWEEN :timeyesterday3 AND :timetoday3 $sql) as compl_today,
            (SELECT COUNT(DISTINCT (userid)) FROM {course_completions} WHERE timecompleted BETWEEN :timelastweek3 AND :timeweek3 $sql) as compl_week", $this->params);
        }
        $data[] = $DB->get_record_sql("SELECT
            (SELECT SUM(sessions) FROM {local_intelliboard_totals} WHERE timepoint BETWEEN :timetoday1 AND :timefinish1) as sessions_today,
            (SELECT SUM(sessions) FROM {local_intelliboard_totals} WHERE timepoint BETWEEN :timeweek1 AND :timefinish2) as sessions_week,
            (SELECT COUNT(userid) FROM {user_enrolments} WHERE timecreated BETWEEN :timetoday2 AND :timefinish3 $sql) as enrolments_today,
            (SELECT COUNT(userid) FROM {user_enrolments} WHERE timecreated BETWEEN :timeweek2 AND :timefinish4 $sql) as enrolments_week,
            (SELECT COUNT(userid) FROM {course_completions} WHERE timecompleted BETWEEN :timetoday3 AND :timefinish5 $sql) as compl_today,
            (SELECT COUNT(userid) FROM {course_completions} WHERE timecompleted BETWEEN :timeweek3 AND :timefinish6 $sql) as compl_week", $this->params);
        return $data;
    }
    public function get_dashboard_avg($params){
        global $DB;
        $sql_enabled = $this->get_filter_in_sql($params->learner_roles,'roleid',false);
        $grade_avg = intelliboard_grade_sql(true, $params);
        return $DB->get_record_sql("
            SELECT a.timespend_site, a.visits_site, c.grade_site
            FROM
                (SELECT ROUND(AVG(b.timespend_site),0) as timespend_site, ROUND(AVG(b.visits_site),0) as visits_site
                    FROM (SELECT SUM(timespend) as timespend_site, SUM(visits) as visits_site
                        FROM {local_intelliboard_tracking}
                        WHERE userid NOT IN (SELECT DISTINCT userid FROM {role_assignments} WHERE $sql_enabled) AND userid != 2 GROUP BY userid) AS b) a,
                (SELECT round(AVG(b.grade),0) AS grade_site FROM (SELECT $grade_avg AS grade
                    FROM {grade_items} gi, {grade_grades} g
            WHERE gi.itemtype = 'course' AND g.itemid = gi.id AND g.finalgrade IS NOT NULL GROUP BY g.userid) b) c", $this->params);
    }

    private function count_records($sql,$unique_id = 'id',$params=array(), $limit=true)
    {
        global $DB;
        if($limit && strpos($sql,"LIMIT") !== false)
            $sql = strstr($sql,"LIMIT",true);

        $sql = "SELECT COUNT( DISTINCT cou.$unique_id) FROM (".$sql.") cou";
        return $DB->count_records_sql($sql,$params);
    }
    public function get_teacher_sql($params, $columns, $include_learning_plan_filter = false)
    {
        global $DB, $CFG;

        $sql = '';
        if (isset($params->externalid) and $params->externalid and !empty($columns)) {
            $query = [];

            $assigns = $DB->get_records_sql("SELECT * FROM {local_intelliboard_assign} WHERE userid = :userid", ['userid' => $params->externalid]);
            $assign_users = [];
            $assign_courses = [];
            $assign_cohorts = [];
            $assign_categories = [];
            $assign_fields = [];
            foreach ( $assigns as  $assign) {
                if ($assign->type == 'users') {
                    $assign_users[] = (int) $assign->instance;
                } elseif ($assign->type == 'courses') {
                    $assign_courses[] = (int) $assign->instance;
                } elseif ($assign->type == 'categories') {
                    $assign_categories[] = (int) $assign->instance;
                } elseif ($assign->type == 'cohorts') {
                    $assign_cohorts[] = (int) $assign->instance;
                } elseif ($assign->type == 'fields') {
                    $assign_fields[] = $assign->instance;
                }
            }
            if ($assign_fields) {
              $sql_arr = array();
              foreach ($assign_fields as $key=>$field) {
                $elem = explode("|", $field);
                $fieldid = (int) $elem[0];
                $value = $elem[1];
                $sql_arr[] = "fieldid = :field{$key} AND " . $DB->sql_like('data', ":data{$key}", false, false);
                $this->params["data{$key}"] = "$value";
                $this->params["field{$key}"] = $fieldid;
              }
              $sql_filter = " AND (".implode(") OR (", $sql_arr) .")";
              if ($list = $DB->get_records_sql("SELECT DISTINCT userid FROM {user_info_data} WHERE data <> '' $sql_filter",	$this->params)) {
                foreach ($list as $item) {
                  $assign_users[] = (int) $item->userid;
                }
              }
            }
            if ($assign_categories) {
              require_once($CFG->libdir. '/coursecatlib.php');

              $categories = coursecat::get_many($assign_categories);
              foreach ($categories as $category) {
                $children_courses = $category->get_courses(['recursive'=>true]);
                foreach($children_courses as $course) {
                  $assign_courses[] = $course->id;
                }
              }
            }

            $assign_users_list = implode(",", array_unique($assign_users));
            $assign_courses_list = implode(",", array_unique($assign_courses));
            $assign_cohorts_list = implode(",", $assign_cohorts);

            foreach ($columns as $column => $type) {
                if ($type == "users") {
                    if ($assign_users) {
                        $this->users = array_unique(array_merge($this->users, $assign_users));
                    }
                    if ($assign_cohorts_list) {
                        $result = $DB->get_records_sql("SELECT userid FROM {cohort_members} WHERE cohortid IN ($assign_cohorts_list)");
                        if ($result) {
                            $list = [];
                            foreach ($result as $value) {
                                $list[] = $value->userid;
                            }
                            $this->users = array_unique(array_merge($this->users, $list));
                        }
                    }
                    if ($assign_courses_list) {
                        $result = $DB->get_records_sql("SELECT distinct ra.userid FROM {role_assignments} ra, {context} ctx WHERE ctx.id = ra.contextid AND ctx.contextlevel = 50 AND ctx.instanceid IN ($assign_courses_list)", $this->params);
                        if ($result) {
                            $list = [];
                            foreach ($result as $value) {
                                $list[] = $value->userid;
                            }
                            $this->users = array_unique(array_merge($this->users, $list));
                        }
                    }
                    if ($this->users) {
                        $query[] = "$column IN (".implode(",", $this->users).")";
                    }
                } elseif($type == "courses") {
                    if ($assign_courses) {
                        $this->courses = array_unique(array_merge($this->courses, $assign_courses));
                    }

                    if ($assign_users_list) {
                        $result = $DB->get_records_sql("SELECT distinct ctx.instanceid FROM {role_assignments} ra, {context} ctx WHERE ctx.id = ra.contextid AND ctx.contextlevel = 50 AND ra.userid IN ($assign_users_list)", $this->params);
                        if ($result) {
                            $list = [];
                            foreach ($result as $value) {
                                $list[] = $value->instanceid;
                            }
                            $this->courses = array_unique(array_merge($this->courses, $list));
                        }
                    }
                    if ($assign_cohorts_list) {
                        $result = $DB->get_records_sql("SELECT userid FROM {cohort_members} WHERE cohortid IN ($assign_cohorts_list)");
                        if ($result) {
                            $list = [];
                            foreach ($result as $value) {
                                $list[] = $value->userid;
                            }
                            $users_list = implode(",", $list);
                            $result = $DB->get_records_sql("SELECT distinct ctx.instanceid FROM {role_assignments} ra, {context} ctx WHERE ctx.id = ra.contextid AND ctx.contextlevel = 50 AND ra.userid IN ($users_list)", $this->params);
                            if ($result) {
                                $list = [];
                                foreach ($result as $value) {
                                    $list[] = $value->instanceid;
                                }
                                $this->courses = array_unique(array_merge($this->courses, $list));
                            }
                        }
                    }
                    if ($this->courses) {
                        $query[] = "$column IN (".implode(",", $this->courses).")";
                    }
                } elseif($type == "cohorts") {
                    if ($assign_cohorts) {
                        $query[] = "$column IN (".implode(",", $assign_cohorts).")";
                    }
                } elseif($type == "categories") {
                    if ($assign_categories) {
                        $query[] = "$column IN (".implode(",", $assign_categories).")";
                    }
                }
            }
            if ($query) {
                $sql = " AND (".implode(" AND ", $query).")";
            }
        } elseif(isset($params->userid) && $params->userid && $columns) {
          if(get_config('local_intelliboard', 'learning_plan_filter') && $include_learning_plan_filter==true){
              $sql .= $this->get_learning_plan_filter_sql($params, 'u.id');
          } else {

              $query = [];
              $mode = get_config('local_intelliboard', 'instructor_mode');
              $visibility = get_config('local_intelliboard', 'instructor_course_visibility');
              $roles = get_config('local_intelliboard', 'filter10');
              $access = get_config('local_intelliboard', 'instructor_mode_access');
              $instructor_custom_groups = get_config('local_intelliboard', 'instructor_custom_groups');
              $user = $DB->get_record_sql("SELECT * FROM {user} WHERE id = :userid", ['userid' => $params->userid]);
              $users = '';


              if ($instructor_custom_groups) {
                  if ($CFG->dbtype == 'pgsql') {
                      $userid_sql = "string_agg( DISTINCT d.userid, ', ')";
                  } else {
                      $userid_sql = "GROUP_CONCAT( DISTINCT d.userid)";
                  }

                  $data = $DB->get_record_sql("SELECT d.data AS codea FROM {user_info_field} f, {user_info_data} d WHERE d.fieldid = f.id AND d.userid = ? and f.shortname= 'codea'", [$params->userid]);
                  $result = $DB->get_record_sql("SELECT $userid_sql AS users FROM {user_info_field} f, {user_info_data} d
                WHERE d.fieldid = f.id AND d.data = ? and f.shortname IN ('codsm', 'coddm', 'codam')", [$data->codea]);
                  if ($result->users) {
                      list($sql, $params_users) = intelliboard_filter_in_sql($result->users, "ra.userid", []);
                      $courses = $DB->get_records_sql("SELECT c.* FROM {role_assignments} ra, {context} ctx, {course} c WHERE ctx.id = ra.contextid AND ctx.contextlevel = 50 AND c.id=ctx.instanceid $sql GROUP BY c.id", $params_users);
                      $users = $result->users;
                  } else {
                      $courses = [];
                      $users = '0,0';
                  }
              } elseif ($mode) {
                  $courses = $DB->get_records_sql("SELECT * FROM {course} WHERE category > 0");
              } else {
                  $params_users = ['userid' => $params->userid];
                  list($sqlf, $params_users) = intelliboard_filter_in_sql($roles, "ra.roleid", $params_users);
                  $courses = $DB->get_records_sql("SELECT c.* FROM {role_assignments} ra, {context} ctx, {course} c WHERE ctx.id = ra.contextid AND ctx.contextlevel = 50 AND c.id=ctx.instanceid AND ra.userid = :userid $sqlf", $params_users);
              }

              foreach ($courses as $key => $course) {
                  if ($access) {
                      if (!can_access_course($course, $user, 'moodle/course:update')) {
                          unset($courses[$key]);
                      }
                  } else {
                      if (!can_access_course($course, $user)) {
                          unset($courses[$key]);
                      }
                  }
                  if (!$visibility and !$course->visible) {
                      unset($courses[$key]);
                  }
              }
              foreach ($columns as $column => $type) {
                  if ($type == "users") {
                      if ($users and $instructor_custom_groups) {
                          $query[] = "$column IN ($users)";
                      } else {
                          $assign_courses_list = (!$courses) ? '0,0' : implode(",", array_keys($courses));
                          $learner_roles = $this->get_filter_in_sql($params->learner_roles, 'ra.roleid');
                          $result = $DB->get_records_sql("SELECT distinct ra.userid FROM {role_assignments} ra, {context} ctx WHERE ctx.id = ra.contextid AND ctx.contextlevel = 50 $learner_roles AND ctx.instanceid IN ($assign_courses_list)", $this->params);
                          if ($result) {
                              $list = [];
                              foreach ($result as $value) {
                                  $list[] = $value->userid;
                              }
                              $this->users = array_unique(array_merge($this->users, $list));
                          }
                          if ($this->users) {
                              $query[] = "$column IN (" . implode(",", $this->users) . ")";
                          }
                      }
                  } elseif ($type == "courses") {
                      //$query = [];
                      $this->courses = array_keys($courses);
                      if ($this->courses) {
                          $query[] = "$column IN (" . implode(",", $this->courses) . ")";
                      }
                  }
              }
              if ($query) {
                  $sql = " AND (" . implode(" AND ", $query) . ")";
              }
          }
        }
        return $sql;
    }

    public function vendor_filter($useridcolumn, $courseidcolumn, $params) {
        global $DB;

        $sql = '';
        static $users = null, $courses = null;

        if (isset($params->vendor_user_id) && $params->vendor_user_id) {
            if ($users === null) {
                $users = array_keys($DB->get_records_sql(
                    "SELECT DISTINCT t.userid
                       FROM (SELECT liu1.userid
                               FROM {local_intellicart_users} liu
                               JOIN {local_intellicart_users} liu1 ON liu1.instanceid = liu.instanceid AND
                                                                      liu1.type = 'vendor' AND liu1.role = 'user'
                              WHERE liu.type = 'vendor' AND liu.role = 'manager' AND liu.userid = :user
                              UNION
                             SELECT com.userid
                               FROM {local_intellicart_users} liu1
                               JOIN {local_intellicart_cohorts} lic ON lic.instanceid = liu1.instanceid AND
                                                                       lic.type = 'vendor'
                               JOIN {cohort_members} com ON com.cohortid = lic.cohortid
                              WHERE liu1.type = 'vendor' AND liu1.role = 'manager' AND liu1.userid = :user1
                            ) t",
                    ['user' => $params->vendor_user_id, 'user1' => $params->vendor_user_id]
                ));
            }

            if (get_config('local_intellicart', 'enable_intelliboard_reports_seats_filter')) {
                if ($courses === null) {
                    $courses = [];
                    $sqlparams = ['user' => $params->vendor_user_id];
                    $checkexpiration = get_config('local_intellicart', 'enableseatsexpiration');

                    $coursesrows = $DB->get_records_sql(
                        "SELECT DISTINCT lir.instanceid, lis.expiration
                           FROM (SELECT id, instanceid, userid
                                   FROM {local_intellicart_users}
                                  WHERE type = 'vendor' AND role = 'manager' AND userid = :user
                                ) liu
                      LEFT JOIN {local_intellicart_users} liu1 ON liu1.instanceid = liu.instanceid AND liu1.id <> liu.id AND
                                                                  liu1.type = 'vendor' AND liu1.role = 'manager'
                      LEFT JOIN {local_intellicart_logs} lil ON lil.type = 'seat' AND lil.status = 'completed' AND
                                                                (lil.userid = liu.userid OR lil.userid = liu1.userid)
                      LEFT JOIN {local_intellicart_relations} lir ON lir.type = 'course' AND lir.productid = lil.instanceid
                      LEFT JOIN {local_intellicart_seats} lis ON lis.productid = lil.instanceid AND lis.userid = lil.userid",
                        $sqlparams
                    );

                    foreach ($coursesrows as $row) {
                        if ($checkexpiration && ($row->expiration != 0 && $row->expiration < time())) {
                            continue;
                        }

                        $courses[] = $row->instanceid;
                    }
                }

                if ($courses && $courseidcolumn) {
                    $coursesids = implode(',', $courses);
                    $sql .= " AND {$courseidcolumn} IN ({$coursesids})";
                }
            }

            if ($users && $useridcolumn) {
                $ids = implode(',', $users);
                $sql .= " AND {$useridcolumn} IN ({$ids})";
            }

            if (!$users || (!$courses && get_config('local_intellicart', 'enable_intelliboard_reports_seats_filter'))) {
                if ($useridcolumn) {
                    return " AND {$useridcolumn} = -1";
                }

                return " AND {$courseidcolumn} = -1";
            }

            return $sql;
        }

        return $sql;
    }

    public function vendor_manager_filter($useridcolumn, $params)
    {
        global $DB;

        if (isset($params->vendor_user_id) && $params->vendor_user_id) {
            $users = array_keys($DB->get_records_sql(
                "SELECT DISTINCT liu1.userid
                   FROM {local_intellicart_users} liu
              LEFT JOIN {local_intellicart_users} liu1 ON liu1.instanceid = liu.instanceid AND
                                                          liu1.type = 'vendor' AND liu1.role = 'manager'
                 WHERE liu.type = 'vendor' AND liu.role = 'manager' AND liu.userid = :user",
                ['user' => $params->vendor_user_id]
            ));

            $managersids = implode(',', $users);

            return " AND {$useridcolumn} IN ({$managersids})";
        }

        return "";
    }

    public function get_info($params)
    {
        global $CFG, $DB;

        require_once($CFG->libdir.'/adminlib.php');

        $sql1 = $this->get_teacher_sql($params, ["id" => "users"]);
        $sql2 = $this->get_teacher_sql($params, ["userid" => "users"]);
        $sql3 = $this->get_teacher_sql($params, ["id" => "courses"]);
        $sql4 = $this->get_teacher_sql($params, ["course" => "courses"]);
        $sql1 .= $this->get_filter_enrolled_users_sql($params, "id");
        $sql1 .= $this->get_filter_user_sql($params, "", true);
        $sql3 .= $this->get_filter_course_sql($params, "");
        $sql4 .= $this->get_filter_module_sql($params, "");
        $size = "0";

        if ($params->custom) {
            set_config("apikey", clean_param($params->custom, PARAM_ALPHANUMEXT), "local_intelliboard");
        }
        if (!$params->filter) {
            $size = "(SELECT SUM(filesize) FROM {files} WHERE id > 0 $sql2)";
        }
        if ($CFG->dbtype == 'pgsql') {
            $func = 'array_agg';
        } elseif($CFG->dbtype == 'mssql' or $CFG->dbtype == 'sqlsrv') {
            $func = 'COUNT';
        } else {
            $func = 'GROUP_CONCAT';
        }

        $totals = $DB->get_record_sql("
            SELECT
                (SELECT COUNT(*) FROM {user} WHERE id > 0 $sql1) AS users,
                $size AS size,
                (SELECT COUNT(*) FROM {course} WHERE category > 0 $sql3) AS courses,
                (SELECT COUNT(*) FROM {course_modules} WHERE id > 0 $sql4) AS activities,
                (SELECT COUNT(*) FROM {course_categories} WHERE visible = 1) AS categories,
                (SELECT $func(name) FROM {modules} WHERE visible = 1) AS modules", $this->params);

        $data = array(
            'size' => $totals->size,
            'users' => $totals->users,
            'courses' => $totals->courses,
            'modules' => $totals->modules,
            'activities' => $totals->activities,
            'categories' => $totals->categories,
            'dbtype' => $CFG->dbtype,
            'moodle' => $CFG->version,
            'intellicart' => get_component_version('local_intellicart'),
            'version' => get_component_version('local_intelliboard'),
            'sso_enabled' => get_config('local_intelliboard', 'sso'),
            'tracking_enabled' => get_config('local_intelliboard', 'enabled'),
            'server' => get_config('local_intelliboard', 'server'),
            'instructor_mode' => get_config('local_intelliboard', 'instructor_mode'),
            'instructor_mode_access' => get_config('local_intelliboard', 'instructor_mode_access'),
        );
        if (get_capability_info('moodle/competency:competencyview')) {
            $data['competency'] = 1;
        } else {
            $data['competency'] = 0;
        }

        return $data;
    }

    public function get_event_contexts($params)
    {
        global $DB;

        $data = $DB->get_records_sql("SELECT DISTINCT objecttable FROM {logstore_standard_log} WHERE objecttable IS NOT NULL ORDER BY objecttable ASC");

        return $data;
    }

    public function kill_db_queries($params)
    {
        global $DB, $CFG;

        if (!$params->request) {
          return array('result' => false);
        }
        $queries = $DB->get_records_sql("SELECT * FROM {local_intelliboard_dbconn}");
        foreach ($queries as $query) {
            try {
                if ($CFG->dbtype == 'pgsql') {
                    $DB->execute("SELECT pg_terminate_backend(:connection_id)", array('connection_id' => $query->connection_id));
                } else {
                    $DB->execute("KILL :connection_id", array('connection_id' => $query->connection_id));
                }
            } catch(Exception $e) {
              return array('result' => $e);
            }
            $DB->delete_records('local_intelliboard_dbconn', array('id'=>$query->id));
        }
        return array('result' => true);
    }

    public function plugin_settings()
    {
        global $CFG, $DB;
        require_once($CFG->libdir.'/adminlib.php');

        $result = [];
        $settingsvalues = $DB->get_records_menu(
            'config_plugins', ['plugin' => 'local_intelliboard'], '', 'name, value'
        );

        $adminroot = admin_get_root();
        $settingspage = $adminroot->locate('intelliboard', true);

        foreach ($settingspage->children as $childpage) {
            if ($childpage->is_hidden() || !$childpage->check_access()) {
                continue;
            }

            $page = [
                'title' => $childpage->visiblename->out(),
                'items' => [],
            ];

            if ($childpage instanceof admin_settingpage) {
                // If its a settings page and has settings lets display them.
                if (!empty($childpage->settings)) {
                    $group = [];

                    foreach ($childpage->settings as $setting) {
                        if($setting->visiblename instanceof  lang_string) {
                            $title = $setting->visiblename->out();
                        } else {
                            $title = $setting->visiblename;
                        }

                        if($setting instanceof admin_setting_heading) {
                            if($group) {$page['items'][] = $group;}
                            $group = [
                                'grouptitle' => $title,
                                'items' => []
                            ];
                        } else {
                            if($setting instanceof admin_setting_configmultiselect){
                                $selected = explode(
                                    ',', $settingsvalues[$setting->name]
                                );

                                $selected = array_filter(
                                    $setting->choices, function($key) use ($selected) {
                                        return in_array($key, $selected);
                                    }, ARRAY_FILTER_USE_KEY
                                );

                                foreach($selected as &$item) {
                                    if($item instanceof lang_string) {
                                        $item = $item->out();
                                    }
                                }
                                $value = implode(', ', $selected);
                                $subtype = 'multiselect';
                            } elseif($setting instanceof admin_setting_configselect) {
                                $value = $setting->choices[
                                    $settingsvalues[$setting->name]
                                ];
                                $subtype = 'select';
                            } elseif($setting instanceof admin_setting_configcheckbox) {
                                $value = $settingsvalues[$setting->name];
                                $subtype = 'checkbox';

                                if($value == '1') {
                                    $value = true;
                                } else {
                                    $value = false;
                                }
                            } else {
                                $subtype = 'other';
                                $value = $setting->get_setting();
                            }
                            $group['items'][] = [
                                'type' => 'setting',
                                'subtype' => $subtype,
                                'title' => $title,
                                'name' => $setting->name,
                                'value' => $value,
                            ];
                        }
                    }

                    if($group) {$page['items'][] = $group;}
                }
            }

            $result[] = $page;
        }

        return $result;
    }

    public function users_overview()
    {
        global $DB;

        $secondsinyear =31557600;

        $sql = "SELECT COUNT(u.id) as total_users,
                       SUM(CASE WHEN (u.lastaccess >= :filter1) THEN 1 ELSE 0 END) as last_year_logged_on,
                       SUM(CASE WHEN er.enr_count IS NOT NULL AND er.enr_count > 0 THEN 1 ELSE 0 END) as enrolled_active_users
                  FROM {user} u
             LEFT JOIN (SELECT ra.userid, COUNT(DISTINCT c.id) as enr_count
                          FROM {role_assignments} ra
                          JOIN {context} cx ON cx.id = ra.contextid AND
                                               cx.contextlevel = 50
                          JOIN {course} c ON c.id = cx.instanceid
                         WHERE c.visible = 1
                      GROUP BY ra.userid
                  ) er ON er.userid = u.id
                 WHERE u.deleted = 0 AND u.suspended = 0";

        return $DB->get_record_sql($sql, ['filter1' => time() - $secondsinyear]);
    }

    public function available_modules($params) {
        global $DB, $OUTPUT;

        $modules = $DB->get_records('modules', ['visible' => 1]);

        foreach($modules as &$module) {
            $module->icon_url = $OUTPUT->pix_url(
                'icon', 'mod_'.$module->name
            )->out(false);
        }

        return $modules;
    }

    /**
     * @param $useralias
     * @param $coursealias
     * @param \stdClass $params
     * @return string SQL
     * @throws dml_exception
     */
    public function group_aggregation($useralias, $coursealias, $params) {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/local/intelliboard/instructor/lib.php');

        $sql = '';

        if (isset($params->userid) and $params->userid && get_config('local_intelliboard', 'group_aggregation')) {
            $user = $DB->get_record('user', ['id' => $params->userid], '*', MUST_EXIST);

            $sql = intelliboard_group_aggregation_sql($useralias, $user->id, $coursealias);
        }

        return $sql;
    }

    public function get_course_categories($params){
        global $DB;

        $sql_filter = "";
        if($params->filter){
            $sql_filter = " AND ".$DB->sql_like('cc.name', ":name", false, false);
            $this->params['name'] = "%$params->filter%";
        }


        return $this->get_report_data("SELECT cc.id,
                                            cc.name,
                                            cc.idnumber,
                                            cc.parent
                                        FROM {course_categories} cc
                                        WHERE cc.id>0 $sql_filter
                                        ORDER BY cc.sortorder", $params, false);
    }

    public function get_learning_plan_filter_sql($params, $useridcolumn) {
        global $DB;

        if (!isset($params->userid) || !$params->userid || !get_config('local_intelliboard', 'learning_plan_filter')) {
            return '';
        }

        $users = $DB->get_records_sql(
            "SELECT DISTINCT cm.userid
               FROM {tool_cohortroles} tc
               JOIN {cohort_members} cm ON cm.cohortid = tc.cohortid
              WHERE tc.userid = :userid AND tc.roleid = :role",
            ['userid' => $params->userid, 'role' => get_config('local_intelliboard', 'learning_plan_viewer_role')]
        );

        if (!$users) {
            return " AND {$useridcolumn} = -1";
        }

        return " AND {$useridcolumn} IN (" . implode(',', array_keys($users)) . ")";
    }

    public function subaccount_export_prepare($params)
    {
        global $DB;

        $params = json_decode($params->custom, true);
        $response = [];

        if (isset($params['users']) && $params['users']) {
            list($insql, $inparams) = $DB->get_in_or_equal($params['users'], SQL_PARAMS_NAMED, 'usr_');
            $response['users'] = $DB->get_records_sql(
                "SELECT id, CONCAT(firstname, ' ', lastname) AS name FROM {user} WHERE id {$insql}", $inparams
            );
        }

        if (isset($params['categories']) && $params['categories']) {
            list($insql, $inparams) = $DB->get_in_or_equal($params['categories'], SQL_PARAMS_NAMED, 'ctg_');
            $response['categories'] = $DB->get_records_sql(
                "SELECT id, name FROM {course_categories} WHERE id {$insql}", $inparams
            );
        }

        if (isset($params['cohorts']) && $params['cohorts']) {
            list($insql, $inparams) = $DB->get_in_or_equal($params['cohorts'], SQL_PARAMS_NAMED, 'cht_');
            $response['cohorts'] = $DB->get_records_sql(
                "SELECT id, name FROM {cohort} WHERE id {$insql}", $inparams
            );
        }

        if (isset($params['courses']) && $params['courses']) {
            list($insql, $inparams) = $DB->get_in_or_equal($params['courses'], SQL_PARAMS_NAMED, 'crs_');
            $response['courses'] = $DB->get_records_sql(
                "SELECT id, shortname FROM {course} WHERE id {$insql}", $inparams
            );
        }

        if (isset($params['fields']) && $params['fields']) {
            list($insql, $inparams) = $DB->get_in_or_equal($params['fields'], SQL_PARAMS_NAMED, 'fld_');
            $response['fields'] = $DB->get_records_sql(
                "SELECT id, name FROM {user_info_field} WHERE id {$insql}", $inparams
            );
        }

        return $response;
    }

    public function sql_cohort_members_filter($params, $userialias = "u.id") {
        global $DB;

        static $cohortmebers = null;

        if (!$params->cohortid && !$params->userid) {
            return "";
        }

        if ($cohortmebers === null) {
            if ($params->cohortid) {
                /** @var array $cohortmebers IDs of selected cohorts members */
                list($insql, $inparams) = $DB->get_in_or_equal(explode(",", $params->cohortid));
                $cohortmebers = array_keys($DB->get_records_sql(
                    "SELECT userid
                       FROM {cohort_members}
                      WHERE id > 0 AND cohortid {$insql}
                   GROUP BY userid", $inparams
                ));
            } elseif (!$params->cohortid && $params->userid) {
                $allusercohorts = user_cohorts($params->userid);

                if ($allusercohorts) {
                    /** @var array $cohortmebers IDs of selected cohorts members */
                    list($insql, $inparams) = $DB->get_in_or_equal(array_keys($allusercohorts));
                    $cohortmebers = array_keys($DB->get_records_sql(
                        "SELECT userid
                           FROM {cohort_members}
                          WHERE id > 0 AND cohortid {$insql}
                       GROUP BY userid", $inparams
                    ));
                }
            }
        }

        if ($cohortmebers) {
            return $this->get_filter_in_sql($cohortmebers, $userialias);
        }

        return " AND {$userialias} = -1";
    }
}
