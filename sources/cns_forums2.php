<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2016

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    core_cns
 */

/**
 * Get a nice list for selection from the forum groupings.
 *
 * @param  ?AUTO_LINK $avoid Category to avoid putting in the list (null: don't avoid any).
 * @param  ?AUTO_LINK $it Category selected by default (null: no specific default).
 * @return Tempcode The list.
 */
function cns_create_selection_list_forum_groupings($avoid = null, $it = null)
{
    $_m = $GLOBALS['FORUM_DB']->query_select('f_forum_groupings', array('*'));
    $entries = new Tempcode();
    foreach ($_m as $m) {
        if ($m['id'] !== $avoid) {
            $entries->attach(form_input_list_entry(strval($m['id']), $it === $m['id'], $m['c_title']));
        }
    }

    return $entries;
}

/**
 * Get a nice, formatted XHTML list of topics, in forum tree structure
 *
 * @param  ?AUTO_LINK $it The currently selected topic (null: none selected)
 * @return Tempcode The list of topics
 */
function cns_create_selection_list_topic_tree($it = null)
{
    $tree = cns_get_topic_tree();

    $out = ''; // XHTMLXHTML
    foreach ($tree as $forum) {
        foreach ($forum['entries'] as $topic_id => $ttitle) {
            $selected = ($topic_id == $it);
            $line = do_template('CNS_FORUM_TOPIC_LIST_LINE', array('_GUID' => 'd58e4176ef0efefa85c83a8b9fa2de51', 'PRE' => $forum['breadcrumbs'], 'TOPIC_TITLE' => $ttitle));
            $out .= '<option value="' . strval($topic_id) . '"' . ($selected ? 'selected="selected"' : '') . '>' . $line->evaluate() . '</option>';
        }
    }

    if ($GLOBALS['XSS_DETECT']) {
        ocp_mark_as_escaped($out);
    }

    return make_string_tempcode($out);
}

/**
 * Get a list of maps containing all the topics, and path information, under the specified forum - and those beneath it, recursively.
 *
 * @param  ?AUTO_LINK $forum_id The forum being at the root of our recursion (null: true root forum)
 * @param  ?string $breadcrumbs The breadcrumbs up to this point in the recursion (null: blank, as we are starting the recursion)
 * @param  ?ID_TEXT $title The forum name of the $forum_id we are currently going through (null: look it up). This is here for efficiency reasons, as finding children IDs to recurse to also reveals the childs title
 * @param  ?integer $levels The number of recursive levels to search (null: all)
 * @return array A list of maps for all forums. Each map entry containins the fields 'id' (forum ID) and 'breadcrumbs' (path to the forum, including the forums own title), and more.
 */
function cns_get_topic_tree($forum_id = null, $breadcrumbs = null, $title = null, $levels = null)
{
    if (is_null($forum_id)) {
        $forum_id = db_get_first_id();
    }
    if (is_null($breadcrumbs)) {
        $breadcrumbs = '';
    }

    if (!has_category_access(get_member(), 'forums', strval($forum_id))) {
        return array();
    }

    // Put our title onto our breadcrumbs
    if (is_null($title)) {
        $title = $GLOBALS['FORUM_DB']->query_select_value('f_forums', 'f_name', array('id' => $forum_id));
    }
    $breadcrumbs .= $title;

    // We'll be putting all children in this entire tree into a single list
    $children = array();
    $children[0] = array();
    $children[0]['id'] = $forum_id;
    $children[0]['title'] = $title;
    $children[0]['breadcrumbs'] = $breadcrumbs;

    // Children of this forum
    $rows = $GLOBALS['FORUM_DB']->query_select('f_forums', array('id', 'f_name'), array('f_parent_forum' => $forum_id), 'ORDER BY f_forum_grouping_id,f_position', 200);
    if (count($rows) == 200) {
        $rows = array(); // Too many, this method will suck
    }
    $tmap = array('t_forum_id' => $forum_id);
    if ((!has_privilege(get_member(), 'see_unvalidated')) && (addon_installed('unvalidated'))) {
        $tmap['t_validated'] = 1;
    }
    $children[0]['entries'] = collapse_2d_complexity('id', 't_cache_first_title', $GLOBALS['FORUM_DB']->query_select('f_topics', array('id', 't_cache_first_title'), $tmap, 'ORDER BY t_cache_first_time DESC', 12));
    $children[0]['child_entry_count'] = count($children[0]['entries']);
    if ($levels === 0) { // We throw them away now because they're not on the desired level
        $children[0]['entries'] = array();
    }
    $children[0]['child_count'] = count($rows);
    $breadcrumbs .= ' > ';
    if ($levels !== 0) {
        foreach ($rows as $child) {
            $child_id = $child['id'];
            $child_title = $child['f_name'];
            $child_breadcrumbs = $breadcrumbs;

            $child_children = cns_get_topic_tree($child_id, $child_breadcrumbs, $child_title, is_null($levels) ? null : max(0, $levels - 1));

            $children = array_merge($children, $child_children);
        }
    }

    return $children;
}

/**
 * Generate a Tempcode tree based selection list for choosing a forum. Also capable of getting comma-separated ancestor forum lists.
 *
 * @param  ?MEMBER $member_id The member that the view privileges are done for (null: current member).
 * @param  ?AUTO_LINK $base_forum The forum we are starting from (null: capture the whole tree).
 * @param  ?array $selected_forum The forum(s) to select by default (null: no preference). An array of AUTO_LINK's (for IDs) or strings (for names).
 * @param  boolean $use_compound_list Whether to generate a compound list (a list of all the ancestors, for each point in the forum tree) as well as the tree.
 * @param  ?integer $levels The number of recursive levels to search (null: all)
 * @param  ?TIME $updated_since Time from which content must be updated (null: no limit).
 * @return Tempcode Forum selection list.
 */
function create_selection_list_forum_tree($member_id = null, $base_forum = null, $selected_forum = null, $use_compound_list = false, $levels = null, $updated_since = null)
{
    $tree = cns_get_forum_tree($member_id, $base_forum, '', null, null, $use_compound_list, $levels, $updated_since !== null, $updated_since);
    if ($use_compound_list) {
        list($tree) = $tree;
    }

    // Flatten out
    for ($i = 0; $i < count($tree); $i++) {
        array_splice($tree, $i + 1, 0, $tree[$i]['children']);
    }

    $real_out = '';
    foreach ($tree as $t) {
        if (($updated_since !== null) && (($t['updated_since'] === null) || ($t['updated_since'] < $updated_since))) {
            continue;
        }

        $selected = false;
        if (!is_null($selected_forum)) {
            foreach ($selected_forum as $s) {
                if ((is_integer($s)) && ($s == $t['id'])) {
                    $selected = true;
                }
                if ((is_string($s)) && ($s == $t['title'])) {
                    $selected = true;
                }
            }
        }

        $line = do_template('CNS_FORUM_LIST_LINE', array(
            '_GUID' => '2fb4bd9ed5c875de6155bef588c877f9',
            'PRE' => $t['breadcrumbs'],
            'NAME' => $t['title'],
            'CAT_BIT' => $t['second_cat'],
        ));

        $real_out .= '<option value="' . (!$use_compound_list ? strval($t['id']) : $t['compound_list']) . '"' . ($selected ? ' selected="selected"' : '') . '>' . $line->evaluate() . '</option>' . "\n";
    }

    if ($GLOBALS['XSS_DETECT']) {
        ocp_mark_as_escaped($real_out);
    }
    return make_string_tempcode($real_out);
}

/**
 * Generate a map of details for choosing a forum. Also capable of getting comma-separated ancestor forum lists.
 *
 * @param  ?MEMBER $member_id The member that the view privileges are done for (null: current member).
 * @param  ?AUTO_LINK $base_forum The forum we are starting from (null: capture the whole tree).
 * @param  string $breadcrumbs The breadcrumbs at this point of the recursion (blank for the start).
 * @param  ?AUTO_LINK $skip ID of a forum to skip display/recursion for (null: none).
 * @param  ?array $forum_details Details of the current forum in the recursion (null: find from DB).
 * @param  boolean $use_compound_list Whether to generate a compound list (a list of all the ancestors, for each point in the forum tree) as well as the tree.
 * @param  ?integer $levels The number of recursive levels to search (null: all)
 * @param  boolean $do_stats Whether to generate tree statistics.
 * @param  ?TIME $updated_since Time from which content must be updated (null: no limit).
 * @return array A list of maps, OR (if $use_compound_list) a pair of the Tempcode and the compound list.
 */
function cns_get_forum_tree($member_id = null, $base_forum = null, $breadcrumbs = '', $skip = null, $forum_details = null, $use_compound_list = false, $levels = null, $do_stats = false, $updated_since = null)
{
    if (($levels == -1) && (!$use_compound_list)) {
        return $use_compound_list ? array(array(), '') : array();
    }

    global $FORUM_TREE_SECURE_CACHE;

    if (is_null($member_id)) {
        $member_id = get_member();
    }

    if (is_null($forum_details)) {
        if (is_null($base_forum)) {
            $forum_details = array('f_order_sub_alpha' => 0); // Optimisation
        } else {
            $_forum_details = $GLOBALS['FORUM_DB']->query_select('f_forums', array('f_order_sub_alpha'), array('id' => $base_forum), '', 1);
            if (!array_key_exists(0, $_forum_details)) {
                warn_exit(do_lang_tempcode('MISSING_RESOURCE', 'forum'));
            }
            $forum_details = $_forum_details[0];
        }
    }
    $order_sub_alpha = $forum_details['f_order_sub_alpha'];

    $out = array();
    $order = $order_sub_alpha ? 'f_name' : 'f_position,id';
    $forums = array();
    if (is_null($FORUM_TREE_SECURE_CACHE)) {
        $FORUM_TREE_SECURE_CACHE = mixed();
        $num_forums = $GLOBALS['FORUM_DB']->query_select_value('f_forums', 'COUNT(*)');
        $FORUM_TREE_SECURE_CACHE = ($num_forums >= 300); // Mark it as 'huge'
    }
    if ($FORUM_TREE_SECURE_CACHE === true) {
        $forums = $GLOBALS['FORUM_DB']->query('SELECT id,f_order_sub_alpha,f_name,f_forum_grouping_id,f_parent_forum,f_position,f_cache_last_time FROM ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_forums WHERE id IS NOT NULL AND ' . db_string_equal_to('f_redirection', '') . ' AND ' . (is_null($base_forum) ? 'f_parent_forum IS NULL' : ('f_parent_forum=' . strval($base_forum))) . ' ORDER BY f_position,f_name', intval(get_option('general_safety_listing_limit'))/*reasonable limit*/);
    } else {
        if ((is_null($FORUM_TREE_SECURE_CACHE)) || ($FORUM_TREE_SECURE_CACHE === false)) {
            $FORUM_TREE_SECURE_CACHE = $GLOBALS['FORUM_DB']->query('SELECT id,f_order_sub_alpha,f_name,f_forum_grouping_id,f_parent_forum,f_position,f_cache_last_time FROM ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_forums WHERE id IS NOT NULL AND ' . db_string_equal_to('f_redirection', '') . ' ORDER BY f_position,f_name');
        }
        foreach ($FORUM_TREE_SECURE_CACHE as $x) {
            if ($x['f_parent_forum'] === $base_forum) {
                $forums[] = $x;
            }
        }
    }
    sort_maps_by($forums, $order);
    $compound_list = '';
    $child_breadcrumbs = ($breadcrumbs == '') ? '' : ($breadcrumbs . ' > ');
    foreach ($forums as $forum) {
        $access = has_category_access($member_id, 'forums', strval($forum['id']));
        $cat_sort_key = '!' . (is_null($forum['f_forum_grouping_id']) ? '' : strval($forum['f_forum_grouping_id']));

        if (($access) && ($skip !== $forum['id']) && ($levels !== 0)) {
            $cat_bit = '';
            if (!is_null($forum['f_forum_grouping_id'])) {
                global $FORUM_GROUPINGS_TITLES_CACHE;
                if (is_null($FORUM_GROUPINGS_TITLES_CACHE)) {
                    $FORUM_GROUPINGS_TITLES_CACHE = collapse_2d_complexity('id', 'c_title', $GLOBALS['FORUM_DB']->query_select('f_forum_groupings', array('id', 'c_title')));
                }
                $cat_bit = array_key_exists($forum['f_forum_grouping_id'], $FORUM_GROUPINGS_TITLES_CACHE) ? $FORUM_GROUPINGS_TITLES_CACHE[$forum['f_forum_grouping_id']] : do_lang('NA');
            }

            $below = cns_get_forum_tree($member_id, $forum['id'], $child_breadcrumbs, $skip, $forum, $use_compound_list, ($levels === null) ? null : ($levels - 1), $do_stats, $updated_since);
            if ($use_compound_list) {
                list($below, $_compound_list) = $below;
                $compound_list .= strval($forum['id']) . ',' . $_compound_list;
            }

            $child = array(
                'id' => $forum['id'],
                'title' => $forum['f_name'],
                'breadcrumbs' => $child_breadcrumbs,
                'compound_list' => (!$use_compound_list ? strval($forum['id']) : (strval($forum['id']) . ',' . $_compound_list)),
                'second_cat' => $cat_bit,
                'group' => $forum['f_forum_grouping_id'],
                'children' => $below,
            );
            if ($do_stats) {
                $child['child_count'] = $GLOBALS['FORUM_DB']->query_select_value('f_forums', 'COUNT(*)', array('f_parent_forum' => $forum['id']));
                $child['updated_since'] = $forum['f_cache_last_time'];
            }

            if (!array_key_exists($cat_sort_key, $out)) {
                $out[$cat_sort_key] = array();
            }
            $out[$cat_sort_key][] = $child;
        }
    }

    // Up to now we worked into an array, so we could benefit from how it would auto-sort into the grouping>forum-position ordering Composr uses. Now we need to unzip it
    $real_out = array();
    foreach ($out as $arr) {
        $real_out = array_merge($real_out, $arr);
    }

    if ($use_compound_list) {
        return array($real_out, $compound_list);
    } else {
        return $real_out;
    }
}