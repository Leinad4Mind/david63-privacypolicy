<?php
/**
*
* @package Privacy Policy Extension
* @copyright (c) 2018 david63
* * @license GNU General Public License, version 2 (GPL-2.0)
*
*/

/**
* DO NOT CHANGE
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

/// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine
//
// Some characters you may want to copy&paste:
// ’ » “ ” …
//

$lang = array_merge($lang, array(
	'ACCEPT_DATE'					=> 'Acceptance date',
	'ACP_PRIVACY_POLICY_EXPLAIN'	=> 'Here you can select and view a user’s privacy data.',
	'ACP_PRIVACY_TITLE'				=> 'Privacy Policy Data',

	'CLEAR_FILTER'					=> 'Clear filter',

	'FILTER_BY'						=> 'Filter by',

	'GO'							=> 'Go',

	'LAST_VISIT'					=> 'Last visit',

	'PAGE_NUMBER'					=> 'Page',
	'PRIVACY_LIST_EXPLAIN'			=> 'Here is a list of all the board members and the date on which they have accepted the Privacy Policy of this board.',

	'REG_DATE'						=> 'Registration date',

	'SELECT_USERNAME_EXPLAIN'		=> 'The user whose privacy data you wish to examine.',
	'SELECT_USERNAME'				=> 'Select username',
	'SORT_BY'						=> 'Sort by',

	'TOTAL_USERS'					=> 'Total',

	'USER_ID'						=> 'User ID',
	'USERNAME'						=> 'Username',

	// Translators - set these to whatever is most appropriate in your language
	// These are used to populate the filter keys
	'START_CHARACTER'	=> 'A',
	'END_CHARACTER'		=> 'Z',
));
