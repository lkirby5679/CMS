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
 * Render a member profile.
 *
 * @param  Tempcode $title Screen title
 * @param  MEMBER $member_id_of The ID of the member who is being viewed
 * @param  ?MEMBER $member_id_viewing The ID of the member who is doing the viewing (null: current member)
 * @param  ?ID_TEXT $username The username of the member who is being viewed (null: work out from member_id_of)
 * @return Tempcode The rendered profile
 */
function render_profile_tabset($title, $member_id_of, $member_id_viewing = null, $username = null)
{
    if (is_null($member_id_viewing)) {
        $member_id_viewing = get_member();
    }

    if (is_null($username)) {
        $username = $GLOBALS['FORUM_DRIVER']->get_username($member_id_of);
        if ((is_null($username)) || (is_guest($member_id_of))) {
            warn_exit(do_lang_tempcode('MEMBER_NO_EXIST'));
        }
    }

    $tabs = array();

    $only_tab = get_param_string('only_tab', null);

    $hooks = find_all_hooks('systems', 'profiles_tabs');
    if (isset($hooks['edit'])) { // Editing must go first, so changes reflect in the renders of the tabs
        $hooks = array('edit' => $hooks['edit']) + $hooks;
    }
    foreach (array_keys($hooks) as $hook) {
        if (($only_tab === null) || (preg_match('#(^|,)' . preg_quote($hook, '#') . '(,|$)#', $only_tab) != 0)) {
            require_code('hooks/systems/profiles_tabs/' . $hook);
            $ob = object_factory('Hook_profiles_tabs_' . $hook);
            if ($ob->is_active($member_id_of, $member_id_viewing)) {
                $tabs[$hook] = $ob->render_tab($member_id_of, $member_id_viewing, (preg_match('#(^|,)' . preg_quote($hook, '#') . '(,|$)#', $only_tab) == 0) && !browser_matches('ie6') && !browser_matches('ie7') && has_js());
            }
        }
    }

    if (!is_null($only_tab)) {
        $_unsorted = $tabs;
        $tabs = array();
        foreach (explode(',', $only_tab) as $tab) {
            if (isset($_unsorted[$tab])) {
                $tabs[$tab] = $_unsorted[$tab];
            }
        }
    } else {
        sort_maps_by($tabs, 2);
    }

    require_javascript('profile');
    require_javascript('ajax');

    // AJAX should load up any scripts embedding in tabs without an issue, but some browsers or optimisers (e.g. Cloudflare) may have issues - so we'll load stuff here
    $scripts = array(
        'ajax',
        'ajax_people_lists',
        'checking',
        'editing',
        'multi',
        'notifications',
        'posting',
        'tree_list',
        'modernizr',
        'jquery',
        'jquery_ui',
        'widget_color',
        'widget_date',
    );
    foreach ($scripts as $script) {
        require_javascript($script);
    }

    $_tabs = array();
    $i = 0;
    foreach ($tabs as $hook => $tab) {
        if ($only_tab === $hook) {
            $title = get_screen_title($tab[0], false);
        }

        if ($tab[1] !== null) {
            //$tab[1]->handle_symbol_preprocessing();
            $tab[1] = $tab[1]->evaluate(); // So that SETs run early, thus things can be moved outside tabs
        }
        $_tabs[] = array('TAB_TITLE' => $tab[0], 'TAB_CODE' => $hook, 'TAB_ICON' => $tab[3], 'TAB_CONTENT' => $tab[1], 'TAB_FIRST' => $i == 0, 'TAB_LAST' => $i + 1 == count($tabs));
        $i++;
    }

    return do_template('CNS_MEMBER_PROFILE_SCREEN', array('_GUID' => '2f33348714723492105c4717974c8f4c', 'TITLE' => $title, 'TABS' => $_tabs, 'MEMBER_ID' => strval($member_id_of)));
}