<?php

/*
ThreadStarter Plugin for MyBB
Copyright (C) 2015 SvePu

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

if (!defined("IN_MYBB"))
{
    die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook('postbit', 'postbit_threadstarter');

function threadstarter_info()
{
    global $plugins_cache, $mybb, $db, $lang;
    $lang->load('config_threadstarter');
    $info = array(
        "name" => $db->escape_string($lang->threadstarter),
        "description" => $db->escape_string($lang->threadstarter_desc),
        "website" => "https://github.com/SvePu/ThreadStarter",
        "author" => "SvePu",
        "authorsite" => "https://github.com/SvePu",
        "codename" => "threadstarter",
        "version" => "1.4",
        "compatibility" => "18*"
    );

    $info_desc = '';
    $gid_result = $db->simple_select('settinggroups', 'gid', "name = 'threadstarter_settings'", array('limit' => 1));
    $settings_group = $db->fetch_array($gid_result);
    if (!empty($settings_group['gid']))
    {
        $info_desc .= "<span style=\"font-size: 0.9em;\">(~<a href=\"index.php?module=config-settings&action=change&gid=" . $settings_group['gid'] . "\"> " . $db->escape_string($lang->threadstarter_settings_title) . " </a>~)</span>";
    }

    if (is_array($plugins_cache) && is_array($plugins_cache['active']) && $plugins_cache['active']['threadstarter'])
    {
        $info_desc .= '<form action="https://www.paypal.com/cgi-bin/webscr" method="post" style="float: right;" target="_blank" />
<input type="hidden" name="cmd" value="_s-xclick" />
<input type="hidden" name="hosted_button_id" value="VGQ4ZDT8M7WS2" />
<input type="image" src="https://www.paypalobjects.com/webstatic/en_US/btn/btn_donate_pp_142x27.png" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!" />
<img alt="" border="0" src="https://www.paypalobjects.com/de_DE/i/scr/pixel.gif" width="1" height="1" />
</form>';
    }

    if ($info_desc != '')
    {
        $info['description'] = $info_desc . '<br />' . $info['description'];
    }

    return $info;
}

function threadstarter_activate()
{
    global $db, $lang;
    $lang->load('config_threadstarter');
    $query_add = $db->simple_select("settinggroups", "COUNT(*) as disorder");
    $disorder = $db->fetch_field($query_add, "disorder");
    $threadstarter_group = array(
        "name"      => "threadstarter_settings",
        "title"     => $db->escape_string($lang->threadstarter_settings_title),
        "description"   => $db->escape_string($lang->threadstarter_settings_title_desc),
        "disporder" =>  $disorder + 1,
        "isdefault"     =>  0
    );
    $db->insert_query("settinggroups", $threadstarter_group);
    $gid = $db->insert_id();

    $threadstarter_1 = array(
        'name'      => 'threadstarter_enable',
        'title'     => $db->escape_string($lang->threadstarter_enable_title),
        'description'   => $db->escape_string($lang->threadstarter_enable_title_desc),
        'optionscode'   => 'yesno',
        'value'         => '1',
        'disporder' => 1,
        "gid"       => (int)$gid
    );
    $db->insert_query('settings', $threadstarter_1);

    $threadstarter_2 = array(
        "name"      => "threadstarter_choise",
        "title"     => $db->escape_string($lang->threadstarter_choise_title),
        "description"   => $db->escape_string($lang->threadstarter_choise_title_desc),
        'optionscode'   => 'radio \n 1=Text \n 2=Image',
        'value'         => '1',
        "disporder" => 2,
        "gid"       => (int)$gid
    );
    $db->insert_query("settings", $threadstarter_2);

    $threadstarter_3 = array(
        "name"      => "threadstarter_text",
        "title"     => $db->escape_string($lang->threadstarter_text_title),
        "description"   => $db->escape_string($lang->threadstarter_text_title_desc),
        'optionscode'   => 'text',
        'value'         => '',
        "disporder" => 3,
        "gid"       => (int)$gid
    );
    $db->insert_query("settings", $threadstarter_3);


    $threadstarter_4 = array(
        "name"      => "threadstarter_image",
        "title"     => $db->escape_string($lang->threadstarter_image_title),
        "description"   => $db->escape_string($lang->threadstarter_image_title_desc),
        'optionscode'   => 'text',
        'value'         => '',
        "disporder" => 4,
        "gid"       => (int)$gid
    );
    $db->insert_query("settings", $threadstarter_4);

    $threadstarter_5 = array(
        "name"      => "threadstarter_firstpostlink",
        "title"     => $db->escape_string($lang->threadstarter_firstpostlink_title),
        "description"   => $db->escape_string($lang->threadstarter_firstpostlink_title_desc),
        'optionscode'   => 'yesno',
        'value'         => '1',
        "disporder" => 5,
        "gid"       => (int)$gid
    );
    $db->insert_query("settings", $threadstarter_5);
    rebuild_settings();

    require MYBB_ROOT . "/inc/adminfunctions_templates.php";
    find_replace_templatesets("postbit", "#" . preg_quote('{$post[\'userstars\']}') . "#i", "{\$post['threadstarter']}\n{\$post['userstars']}");
    find_replace_templatesets("postbit_classic", "#" . preg_quote('{$post[\'userstars\']}') . "#i", "{\$post['threadstarter']}\n{\$post['userstars']}");
}

function threadstarter_deactivate()
{
    global $mybb, $db;

    $result = $db->simple_select('settinggroups', 'gid', "name = 'threadstarter_settings'", array('limit' => 1));
    $group = $db->fetch_array($result);

    if (!empty($group['gid']))
    {
        $db->delete_query('settinggroups', "gid='{$group['gid']}'");
        $db->delete_query('settings', "gid='{$group['gid']}'");
        rebuild_settings();
    }

    require MYBB_ROOT . "/inc/adminfunctions_templates.php";
    find_replace_templatesets("postbit", "#" . preg_quote('{$post[\'threadstarter\']}') . "(\r?)\n#", '', 0);
    find_replace_templatesets("postbit_classic", "#" . preg_quote('{$post[\'threadstarter\']}') . "(\r?)\n#", '', 0);
}

function postbit_threadstarter(&$post)
{
    global $thread, $mybb, $postcounter, $theme;

    if ($mybb->settings['threadstarter_enable'] == 0 || $thread['uid'] == 0)
    {
        return;
    }

    $threadstarter = "";

    switch ($mybb->settings['threadstarter_choise'])
    {
        case 1:
            $tstext = "ThreadStarter";

            if (!empty($mybb->settings['threadstarter_text']))
            {
                $tstext = htmlspecialchars_uni($mybb->settings['threadstarter_text']);
            }

            $threadstarter = "<span class=\"ts_text\">" .  $tstext . "</span>";
            break;
        case 2:
            $tsimage = $mybb->settings['bburl'] . "/images/default_ts_image.png";

            if (!empty($mybb->settings['threadstarter_image']))
            {
                $tsimage_check = trim($mybb->settings['threadstarter_image']);

                if (strpos($tsimage_check, '{theme}') !== false)
                {
                    $tsimage_check = preg_replace('/\{theme\}/i', $theme['imgdir'], $tsimage_check);
                }

                if (@getimagesize($tsimage_check))
                {
                    $tsimage = $tsimage_check;
                }
            }

            if ($imgdim = @getimagesize($tsimage))
            {
                list($width, $height, $type, $width_height) = $imgdim;
                $threadstarter = "<img class=\"ts_image\" " . $width_height . " src=\"" . $tsimage . "\" alt=\"threadstarter\" />";
            }
            break;
    }

    $post['threadstarter'] = "";
    if ($post['uid'] == $thread['uid'] && $postcounter > 1 && !empty($threadstarter))
    {
        $post['threadstarter'] = $threadstarter . "<br />";

        if ($mybb->settings['threadstarter_firstpostlink'] != 0)
        {
            $post['threadstarter'] = "<a href=\"" . $mybb->settings['bburl'] . "/" . get_post_link($thread['firstpost'], $thread['tid']) . "#pid" . $thread['firstpost'] . "\">" . $threadstarter . "</a><br />";
        }
    }
}
