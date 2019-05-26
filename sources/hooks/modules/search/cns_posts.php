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
 * @package    cns_forum
 */

/**
 * Hook class.
 */
class Hook_search_cns_posts extends FieldsSearchHook
{
    /**
     * Find details for this search hook.
     *
     * @param  boolean $check_permissions Whether to check permissions
     * @param  ?MEMBER $member_id The member ID to check with (null: current member)
     * @return ~?array Map of search hook details (null: hook is disabled) (false: access denied)
     */
    public function info($check_permissions = true, $member_id = null)
    {
        if ($member_id === null) {
            $member_id = get_member();
        }

        if (get_forum_type() != 'cns') {
            return null;
        }

        if ($check_permissions) {
            if (!has_actual_page_access($member_id, 'topicview')) {
                return false;
            }
        }

        if ($GLOBALS['FORUM_DRIVER']->get_num_forum_posts() == 0) {
            return null;
        }

        require_lang('cns');

        $info = array();
        $info['lang'] = do_lang_tempcode('FORUM_POSTS');
        $info['default'] = false;
        $info['special_on'] = array();
        $info['special_off'] = array('open' => do_lang_tempcode('POST_SEARCH_OPEN'), 'closed' => do_lang_tempcode('POST_SEARCH_CLOSED'), 'pinned' => do_lang_tempcode('POST_SEARCH_PINNED'), 'starter' => do_lang_tempcode('POST_SEARCH_STARTER'));
        if ((has_privilege($member_id, 'see_unvalidated')) && (addon_installed('unvalidated'))) {
            $info['special_off']['unvalidated'] = do_lang_tempcode('POST_SEARCH_UNVALIDATED');
        }
        $info['category'] = 'p_cache_forum_id';
        $info['integer_category'] = true;
        $info['extra_sort_fields'] = $this->_get_extra_sort_fields('_post');

        $info['permissions'] = array(
            array(
                'type' => 'zone',
                'zone_name' => get_module_zone('topicview'),
            ),
            array(
                'type' => 'page',
                'zone_name' => get_module_zone('topicview'),
                'page_name' => 'topicview',
            ),
        );

        return $info;
    }

    /**
     * Get details for an ajax-tree-list of entries for the content covered by this search hook.
     *
     * @return array A pair: the hook, and the options
     */
    public function ajax_tree()
    {
        return array('choose_forum', array('compound_list' => true));
    }

    /**
     * Get a list of extra fields to ask for.
     *
     * @return ?array A list of maps specifying extra fields (null: no tree)
     */
    public function get_fields()
    {
        return $this->_get_fields('_post');
    }

    /**
     * Run function for search results.
     *
     * @param  string $content Search string
     * @param  boolean $only_search_meta Whether to only do a META (tags) search
     * @param  ID_TEXT $direction Order direction
     * @param  integer $max Start position in total results
     * @param  integer $start Maximum results to return in total
     * @param  boolean $only_titles Whether only to search titles (as opposed to both titles and content)
     * @param  string $content_where Where clause that selects the content according to the main search string (SQL query fragment) (blank: full-text search)
     * @param  SHORT_TEXT $author Username/Author to match for
     * @param  ?MEMBER $author_id Member-ID to match for (null: unknown)
     * @param  mixed $cutoff Cutoff date (TIME or a pair representing the range)
     * @param  string $sort The sort type (gets remapped to a field in this function)
     * @set    title add_date
     * @param  integer $limit_to Limit to this number of results
     * @param  string $boolean_operator What kind of boolean search to do
     * @set    or and
     * @param  string $where_clause Where constraints known by the main search code (SQL query fragment)
     * @param  string $search_under Comma-separated list of categories to search under
     * @param  boolean $boolean_search Whether it is a boolean search
     * @return array List of maps (template, orderer)
     */
    public function run($content, $only_search_meta, $direction, $max, $start, $only_titles, $content_where, $author, $author_id, $cutoff, $sort, $limit_to, $boolean_operator, $where_clause, $search_under, $boolean_search)
    {
        if (in_array($content, array(
            do_lang('POSTS_WITHIN_TOPIC'),
            do_lang('SEARCH_POSTS_WITHIN_TOPIC'),
            do_lang('SEARCH_FORUM_POSTS'),
            do_lang('_SEARCH_PRIVATE_TOPICS'),
        ))) {
            return array(); // Search placeholder label, not real search
        }

        if (get_forum_type() != 'cns') {
            return array();
        }
        require_code('cns_forums');
        require_code('cns_posts');
        require_css('cns');

        $remapped_orderer = '';
        switch ($sort) {
            case 'title':
                $remapped_orderer = 'p_title';
                break;

            case 'add_date':
                $remapped_orderer = 'p_time';
                break;
        }

        require_lang('cns');

        // Calculate our where clause (search)
        $sq = build_search_submitter_clauses('p_poster', $author_id, $author);
        if (is_null($sq)) {
            return array();
        } else {
            $where_clause .= $sq;
        }
        $this->_handle_date_check($cutoff, 'p_time', $where_clause);
        if (get_param_integer('option_cns_posts_unvalidated', 0) == 1) {
            $where_clause .= ' AND ';
            $where_clause .= 'r.p_validated=0';
        }
        if (get_param_integer('option_cns_posts_open', 0) == 1) {
            $where_clause .= ' AND ';
            $where_clause .= 's.t_is_open=1';
        }
        if (get_param_integer('option_cns_posts_closed', 0) == 1) {
            $where_clause .= ' AND ';
            $where_clause .= 's.t_is_open=0';
        }
        if (get_param_integer('option_cns_posts_pinned', 0) == 1) {
            $where_clause .= ' AND ';
            $where_clause .= 's.t_pinned=1';
        }
        if (get_param_integer('option_cns_posts_starter', 0) == 1) {
            $where_clause .= ' AND ';
            $where_clause .= 's.t_cache_first_post_id=r.id';
        }
        $where_clause .= ' AND ';
        $where_clause .= 'p_cache_forum_id IS NOT NULL AND (p_intended_solely_for IS NULL';
        if (!is_guest()) {
            $where_clause .= ' OR (p_intended_solely_for=' . strval(get_member()) . ' OR p_poster=' . strval(get_member()) . ')';
        }
        $where_clause .= ')';

        if ((!has_privilege(get_member(), 'see_unvalidated')) && (addon_installed('unvalidated'))) {
            $where_clause .= ' AND ';
            $where_clause .= 'p_validated=1';
        }

        $table = 'f_posts r JOIN ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_topics s ON r.p_topic_id=s.id';
        $trans_fields = array('!' => '!', 'r.p_post' => 'LONG_TRANS__COMCODE');
        $nontrans_fields = array('r.p_title'/*,'s.t_description' Performance problem due to how full text works*/);
        $this->_get_search_parameterisation_advanced_for_content_type('_post', $table, $where_clause, $trans_fields, $nontrans_fields);

        // Calculate and perform query
        $rows = get_search_rows(null, null, $content, $boolean_search, $boolean_operator, $only_search_meta, $direction, $max, $start, $only_titles, $table, $trans_fields, $where_clause, $content_where, $remapped_orderer, 'r.*,t_forum_id,t_cache_first_title', $nontrans_fields, 'forums', 't_forum_id');

        $out = array();
        foreach ($rows as $i => $row) {
            $out[$i]['data'] = $row;
            unset($rows[$i]);
            if (($remapped_orderer != '') && (array_key_exists($remapped_orderer, $row))) {
                $out[$i]['orderer'] = $row[$remapped_orderer];
            } elseif (strpos($remapped_orderer, '_rating:') !== false) {
                $out[$i]['orderer'] = $row[$remapped_orderer];
            }
        }

        return $out;
    }

    /**
     * Run function for rendering a search result.
     *
     * @param  array $row The data row stored when we retrieved the result
     * @return Tempcode The output
     */
    public function render($row)
    {
        global $SEARCH__CONTENT_BITS, $LAX_COMCODE;
        $highlight_bits = ($SEARCH__CONTENT_BITS === null) ? array() : $SEARCH__CONTENT_BITS;
        $LAX_COMCODE = true;
        $summary = get_translated_text($row['p_post']);
        $text_summary_h = comcode_to_tempcode($summary, null, false, null, null, null, false, false, false, false, false, $highlight_bits);
        $LAX_COMCODE = false;
        $text_summary = generate_text_summary($text_summary_h->evaluate(), $highlight_bits);

        require_code('cns_posts2');
        return render_post_box($row, false, true, true, null, '', protect_from_escaping($text_summary));
    }
}
