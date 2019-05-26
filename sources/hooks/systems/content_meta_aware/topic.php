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
 * Hook class.
 */
class Hook_content_meta_aware_topic
{
    /**
     * Get content type details. Provides information to allow task reporting, randomisation, and add-screen linking, to function.
     *
     * @param  ?ID_TEXT $zone The zone to link through to (null: autodetect).
     * @param  boolean $get_extended_data Populate additional data that is somewhat costly to compute (add_url, archive_url).
     * @return ?array Map of award content-type info (null: disabled).
     */
    public function info($zone = null, $get_extended_data = false)
    {
        if (get_forum_type() != 'cns' || !isset($GLOBALS['FORUM_DB'])) {
            return null;
        }

        if (($zone === null) && ($get_extended_data)) {
            $zone = get_module_zone('forumview');
            if (is_null($zone)) {
                return null;
            }
        }

        return array(
            'support_custom_fields' => true,

            'content_type_label' => 'cns:FORUM_TOPIC',
            'content_type_universal_label' => 'Forum topic',

            'connection' => $GLOBALS['FORUM_DB'],
            'table' => 'f_topics',
            'id_field' => 'id',
            'id_field_numeric' => true,
            'parent_category_field' => 't_forum_id',
            'parent_category_meta_aware_type' => 'forum',
            'is_category' => true,
            'is_entry' => true,
            'category_field' => 't_forum_id', // For category permissions
            'category_type' => 'forums', // For category permissions
            'parent_spec__table_name' => 'f_forums',
            'parent_spec__parent_name' => 'f_parent_forum',
            'parent_spec__field_name' => 'id',
            'category_is_string' => false,

            'title_field' => 't_cache_first_title',
            'title_field_post' => 'title',
            'title_field_dereference' => false,
            'title_field__resource_fs' => 't_cache_first_title',
            'title_field_dereference__resource_fs' => false,
            /*'title_field__resource_fs' => 't_description',
            'title_field_dereference__resource_fs' => false,*/
            'description_field' => 't_description',
            'thumb_field' => 't_emoticon',
            'thumb_field_is_theme_image' => true,
            'alternate_icon_theme_image' => 'icons/48x48/menu/social/forum/forums',

            'view_page_link_pattern' => '_SEARCH:topicview:browse:_WILD',
            'edit_page_link_pattern' => '_SEARCH:topics:edit_topic:_WILD',
            'edit_page_link_pattern_post' => '_SEARCH:topics:_edit_topic:_WILD',
            'view_category_page_link_pattern' => '_SEARCH:forumview:browse:_WILD',
            'add_url' => null,
            'archive_url' => $get_extended_data ? ($zone . ':forumview') : null,

            'support_url_monikers' => true,

            'views_field' => 't_num_views',
            'order_field' => null,
            'submitter_field' => 't_cache_first_member_id',
            'author_field' => null,
            'add_time_field' => 't_cache_first_time',
            'edit_time_field' => 't_cache_last_time',
            'date_field' => 't_cache_first_time',
            'validated_field' => 't_validated',

            'seo_type_code' => 'topic',

            'feedback_type_code' => null,

            'permissions_type_code' => 'forums', // null if has no permissions

            'search_hook' => 'cns_posts',
            'rss_hook' => 'cns_forumview',
            'attachment_hook' => null,
            'unvalidated_hook' => 'cns_topics',
            'notification_hook' => 'cns_topic',
            'sitemap_hook' => 'topic',

            'addon_name' => 'cns_forum',

            'cms_page' => 'topics',
            'module' => 'topicview',

            'commandr_filesystem_hook' => 'forums',
            'commandr_filesystem__is_folder' => true,

            'support_revisions' => true,

            'support_privacy' => false,

            'support_content_reviews' => false,

            'actionlog_regexp' => '\w+_TOPIC',
        );
    }

    /**
     * Run function for content hooks. Renders a content box for an award/randomisation.
     *
     * @param  array $row The database row for the content
     * @param  ID_TEXT $zone The zone to display in
     * @param  boolean $give_context Whether to include context (i.e. say WHAT this is, not just show the actual content)
     * @param  boolean $include_breadcrumbs Whether to include breadcrumbs (if there are any)
     * @param  ?ID_TEXT $root Virtual root to use (null: none)
     * @param  boolean $attach_to_url_filter Whether to copy through any filter parameters in the URL, under the basis that they are associated with what this box is browsing
     * @param  ID_TEXT $guid Overridden GUID to send to templates (blank: none)
     * @return Tempcode Results
     */
    public function run($row, $zone, $give_context = true, $include_breadcrumbs = true, $root = null, $attach_to_url_filter = false, $guid = '')
    {
        require_code('cns_topics');

        return render_topic_box($row, $zone, $give_context, $include_breadcrumbs, is_null($root) ? null : intval($root), $guid);
    }
}