<?php

/**
 * @name      ElkArte Forum
 * @copyright ElkArte Forum contributors
 * @license   BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * This software is a derived product, based on:
 *
 * Simple Machines Forum (SMF)
 * copyright:	2011 Simple Machines (http://www.simplemachines.org)
 * license:  	BSD, See included LICENSE.TXT for terms and conditions.
 *
 * @version 1.0.3
 *
 */

/**
 * Loads the template used to display boards
 */
function template_BoardIndex_init()
{
	loadTemplate('GenericBoards');
}

/**
 * Main template for displaying the list of boards
 */
function template_boards_list()
{
	global $context, $txt;

	// Each category in categories is made up of:
	// id, href, link, name, is_collapsed (is it collapsed?), can_collapse (is it okay if it is?),
	// new (is it new?), collapse_href (href to collapse/expand), collapse_image (up/down image),
	// and boards. (see below.)
	foreach ($context['categories'] as $category)
	{
		// If there are no parent boards we can see, avoid showing an empty category (unless its collapsed).
		if (empty($category['boards']) && !$category['is_collapsed'])
			continue;

		// @todo - Invent nifty class name for boardindex header bars.
		echo '
				<div class="forum_category" id="category_', $category['id'], '">
					<h2 class="category_header">';

		// If this category even can collapse, show a link to collapse it.
		if ($category['can_collapse'])
			echo '
						<a class="collapse" href="', $category['collapse_href'], '" title="', $category['is_collapsed'] ? $txt['show'] : $txt['hide'], '">', $category['collapse_image'], '</a>';

		// The "category link" is only a link for logged in members. Guests just get the name.
		echo '
						<i class="fa fa-lg fa-folder"></i>&nbsp;', $category['link'], '
					</h2>';

		// Assuming the category hasn't been collapsed...
		if (!$category['is_collapsed'])
					template_list_boards($category['boards'], 'category_' . $category['id'] . '_boards');

		echo '
				</div>';
	}
}

/**
 * Show information above the boardindex, like the newsfader
 */
function template_boardindex_outer_above()
{
	global $context, $settings, $txt;

	// Show some statistics if info centre stats is off.
	if (!$settings['show_stats_index'])
		echo '
		<div id="index_common_stats">
			', $txt['members'], ': ', $context['common_stats']['total_members'], ' &nbsp;&#8226;&nbsp; ', $txt['posts_made'], ': ', $context['common_stats']['total_posts'], ' &nbsp;&#8226;&nbsp; ', $txt['topics_made'], ': ', $context['common_stats']['total_topics'], '<br />
			', $settings['show_latest_member'] ? ' ' . sprintf($txt['welcome_newest_member'], ' <strong>' . $context['common_stats']['latest_member']['link'] . '</strong>') : '', '
		</div>';
}

/**
 * Show information below the boardindex, like stats, infocenter
 */
function template_boardindex_outer_below()
{
	global $context, $settings, $txt;

	// The key line, new posts, no new posts, etc
	echo '
		<div id="posting_icons">';

	// Show the mark all as read button?
	if ($settings['show_mark_read'] && !$context['user']['is_guest'] && !empty($context['categories']))
		echo '
			', template_button_strip($context['mark_read_button'], 'right');

	if ($context['user']['is_logged'])
		echo '
			<p class="board_key new_some_board" title="', $txt['new_posts'], '">', $txt['new_posts'], '</p>';

	echo '
			<p class="board_key new_none_board" title="', $txt['old_posts'], '">', $txt['old_posts'], '</p>
			<p class="board_key new_redirect_board" title="', $txt['redirect_board'], '">', $txt['redirect_board'], '</p>
		</div>';

	if (!empty($context['info_center_callbacks']))
		template_info_center();
}

/**
 * The info center ... stats, recent topics, other important information that never gets seen :P
 */
function template_info_center()
{
	global $context, $txt;

	// Here's where the "Info Center" starts...
	echo '
	<div id="info_center" class="forum_category">
		<h2 class="category_header">
			<span id="category_toggle">&nbsp;
				<span id="upshrink_ic" class="', empty($context['minmax_preferences']['info']) ? 'collapse' : 'expand', '" style="display: none;" title="', $txt['hide'], '"></span>
			</span>
			<a href="#" id="upshrink_link">', sprintf($txt['info_center_title'], $context['forum_name_html_safe']), '</a>
		</h2>
		<ul id="upshrinkHeaderIC" class="category_boards"', empty($context['minmax_preferences']['info']) ? '' : ' style="display: none;"', '>';

	call_template_callbacks('ic', $context['info_center_callbacks']);

	echo '
		</ul>
	</div>';
}

/**
 * This is the "Recent Posts" bar.
 */
function template_ic_recent_posts()
{
	global $context, $txt, $scripturl, $settings, $user_profile;

	// Show the Recent Posts title, and attach webslices feed to this section
	// The format requires: hslice, entry-title and entry-content classes.
	echo '
			<li class="board_row hslice" id="recent_posts_content">
				<h3 class="ic_section_header">
					<a href="', $scripturl, '?action=recent">
						<i class="fa fa-file-text-o fa-lg icon"></i>', $txt['recent_posts'], '
					</a>
				</h3>
				<div class="entry-title" style="display: none;">', $context['forum_name_html_safe'], ' - ', $txt['recent_posts'], '</div>
				<div class="entry-content" style="display: none;">
					<a rel="feedurl" href="', $scripturl, '?action=.xml;type=webslice">', $txt['subscribe_webslice'], '</a>
				</div>';

	// Only show one post.
	if ($settings['number_recent_posts'] == 1)
	{
		// latest_post has link, href, time, subject, short_subject (shortened with...), and topic. (its id.)
		echo '
				<p id="infocenter_onepost" class="inline">
					<a href="', $scripturl, '?action=recent">', $txt['recent_view'], '</a>&nbsp;', sprintf($txt['is_recent_updated'], '&quot;' . $context['latest_post']['link'] . '&quot;'), ' (', $context['latest_post']['html_time'], ')
				</p>';
	}
	// Show lots of posts.
	elseif (!empty($context['latest_posts']))
	{
		echo '
				<ul id="ic_recentposts">';

		// Each post in latest_posts has:
		// board (with an id, name, and link.), topic (the topic's id.), poster (with id, name, and link.),
		// subject, short_subject (shortened with...), time, link, and href.
		foreach ($context['latest_posts'] as $post)
		{
			// This is really not the way to go about this :P
			if (empty($user_profile[$post['poster']['id']]['avatar']))
			{
				loadMemberData($post['poster']['id']);
				$user_profile[$post['poster']['id']]['avatar'] = determineAvatar($user_profile[$post['poster']['id']]);
			}

			echo '
					<li class="ic_recent">
						<div class="ic_recent_avatar">
							', $user_profile[$post['poster']['id']]['avatar']['image'], '
						</div>
						<div>
							<p>', $post['poster']['link'], ' | ', $post['link'], '</p>
							<p>', $post['html_time'], '</p>
						</div>
					</li>';
		}

		echo '
				</ul>';
	}

	echo '
			</li>';
}

/**
 * Show information about events, birthdays, and holidays on the calendar in the info center
 */
function template_ic_show_events()
{
	global $context, $txt, $scripturl, $settings;

	if (empty($context['calendar_holidays']) && empty($context['calendar_birthdays']) && empty($context['calendar_events']))
		return;

	echo '
			<li class="board_row">
				<h3 class="ic_section_header">
					<a href="', $scripturl, '?action=calendar">
					<i class="fa fa-calendar fa-lg icon"></i>', $context['calendar_only_today'] ? $txt['calendar_today'] : $txt['calendar_upcoming'], '</a>
				</h3>
				<ul class="bbc_list">';

	// Holidays like "Christmas", "Hanukkah", and "We Love [Unknown] Day" :P.
	if (!empty($context['calendar_holidays']))
		echo '
					<li>', $txt['calendar_prompt'], '
						<p class="holiday smalltext"> ', implode(', ', $context['calendar_holidays']), '</p>
					</li>';

	// People's birthdays. Like mine. And yours, I guess. Kidding.
	if (!empty($context['calendar_birthdays']))
	{
		echo '
					<li>
						<span class="birthday">', $context['calendar_only_today'] ? $txt['birthdays'] : $txt['birthdays_upcoming'], '</span>
						<p class="smalltext">';

		// Each member in calendar_birthdays has: id, name (person), age (if they have one set?), is_last. (last in list?), and is_today (birthday is today?)
		foreach ($context['calendar_birthdays'] as $member)
			echo '
					<a href="', $scripturl, '?action=profile;u=', $member['id'], '">', $member['is_today'] ? '<strong class="fix_rtl_names">' : '', $member['name'], $member['is_today'] ? '</strong>' : '', isset($member['age']) ? ' (' . $member['age'] . ')' : '', '</a>', $member['is_last'] ? '' : ', ';

		echo '
						</p>
					</li>';
	}

	// Events like community get-togethers.
	if (!empty($context['calendar_events']))
	{
		echo '

					<li>
						<span class="event">', $context['calendar_only_today'] ? $txt['events'] : $txt['events_upcoming'], '</span>
						<p class="smallext"> ';

		// Each event in calendar_events should have:
		// title, href, is_last, can_edit (are they allowed?), modify_href, and is_today.
		foreach ($context['calendar_events'] as $event)
			echo '
						', $event['can_edit'] ? '<a href="' . $event['modify_href'] . '" title="' . $txt['calendar_edit'] . '"><img src="' . $settings['images_url'] . '/icons/calendar_modify.png" alt="*" class="centericon" /></a> ' : '', $event['href'] == '' ? '' : '<a href="' . $event['href'] . '">', $event['is_today'] ? '<strong>' . $event['title'] . '</strong>' : $event['title'], $event['href'] == '' ? '' : '</a>', $event['is_last'] ? '<br />' : ', ';

		echo '
						</p>
					</li>';
	}

	echo '
				</ul>
			</li>';
}

/**
 * Show statistical style information in the info center
 */
function template_ic_show_stats()
{
	global $txt, $scripturl, $context, $settings, $modSettings;

	echo '
			<li class="board_row">
				<h3 class="ic_section_header">
					<i class="fa fa-pie-chart fa-lg icon"></i>
					', $context['show_stats'] ? '<a href="' . $scripturl . '?action=stats" title="' . $txt['more_stats'] . '">' . $txt['forum_stats'] . '</a>' : $txt['forum_stats'], '
				</h3>
				<ul class="bbc_list multi_column">
					<li>', $txt['total_posts'], ': ', $context['common_stats']['total_posts'], '</li>
					<li>', $txt['total_topics'], ': ', $context['common_stats']['total_topics'], '</li>
					<li>', $txt['total_members'], ': ', $context['common_stats']['total_members'], '</li>',
					!empty($settings['show_latest_member']) ? '<li>' . $txt['latest_member'] . ': <strong> ' . $context['common_stats']['latest_member']['link'] . '</strong></li>' : '',
					'<li>', $txt['most_online_today'], ': ', comma_format($modSettings['mostOnlineToday']), '</li>
					<li>', (!empty($context['latest_post']) ? $txt['latest_post'] . ': <strong>&quot;' . $context['latest_post']['link'] . '&quot;<br /></strong><i class="fa fa-clock-o"></i> <span class="smalltext">' . $context['latest_post']['time'] . '</span>' : ''), '<br />
					<a class="linkbutton" href="', $scripturl, '?action=recent">', $txt['recent_view'], '</a></li>
				</ul>
			</li>';
}

/**
 * Show the online users in the info center
 */
function template_ic_show_users()
{
	global $context, $txt, $scripturl, $settings, $modSettings;

	// "Users online" - in order of activity.
	echo '
			<li class="board_row">
				<h3 class="ic_section_header">
					', $context['show_who'] ? '<a href="' . $scripturl . '?action=who">' : '', '<i class="fa fa-users fa-lg icon"></i> ', $txt['online_now'], $context['show_who'] ? '</a>' : '', '
				</h3>
				<ul class="bbc_list">';

	echo '
					<li>', comma_format($context['num_guests']), ' ', $context['num_guests'] == 1 ? $txt['guest'] : $txt['guests'], '</li>
					<li>', comma_format($context['num_users_online']), ' ', $context['num_users_online'] == 1 ? $txt['user'] : $txt['users'], '</li>';

	// Handle hidden users and buddies.
	$bracketList = array();
	if ($context['show_buddies'])
		$bracketList[] = comma_format($context['num_buddies']) . ' ' . ($context['num_buddies'] == 1 ? $txt['buddy'] : $txt['buddies']);

	if (!empty($context['num_spiders']))
		$bracketList[] = comma_format($context['num_spiders']) . ' ' . ($context['num_spiders'] == 1 ? $txt['spider'] : $txt['spiders']);

	if (!empty($context['num_users_hidden']))
		$bracketList[] = comma_format($context['num_users_hidden']) . ' ' . ($context['num_users_hidden'] == 1 ? $txt['hidden'] : $txt['hidden_s']);

	if (!empty($bracketList))
		echo '
					<li>(' . implode(', ', $bracketList) . ')</li>';

	echo '
				</ul>';

	// Assuming there ARE users online... each user in users_online has an id, username, name, group, href, and link.
	if (!empty($context['users_online']))
	{
		echo '
				<p class="inline">', sprintf($txt['users_active'], $modSettings['lastActive']), ': ', implode(', ', $context['list_users_online']), '</p>';

		// Showing membergroups?
		if (!empty($settings['show_group_key']) && !empty($context['membergroups']))
			echo '
				<p class="inline membergroups">[' . implode(',&nbsp;', $context['membergroups']) . ']</p>';
	}
	echo '
			</li>';
}