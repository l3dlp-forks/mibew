<?php
/*
 * Copyright 2005-2013 the original author or authors.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

// Import namespaces and classes of the core
use Mibew\Database;
use Mibew\Settings;

function group_by_id($id)
{
	$db = Database::getInstance();
	$group = $db->query(
		"select * from {chatgroup} where groupid = ?",
		array($id),
		array('return_rows' => Database::RETURN_ONE_ROW)
	);
	return $group;
}

function get_group_name($group)
{
	if (HOME_LOCALE == CURRENT_LOCALE || !isset($group['vccommonname']) || !$group['vccommonname'])
		return $group['vclocalname'];
	else
		return $group['vccommonname'];
}

/**
 * Builds list of group settings tabs. The keys are tabs titles and the values
 * are tabs URLs.
 *
 * @param int $gid ID of the group whose settings page is displayed.
 * @param int $active Number of the active tab. The count starts from 0.
 * @return array Tabs list
 */
function setup_group_settings_tabs($gid, $active) {
	$tabs = array();

	if ($gid) {
		$tabs = array(
			getlocal("page_group.tab.main") => $active != 0 ? (MIBEW_WEB_ROOT . "/operator/group.php?gid=$gid") : "",
			getlocal("page_group.tab.members") => $active != 1 ? (MIBEW_WEB_ROOT . "/operator/groupmembers.php?gid=$gid") : "",
		);
	}

	return $tabs;
}

function get_operator_groupslist($operatorid)
{
	$db = Database::getInstance();
	if (Settings::get('enablegroups') == '1') {
		$groupids = array(0);
		$allgroups = $db->query(
			"select groupid from {chatgroupoperator} where operatorid = ? order by groupid",
			array($operatorid),
			array('return_rows' => Database::RETURN_ALL_ROWS)
		);
		foreach ($allgroups as $g) {
			$groupids[] = $g['groupid'];
		}
		return implode(",", $groupids);
	} else {
		return "";
	}
}

function get_available_parent_groups($skipgroup)
{
	$db = Database::getInstance();
	$groupslist = $db->query(
		"select {chatgroup}.groupid as groupid, parent, vclocalname " .
		"from {chatgroup} order by vclocalname",
		NULL,
		array('return_rows' => Database::RETURN_ALL_ROWS)
	);
	$result = array(array('groupid' => '', 'level' => '', 'vclocalname' => getlocal("form.field.groupparent.root")));

	if ($skipgroup) {
		$skipgroup = (array)$skipgroup;
	} else {
		$skipgroup = array();
	}

	$result = array_merge($result, get_sorted_child_groups_($groupslist, $skipgroup, 0) );
	return $result;
}

function group_has_children($groupid)
{
	$db = Database::getInstance();
	$children = $db->query(
		"select COUNT(*) as count from {chatgroup} where parent = ?",
		array($groupid),
		array('return_rows' => Database::RETURN_ONE_ROW)
	);
	return ($children['count'] > 0);
}

function get_top_level_group($group)
{
	return is_null($group['parent'])?$group:group_by_id($group['parent']);
}

/**
 * Try to load email for specified group or for its parent.
 * @param int $group_id Group id
 * @return string|boolean Email address or false if there is no email
 */
function get_group_email($group_id) {
	// Try to get group email
	$group = group_by_id($group_id);
	if ($group && !empty($group['vcemail'])) {
		return $group['vcemail'];
	}

	// Try to get parent group email
	if (! is_null($group['parent'])) {
		$group = group_by_id($group['parent']);
		if ($group && !empty($group['vcemail'])) {
			return $group['vcemail'];
		}
	}

	// There is no email
	return false;
}

/**
 * Check if group online
 *
 * @param array $group Associative group array. Should contain 'ilastseen' key.
 * @return bool
 */
function group_is_online($group) {
	return ($group['ilastseen'] !== NULL
		&& $group['ilastseen'] < Settings::get('online_timeout'));
}

/**
 * Return local or common group description depending on current locale.
 *
 * @param array $group Associative group array. Should contain following keys:
 *  - 'vccommondescription': string, contain common description of the group;
 *  - 'vclocaldescription': string, contain local description of the group.
 * @return string Group description
 */
function get_group_description($group) {
	if (HOME_LOCALE == CURRENT_LOCALE
			|| !isset($group['vccommondescription'])
			|| !$group['vccommondescription']) {
		return $group['vclocaldescription'];
	} else {
		return $group['vccommondescription'];
	}
}

?>