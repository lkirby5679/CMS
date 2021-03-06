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
 * @package    core_forum_drivers
 */

/**
 * Base class for IPB forum drivers.
 *
 * @package    core_forum_drivers
 */

/**
 * Forum driver class.
 *
 * @package    core_forum_drivers
 */
class Forum_driver_ipb_shared extends Forum_driver_base
{
    /**
     * Check the connected DB is valid for this forum driver.
     *
     * @return boolean Whether it is valid
     */
    public function check_db()
    {
        $test = $this->connection->query('SELECT COUNT(*) FROM ' . $this->connection->get_table_prefix() . 'groups', null, null, true);
        return !is_null($test);
    }

    /**
     * Get the rows for the top given number of posters on the forum.
     *
     * @param  integer $limit The limit to the number of top posters to fetch
     * @return array The rows for the given number of top posters in the forum
     */
    public function get_top_posters($limit)
    {
        return $this->connection->query('SELECT * FROM ' . $this->connection->get_table_prefix() . 'members WHERE id<>' . strval($this->get_guest_id()) . ' ORDER BY posts DESC', $limit);
    }

    /**
     * Attempt to to find the member's language from their forum profile. It converts between language-identifiers using a map (lang/map.ini).
     *
     * @param  MEMBER $member The member who's language needs to be fetched
     * @return ?LANGUAGE_NAME The member's language (null: unknown)
     */
    public function forum_get_lang($member)
    {
        $lang = $this->get_member_row_field($member, 'language');
        if (!is_string($lang)) {
            return null;
        }
        return $lang;
    }

    /**
     * Escape a value for HTML embedding, IPB style.
     *
     * @param  string $val The value to escape
     * @return string The escaped value
     */
    public function ipb_escape($val)
    {
        $val = str_replace('&#032;', '', $val);
        $val = str_replace('&', '&amp;', $val);
        $val = str_replace('<!--', '&#60;&#33;--', $val);
        $val = str_replace('-->', '--&#62;', $val);
        $val = preg_replace('/<script/i', '&#60;script', $val);
        $val = str_replace('>', '&gt;', $val);
        $val = str_replace('<', '&lt;', $val);
        $val = str_replace('\"', '&quot;', $val);
        $val = preg_replace("/\n/", '<br>', $val);
        $val = preg_replace('/\\$/', '&#036;', $val);
        $val = preg_replace('/\r/', '', $val);
        $val = str_replace('!', '&#33;', $val);
        $val = str_replace('\'', '&#39;', $val);
        $val = preg_replace('/\\\(?!&amp;#|\?#)/', '&#092;', $val);

        return $val;
    }

    /**
     * preg callback.
     *
     * @param  array $matches Matches
     * @return string Replacement
     */
    public function unentity_1($matches)
    {
        $x = hexdec($matches[1]);
        if ($x >= 128) {
            return '?';
        }
        return chr($x);
    }

    /**
     * preg callback.
     *
     * @param  array $matches Matches
     * @return string Replacement
     */
    public function unentity_2($matches)
    {
        $x = intval($matches[1]);
        if ($x >= 128) {
            return '?';
        }
        return chr($x);
    }

    /**
     * Unescape from IPB escaping.
     *
     * @param  string $val The value to unescape
     * @return string The unescaped value
     */
    public function ipb_unescape($val)
    {
        $val = @html_entity_decode($val, ENT_QUOTES, get_charset());

        $val = preg_replace_callback('/&#x([0-9a-f]+);/i', array($this, 'unentity_1'), $val);
        $val = preg_replace_callback('/&#([0-9]+);/', array($this, 'unentity_2'), $val);

        return $val;
    }

    /**
     * Find if the login cookie contains the login name instead of the member ID.
     *
     * @return boolean Whether the login cookie contains a login name or a member ID
     */
    public function is_cookie_login_name()
    {
        return false;
    }

    /**
     * Find if login cookie is md5-hashed.
     *
     * @return boolean Whether the login cookie is md5-hashed
     */
    public function is_hashed()
    {
        return true;
    }

    /**
     * Find the member ID of the forum guest member.
     *
     * @return MEMBER The member ID of the forum guest member
     */
    public function get_guest_id()
    {
        return 0;
    }

    /**
     * Get the forums' table prefix for the database.
     *
     * @return string The forum database table prefix
     */
    public function get_drivered_table_prefix()
    {
        global $SITE_INFO;
        return array_key_exists('ipb_table_prefix', $SITE_INFO) ? $SITE_INFO['ipb_table_prefix'] : 'ibf_';
    }

    /**
     * Get an array of attributes to take in from the installer. Almost all forums require a table prefix, which the requirement there-of is defined through this function.
     * The attributes have 4 values in an array
     * - name, the name of the attribute for _config.php
     * - default, the default value (perhaps obtained through autodetection from forum config)
     * - description, a textual description of the attributes
     * - title, a textual title of the attribute
     *
     * @return array The attributes for the forum
     */
    public function install_specifics()
    {
        global $PROBED_FORUM_CONFIG;
        $a = array();
        $a['name'] = 'ipb_table_prefix';
        $a['default'] = array_key_exists('sql_tbl_prefix', $PROBED_FORUM_CONFIG) ? $PROBED_FORUM_CONFIG['sql_tbl_prefix'] : 'ibf_';
        $a['description'] = do_lang('MOST_DEFAULT');
        $a['title'] = 'IPB ' . do_lang('TABLE_PREFIX');
        return array($a);
    }

    /**
     * Get an emoticon chooser template.
     *
     * @param  string $field_name The ID of the form field the emoticon chooser adds to
     * @return Tempcode The emoticon chooser template
     */
    public function get_emoticon_chooser($field_name = 'post')
    {
        require_code('comcode_compiler');
        $emoticons = $this->connection->query_select('emoticons', array('*'), array('clickable' => 1));
        $em = new Tempcode();
        foreach ($emoticons as $emo) {
            $code = $this->ipb_unescape($emo['typed']);
            $em->attach(do_template('EMOTICON_CLICK_CODE', array('_GUID' => '0d84b3bc399b53c6dda24ae6369e641d', 'FIELD_NAME' => $field_name, 'CODE' => $code, 'IMAGE' => apply_emoticons($code))));
        }

        return $em;
    }

    /**
     * Pin a topic.
     *
     * @param  AUTO_LINK $id The topic ID
     * @param  boolean $pin True: pin it, False: unpin it
     */
    public function pin_topic($id, $pin = true)
    {
        $this->connection->query_update('topics', array('pinned' => $pin ? 1 : 0), array('tid' => $id), '', 1);
    }

    /**
     * From a member row, get the member's primary usergroup.
     *
     * @param  array $r The profile-row
     * @return GROUP The member's primary usergroup
     */
    public function mrow_group($r)
    {
        return $r['mgroup'];
    }

    /**
     * From a member row, get the member's member ID.
     *
     * @param  array $r The profile-row
     * @return MEMBER The member ID
     */
    public function mrow_id($r)
    {
        return $r['id'];
    }

    /**
     * From a member row, get the member's last visit date.
     *
     * @param  array $r The profile-row
     * @return TIME The last visit date
     */
    public function mrow_lastvisit($r)
    {
        return $r['last_visit'];
    }

    /**
     * From a member row, get the member's e-mail address.
     *
     * @param  array $r The profile-row
     * @return SHORT_TEXT The member e-mail address
     */
    public function mrow_email($r)
    {
        return $r['email'];
    }

    /**
     * Get a URL to the specified member's home (control panel).
     *
     * @param  MEMBER $id The member ID
     * @return URLPATH The URL to the members home
     */
    public function member_home_url($id)
    {
        return get_forum_base_url() . '/index.php?act=UserCP&CODE=00';
    }

    /**
     * Get a URL to the specified member's profile.
     *
     * @param  MEMBER $id The member ID
     * @return URLPATH The URL to the member profile
     */
    protected function _member_profile_url($id)
    {
        return get_forum_base_url() . '/index.php?showuser=' . strval($id);
    }

    /**
     * Get a URL to the registration page (for people to create member accounts).
     *
     * @return URLPATH The URL to the registration page
     */
    protected function _join_url()
    {
        return get_forum_base_url() . '/index.php?act=Reg&CODE=00';
    }

    /**
     * Get a URL to the members-online page.
     *
     * @return URLPATH The URL to the members-online page
     */
    protected function _users_online_url()
    {
        return get_forum_base_url() . '/index.php?act=Online&CODE=listall';
    }

    /**
     * Get a URL to send a private/personal message to the given member.
     *
     * @param  MEMBER $id The member ID
     * @return URLPATH The URL to the private/personal message page
     */
    protected function _member_pm_url($id)
    {
        return get_forum_base_url() . '/index.php?act=Msg&CODE=04&MID=' . strval($id);
    }

    /**
     * Get a URL to the specified forum.
     *
     * @param  integer $id The forum ID
     * @return URLPATH The URL to the specified forum
     */
    protected function _forum_url($id)
    {
        return get_forum_base_url() . '/index.php?showforum=' . strval($id);
    }

    /**
     * Get the forum ID from a forum name.
     *
     * @param  SHORT_TEXT $forum_name The forum name
     * @return ?integer The forum ID (null: not found)
     */
    public function forum_id_from_name($forum_name)
    {
        return is_numeric($forum_name) ? intval($forum_name) : $this->connection->query_select_value_if_there('forums', 'id', array('name' => $this->ipb_escape($forum_name)));
    }

    /**
     * Get the topic ID from a topic identifier in the specified forum. It is used by comment topics, which means that the unique-topic-name assumption holds valid.
     *
     * @param  string $forum The forum name / ID
     * @param  SHORT_TEXT $topic_identifier The topic identifier
     * @return ?integer The topic ID (null: not found)
     */
    public function find_topic_id_for_topic_identifier($forum, $topic_identifier)
    {
        if (is_integer($forum)) {
            $forum_id = $forum;
        } else {
            $forum_id = $this->forum_id_from_name($forum);
        }
        $query = 'SELECT tid FROM ' . $this->connection->get_table_prefix() . 'topics WHERE forum_id=' . strval($forum_id);
        $query .= ' AND (' . db_string_equal_to('description', $topic_identifier) . ' OR description LIKE \'%: #' . db_encode_like($topic_identifier) . '\')';

        return $this->connection->query_value_if_there($query);
    }

    /**
     * Get a URL to the specified topic ID. Most forums don't require the second parameter, but some do, so it is required in the interface.
     *
     * @param  integer $id The topic ID
     * @param  string $forum The forum ID
     * @return URLPATH The URL to the topic
     */
    public function topic_url($id, $forum)
    {
        return get_forum_base_url() . '/index.php?showtopic=' . strval($id) . '&view=getnewpost';
    }

    /**
     * Get a URL to the specified post ID.
     *
     * @param  integer $id The post ID
     * @param  string $forum The forum ID
     * @return URLPATH The URL to the post
     */
    public function post_url($id, $forum)
    {
        $topic_id = $this->connection->query_select_value_if_there('posts', 'topic_id', array('pid' => $id));
        if (is_null($topic_id)) {
            return '?';
        }
        $url = get_forum_base_url() . '/index.php?act=findpost&pid=' . strval($id);
        return $url;
    }

    /**
     * Get an array of members who are in at least one of the given array of usergroups.
     *
     * @param  array $groups The array of usergroups
     * @param  ?integer $max Return up to this many entries for primary members and this many entries for secondary members (null: no limit, only use no limit if querying very restricted usergroups!)
     * @param  integer $start Return primary members after this offset and secondary members after this offset
     * @return ?array The array of members (null: no members)
     */
    public function member_group_query($groups, $max = null, $start = 0)
    {
        $_groups = '';
        foreach ($groups as $group) {
            if ($_groups != '') {
                $_groups .= ' OR ';
            }
            $_groups .= 'mgroup=' . strval($group);
        }
        return $this->connection->query('SELECT * FROM ' . $this->connection->get_table_prefix() . 'members WHERE ' . $_groups . ' ORDER BY mgroup,id ASC', $max, $start);
    }

    /**
     * This is the opposite of the get_next_member function.
     *
     * @param  MEMBER $member The member ID to decrement
     * @return ?MEMBER The previous member ID (null: no previous member)
     */
    public function get_previous_member($member)
    {
        $tempid = $this->connection->query_value_if_there('SELECT id FROM ' . $this->connection->get_table_prefix() . 'members WHERE id<' . strval($member) . ' AND id<>0 ORDER BY id DESC');
        return $tempid;
    }

    /**
     * Get the member ID of the next member after the given one, or null.
     * It cannot be assumed there are no gaps in member IDs, as members may be deleted.
     *
     * @param  MEMBER $member The member ID to increment
     * @return ?MEMBER The next member ID (null: no next member)
     */
    public function get_next_member($member)
    {
        $tempid = $this->connection->query_value_if_there('SELECT id FROM ' . $this->connection->get_table_prefix() . 'members WHERE id>' . strval($member) . ' ORDER BY id');
        return $tempid;
    }

    /**
     * Try to find a member with the given IP address
     *
     * @param  IP $ip The IP address
     * @return array The distinct rows found
     */
    public function probe_ip($ip)
    {
        $a = $this->connection->query_select('members', array('DISTINCT id'), array('ip_address' => $ip));
        $b = $this->connection->query_select('posts', array('DISTINCT author_id AS id'), array('ip_address' => $ip));
        return array_merge($a, $b);
    }

    /**
     * Get the e-mail address for the specified member ID.
     *
     * @param  MEMBER $member The member ID
     * @return SHORT_TEXT The e-mail address
     */
    protected function _get_member_email_address($member)
    {
        return $this->get_member_row_field($member, 'email');
    }

    /**
     * Get the photo thumbnail URL for the specified member ID.
     *
     * @param  MEMBER $member The member ID
     * @return URLPATH The URL (blank: none)
     */
    public function get_member_photo_url($member)
    {
        $pic = $this->connection->query_select_value_if_there('member_extra', 'photo_location', array('id' => $member));
        if (is_null($pic)) {
            $pic = '';
        } elseif ((url_is_local($pic)) && ($pic != '')) {
            $pic = get_forum_base_url() . '/uploads/' . $pic;
        }

        return $pic;
    }

    /**
     * Find if this member may have e-mails sent to them
     *
     * @param  MEMBER $member The member ID
     * @return boolean Whether the member may have e-mails sent to them
     */
    public function get_member_email_allowed($member)
    {
        $v = $this->get_member_row_field($member, 'email_pm');
        if ($v == 1) {
            return true;
        }
        return false;
    }

    /**
     * Get the timestamp of a member's join date.
     *
     * @param  MEMBER $member The member ID
     * @return TIME The timestamp
     */
    public function get_member_join_timestamp($member)
    {
        return $this->get_member_row_field($member, 'joined');
    }

    /**
     * Get the given member's post count.
     *
     * @param  MEMBER $member The member ID
     * @return integer The post count
     */
    public function get_post_count($member)
    {
        $c = $this->get_member_row_field($member, 'posts');
        if (is_null($c)) {
            return 0;
        }
        return $c;
    }

    /**
     * Get the given member's topic count.
     *
     * @param  MEMBER $member The member ID
     * @return integer The topic count
     */
    public function get_topic_count($member)
    {
        return $this->connection->query_select_value('topics', 'COUNT(*)', array('starter_id' => $member));
    }

    /**
     * Find out if the given member ID is banned.
     *
     * @param  MEMBER $member The member ID
     * @return boolean Whether the member is banned
     */
    public function is_banned($member)
    {
        // Are they banned
        $banned = $this->connection->query_select_value_if_there('groups', 'g_id', array('g_view_board' => 0));
        if (is_null($banned)) {
            return false;
        }
        $group = $this->get_member_row_field($member, 'mgroup');
        if (($group == $banned) || (is_null($group))) {
            return true;
        }

        return false;
    }

    /**
     * Find if the specified member ID is marked as staff or not.
     *
     * @param  MEMBER $member The member ID
     * @return boolean Whether the member is staff
     */
    protected function _is_staff($member)
    {
        $usergroup = $this->get_member_row_field($member, 'mgroup');
        if ((!is_null($usergroup)) && ($this->connection->query_select_value_if_there('groups', 'g_is_supmod', array('g_id' => $usergroup)) == 1)) {
            return true;
        }
        return false;
    }

    /**
     * Find if the specified member ID is marked as a super admin or not.
     *
     * @param  MEMBER $member The member ID
     * @return boolean Whether the member is a super admin
     */
    protected function _is_super_admin($member)
    {
        $usergroup = $this->get_member_row_field($member, 'mgroup');
        if ((!is_null($usergroup)) && ($this->connection->query_select_value_if_there('groups', 'g_access_cp', array('g_id' => $usergroup)) == 1)) {
            return true;
        }
        return false;
    }

    /**
     * Get the number of members currently online on the forums.
     *
     * @return integer The number of members
     */
    public function get_num_users_forums()
    {
        return $this->connection->query_value_if_there('SELECT COUNT(*) FROM ' . $this->connection->get_table_prefix() . 'sessions WHERE running_time>' . strval(time() - 60 * intval(get_option('users_online_time'))));
    }

    /**
     * Get the number of new forum posts.
     *
     * @return integer The number of posts
     */
    protected function _get_num_new_forum_posts()
    {
        return $this->connection->query_value_if_there('SELECT COUNT(*) FROM ' . $this->connection->get_table_prefix() . 'posts WHERE post_date>' . strval(time() - 60 * 60 * 24));
    }

    /**
     * Get the IDs of the admin usergroups.
     *
     * @return array The admin usergroup IDs
     */
    protected function _get_super_admin_groups()
    {
        return collapse_1d_complexity('g_id', $this->connection->query_select('groups', array('g_id'), array('g_access_cp' => 1)));
    }

    /**
     * Get the IDs of the moderator usergroups.
     * It should not be assumed that a member only has one usergroup - this depends upon the forum the driver works for. It also does not take the staff site filter into account.
     *
     * @return array The moderator usergroup IDs
     */
    protected function _get_moderator_groups()
    {
        return collapse_1d_complexity('g_id', $this->connection->query_select('groups', array('g_id'), array('g_access_cp' => 0, 'g_is_supmod' => 1)));
    }

    /**
     * Get the forum usergroup list.
     *
     * @return array The usergroup list
     */
    protected function _get_usergroup_list()
    {
        $results = $this->connection->query_select('groups', array('g_id', 'g_title'));
        $out = array();
        foreach ($results as $g) {
            $out[$g['g_id']] = $this->ipb_unescape($g['g_title']);
        }
        return $out;
    }

    /**
     * Get a first known IP address of the given member.
     *
     * @param  MEMBER $member The member ID
     * @return IP The IP address
     */
    public function get_member_ip($member)
    {
        return $this->get_member_row_field($member, 'ip_address');
    }

    /**
     * Gets a named field of a member row from the database.
     *
     * @param  MEMBER $member The member ID
     * @param  string $field The field identifier
     * @return mixed The field
     */
    public function get_member_row_field($member, $field)
    {
        $row = $this->get_member_row($member);
        return is_null($row) ? null : (array_key_exists($field, $row) ? $row[$field] : null);
    }
}
