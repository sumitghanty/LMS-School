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

// In versions before Moodle 2.9, the supported callbacks have _extends_ (not imperative mood) in their names. This was a consistency bug fixed in MDL-49643.
function local_intelliboard_extends_navigation(global_navigation $nav)
{
	global $CFG, $USER;

	$context = context_system::instance();
	if (isloggedin() and get_config('local_intelliboard', 't1') and has_capability('local/intelliboard:students', $context)) {
		$alt_name = get_config('local_intelliboard', 't0');
		$def_name = get_string('ts1', 'local_intelliboard');
		$name = ($alt_name) ? $alt_name : $def_name;


		$learner_menu = get_config('local_intelliboard', 'learner_menu');
		if ($learner_menu) {
			if($courses = enrol_get_users_courses($USER->id)) {
				$nav->add($name, new moodle_url($CFG->wwwroot.'/local/intelliboard/student/index.php'));
			}
		} else {
			$nav->add($name, new moodle_url($CFG->wwwroot.'/local/intelliboard/student/index.php'));
		}
	}

	if(has_capability('local/intelliboard:view', $context) and get_config('local_intelliboard', 'ssomenu')){
		$nav->add(get_string('ianalytics', 'local_intelliboard'), new moodle_url($CFG->wwwroot.'/local/intelliboard/index.php?action=sso'));
	}
	if (isloggedin() and get_config('local_intelliboard', 'n10')){
	    //Check if user is enrolled to any courses with "instructor" role(s)
		$instructor_roles = get_config('local_intelliboard', 'filter10');
	    if (!empty($instructor_roles)) {
	    	$access = false;
		    $roles = explode(',', $instructor_roles);
		    if (!empty($roles)) {
			    foreach ($roles as $role) {
			    	if ($role and user_has_role_assignment($USER->id, $role)){
			    		$access = true;
			    		break;
			    	}
			    }
				if ($access) {
					$alt_name = get_config('local_intelliboard', 'n11');
					$def_name = get_string('n10', 'local_intelliboard');
					$name = ($alt_name) ? $alt_name : $def_name;
					$nav->add($name, new moodle_url($CFG->wwwroot.'/local/intelliboard/instructor/index.php'));
				}
			}
		}
	}
}
//call-back method to extend the navigation
function local_intelliboard_extend_navigation(global_navigation $nav)
{
	global $CFG, $DB, $USER, $PAGE;

	try {
		$mynode = $PAGE->navigation->find('myprofile', navigation_node::TYPE_ROOTNODE);
		$mynode->collapse = true;
		$mynode->make_inactive();
		$context = context_system::instance();

    if(has_capability('local/intelliboard:view', $context) and get_config('local_intelliboard', 'ssomenu')){
        $name = get_string('ianalytics', 'local_intelliboard');
        $url = new moodle_url($CFG->wwwroot.'/local/intelliboard/index.php?action=sso');
        $nav->add($name, $url);
        $node = $mynode->add($name, $url, 0, null, 'intelliboard_admin');
        $node->showinflatnavigation = true;
    }

		if (isloggedin() and get_config('local_intelliboard', 't1') and has_capability('local/intelliboard:students', $context)) {
			$alt_name = get_config('local_intelliboard', 't0');
			$def_name = get_string('ts1', 'local_intelliboard');
			$name = ($alt_name) ? $alt_name : $def_name;

			$learner_menu = get_config('local_intelliboard', 'learner_menu');
			if ($learner_menu) {
				if($courses = enrol_get_users_courses($USER->id)) {
					$url = new moodle_url($CFG->wwwroot.'/local/intelliboard/student/index.php');
					$nav->add($name, $url);
					$node = $mynode->add($name, $url, 0, null, 'intelliboard_student');
					$node->showinflatnavigation = true;
				}
			} else {
				$url = new moodle_url($CFG->wwwroot.'/local/intelliboard/student/index.php');
				$nav->add($name, $url);
				$node = $mynode->add($name, $url, 0, null, 'intelliboard_student');
				$node->showinflatnavigation = true;
			}
		}

		if (isloggedin() and get_config('local_intelliboard', 'n10')) {
		    //Check if user is enrolled to any courses with "instructor" role(s)
			$instructor_roles = get_config('local_intelliboard', 'filter10');
		    if (!empty($instructor_roles)) {
		    	$access = false;
			    $roles = explode(',', $instructor_roles);
			    if (!empty($roles)) {
				    foreach ($roles as $role) {
				    	if ($role and user_has_role_assignment($USER->id, $role)){
				    		$access = true;
				    		break;
				    	}
				    }
					if ($access) {
						$alt_name = get_config('local_intelliboard', 'n11');
						$def_name = get_string('n10', 'local_intelliboard');
						$name = ($alt_name) ? $alt_name : $def_name;
						$url = new moodle_url($CFG->wwwroot.'/local/intelliboard/instructor/index.php');
						$nav->add($name, $url);

						$node = $mynode->add($name, $url, 0, null, 'intelliboard_instructor');
						$node->showinflatnavigation = true;
					}
				}
			}
		}
		if (isloggedin() and get_config('local_intelliboard', 'competency_dashboard') and has_capability('local/intelliboard:competency', $context)) {
			$alt_name = get_config('local_intelliboard', 'a11');
			$def_name = get_string('a0', 'local_intelliboard');
			$name = ($alt_name) ? $alt_name : $def_name;
			$url = new moodle_url($CFG->wwwroot.'/local/intelliboard/competencies/index.php');
			$nav->add($name, $url);

			$node = $mynode->add($name, $url, 0, null, 'intelliboard_competency');
			$node->showinflatnavigation = true;
		}

        // attendance
        if(isloggedin() and get_config('local_intelliboard', 'enableattendance')) {
            $coursenode = $nav->find($PAGE->course->id, navigation_node::TYPE_COURSE);

            if($coursenode === false OR !($PAGE->course->id > 1))  {
                // show attendance in site navigation
                $name = get_string('attendance', 'local_intelliboard');
                $url = new moodle_url('/local/intelliboard/attendance/index.php');
                $nav->add($name, $url);

                $node = $mynode->add($name, $url, 0, null, 'intelliboard_attendance');
                $node->showinflatnavigation = true;
            } else {
                // show attendance in course navigation
                $name = get_string('attendance', 'local_intelliboard');
                $url = new moodle_url(
                    '/local/intelliboard/attendance/index.php',
                    ['course_id' => $PAGE->course->id]
                );
                $node = navigation_node::create(
                    $name,
                    $url,
                    navigation_node::TYPE_CUSTOM,
                    null,
                    'intelliboard_attendance',
                    new pix_icon('i/calendar', '', 'core')
                );
                $coursenode->add_node($node);
            }
        }
	} catch (Exception $e) {}
}
function local_intelliboard_extend_settings_navigation(settings_navigation $settingsnav, context $context)
{
    global $CFG;
    require_once($CFG->dirroot .'/local/intelliboard/locallib.php');
    require_once($CFG->dirroot .'/local/intelliboard/instructor/lib.php');

    $coursenode = $settingsnav->get('courseadmin');
    if ($coursenode && get_config('local_intelliboard', 'n19') && get_config('local_intelliboard', 'n10') && check_intelliboard_instructor_access()) {
        $cache = cache::make('local_intelliboard', 'reports_list');

        $reports = $cache->get('reports_list');
        if(!$reports){
            $params = array('do'=>'instructor','mode'=> 2);
            $intelliboard = intelliboard($params);

            if(isset($intelliboard->reports)){
                $reports = $intelliboard->reports;
                $cache->set('reports_list', $reports);
            }
        }

        $cat = $coursenode->add(get_string('intelliboard_reports', 'local_intelliboard'), null, navigation_node::TYPE_CONTAINER, null, 'intelliboard');
        foreach ($reports as $key=>$report) {
            $cat->add(format_string($report->name), new moodle_url('/local/intelliboard/instructor/reports.php',array('id'=>format_string($key))), navigation_node::TYPE_CUSTOM);
        }
    }
}

function local_intelliboard_user_details()
{
	$platform = "Unknown OS Platform";
	$browser = "Unknown Browser";

		try {
			$regexes = local_intelliboard_get_regexes();
			$agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
	    foreach ($regexes->os_parsers as $regex) {
	        $flag = isset($regex->regex_flag) ? $regex->regex_flag : '';
	        if (preg_match('@' . $regex->regex . '@' . $flag, $agent, $matches)) {
	            $platform = (isset($regex->os_replacement))?str_replace('$1', $matches[1], $regex->os_replacement):$matches[1];
	            $platform .= ' '.((isset($regex->os_v1_replacement))?str_replace('$1', @$matches[2], $regex->os_v1_replacement):@$matches[2]);
	            break;
	        }
	    }
	    foreach ($regexes->user_agent_parsers as $regex) {
	        $flag = isset($regex->regex_flag) ? $regex->regex_flag : '';
	        if (preg_match('@' . $regex->regex . '@' . $flag, $agent, $matches)) {
	            $browser = (isset($regex->family_replacement))?str_replace('$1', $matches[1], @$regex->family_replacement):$matches[1];
	            break;
	        }
	    }
		} catch (Exception $e) {}

	if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])){
		$ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
	}elseif (isset($_SERVER["HTTP_CLIENT_IP"])){
		$ip = $_SERVER["HTTP_CLIENT_IP"];
	}else{
		$ip = $_SERVER["REMOTE_ADDR"];
	}
	$ip = ($ip) ? $ip : 0;
	if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
		$userlang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
	} else {
		$userlang = 'Unknown';
	}

	return array('useragent' => $browser, 'useros' => $platform, 'userip' => $ip, 'userlang' => $userlang);
}

function local_intelliboard_get_regexes(){
    global $CFG;

    return json_decode(file_get_contents($CFG->dirroot .'/local/intelliboard/classes/regexes.json'));
}

function local_intelliboard_insert_tracking($ajaxRequest = false) {
    global $CFG, $PAGE, $SITE, $DB, $USER;

	$version = get_config('local_intelliboard', 'version');
	$enabled = get_config('local_intelliboard', 'enabled');
	$ajax = (int) get_config('local_intelliboard', 'ajax');
	$inactivity = (int) get_config('local_intelliboard', 'inactivity');
	$trackadmin = get_config('local_intelliboard', 'trackadmin');
	$trackpoint = get_config('local_intelliboard', 'trackpoint');
	$intelliboardMediaTrack = get_config('local_intelliboard', 'trackmedia');
	$path = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';

	if (strpos($path,'cron.php') !== false) {
		return false;
	}

	if ($enabled and isloggedin() and !isguestuser()) {
		if (is_siteadmin() and !$trackadmin) {
			return false;
		}
		$intelliboardPage = (isset($_COOKIE['intelliboardPage'])) ? clean_param($_COOKIE['intelliboardPage'], PARAM_ALPHANUMEXT) : '';
		$intelliboardParam = (isset($_COOKIE['intelliboardParam'])) ? clean_param($_COOKIE['intelliboardParam'], PARAM_INT) : 0;
		$intelliboardTime = (isset($_COOKIE['intelliboardTime'])) ? clean_param($_COOKIE['intelliboardTime'], PARAM_INT) : 0;

		if (!empty($intelliboardPage) and !empty($intelliboardParam) and !empty($intelliboardTime)) {
			if ($data = $DB->get_record('local_intelliboard_tracking', array('userid' => $USER->id, 'page' => $intelliboardPage, 'param' => $intelliboardParam), 'id, visits, timespend, lastaccess')) {
				if (!$ajaxRequest) {
					$data->visits = $data->visits + 1;
          $data->lastaccess = time();
				} else {
				    if ($data->lastaccess < strtotime('today')) {
                $data->lastaccess = time();
            } else {
                unset($data->lastaccess);
            }
					unset($data->visits);
				}
				$data->timespend = $data->timespend + $intelliboardTime;
				$DB->update_record('local_intelliboard_tracking', $data);
			} else {
				$userDetails = (object)local_intelliboard_user_details();
				$courseid = 0;
				if ($intelliboardPage == "module") {
					$courseid = $DB->get_field_sql("SELECT c.id FROM {course} c, {course_modules} cm WHERE c.id = cm.course AND cm.id = $intelliboardParam");
				} elseif($intelliboardPage == "course") {
					$courseid = $intelliboardParam;
				}
				$data = new stdClass();
				$data->userid = $USER->id;
				$data->courseid = $courseid;
				$data->page = $intelliboardPage;
				$data->param = $intelliboardParam;
				$data->visits = 1;
				$data->timespend = $intelliboardTime;
				$data->firstaccess = time();
				$data->lastaccess = time();
				$data->useragent = $userDetails->useragent;
				$data->useros = $userDetails->useros;
				$data->userlang = $userDetails->userlang;
				$data->userip = $userDetails->userip;
				$data->id = $DB->insert_record('local_intelliboard_tracking', $data, true);
			}

			$tracklogs = get_config('local_intelliboard', 'tracklogs');
			$trackdetails = get_config('local_intelliboard', 'trackdetails');
			$tracktotals = get_config('local_intelliboard', 'tracktotals');

			if ($version >= 2016011300) {
				$currentstamp  = strtotime('today');
				if ($data->id and $tracklogs) {
					if ($log = $DB->get_record('local_intelliboard_logs', array('trackid' => $data->id, 'timepoint' => $currentstamp))) {
						if (!$ajaxRequest) {
							$log->visits = $log->visits + 1;
						}
						$log->timespend = $log->timespend + $intelliboardTime;
						$DB->update_record('local_intelliboard_logs', $log);
					} else {
						$log = new stdClass();
						$log->trackid = $data->id;
						$log->visits = 1;
						$log->timespend = $intelliboardTime;
						$log->timepoint = $currentstamp;
						$log->id = $DB->insert_record('local_intelliboard_logs', $log, true);
					}

					if ($version >= 2017072300 and isset($log->id) and $trackdetails) {
						$currenthour  = date('G');
						if ($detail = $DB->get_record('local_intelliboard_details', array('logid' => $log->id, 'timepoint' => $currenthour))) {
							if (!$ajaxRequest) {
								$detail->visits = $detail->visits + 1;
							}
							$detail->timespend = $detail->timespend + $intelliboardTime;
							$DB->update_record('local_intelliboard_details', $detail);
						} else {
							$detail = new stdClass();
							$detail->logid = $log->id;
							$detail->visits = 1;
							$detail->timespend = $intelliboardTime;
							$detail->timepoint = $currenthour;
							$detail->id = $DB->insert_record('local_intelliboard_details', $detail, true);
						}
					}
				}
				if ($tracktotals) {
					$sessions = false; $courses = false;

					if (!$ajaxRequest) {
						if ($trackpoint != $currentstamp) {
							set_config("trackpoint", $currentstamp, "local_intelliboard");

							$DB->delete_records('local_intelliboard_config');
						}
						if (!$DB->get_record('local_intelliboard_config', ['type'=>0, 'instanceid' => $USER->id])) {
							$sessions = new stdClass();
							$sessions->type = 0;
							$sessions->instanceid = (int) $USER->id;
							$sessions->timecreated = $currentstamp;
							$DB->insert_record('local_intelliboard_config', $sessions);
						}

						if ($intelliboardPage == 'course' and !$DB->get_record('local_intelliboard_config', ['type'=>1, 'instanceid' => $intelliboardParam])) {
							$courses = new stdClass();
							$courses->type = 1;
							$courses->instanceid = (int) $intelliboardParam;
							$courses->timecreated = $currentstamp;
							$DB->insert_record('local_intelliboard_config', $courses);
						}
					}

					if ($data = $DB->get_record('local_intelliboard_totals', array('timepoint' => $currentstamp))) {
						if (!$ajaxRequest) {
							$data->visits = $data->visits + 1;
						}
						if ($sessions) {
							$data->sessions = $data->sessions + 1;
						}
						if ($courses) {
							$data->courses = $data->courses + 1;
						}
						$data->timespend = $data->timespend + $intelliboardTime;
						$DB->update_record('local_intelliboard_totals', $data);
					} else {
						$data = new stdClass();
						$data->sessions = 1;
						$data->courses = ($courses)?1:0;
						$data->visits = 1;
						$data->timespend = $intelliboardTime;
						$data->timepoint = $currentstamp;
						$DB->insert_record('local_intelliboard_totals', $data);
					}
				}
			}
		}

		if ($ajaxRequest) {
			return ['time' => $intelliboardTime];
		}
		$page_url = isset($PAGE->url) ? $PAGE->url : '';

		if (isset($PAGE->cm->id)) {
			$intelliboardPage = 'module';
			$intelliboardParam = $PAGE->cm->id;
		} elseif(isset($PAGE->course->id) and $SITE->id != $PAGE->course->id) {
			$intelliboardPage = 'course';
			$intelliboardParam = $PAGE->course->id;
		} elseif(strpos($page_url, '/user/') !== false) {
			$intelliboardPage = 'user';
			$intelliboardParam = $USER->id;
		} elseif(strpos($page_url, '/intelliboard/student/courses') !== false) {
			$intelliboardPage = 'local_intelliboard';
			$intelliboardParam = 1;
		} elseif(strpos($page_url, '/intelliboard/student/grades') !== false) {
			$intelliboardPage = 'local_intelliboard';
			$intelliboardParam = 2;
		} elseif(strpos($page_url, '/intelliboard/student/reports') !== false) {
			$intelliboardPage = 'local_intelliboard';
			$intelliboardParam = 3;
		} elseif(strpos($page_url, '/intelliboard/student/monitors') !== false) {
			$intelliboardPage = 'local_intelliboard';
			$intelliboardParam = 4;
		} elseif(strpos($page_url, '/intelliboard/student/') !== false) {
			$intelliboardPage = 'local_intelliboard';
			$intelliboardParam = 5;
		} elseif(strpos($page_url, '/intelliboard/instructor/monitors') !== false) {
			$intelliboardPage = 'local_intelliboard';
			$intelliboardParam = 6;
		} elseif(strpos($page_url, '/intelliboard/instructor/reports') !== false) {
			$intelliboardPage = 'local_intelliboard';
			$intelliboardParam = 7;
		} elseif(strpos($page_url, '/intelliboard/instructor/courses') !== false) {
			$intelliboardPage = 'local_intelliboard';
			$intelliboardParam = 8;
		} elseif(strpos($page_url, '/intelliboard/instructor/') !== false) {
			$intelliboardPage = 'local_intelliboard';
			$intelliboardParam = 9;
		} elseif(strpos($page_url, '/intelliboard/competencies/') !== false) {
			$intelliboardPage = 'local_intelliboard';
			$intelliboardParam = 10;
		} elseif(strpos($page_url, '/intelliboard/monitors') !== false) {
			$intelliboardPage = 'local_intelliboard';
			$intelliboardParam = 11;
		} elseif(strpos($page_url, '/intelliboard/reports') !== false) {
			$intelliboardPage = 'local_intelliboard';
			$intelliboardParam = 12;
		} elseif(strpos($page_url, '/intelliboard/') !== false) {
			$intelliboardPage = 'local_intelliboard';
			$intelliboardParam = 1;
		}  elseif(strpos($page_url, '/local/') !== false) {
			$start = strpos($page_url, '/', strpos($page_url, '/local/') + 1) + 1;
			$end = strpos($page_url, '/', $start);
			$intelliboardPage = 'local_' . substr($page_url, $start, ($end - $start));
			$intelliboardParam = 1;
		} else {
			$intelliboardPage = 'site';
			$intelliboardParam = 1;
		}
		$params = new stdClass();
		$params->intelliboardAjax = $ajax;
		$params->intelliboardAjaxUrl = $ajax ? "$CFG->wwwroot/local/intelliboard/ajax.php" : "";
		$params->intelliboardInactivity = $inactivity;
		$params->intelliboardPeriod = 1000;
		$params->intelliboardPage = $intelliboardPage;
		$params->intelliboardParam = $intelliboardParam;
		$params->intelliboardMediaTrack = $intelliboardMediaTrack;
		$params->intelliboardTime = 0;
		$params->intelliboardSSOLink = (get_config('local_intelliboard', 'ssomenu')) ? $CFG->wwwroot.'/local/intelliboard/index.php?action=sso' : false;

		$PAGE->requires->js('/local/intelliboard/module.js', false);
		$PAGE->requires->js_init_call('intelliboardInit', array($params), false);

		return true;
	}
}
function local_intelliboard_init()
{
	$tracking = get_config('local_intelliboard', 'enabled');
	if ($tracking && !CLI_SCRIPT && !AJAX_SCRIPT) {
		local_intelliboard_insert_tracking();
	}
}
local_intelliboard_init();
