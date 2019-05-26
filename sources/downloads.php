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
 * @package    downloads
 */

/**
 * Standard code module initialisation function.
 *
 * @ignore
 */
function init__downloads()
{
    global $PT_PAIR_CACHE_D;
    $PT_PAIR_CACHE_D = array();
}

/**
 * Show a download licence for display
 */
function download_licence_script()
{
    $id = get_param_integer('id');

    $rows = $GLOBALS['SITE_DB']->query_select('download_licences', array('*'), array('id' => $id), '', 1);
    if (!array_key_exists(0, $rows)) {
        warn_exit(do_lang_tempcode('MISSING_RESOURCE', 'download_licence'));
    }
    $licence_title = $rows[0]['l_title'];
    $licence_text = $rows[0]['l_text'];
    $echo = do_template('STANDALONE_HTML_WRAP', array('_GUID' => 'd8f60d5f6f56b08589ed6f4b874dad85', 'TITLE' => $licence_title, 'POPUP' => true, 'CONTENT' => comcode_to_tempcode($licence_text, $GLOBALS['FORUM_DRIVER']->get_guest_id(), false)));
    $echo->evaluate_echo();
}

/**
 * Get Tempcode for a download 'feature box' for the given row
 *
 * @param  array $row The database field row of this download
 * @param  boolean $pic Whether to show a picture
 * @param  boolean $include_breadcrumbs Whether to show breadcrumbs
 * @param  ?ID_TEXT $zone The zone the download module we're using is in (null: find it)
 * @param  ?Tempcode $text_summary Text summary for result (e.g. highlighted portion of actual file from search result) (null: none)
 * @param  boolean $give_context Whether to include context (i.e. say WHAT this is, not just show the actual content)
 * @param  ?AUTO_LINK $root The virtual root (null: read from environment)
 * @param  ID_TEXT $guid Overridden GUID to send to templates (blank: none)
 * @return Tempcode A box for this download, linking to the full download page
 */
function render_download_box($row, $pic = true, $include_breadcrumbs = true, $zone = null, $text_summary = null, $give_context = true, $root = null, $guid = '')
{
    if (is_null($row)) { // Should never happen, but we need to be defensive
        return new Tempcode();
    }

    require_lang('downloads');
    require_css('downloads');
    require_code('files');

    if (is_null($zone)) {
        $zone = get_module_zone('downloads');
    }

    if (array_key_exists('id', $row)) {
        $just_download_row = db_map_restrict($row, array('id', 'description'));
    } else {
        $just_download_row = db_map_restrict($row, array('description'));
    }

    // Details
    $file_size = $row['file_size'];
    $file_size = ($file_size > 0) ? clean_file_size($file_size) : do_lang('UNKNOWN');
    $description = (is_string($row['description']) && !isset($row['description__text_parsed'])) ? comcode_to_tempcode($row['description']) : get_translated_tempcode('download_downloads', $just_download_row, 'description');
    if (array_key_exists('id', $row)) {
        $map = array('page' => 'downloads', 'type' => 'entry', 'id' => $row['id']);
        if (!is_null($root)) {
            $map['keep_download_root'] = ($root == db_get_first_id()) ? null : $root;
        }
        $view_url = build_url($map, $zone);
    } else {
        $view_url = new Tempcode();
    }
    $date = get_timezoned_date_tempcode($row['add_date'], false);
    $date_raw = $row['add_date'];

    $breadcrumbs = $include_breadcrumbs ? breadcrumb_segments_to_tempcode(download_breadcrumbs($row['category_id'], is_null($root) ? get_param_integer('keep_download_root', null) : $root, false, $zone)) : new Tempcode();

    // Download has image?
    $pic_suffix = '';
    $thumb_url = '';
    $full_img_url = '';
    if ((addon_installed('galleries')) && ($pic) && (array_key_exists('id', $row))) {
        // Images
        $rows = $GLOBALS['SITE_DB']->query_select('images', array('url', 'thumb_url', 'id'), array('cat' => 'download_' . strval($row['id'])), '', 1, $row['default_pic'] - 1);
        if (array_key_exists(0, $rows)) {
            $pic_suffix = '_pic';
            require_code('images');
            $full_img_url = $rows[0]['url'];
            $thumb_url = ensure_thumbnail($rows[0]['url'], $rows[0]['thumb_url'], 'galleries', 'images', $rows[0]['id']);
            $imgcode = do_image_thumb($thumb_url, do_lang('THUMBNAIL'));
        } else {
            $imgcode = new Tempcode();
        }
    } else {
        $imgcode = new Tempcode();
    }

    // Rating
    require_code('feedback');
    $rating = ($row['allow_rating'] == 1 && array_key_exists('id', $row)) ? display_rating($view_url, is_string($row['name']) ? $row['name'] : get_translated_text($row['name']), 'downloads', strval($row['id']), 'RATING_INLINE_STATIC', $row['submitter']) : null;
    if (!is_null($rating)) {
        if (trim($rating->evaluate()) == '') {
            $rating = null;
        }
    }

    // Licensing
    $licence_title = null;
    $licence_url = null;
    $licence_hyperlink = null;
    $licence = $row['download_licence'];
    if (!is_null($licence)) {
        $licence_title = $GLOBALS['SITE_DB']->query_select_value_if_there('download_licences', 'l_title', array('id' => $licence));
        if (!is_null($licence_title)) {
            $keep = symbol_tempcode('KEEP');
            $licence_url = find_script('download_licence') . '?id=' . strval($licence) . $keep->evaluate();
            $licence_hyperlink = do_template('HYPERLINK_POPUP_WINDOW', array('_GUID' => ($guid != '') ? $guid : '58a9e5c99bd236290009b6eab44dbac3', 'TITLE' => '', 'CAPTION' => $licence_title, 'URL' => $licence_url, 'WIDTH' => '600', 'HEIGHT' => '500', 'REL' => 'license'));
        } else {
            $licence = null; // Orphaned
        }
    }

    $may_download = has_privilege(get_member(), 'download', 'downloads', array(strval($row['category_id'])));

    if (array_key_exists('id', $row)) {
        $download_url = generate_dload_url($row['id'], $row['url_redirect'] != '');
    } else {
        $download_url = new Tempcode();
    }

    // Final template
    if (($full_img_url != '') && (url_is_local($full_img_url))) {
        $full_img_url = get_custom_base_url() . '/' . $full_img_url;
    }
    return do_template('DOWNLOAD_BOX', array(
        '_GUID' => ($guid != '') ? $guid : '7a4737e21bdb4bd15ac5fe8570915d08',
        'ORIGINAL_FILENAME' => $row['original_filename'],
        'GIVE_CONTEXT' => $give_context,
        'TEXT_SUMMARY' => $text_summary,
        'AUTHOR' => $row['author'],
        'ID' => array_key_exists('id', $row) ? strval($row['id']) : '',
        'RATING' => $rating,
        'VIEWS' => integer_format($row['download_views']),
        'SUBMITTER' => strval($row['submitter']),
        'DESCRIPTION' => $description,
        'FILE_SIZE' => $file_size,
        'DOWNLOADS' => integer_format($row['num_downloads']),
        'DATE_RAW' => strval($date_raw),
        'DATE' => $date,
        'EDIT_DATE_RAW' => is_null($row['edit_date']) ? '' : strval($row['edit_date']),
        'URL' => $view_url,
        'NAME' => is_string($row['name']) ? $row['name'] : get_translated_text($row['name']),
        'BREADCRUMBS' => $breadcrumbs,
        'IMG_URL' => $thumb_url,
        'FULL_IMG_URL' => $full_img_url,
        'IMGCODE' => $imgcode,
        'LICENCE' => is_null($licence) ? null : strval($licence),
        'LICENCE_TITLE' => $licence_title,
        'LICENCE_HYPERLINK' => $licence_hyperlink,
        'MAY_DOWNLOAD' => $may_download,
        'DOWNLOAD_URL' => $download_url,
    ));
}

/**
 * Get Tempcode for a download category 'feature box' for the given row
 *
 * @param  array $row The database field row of it
 * @param  ID_TEXT $zone The zone to use
 * @param  boolean $give_context Whether to include context (i.e. say WHAT this is, not just show the actual content)
 * @param  boolean $include_breadcrumbs Whether to include breadcrumbs (if there are any)
 * @param  ?AUTO_LINK $root Virtual root to use (null: none)
 * @param  boolean $attach_to_url_filter Whether to copy through any filter parameters in the URL, under the basis that they are associated with what this box is browsing
 * @param  ID_TEXT $guid Overridden GUID to send to templates (blank: none)
 * @return Tempcode A box for it, linking to the full page
 */
function render_download_category_box($row, $zone = '_SEARCH', $give_context = true, $include_breadcrumbs = true, $root = null, $attach_to_url_filter = false, $guid = '')
{
    if (is_null($row)) { // Should never happen, but we need to be defensive
        return new Tempcode();
    }

    require_lang('downloads');

    $map = array('page' => 'downloads', 'type' => 'browse', 'id' => ($row['id'] == db_get_first_id()) ? null : $row['id']);
    if (!is_null($root)) {
        $map['keep_download_root'] = $root;
    }
    if ($attach_to_url_filter) {
        $map += propagate_filtercode();
    }
    $url = build_url($map, $zone);

    $_title = get_translated_text($row['category']);
    $title = $give_context ? do_lang('CONTENT_IS_OF_TYPE', do_lang('DOWNLOAD_CATEGORY'), $_title) : $_title;

    $breadcrumbs = mixed();
    if ($include_breadcrumbs) {
        $breadcrumbs = breadcrumb_segments_to_tempcode(download_breadcrumbs($row['parent_id'], is_null($root) ? get_param_integer('keep_download_root', null) : $root, false, $zone, $attach_to_url_filter));
    }

    $just_download_category_row = db_map_restrict($row, array('id', 'description'));

    $summary = get_translated_tempcode('download_downloads', $just_download_category_row, 'description');

    $child_counts = count_download_category_children($row['id']);
    $num_children = $child_counts['num_children_children'];
    $num_entries = $child_counts['num_downloads_children'];
    $entry_details = do_lang_tempcode('CATEGORY_SUBORDINATE', escape_html(integer_format($num_entries)), escape_html(integer_format($num_children)));

    // Image
    $img = $row['rep_image'];
    $rep_image = mixed();
    $_rep_image = mixed();
    if ($img != '') {
        require_code('images');
        $_rep_image = $img;
        $rep_image = do_image_thumb($img, $_title, false);
    }

    return do_template('SIMPLE_PREVIEW_BOX', array(
        '_GUID' => ($guid != '') ? $guid : '4074a20248289c28cde8201272627129',
        'ID' => strval($row['id']),
        'BREADCRUMBS' => $breadcrumbs,
        'TITLE' => $title,
        'TITLE_PLAIN' => $_title,
        'SUMMARY' => $summary,
        '_REP_IMAGE' => $_rep_image,
        'REP_IMAGE' => $rep_image,
        'ENTRY_DETAILS' => $entry_details,
        'URL' => $url,
        'FRACTIONAL_EDIT_FIELD_NAME' => $give_context ? null : 'category',
        'FRACTIONAL_EDIT_FIELD_URL' => $give_context ? null : '_SEARCH:cms_downloads:__edit_category:' . strval($row['id']),
        'RESOURCE_TYPE' => 'download_category',
    ));
}

/**
 * Get a nice, formatted XHTML list of downloads, in download tree structure
 *
 * @param  ?AUTO_LINK $it The currently selected entry (null: none selected)
 * @param  ?AUTO_LINK $submitter Only show entries submitted by this member (null: no filter)
 * @param  ?AUTO_LINK $shun Download we do not want to show (null: none to not show)
 * @param  boolean $use_compound_list Whether to get a list of child categories (not just direct ones, recursively), instead of just IDs
 * @param  boolean $editable_filter Whether to only show for what may be edited by the current member
 * @return Tempcode The list of entries
 */
function create_selection_list_downloads_tree($it = null, $submitter = null, $shun = null, $use_compound_list = false, $editable_filter = false)
{
    $tree = get_downloads_tree($submitter, null, null, null, $shun, null, $use_compound_list, $editable_filter);
    if ($use_compound_list) {
        $tree = $tree[0];
    }

    $out = ''; // XHTMLXHMTML
    foreach ($tree as $category) {
        foreach ($category['entries'] as $eid => $etitle) {
            $selected = ($eid == $it);
            $line = do_template('DOWNLOAD_LIST_LINE', array('_GUID' => '7bb13e4418b500cb2b330e629710138a', 'BREADCRUMBS' => $category['breadcrumbs'], 'DOWNLOAD' => $etitle));
            $out .= '<option value="' . (!$use_compound_list ? strval($eid) : $category['compound_list']) . '"' . ($selected ? ' selected="selected"' : '') . '>' . $line->evaluate() . '</option>';
        }
    }

    if ($GLOBALS['XSS_DETECT']) {
        ocp_mark_as_escaped($out);
    }

    return make_string_tempcode($out);
}

/**
 * Get a list of maps containing all the downloads, and path information, under the specified category - and those beneath it, recursively.
 *
 * @param  ?MEMBER $submitter Only show images/videos submitted by this member (null: no filter)
 * @param  ?AUTO_LINK $category_id The category being at the root of our recursion (null: true root)
 * @param  ?string $breadcrumbs The breadcrumbs up to this point in the recursion (null: blank, as we are starting the recursion)
 * @param  ?ID_TEXT $title The name of the $category_id we are currently going through (null: look it up). This is here for efficiency reasons, as finding children IDs to recurse to also reveals the childs title
 * @param  ?integer $shun The number of recursive levels to search (null: all)
 * @param  ?AUTO_LINK $levels Download we do not want to show (null: none to not show)
 * @param  boolean $use_compound_list Whether to get a list of child categories (not just direct ones, recursively), instead of just IDs
 * @param  boolean $editable_filter Whether to only show for what may be edited by the current member
 * @param  boolean $tar_filter Whether to only show entries that are TAR files (addons)
 * @return array A list of maps for all categories. Each map entry containins the fields 'id' (category ID) and 'breadcrumbs' (to the category, including the categories own title), and more. Or if $use_compound_list, the tree structure built with pairs containing the compound list in addition to the child branches
 */
function get_downloads_tree($submitter = null, $category_id = null, $breadcrumbs = null, $title = null, $shun = null, $levels = null, $use_compound_list = false, $editable_filter = false, $tar_filter = false)
{
    if (is_null($category_id)) {
        $category_id = db_get_first_id();
    }

    if (!has_category_access(get_member(), 'downloads', strval($category_id))) {
        return array();
    }

    if (is_null($breadcrumbs)) {
        $breadcrumbs = '';
    }

    // Put our title onto our breadcrumbs
    if (is_null($title)) {
        $title = get_translated_text($GLOBALS['SITE_DB']->query_select_value('download_categories', 'category', array('id' => $category_id)));
    }
    $breadcrumbs .= $title;

    $compound_list = strval($category_id) . ',';

    // We'll be putting all children in this entire tree into a single list
    $children = array();
    $children[0] = array();
    $children[0]['id'] = $category_id;
    $children[0]['title'] = $title;
    $children[0]['breadcrumbs'] = $breadcrumbs;

    // Children of this category
    $rows = $GLOBALS['SITE_DB']->query_select('download_categories', array('id', 'category'), array('parent_id' => $category_id), 'ORDER BY ' . $GLOBALS['SITE_DB']->translate_field_ref('category') . ' ASC', intval(get_option('general_safety_listing_limit'))/*reasonable limit*/);
    if (count($rows) == intval(get_option('general_safety_listing_limit'))) {
        $rows = array();
    }
    $where = array('category_id' => $category_id);
    if (!is_null($submitter)) {
        $where['submitter'] = $submitter;
    }
    $erows = $GLOBALS['SITE_DB']->query_select('download_downloads', array('id', 'name', 'submitter', 'original_filename'), $where, 'ORDER BY ' . $GLOBALS['SITE_DB']->translate_field_ref('name') . ' ASC', intval(get_option('general_safety_listing_limit'))/*reasonable limit*/);
    if (count($erows) == intval(get_option('general_safety_listing_limit'))) {
        $erows = $GLOBALS['SITE_DB']->query_select('download_downloads', array('id', 'name', 'submitter', 'original_filename'), $where, 'ORDER BY add_date DESC', intval(get_option('general_safety_listing_limit'))/*reasonable limit*/);
    }
    $children[0]['entries'] = array();
    foreach ($erows as $row) {
        if (($tar_filter) && (substr(strtolower($row['original_filename']), -4) != '.tar')) {
            continue;
        }
        if (($editable_filter) && (!has_edit_permission('mid', get_member(), $row['submitter'], 'cms_downloads', array('download_downloads', $category_id)))) {
            continue;
        }
        if ((!is_null($shun)) && ($shun == $row['id'])) {
            continue;
        }

        $children[0]['entries'][$row['id']] = get_translated_text($row['name']);
    }
    $children[0]['child_entry_count'] = count($children[0]['entries']);
    if ($levels === 0) { // We throw them away now because they're not on the desired level
        $children[0]['entries'] = array();
    }
    $children[0]['child_count'] = count($rows);
    $breadcrumbs .= ' > ';
    if (($levels !== 0) || ($use_compound_list)) {
        foreach ($rows as $child) {
            $child_id = $child['id'];
            $child_title = get_translated_text($child['category']);
            $child_breadcrumbs = $breadcrumbs;

            $child_children = get_downloads_tree($submitter, $child_id, $child_breadcrumbs, $child_title, $shun, is_null($levels) ? null : max(0, $levels - 1), $use_compound_list, $editable_filter, $tar_filter);
            if ($use_compound_list) {
                list($child_children, $_compound_list) = $child_children;
                $compound_list .= $_compound_list;
            }

            if ($levels !== 0) {
                $children = array_merge($children, $child_children);
            }
        }
    }
    $children[0]['compound_list'] = $compound_list;

    return $use_compound_list ? array($children, $compound_list) : $children;
}

/**
 * Get a nice, formatted XHTML list extending from the root, and showing all subcategories, and their subcategories (ad infinitum). The tree bit is because each entry in the list is shown to include the path through the tree that gets to it
 *
 * @param  ?AUTO_LINK $it The currently selected category (null: none selected)
 * @param  boolean $use_compound_list Whether to make the list elements store comma-separated child lists instead of IDs
 * @param  boolean $addable_filter Whether to only show for what may be added to by the current member
 * @param  ?TIME $updated_since Time from which content must be updated (null: no limit).
 * @return Tempcode The list of categories
 */
function create_selection_list_download_category_tree($it = null, $use_compound_list = false, $addable_filter = false, $updated_since = null)
{
    $tree = get_download_category_tree(null, null, null, $updated_since !== null, $use_compound_list, null, $addable_filter);
    if ($use_compound_list) {
        $tree = $tree[0];
    }

    $out = ''; // XHTMLXHTML
    foreach ($tree as $category) {
        if (($addable_filter) && (!$category['addable'])) {
            continue;
        }

        if (($updated_since !== null) && (($category['updated_since'] === null) || ($category['updated_since'] < $updated_since))) {
            continue;
        }

        $selected = ($category['id'] == $it);
        $line = do_template('DOWNLOAD_LIST_LINE_2', array('_GUID' => '0ccffeff5b80b1840188b839aee8d9f2', 'BREADCRUMBS' => $category['breadcrumbs'], 'FILECOUNT' => '?'));
        $out .= '<option value="' . (!$use_compound_list ? strval($category['id']) : $category['compound_list']) . '"' . ($selected ? ' selected="selected"' : '') . '>' . $line->evaluate() . '</option>' . "\n";
    }

    if ($GLOBALS['XSS_DETECT']) {
        ocp_mark_as_escaped($out);
    }

    return make_string_tempcode($out);
}

/**
 * Get a list of maps containing all the subcategories, and path information, of the specified category - and those beneath it, recursively.
 *
 * @param  ?AUTO_LINK $category_id The category being at the root of our recursion (null: true root category)
 * @param  ?string $breadcrumbs The breadcrumbs up to this point in the recursion (null: blank, as we are starting the recursion)
 * @param  ?array $category_info The category row of the $category_id we are currently going through (null: look it up). This is here for efficiency reasons, as finding children IDs to recurse to also reveals the childs details
 * @param  boolean $do_stats Whether to collect download counts with our tree information
 * @param  boolean $use_compound_list Whether to make a compound list (a pair of a comma-separated list of children, and the child array)
 * @param  ?integer $levels The number of recursive levels to search (null: all)
 * @param  boolean $addable_filter Whether to only show for what may be added to by the current member
 * @return array A list of maps for all subcategories. Each map entry containins the fields 'id' (category ID) and 'breadcrumbs' (path to the category, including the categories own title). There is also an additional 'downloadcount' entry if stats were requested
 */
function get_download_category_tree($category_id = null, $breadcrumbs = null, $category_info = null, $do_stats = false, $use_compound_list = false, $levels = null, $addable_filter = false)
{
    if (!$use_compound_list) {
        if ($levels == -1) {
            return array();
        }
    }

    if (is_null($category_id)) {
        $category_id = db_get_first_id();
    }
    if (is_null($breadcrumbs)) {
        $breadcrumbs = '';
    }

    if (!has_category_access(get_member(), 'downloads', strval($category_id))) {
        return $use_compound_list ? array(array(), '') : array();
    }

    if (is_null($category_info)) {
        $_category_info = $GLOBALS['SITE_DB']->query_select('download_categories', array('*'), array('id' => $category_id), '', 1);
        if (!array_key_exists(0, $_category_info)) {
            warn_exit(do_lang_tempcode('MISSING_RESOURCE', 'download_category'));
        }
        $category_info = $_category_info[0];
    }

    $title = get_translated_text($category_info['category']);
    $breadcrumbs .= $title;

    // We'll be putting all children in this entire tree into a single list
    $children = array();
    $children[0] = array();
    $children[0]['id'] = $category_id;
    $children[0]['title'] = $title;
    $children[0]['breadcrumbs'] = $breadcrumbs;
    $children[0]['compound_list'] = strval($category_id) . ',';
    if ($addable_filter) {
        $children[0]['addable'] = has_submit_permission('mid', get_member(), get_ip_address(), 'cms_downloads', array('downloads', $category_id));
    }
    if ($do_stats) {
        $stats = $GLOBALS['SITE_DB']->query_select('download_downloads', array('COUNT(*) AS downloads_count', 'MAX(add_date) AS updated_since'), array('category_id' => $category_id));
        $children[0] += $stats[0];
    }

    // Children of this category
    $rows = $GLOBALS['SITE_DB']->query_select('download_categories', array('id', 'category'), array('parent_id' => $category_id), '', intval(get_option('general_safety_listing_limit'))/*reasonable*/);
    if (count($rows) == intval(get_option('general_safety_listing_limit'))) {
        $rows = array();
    }
    $children[0]['child_count'] = count($rows);
    $child_breadcrumbs = ($breadcrumbs == '') ? '' : ($breadcrumbs . ' > ');
    if (($levels !== 0) || ($use_compound_list)) {
        foreach ($rows as $child) {
            $child_id = $child['id'];

            $child_children = get_download_category_tree($child_id, $child_breadcrumbs, $child, $do_stats, $use_compound_list, is_null($levels) ? null : max(0, $levels - 1), $addable_filter);
            if ($use_compound_list) {
                list($child_children, $_compound_list) = $child_children;
                $children[0]['compound_list'] .= $_compound_list;
            }

            if ($levels !== 0) {
                $children = array_merge($children, $child_children);
            }
        }
    }

    return $use_compound_list ? array($children, $children[0]['compound_list']) : $children;
}

/**
 * Get a nice, formatted XHTML list to select a download licence
 *
 * @param  ?AUTO_LINK $it The currently selected licence (null: none selected)
 * @param  boolean $allow_na Whether to allow an N/A selection
 * @return Tempcode The list of categories
 */
function create_selection_list_download_licences($it = null, $allow_na = false)
{
    $list = new Tempcode();
    if ($allow_na) {
        $list->attach(form_input_list_entry('-1', false, do_lang_tempcode('NA_EM')));
    }
    $rows = $GLOBALS['SITE_DB']->query_select('download_licences', array('id', 'l_title'));
    foreach ($rows as $row) {
        $list->attach(form_input_list_entry(strval($row['id']), $it == $row['id'], $row['l_title']));
    }
    return $list;
}

/**
 * Get a formatted XHTML string of the route back to the specified root, from the specified category.
 *
 * @param  AUTO_LINK $category_id The category we are finding for
 * @param  ?AUTO_LINK $root The root of the tree (null: the true root)
 * @param  boolean $no_link_for_me_sir Whether to include category links at this level (the recursed levels will always contain links - the top level is optional, hence this parameter)
 * @param  ?ID_TEXT $zone The zone the download module we're using is in (null: find it)
 * @param  boolean $attach_to_url_filter Whether to copy through any filter parameters in the URL, under the basis that they are associated with what this box is browsing
 * @return Tempcode The breadcrumbs
 * @return array The breadcrumb segments
 */
function download_breadcrumbs($category_id, $root = null, $no_link_for_me_sir = true, $zone = null, $attach_to_url_filter = false)
{
    if (is_null($root)) {
        $root = db_get_first_id();
    }
    if (is_null($zone)) {
        $zone = get_module_zone('downloads');
    }

    $map = array('page' => 'downloads', 'type' => 'browse', 'id' => ($category_id == db_get_first_id()) ? null : $category_id, 'keep_download_root' => ($root == db_get_first_id()) ? null : $root);
    if (get_page_name() == 'downloads') {
        $map += propagate_filtercode();
    }
    $page_link = build_page_link($map, $zone);

    if (($category_id == $root) || ($category_id == db_get_first_id())) {
        if ($no_link_for_me_sir) {
            return array();
        }
        $title = get_translated_text($GLOBALS['SITE_DB']->query_select_value('download_categories', 'category', array('id' => $category_id)));
        return array(array($page_link, $title));
    }

    global $PT_PAIR_CACHE_D;
    if (!array_key_exists($category_id, $PT_PAIR_CACHE_D)) {
        $category_rows = $GLOBALS['SITE_DB']->query_select('download_categories', array('parent_id', 'category'), array('id' => $category_id), '', 1);
        if (!array_key_exists(0, $category_rows)) {
            //warn_exit(do_lang_tempcode('CAT_NOT_FOUND', escape_html(strval($category_id)), 'download_category'));
            return array();
        }
        $PT_PAIR_CACHE_D[$category_id] = $category_rows[0];
    }

    $title = get_translated_text($PT_PAIR_CACHE_D[$category_id]['category']);
    $segments = array();
    if (!$no_link_for_me_sir) {
        $segments[] = array($page_link, $title);
    }

    if ($PT_PAIR_CACHE_D[$category_id]['parent_id'] == $category_id) {
        fatal_exit(do_lang_tempcode('RECURSIVE_TREE_CHAIN', escape_html(strval($category_id)), 'download_category'));
    }

    $below = download_breadcrumbs($PT_PAIR_CACHE_D[$category_id]['parent_id'], $root, false, $zone, $attach_to_url_filter);

    return array_merge($below, $segments);
}

/**
 * Count the downloads and subcategories underneath the specified category, recursively.
 *
 * @param  AUTO_LINK $category_id The ID of the category for which count details are collected
 * @return array The number of downloads is returned in $output['num_downloads'], and the number of subcategories is returned in $output['num_children'], and the (possibly recursive) number of downloads is returned in $output['num_downloads_children'].
 */
function count_download_category_children($category_id)
{
    static $total_categories = null;
    if (is_null($total_categories)) {
        $total_categories = $GLOBALS['SITE_DB']->query_select_value('download_categories', 'COUNT(*)');
    }

    $out = array();
    $out['num_children'] = $GLOBALS['SITE_DB']->query_select_value('download_categories', 'COUNT(*)', array('parent_id' => $category_id));
    $out['num_downloads'] = $GLOBALS['SITE_DB']->query_select_value('download_downloads', 'COUNT(*)', array('category_id' => $category_id, 'validated' => 1));

    if ($category_id == db_get_first_id()) {
        $out['num_children_children'] = $GLOBALS['SITE_DB']->query_select_value('download_categories', 'COUNT(*)') - 1;
        $out['num_downloads_children'] = $GLOBALS['SITE_DB']->query_select_value('download_downloads', 'COUNT(*)', array('validated' => 1));
    } else {
        $out['num_children_children'] = $out['num_children'];
        $out['num_downloads_children'] = $out['num_downloads'];

        if ($total_categories < 200) { // Make sure not too much, performance issue
            $rows = $GLOBALS['SITE_DB']->query_select('download_categories', array('id'), array('parent_id' => $category_id));
            foreach ($rows as $child) {
                $temp = count_download_category_children($child['id']);
                $out['num_downloads_children'] += $temp['num_downloads_children'];
                $out['num_children_children'] += $temp['num_children_children'];
            }
        }
    }

    return $out;
}

/**
 * Generate a link to a Composr download.
 *
 * @param  AUTO_LINK $id The ID of the download to be downloaded
 * @param  boolean $use_gateway Whether to use the gateway script
 * @return Tempcode The URL
 */
function generate_dload_url($id, $use_gateway)
{
    if (get_option('immediate_downloads') == '1') {
        $use_gateway = false;
    }

    $keep = symbol_tempcode('KEEP', array('0', '1'));

    if ($use_gateway) {
        $download_url = make_string_tempcode(find_script('download_gateway'));
    } else {
        $download_url = make_string_tempcode(find_script('dload'));
    }

    $download_url->attach('?id=' . strval($id));
    $download_url->attach($keep);

    if (get_option('anti_leech') == '1') {
        $download_url->attach('&for_session=');
        $download_url->attach(symbol_tempcode('SESSION_HASHED'));
    }

    return $download_url;
}