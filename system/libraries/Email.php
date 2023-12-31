<?php
 
defined('BASEPATH') OR exit('No direct script access allowed');
 
class CI_Email {
 
	public $useragent	= 'CodeIgniter';
 
	public $mailpath	= '/usr/sbin/sendmail';	 
	public $protocol	= 'mail';	 
	public $smtp_host	= '';
 
	public $smtp_user	= '';
 
	public $smtp_pass	= '';
 
	public $smtp_port	= 25;
 
	public $smtp_timeout	= 5;
 
	public $smtp_keepalive	= FALSE;
 
	public $smtp_crypto	= '';
 
	public $wordwrap	= TRUE;
 
	public $wrapchars	= 76;
 
	public $mailtype	= 'text';
 
	public $charset		= 'utf-8';
 
	public $multipart	= 'mixed';		 
	public $alt_message	= '';
 
	public $validate	= FALSE;
 
	public $priority	= 3;		 
	public $crlf		= "\n";
 
	public $dsn		= FALSE;
 
	public $send_multipart	= TRUE;
 
	public $bcc_batch_mode	= FALSE;
 
	public $bcc_batch_size	= 200;
 
	protected $_safe_mode		= FALSE;
 
	protected $_subject		= '';
 
	protected $_body		= '';
 
	protected $_finalbody		= '';
 
	protected $_alt_boundary	= '';
 
	protected $_atc_boundary	= '';
 
	protected $_header_str		= '';
 
	protected $_smtp_connect	= '';
 
	protected $_encoding		= '8bit';
 
	protected $_smtp_auth		= FALSE;
 
	protected $_replyto_flag	= FALSE;
 
	protected $_debug_msg		= array();
 
	protected $_recipients		= array();
 
	protected $_cc_array		= array();
 
	protected $_bcc_array		= array();
 
	protected $_headers		= array();
 
	protected $_attachments		= array();
 
	protected $_protocols		= array('mail', 'sendmail', 'smtp');
 
	protected $_base_charsets	= array('us-ascii', 'iso-2022-');
 
	protected $_bit_depths		= array('7bit', '8bit');
 
	protected $_priorities = array(
		1 => '1 (Highest)',
		2 => '2 (High)',
		3 => '3 (Normal)',
		4 => '4 (Low)',
		5 => '5 (Lowest)'
	);
 
	public function __construct(array $config = array())
	{
		$this->charset = config_item('charset');

		if (count($config) > 0)
		{
			$this->initialize($config);
		}
		else
		{
			$this->_smtp_auth = ! ($this->smtp_user === '' && $this->smtp_pass === '');
		}

		$this->_safe_mode = ( ! is_php('5.4') && ini_get('safe_mode'));
		$this->charset = strtoupper($this->charset);

		log_message('info', 'Email Class Initialized');
	}
 
	public function __destruct()
	{
		if (is_resource($this->_smtp_connect))
		{
			$this->_send_command('quit');
		}
	}
 
	public function initialize($config = array())
	{
		foreach ($config as $key => $val)
		{
			if (isset($this->$key))
			{
				$method = 'set_'.$key;

				if (method_exists($this, $method))
				{
					$this->$method($val);
				}
				else
				{
					$this->$key = $val;
				}
			}
		}
		$this->clear();

		$this->_smtp_auth = ! ($this->smtp_user === '' && $this->smtp_pass === '');

		return $this;
	}
 
	public function clear($clear_attachments = FALSE)
	{
		$this->_subject		= '';
		$this->_body		= '';
		$this->_finalbody	= '';
		$this->_header_str	= '';
		$this->_replyto_flag	= FALSE;
		$this->_recipients	= array();
		$this->_cc_array	= array();
		$this->_bcc_array	= array();
		$this->_headers		= array();
		$this->_debug_msg	= array();

		$this->set_header('User-Agent', $this->useragent);
		$this->set_header('Date', $this->_set_date());

		if ($clear_attachments !== FALSE)
		{
			$this->_attachments = array();
		}

		return $this;
	}
 
	public function from($from, $name = '', $return_path = NULL)
	{
		if (preg_match('/\<(.*)\>/', $from, $match))
		{
			$from = $match[1];
		}

		if ($this->validate)
		{
			$this->validate_email($this->_str_to_array($from));
			if ($return_path)
			{
				$this->validate_email($this->_str_to_array($return_path));
			}
		}
 
		if ($name !== '')
		{
			 
			if ( ! preg_match('/[\200-\377]/', $name))
			{
				 
				$name = '"'.addcslashes($name, "\0..\37\177'\"\\").'"';
			}
			else
			{
				$name = $this->_prep_q_encoding($name);
			}
		}

		$this->set_header('From', $name.' <'.$from.'>');

		isset($return_path) OR $return_path = $from;
		$this->set_header('Return-Path', '<'.$return_path.'>');

		return $this;
	}
 
	public function reply_to($replyto, $name = '')
	{
		if (preg_match('/\<(.*)\>/', $replyto, $match))
		{
			$replyto = $match[1];
		}

		if ($this->validate)
		{
			$this->validate_email($this->_str_to_array($replyto));
		}

		if ($name === '')
		{
			$name = $replyto;
		}

		if (strpos($name, '"') !== 0)
		{
			$name = '"'.$name.'"';
		}

		$this->set_header('Reply-To', $name.' <'.$replyto.'>');
		$this->_replyto_flag = TRUE;

		return $this;
	}
 
	public function to($to)
	{
		$to = $this->_str_to_array($to);
		$to = $this->clean_email($to);

		if ($this->validate)
		{
			$this->validate_email($to);
		}

		if ($this->_get_protocol() !== 'mail')
		{
			$this->set_header('To', implode(', ', $to));
		}

		$this->_recipients = $to;

		return $this;
	}
 
	public function cc($cc)
	{
		$cc = $this->clean_email($this->_str_to_array($cc));

		if ($this->validate)
		{
			$this->validate_email($cc);
		}

		$this->set_header('Cc', implode(', ', $cc));

		if ($this->_get_protocol() === 'smtp')
		{
			$this->_cc_array = $cc;
		}

		return $this;
	}
 
	public function bcc($bcc, $limit = '')
	{
		if ($limit !== '' && is_numeric($limit))
		{
			$this->bcc_batch_mode = TRUE;
			$this->bcc_batch_size = $limit;
		}

		$bcc = $this->clean_email($this->_str_to_array($bcc));

		if ($this->validate)
		{
			$this->validate_email($bcc);
		}

		if ($this->_get_protocol() === 'smtp' OR ($this->bcc_batch_mode && count($bcc) > $this->bcc_batch_size))
		{
			$this->_bcc_array = $bcc;
		}
		else
		{
			$this->set_header('Bcc', implode(', ', $bcc));
		}

		return $this;
	}
 
	public function subject($subject)
	{
		$subject = $this->_prep_q_encoding($subject);
		$this->set_header('Subject', $subject);
		return $this;
	}
 
	public function message($body)
	{
		$this->_body = rtrim(str_replace("\r", '', $body));
 
		if ( ! is_php('5.4') && get_magic_quotes_gpc())
		{
			$this->_body = stripslashes($this->_body);
		}

		return $this;
	}
 
	public function attach($file, $disposition = '', $newname = NULL, $mime = '')
	{
		if ($mime === '')
		{
			if (strpos($file, '://') === FALSE && ! file_exists($file))
			{
				$this->_set_error_message('lang:email_attachment_missing', $file);
				return FALSE;
			}

			if ( ! $fp = @fopen($file, 'rb'))
			{
				$this->_set_error_message('lang:email_attachment_unreadable', $file);
				return FALSE;
			}

			$file_content = stream_get_contents($fp);
			$mime = $this->_mime_types(pathinfo($file, PATHINFO_EXTENSION));
			fclose($fp);
		}
		else
		{
			$file_content =& $file;  
		}

		$this->_attachments[] = array(
			'name'		=> array($file, $newname),
			'disposition'	=> empty($disposition) ? 'attachment' : $disposition,  
			'type'		=> $mime,
			'content'	=> chunk_split(base64_encode($file_content))
		);

		return $this;
	}
 
	public function attachment_cid($filename)
	{
		if ($this->multipart !== 'related')
		{
			$this->multipart = 'related';  
		}

		for ($i = 0, $c = count($this->_attachments); $i < $c; $i++)
		{
			if ($this->_attachments[$i]['name'][0] === $filename)
			{
				$this->_attachments[$i]['cid'] = uniqid(basename($this->_attachments[$i]['name'][0]).'@');
				return $this->_attachments[$i]['cid'];
			}
		}

		return FALSE;
	}
 
	public function set_header($header, $value)
	{
		$this->_headers[$header] = str_replace(array("\n", "\r"), '', $value);
	}
 
	protected function _str_to_array($email)
	{
		if ( ! is_array($email))
		{
			return (strpos($email, ',') !== FALSE)
				? preg_split('/[\s,]/', $email, -1, PREG_SPLIT_NO_EMPTY)
				: (array) trim($email);
		}

		return $email;
	}
 
	public function set_alt_message($str)
	{
		$this->alt_message = (string) $str;
		return $this;
	}
 
	public function set_mailtype($type = 'text')
	{
		$this->mailtype = ($type === 'html') ? 'html' : 'text';
		return $this;
	}
 
	public function set_wordwrap($wordwrap = TRUE)
	{
		$this->wordwrap = (bool) $wordwrap;
		return $this;
	}
 
	public function set_protocol($protocol = 'mail')
	{
		$this->protocol = in_array($protocol, $this->_protocols, TRUE) ? strtolower($protocol) : 'mail';
		return $this;
	}
 
	public function set_priority($n = 3)
	{
		$this->priority = preg_match('/^[1-5]$/', $n) ? (int) $n : 3;
		return $this;
	}
 
	public function set_newline($newline = "\n")
	{
		$this->newline = in_array($newline, array("\n", "\r\n", "\r")) ? $newline : "\n";
		return $this;
	}
 
	public function set_crlf($crlf = "\n")
	{
		$this->crlf = ($crlf !== "\n" && $crlf !== "\r\n" && $crlf !== "\r") ? "\n" : $crlf;
		return $this;
	}
 
	protected function _set_boundaries()
	{
		$this->_alt_boundary = 'B_ALT_'.uniqid('');  
		$this->_atc_boundary = 'B_ATC_'.uniqid('');  
	}
 
	protected function _get_message_id()
	{
		$from = str_replace(array('>', '<'), '', $this->_headers['Return-Path']);
		return '<'.uniqid('').strstr($from, '@').'>';
	}
 
	protected function _get_protocol($return = TRUE)
	{
		$this->protocol = strtolower($this->protocol);
		in_array($this->protocol, $this->_protocols, TRUE) OR $this->protocol = 'mail';

		if ($return === TRUE)
		{
			return $this->protocol;
		}
	}
 
	protected function _get_encoding($return = TRUE)
	{
		in_array($this->_encoding, $this->_bit_depths) OR $this->_encoding = '8bit';

		foreach ($this->_base_charsets as $charset)
		{
			if (strpos($charset, $this->charset) === 0)
			{
				$this->_encoding = '7bit';
			}
		}

		if ($return === TRUE)
		{
			return $this->_encoding;
		}
	}
 
	protected function _get_content_type()
	{
		if ($this->mailtype === 'html')
		{
			return (count($this->_attachments) === 0) ? 'html' : 'html-attach';
		}
		elseif	($this->mailtype === 'text' && count($this->_attachments) > 0)
		{
			return 'plain-attach';
		}
		else
		{
			return 'plain';
		}
	}
 
	protected function _set_date()
	{
		$timezone = date('Z');
		$operator = ($timezone[0] === '-') ? '-' : '+';
		$timezone = abs($timezone);
		$timezone = floor($timezone/3600) * 100 + ($timezone % 3600) / 60;

		return sprintf('%s %s%04d', date('D, j M Y H:i:s'), $operator, $timezone);
	}
 
	protected function _get_mime_message()
	{
		return 'This is a multi-part message in MIME format.'.$this->newline.'Your email application may not support this format.';
	}
 
	public function validate_email($email)
	{
		if ( ! is_array($email))
		{
			$this->_set_error_message('lang:email_must_be_array');
			return FALSE;
		}

		foreach ($email as $val)
		{
			if ( ! $this->valid_email($val))
			{
				$this->_set_error_message('lang:email_invalid_address', $val);
				return FALSE;
			}
		}

		return TRUE;
	}
 
	public function valid_email($email)
	{
		if (function_exists('idn_to_ascii') && $atpos = strpos($email, '@'))
		{
			$email = substr($email, 0, ++$atpos).idn_to_ascii(substr($email, $atpos));
		}

		return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
	}
 
	public function clean_email($email)
	{
		if ( ! is_array($email))
		{
			return preg_match('/\<(.*)\>/', $email, $match) ? $match[1] : $email;
		}

		$clean_email = array();

		foreach ($email as $addy)
		{
			$clean_email[] = preg_match('/\<(.*)\>/', $addy, $match) ? $match[1] : $addy;
		}

		return $clean_email;
	}
 
	protected function _get_alt_message()
	{
		if ( ! empty($this->alt_message))
		{
			return ($this->wordwrap)
				? $this->word_wrap($this->alt_message, 76)
				: $this->alt_message;
		}

		$body = preg_match('/\<body.*?\>(.*)\<\/body\>/si', $this->_body, $match) ? $match[1] : $this->_body;
		$body = str_replace("\t", '', preg_replace('#<!--(.*)--\>#', '', trim(strip_tags($body))));

		for ($i = 20; $i >= 3; $i--)
		{
			$body = str_replace(str_repeat("\n", $i), "\n\n", $body);
		}
 
		$body = preg_replace('| +|', ' ', $body);

		return ($this->wordwrap)
			? $this->word_wrap($body, 76)
			: $body;
	}
 
	public function word_wrap($str, $charlim = NULL)
	{
		 
		if (empty($charlim))
		{
			$charlim = empty($this->wrapchars) ? 76 : $this->wrapchars;
		}
 
		if (strpos($str, "\r") !== FALSE)
		{
			$str = str_replace(array("\r\n", "\r"), "\n", $str);
		}
 
		$str = preg_replace('| +\n|', "\n", $str);
 
		$unwrap = array();
		if (preg_match_all('|\{unwrap\}(.+?)\{/unwrap\}|s', $str, $matches))
		{
			for ($i = 0, $c = count($matches[0]); $i < $c; $i++)
			{
				$unwrap[] = $matches[1][$i];
				$str = str_replace($matches[0][$i], '{{unwrapped'.$i.'}}', $str);
			}
		}
 
		$str = wordwrap($str, $charlim, "\n", FALSE);
 
		$output = '';
		foreach (explode("\n", $str) as $line)
		{
		 
			if (mb_strlen($line) <= $charlim)
			{
				$output .= $line.$this->newline;
				continue;
			}

			$temp = '';
			do
			{
				 
				if (preg_match('!\[url.+\]|://|www\.!', $line))
				{
					break;
				}
 
				$temp .= mb_substr($line, 0, $charlim - 1);
				$line = mb_substr($line, $charlim - 1);
			}
			while (mb_strlen($line) > $charlim);
 
			if ($temp !== '')
			{
				$output .= $temp.$this->newline;
			}

			$output .= $line.$this->newline;
		}
 
		if (count($unwrap) > 0)
		{
			foreach ($unwrap as $key => $val)
			{
				$output = str_replace('{{unwrapped'.$key.'}}', $val, $output);
			}
		}

		return $output;
	}
 
	protected function _build_headers()
	{
		$this->set_header('X-Sender', $this->clean_email($this->_headers['From']));
		$this->set_header('X-Mailer', $this->useragent);
		$this->set_header('X-Priority', $this->_priorities[$this->priority]);
		$this->set_header('Message-ID', $this->_get_message_id());
		$this->set_header('Mime-Version', '1.0');
	}
 
	protected function _write_headers()
	{
		if ($this->protocol === 'mail')
		{
			if (isset($this->_headers['Subject']))
			{
				$this->_subject = $this->_headers['Subject'];
				unset($this->_headers['Subject']);
			}
		}

		reset($this->_headers);
		$this->_header_str = '';

		foreach ($this->_headers as $key => $val)
		{
			$val = trim($val);

			if ($val !== '')
			{
				$this->_header_str .= $key.': '.$val.$this->newline;
			}
		}

		if ($this->_get_protocol() === 'mail')
		{
			$this->_header_str = rtrim($this->_header_str);
		}
	}
 
	protected function _build_message()
	{
		if ($this->wordwrap === TRUE && $this->mailtype !== 'html')
		{
			$this->_body = $this->word_wrap($this->_body);
		}

		$this->_set_boundaries();
		$this->_write_headers();

		$hdr = ($this->_get_protocol() === 'mail') ? $this->newline : '';
		$body = '';

		switch ($this->_get_content_type())
		{
			case 'plain' :

				$hdr .= 'Content-Type: text/plain; charset='.$this->charset.$this->newline
					.'Content-Transfer-Encoding: '.$this->_get_encoding();

				if ($this->_get_protocol() === 'mail')
				{
					$this->_header_str .= $hdr;
					$this->_finalbody = $this->_body;
				}
				else
				{
					$this->_finalbody = $hdr.$this->newline.$this->newline.$this->_body;
				}

				return;

			case 'html' :

				if ($this->send_multipart === FALSE)
				{
					$hdr .= 'Content-Type: text/html; charset='.$this->charset.$this->newline
						.'Content-Transfer-Encoding: quoted-printable';
				}
				else
				{
					$hdr .= 'Content-Type: multipart/alternative; boundary="'.$this->_alt_boundary.'"';

					$body .= $this->_get_mime_message().$this->newline.$this->newline
						.'--'.$this->_alt_boundary.$this->newline

						.'Content-Type: text/plain; charset='.$this->charset.$this->newline
						.'Content-Transfer-Encoding: '.$this->_get_encoding().$this->newline.$this->newline
						.$this->_get_alt_message().$this->newline.$this->newline.'--'.$this->_alt_boundary.$this->newline

						.'Content-Type: text/html; charset='.$this->charset.$this->newline
						.'Content-Transfer-Encoding: quoted-printable'.$this->newline.$this->newline;
				}

				$this->_finalbody = $body.$this->_prep_quoted_printable($this->_body).$this->newline.$this->newline;

				if ($this->_get_protocol() === 'mail')
				{
					$this->_header_str .= $hdr;
				}
				else
				{
					$this->_finalbody = $hdr.$this->newline.$this->newline.$this->_finalbody;
				}

				if ($this->send_multipart !== FALSE)
				{
					$this->_finalbody .= '--'.$this->_alt_boundary.'--';
				}

				return;

			case 'plain-attach' :

				$hdr .= 'Content-Type: multipart/'.$this->multipart.'; boundary="'.$this->_atc_boundary.'"';

				if ($this->_get_protocol() === 'mail')
				{
					$this->_header_str .= $hdr;
				}

				$body .= $this->_get_mime_message().$this->newline
					.$this->newline
					.'--'.$this->_atc_boundary.$this->newline
					.'Content-Type: text/plain; charset='.$this->charset.$this->newline
					.'Content-Transfer-Encoding: '.$this->_get_encoding().$this->newline
					.$this->newline
					.$this->_body.$this->newline.$this->newline;

			break;
			case 'html-attach' :

				$hdr .= 'Content-Type: multipart/'.$this->multipart.'; boundary="'.$this->_atc_boundary.'"';

				if ($this->_get_protocol() === 'mail')
				{
					$this->_header_str .= $hdr;
				}

				$body .= $this->_get_mime_message().$this->newline.$this->newline
					.'--'.$this->_atc_boundary.$this->newline

					.'Content-Type: multipart/alternative; boundary="'.$this->_alt_boundary.'"'.$this->newline.$this->newline
					.'--'.$this->_alt_boundary.$this->newline

					.'Content-Type: text/plain; charset='.$this->charset.$this->newline
					.'Content-Transfer-Encoding: '.$this->_get_encoding().$this->newline.$this->newline
					.$this->_get_alt_message().$this->newline.$this->newline.'--'.$this->_alt_boundary.$this->newline

					.'Content-Type: text/html; charset='.$this->charset.$this->newline
					.'Content-Transfer-Encoding: quoted-printable'.$this->newline.$this->newline

					.$this->_prep_quoted_printable($this->_body).$this->newline.$this->newline
					.'--'.$this->_alt_boundary.'--'.$this->newline.$this->newline;

			break;
		}

		$attachment = array();
		for ($i = 0, $c = count($this->_attachments), $z = 0; $i < $c; $i++)
		{
			$filename = $this->_attachments[$i]['name'][0];
			$basename = ($this->_attachments[$i]['name'][1] === NULL)
				? basename($filename) : $this->_attachments[$i]['name'][1];

			$attachment[$z++] = '--'.$this->_atc_boundary.$this->newline
				.'Content-type: '.$this->_attachments[$i]['type'].'; '
				.'name="'.$basename.'"'.$this->newline
				.'Content-Disposition: '.$this->_attachments[$i]['disposition'].';'.$this->newline
				.'Content-Transfer-Encoding: base64'.$this->newline
				.(empty($this->_attachments[$i]['cid']) ? '' : 'Content-ID: <'.$this->_attachments[$i]['cid'].'>'.$this->newline);

			$attachment[$z++] = $this->_attachments[$i]['content'];
		}

		$body .= implode($this->newline, $attachment).$this->newline.'--'.$this->_atc_boundary.'--';
		$this->_finalbody = ($this->_get_protocol() === 'mail')
			? $body
			: $hdr.$this->newline.$this->newline.$body;

		return TRUE;
	}
 
	protected function _prep_quoted_printable($str)
	{
		 
		if ($this->crlf === "\r\n")
		{
			if (is_php('5.3'))
			{
				return quoted_printable_encode($str);
			}
			elseif (function_exists('imap_8bit'))
			{
				return imap_8bit($str);
			}
		}
 
		$str = preg_replace(array('| +|', '/\x00+/'), array(' ', ''), $str);
 
		if (strpos($str, "\r") !== FALSE)
		{
			$str = str_replace(array("\r\n", "\r"), "\n", $str);
		}

		$escape = '=';
		$output = '';

		foreach (explode("\n", $str) as $line)
		{
			$length = strlen($line);
			$temp = '';
 
			for ($i = 0; $i < $length; $i++)
			{
				 
				$char = $line[$i];
				$ascii = ord($char);
 
				if ($i === ($length - 1) && ($ascii === 32 OR $ascii === 9))
				{
					$char = $escape.sprintf('%02s', dechex($ascii));
				}
				elseif ($ascii === 61)  
				{
					$char = $escape.strtoupper(sprintf('%02s', dechex($ascii)));  
				}
 
				if ((strlen($temp) + strlen($char)) >= 76)
				{
					$output .= $temp.$escape.$this->crlf;
					$temp = '';
				}
 
				$temp .= $char;
			}
 
			$output .= $temp.$this->crlf;
		}
 
		return substr($output, 0, strlen($this->crlf) * -1);
	}
 
	protected function _prep_q_encoding($str)
	{
		$str = str_replace(array("\r", "\n"), '', $str);

		if ($this->charset === 'UTF-8')
		{
			if (MB_ENABLED === TRUE)
			{
				return mb_encode_mimeheader($str, $this->charset, 'Q', $this->crlf);
			}
			elseif (ICONV_ENABLED === TRUE)
			{
				$output = @iconv_mime_encode('', $str,
					array(
						'scheme' => 'Q',
						'line-length' => 76,
						'input-charset' => $this->charset,
						'output-charset' => $this->charset,
						'line-break-chars' => $this->crlf
					)
				);
 
				if ($output !== FALSE)
				{
				 
					return substr($output, 2);
				}

				$chars = iconv_strlen($str, 'UTF-8');
			}
		}
 
		isset($chars) OR $chars = strlen($str);

		$output = '=?'.$this->charset.'?Q?';
		for ($i = 0, $length = strlen($output); $i < $chars; $i++)
		{
			$chr = ($this->charset === 'UTF-8' && ICONV_ENABLED === TRUE)
				? '='.implode('=', str_split(strtoupper(bin2hex(iconv_substr($str, $i, 1, $this->charset))), 2))
				: '='.strtoupper(bin2hex($str[$i]));
 
			if ($length + ($l = strlen($chr)) > 74)
			{
				$output .= '?='.$this->crlf  
					.' =?'.$this->charset.'?Q?'.$chr;  
				$length = 6 + strlen($this->charset) + $l;  
			}
			else
			{
				$output .= $chr;
				$length += $l;
			}
		}
 
		return $output.'?=';
	}
 
	public function send($auto_clear = TRUE)
	{
		if ( ! isset($this->_headers['From']))
		{
			$this->_set_error_message('lang:email_no_from');
			return FALSE;
		}

		if ($this->_replyto_flag === FALSE)
		{
			$this->reply_to($this->_headers['From']);
		}

		if ( ! isset($this->_recipients) && ! isset($this->_headers['To'])
			&& ! isset($this->_bcc_array) && ! isset($this->_headers['Bcc'])
			&& ! isset($this->_headers['Cc']))
		{
			$this->_set_error_message('lang:email_no_recipients');
			return FALSE;
		}

		$this->_build_headers();

		if ($this->bcc_batch_mode && count($this->_bcc_array) > $this->bcc_batch_size)
		{
			$result = $this->batch_bcc_send();

			if ($result && $auto_clear)
			{
				$this->clear();
			}

			return $result;
		}

		if ($this->_build_message() === FALSE)
		{
			return FALSE;
		}

		$result = $this->_spool_email();

		if ($result && $auto_clear)
		{
			$this->clear();
		}

		return $result;
	}
 
	public function batch_bcc_send()
	{
		$float = $this->bcc_batch_size - 1;
		$set = '';
		$chunk = array();

		for ($i = 0, $c = count($this->_bcc_array); $i < $c; $i++)
		{
			if (isset($this->_bcc_array[$i]))
			{
				$set .= ', '.$this->_bcc_array[$i];
			}

			if ($i === $float)
			{
				$chunk[] = substr($set, 1);
				$float += $this->bcc_batch_size;
				$set = '';
			}

			if ($i === $c-1)
			{
				$chunk[] = substr($set, 1);
			}
		}

		for ($i = 0, $c = count($chunk); $i < $c; $i++)
		{
			unset($this->_headers['Bcc']);

			$bcc = $this->clean_email($this->_str_to_array($chunk[$i]));

			if ($this->protocol !== 'smtp')
			{
				$this->set_header('Bcc', implode(', ', $bcc));
			}
			else
			{
				$this->_bcc_array = $bcc;
			}

			if ($this->_build_message() === FALSE)
			{
				return FALSE;
			}

			$this->_spool_email();
		}
	}
 
	protected function _unwrap_specials()
	{
		$this->_finalbody = preg_replace_callback('/\{unwrap\}(.*?)\{\/unwrap\}/si', array($this, '_remove_nl_callback'), $this->_finalbody);
	}
 
	protected function _remove_nl_callback($matches)
	{
		if (strpos($matches[1], "\r") !== FALSE OR strpos($matches[1], "\n") !== FALSE)
		{
			$matches[1] = str_replace(array("\r\n", "\r", "\n"), '', $matches[1]);
		}

		return $matches[1];
	}
 
	protected function _spool_email()
	{
		$this->_unwrap_specials();

		$method = '_send_with_'.$this->_get_protocol();
		if ( ! $this->$method())
		{
			$this->_set_error_message('lang:email_send_failure_'.($this->_get_protocol() === 'mail' ? 'phpmail' : $this->_get_protocol()));
			return FALSE;
		}

		$this->_set_error_message('lang:email_sent', $this->_get_protocol());
		return TRUE;
	}
 
	protected function _send_with_mail()
	{
		if (is_array($this->_recipients))
		{
			$this->_recipients = implode(', ', $this->_recipients);
		}

		if ($this->_safe_mode === TRUE)
		{
			return mail($this->_recipients, $this->_subject, $this->_finalbody, $this->_header_str);
		}
		else
		{
		 
			return mail($this->_recipients, $this->_subject, $this->_finalbody, $this->_header_str, '-f '.$this->clean_email($this->_headers['Return-Path']));
		}
	}
 
	protected function _send_with_sendmail()
	{
		 
		if ( ! function_usable('popen')
			OR FALSE === ($fp = @popen(
						$this->mailpath.' -oi -f '.$this->clean_email($this->_headers['From'])
							.' -t -r '.$this->clean_email($this->_headers['Return-Path'])
						, 'w'))
		) 
		{
			return FALSE;
		}

		fputs($fp, $this->_header_str);
		fputs($fp, $this->_finalbody);

		$status = pclose($fp);

		if ($status !== 0)
		{
			$this->_set_error_message('lang:email_exit_status', $status);
			$this->_set_error_message('lang:email_no_socket');
			return FALSE;
		}

		return TRUE;
	}
 
	protected function _send_with_smtp()
	{
		if ($this->smtp_host === '')
		{
			$this->_set_error_message('lang:email_no_hostname');
			return FALSE;
		}

		if ( ! $this->_smtp_connect() OR ! $this->_smtp_authenticate())
		{
			return FALSE;
		}

		$this->_send_command('from', $this->clean_email($this->_headers['From']));

		foreach ($this->_recipients as $val)
		{
			$this->_send_command('to', $val);
		}

		if (count($this->_cc_array) > 0)
		{
			foreach ($this->_cc_array as $val)
			{
				if ($val !== '')
				{
					$this->_send_command('to', $val);
				}
			}
		}

		if (count($this->_bcc_array) > 0)
		{
			foreach ($this->_bcc_array as $val)
			{
				if ($val !== '')
				{
					$this->_send_command('to', $val);
				}
			}
		}

		$this->_send_command('data');

		 
		$this->_send_data($this->_header_str.preg_replace('/^\./m', '..$1', $this->_finalbody));

		$this->_send_data('.');

		$reply = $this->_get_smtp_data();

		$this->_set_error_message($reply);

		if (strpos($reply, '250') !== 0)
		{
			$this->_set_error_message('lang:email_smtp_error', $reply);
			return FALSE;
		}

		if ($this->smtp_keepalive)
		{
			$this->_send_command('reset');
		}
		else
		{
			$this->_send_command('quit');
		}

		return TRUE;
	}
 
	protected function _smtp_connect()
	{
		if (is_resource($this->_smtp_connect))
		{
			return TRUE;
		}

		$ssl = ($this->smtp_crypto === 'ssl') ? 'ssl://' : '';

		$this->_smtp_connect = fsockopen($ssl.$this->smtp_host,
							$this->smtp_port,
							$errno,
							$errstr,
							$this->smtp_timeout);

		if ( ! is_resource($this->_smtp_connect))
		{
			$this->_set_error_message('lang:email_smtp_error', $errno.' '.$errstr);
			return FALSE;
		}

		stream_set_timeout($this->_smtp_connect, $this->smtp_timeout);
		$this->_set_error_message($this->_get_smtp_data());

		if ($this->smtp_crypto === 'tls')
		{
			$this->_send_command('hello');
			$this->_send_command('starttls');

			$crypto = stream_socket_enable_crypto($this->_smtp_connect, TRUE, STREAM_CRYPTO_METHOD_TLS_CLIENT);

			if ($crypto !== TRUE)
			{
				$this->_set_error_message('lang:email_smtp_error', $this->_get_smtp_data());
				return FALSE;
			}
		}

		return $this->_send_command('hello');
	}
 
	protected function _send_command($cmd, $data = '')
	{
		switch ($cmd)
		{
			case 'hello' :

						if ($this->_smtp_auth OR $this->_get_encoding() === '8bit')
						{
							$this->_send_data('EHLO '.$this->_get_hostname());
						}
						else
						{
							$this->_send_data('HELO '.$this->_get_hostname());
						}

						$resp = 250;
			break;
			case 'starttls'	:

						$this->_send_data('STARTTLS');
						$resp = 220;
			break;
			case 'from' :

						$this->_send_data('MAIL FROM:<'.$data.'>');
						$resp = 250;
			break;
			case 'to' :

						if ($this->dsn)
						{
							$this->_send_data('RCPT TO:<'.$data.'> NOTIFY=SUCCESS,DELAY,FAILURE ORCPT=rfc822;'.$data);
						}
						else
						{
							$this->_send_data('RCPT TO:<'.$data.'>');
						}

						$resp = 250;
			break;
			case 'data'	:

						$this->_send_data('DATA');
						$resp = 354;
			break;
			case 'reset':

						$this->_send_data('RSET');
						$resp = 250;
			break;
			case 'quit'	:

						$this->_send_data('QUIT');
						$resp = 221;
			break;
		}

		$reply = $this->_get_smtp_data();

		$this->_debug_msg[] = '<pre>'.$cmd.': '.$reply.'</pre>';

		if ((int) substr($reply, 0, 3) !== $resp)
		{
			$this->_set_error_message('lang:email_smtp_error', $reply);
			return FALSE;
		}

		if ($cmd === 'quit')
		{
			fclose($this->_smtp_connect);
		}

		return TRUE;
	}
 
	protected function _smtp_authenticate()
	{
		if ( ! $this->_smtp_auth)
		{
			return TRUE;
		}

		if ($this->smtp_user === '' && $this->smtp_pass === '')
		{
			$this->_set_error_message('lang:email_no_smtp_unpw');
			return FALSE;
		}

		$this->_send_data('AUTH LOGIN');

		$reply = $this->_get_smtp_data();

		if (strpos($reply, '503') === 0) 
		{
			return TRUE;
		}
		elseif (strpos($reply, '334') !== 0)
		{
			$this->_set_error_message('lang:email_failed_smtp_login', $reply);
			return FALSE;
		}

		$this->_send_data(base64_encode($this->smtp_user));

		$reply = $this->_get_smtp_data();

		if (strpos($reply, '334') !== 0)
		{
			$this->_set_error_message('lang:email_smtp_auth_un', $reply);
			return FALSE;
		}

		$this->_send_data(base64_encode($this->smtp_pass));

		$reply = $this->_get_smtp_data();

		if (strpos($reply, '235') !== 0)
		{
			$this->_set_error_message('lang:email_smtp_auth_pw', $reply);
			return FALSE;
		}

		return TRUE;
	}
 
	protected function _send_data($data)
	{
		$data .= $this->newline;
		for ($written = 0, $length = strlen($data); $written < $length; $written += $result)
		{
			if (($result = fwrite($this->_smtp_connect, substr($data, $written))) === FALSE)
			{
				break;
			}
		}

		if ($result === FALSE)
		{
			$this->_set_error_message('lang:email_smtp_data_failure', $data);
			return FALSE;
		}

		return TRUE;
	}
 
	protected function _get_smtp_data()
	{
		$data = '';

		while ($str = fgets($this->_smtp_connect, 512))
		{
			$data .= $str;

			if ($str[3] === ' ')
			{
				break;
			}
		}

		return $data;
	}
 
	protected function _get_hostname()
	{
		if (isset($_SERVER['SERVER_NAME']))
		{
			return $_SERVER['SERVER_NAME'];
		}

		return isset($_SERVER['SERVER_ADDR']) ? '['.$_SERVER['SERVER_ADDR'].']' : '[127.0.0.1]';
	}
 
	public function print_debugger($include = array('headers', 'subject', 'body'))
	{
		$msg = '';

		if (count($this->_debug_msg) > 0)
		{
			foreach ($this->_debug_msg as $val)
			{
				$msg .= $val;
			}
		}
 
		$raw_data = '';
		is_array($include) OR $include = array($include);

		if (in_array('headers', $include, TRUE))
		{
			$raw_data = htmlspecialchars($this->_header_str)."\n";
		}

		if (in_array('subject', $include, TRUE))
		{
			$raw_data .= htmlspecialchars($this->_subject)."\n";
		}

		if (in_array('body', $include, TRUE))
		{
			$raw_data .= htmlspecialchars($this->_finalbody);
		}

		return $msg.($raw_data === '' ? '' : '<pre>'.$raw_data.'</pre>');
	}
 
	protected function _set_error_message($msg, $val = '')
	{
		$CI =& get_instance();
		$CI->lang->load('email');

		if (sscanf($msg, 'lang:%s', $line) !== 1 OR FALSE === ($line = $CI->lang->line($line)))
		{
			$this->_debug_msg[] = str_replace('%s', $val, $msg).'<br />';
		}
		else
		{
			$this->_debug_msg[] = str_replace('%s', $val, $line).'<br />';
		}
	}
 
	protected function _mime_types($ext = '')
	{
		$ext = strtolower($ext);

		$mimes =& get_mimes();

		if (isset($mimes[$ext]))
		{
			return is_array($mimes[$ext])
				? current($mimes[$ext])
				: $mimes[$ext];
		}

		return 'application/x-unknown-content-type';
	}

}
