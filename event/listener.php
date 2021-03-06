<?php
/**
*
* @package Privacy Policy Extension
* @copyright (c) 2018 david63
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/

namespace david63\privacypolicy\event;

/**
* @ignore
*/
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use \phpbb\config\config;
use \phpbb\template\template;
use \phpbb\user;
use \phpbb\log\log;
use \phpbb\controller\helper;
use \phpbb\request\request;
use \phpbb\request\request_interface;
use \phpbb\language\language;

/**
* Event listener
*/
class listener implements EventSubscriberInterface
{
	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\log */
	protected $log;

	/** @var \phpbb\controller\helper */
	protected $helper;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\language\language */
	protected $language;

	protected $policy_required;

	/**
	* Constructor for listener
	*
	* @param \phpbb\config\config		$config		Config object
	* @param \phpbb\template\template	$template	Template object
	* @param \phpbb\user                $user		User object
	* @param \phpbb\log\log				$log		phpBB log
	* @param \phpbb\controller\helper	$helper		Helper object
	* @param \phpbb\request\request		$request	Request object
	* @param \phpbb\language\language	$language	Language object
	*
	* @return \david63\privacypolicy\event\listener
	* @access public
	*/
	public function __construct(config $config, template $template, user $user, log $log, helper $helper, request $request, language $language)
	{
		$this->config	= $config;
		$this->template	= $template;
		$this->user		= $user;
		$this->log		= $log;
		$this->helper	= $helper;
		$this->request	= $request;
		$this->language	= $language;
	}

	/**
	* Assign functions defined in this class to event listeners in the core
	*
	* @return array
	* @static
	* @access public
	*/
	static public function getSubscribedEvents()
	{
		return array(
			'core.page_footer'									=> 'page_footer',
			'core.page_header'									=> 'page_header',
			'core.page_header_after'							=> 'check_cookie',
			'core.user_setup'									=> 'load_language_on_setup',
			'core.ucp_register_agreement_modify_template_data'	=> 'load_modified_agreement',
			'core.ucp_register_user_row_after'					=> 'add_acceptance_date',
			'core.login_box_redirect'							=> array(
				'privacy_redirect',
				90, // Needed to allow this to run before any other extension.
			),
		);
	}

	/**
	* Redirect to the privacy policy acceptance page
	*
	* @param object $event The event object
	* @return null
	* @access public
	*/
	public function privacy_redirect($event)
	{
		$this->policy_required = $this->check_ip($this->user->ip);

	   if ($this->config['privacy_policy_enable'] && $this->config['privacy_policy_force'] && $this->user->data['user_accept_date'] == 0 && $this->policy_required)
		{
			redirect($this->helper->route('david63_privacypolicy_acceptance'));
		}
	}

	/**
	* Add the acceptance date to the user data
	*
	* @param object $event The event object
	* @return null
	* @access public
	*/
	public function add_acceptance_date($event)
	{
		if ($this->config['privacy_policy_enable'] && $this->policy_required)
		{
			$user_row = $event['user_row'];

			$user_row['user_accept_date'] = $user_row['user_regdate'];

			$event->offsetSet('user_row', $user_row);
		}
	}

	/**
	* Load the modified agreement
	*
	* @param object $event The event object
	* @return null
	* @access public
	*/
	public function load_modified_agreement($event)
	{
		if ($this->config['privacy_policy_enable'] && $this->policy_required)
		{
			$template_vars = $event['template_vars'];

			$this->language->add_lang('agreement', 'david63/privacypolicy');

			$event->update_subarray('template_vars', 'L_TERMS_OF_USE', $this->user->lang('TERMS_OF_USE_CONTENT', $this->config['sitename'], generate_board_url()) . $this->user->lang('TERMS_OF_USE_CONTENT_2', $this->config['sitename']));
		}
	}

    /**
     * Block links if option set
     *
     * @param $event
     *
     * @return null
     * @static
     * @access public
     */
	public function check_cookie($event)
	{
		if ($this->config['cookie_policy_enable'])
		{
			if ($this->config['cookie_require_access'] && !isset($_COOKIE[$this->config['cookie_name'] . '_ca']))
			{
				//$this->policy_required = $this->check_ip($this->user->ip);

				$this->template->assign_vars(array(
					'U_REGISTER'		=> $this->helper->route('david63_privacypolicy_policyoutput', array('name' => 'access')),
					'U_LOGIN_LOGOUT'	=> $this->helper->route('david63_privacypolicy_policyoutput', array('name' => 'access')),
				));
			}

			// Disable phpBB Cookie Notice
			$this->template->assign_var('S_COOKIE_NOTICE', false);
		}
	}

	/**
	* Load common cookie policy language files during user setup
	*
	* @param object $event The event object
	* @return null
	* @access public
	*/
	public function load_language_on_setup($event)
	{
		// Only load the language if it is required
		if ($this->config['cookie_policy_enable'] || $this->config['cookie_show_policy'] || $this->config['privacy_policy_enable'])
		{
			$lang_set_ext	= $event['lang_set_ext'];
			$lang_set_ext[]	= array(
				'ext_name' => 'david63/privacypolicy',
				'lang_set' => 'privacypolicy',
			);
			$event['lang_set_ext'] = $lang_set_ext;
		}
	}

	/**
	* Create the options to show the cookie acceptance box
	*
	* @param object $event The event object
	* @return null
	* @access public
	*/
	public function page_header($event)
	{
		// If we have already set the cookie on this device then there is no need to process
		$cookie_set = $this->request->is_set($this->config['cookie_name'] . '_ca', request_interface::COOKIE) ? true : false;

		$cookie_enabled = $this->check_ip($this->user->ip);

		if ($this->config['cookie_policy_enable'] && !$this->user->data['is_bot'] && !$cookie_set)
		{
			$this->template->assign_vars(array(
				'COOKIE_BOX_BD_COLOUR'		=> $this->config['cookie_box_bdr_colour'],
				'COOKIE_BOX_BD_WIDTH'		=> $this->config['cookie_box_bdr_width'],
				'COOKIE_BOX_BG_COLOUR'		=> $this->config['cookie_box_bg_colour'],
				'COOKIE_BOX_HREF_COLOUR'	=> $this->config['cookie_box_href_colour'],
				'COOKIE_BOX_TXT_COLOUR'		=> $this->config['cookie_box_txt_colour'],
				'COOKIE_CLASS'				=> $this->config['cookie_box_position'] ? 'cookie-box leftside' : 'cookie-box rightside',
				'COOKIE_EXPIRES'			=> $this->config['cookie_expire'],
				'COOKIE_NAME'				=> $this->config['cookie_name'],
			));
		}

		$this->template->assign_vars(array(
			'S_SHOW_COOKIE_ACCEPT'	=> $cookie_set,
			'S_COOKIE_ENABLED'		=> $cookie_enabled,
		));
	}

    /**
     * Set the template variables
     *
     * @param $event
     *
     * @return array
     * @static
     * @access public
     */
	public function page_footer($event)
	{
		$this->template->assign_vars(array(
			'S_COOKIE_BLOCK_LINKS'	=> $this->config['cookie_block_links'],
			'S_COOKIE_ON_INDEX'		=> $this->config['cookie_on_index'],
			'S_COOKIE_SHOW_POLICY'	=> $this->config['cookie_show_policy'],

			'U_COOKIE_PAGE'			=> $this->helper->route('david63_privacypolicy_policyoutput', array('name' => 'policy')),
		));
	}

	/**
	* Check the user's IP address for EU state
	*
	* @param $user_ip
	*
	* @return boolean
	* @static
	* @access public
	*/
	public function check_ip($user_ip)
	{
		$cookie_enabled = true;

		// Let's try to stop some spam attacks - check if the same IP as the last is being requested
		if ($user_ip != $this->config['cookie_last_ip'])
		{
			// Check if cURL is available on the server
			if (function_exists('curl_version'))
			{
				$curl_handle = curl_init();
				curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
				curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl_handle, CURLOPT_URL, 'freegeoip.net/json/' . $user_ip);

				$ip_query	= curl_exec($curl_handle);
				$http_code	= curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
				curl_close($curl_handle);

				switch ($http_code)
				{
					case 200: // Success
						$ip_array = json_decode($ip_query, true);
						$eu_array = array('AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'EU', 'FI', 'FR', 'FX', 'GB', 'GR', 'HR', 'HU', 'IE', 'IM', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK', 'UK');

						if (!in_array($ip_array['country_code'], $eu_array))
						{
							// IP not in an EU country therefore we do not need to invoke the Cookie Policy
							$cookie_enabled = false;
						}

						// This lookup was successful so unset the quota flag
						$this->config->set('cookie_quota_exceeded', false, false);
					break;

					case 403: // Quota exceeded
						// No need to report every occurence
						if (!$this->config['cookie_quota_exceeded'])
						{
							$this->log->add('critical', $this->user->data['user_id'], $user_ip, 'LOG_QUOTA_EXCEEDED');

							// Quota exceeded so set the flag to prevet excessive logging
							$this->config->set('cookie_quota_exceeded', true, false);
						}
					break;

					case 404: // Not found
						$this->log->add('critical', $this->user->data['user_id'], $user_ip, 'LOG_SERVER_ERROR');
					break;

					default: // Any other condition
						$this->log->add('critical', $this->user->data['user_id'], $user_ip, 'LOG_IP_LOOKUP_ERROR');
					break;
				}
			}
			else
			{
				$this->log->add('critical', $this->user->data['user_id'], $user_ip, 'LOG_CURL_ERROR');
			}

			// Update last IP search
			$this->config->set('cookie_last_ip', $user_ip, false);
		}
		return $cookie_enabled;
	}
}
