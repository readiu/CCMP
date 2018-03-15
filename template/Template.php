<?php
/**
 * @author megelj1
* Creates wrapper for WW2 site.
*/
class Template {

	private static $templates = [
			'default' => [
					'path' => 'default/',
					'file-header' => 'header.php',
					'file-footer' => 'footer.php'
			]
	];
	
	private $templateName = null;

	private $title = '';

	private $headAppend = '';

	private $bodyAppend = '';

	/**
	 *
	 * @param string $title
	 *        	Page title shown in &lt;title&gt; tag.
	 * @param array $groups
	 *        	An array of AD group names, at least one of which the user must be a member of in
	 *        	order to view this page.
	 */
	public function __construct($title = '', $groups = null, $templateName = 'default') {
		if (array_key_exists($templateName, self::$templates)) {
			$this->templateName = $templateName;
		} else {
			throw new Exception('Template `' . $templateName . '` does not exist');
		}
		$this->title = $title;
		if ((is_array($groups)) && ((!is_array($_SESSION)) ||
				 (!array_key_exists('groups', $_SESSION)) || (empty($_SESSION['groups'])) ||
				 (!is_array($_SESSION['groups'])) ||
				 (count(array_intersect($groups, $_SESSION['groups'])) == 0))) {
			// User does not have privileges for this page.
			$subdomain = '';
			$subdomainRegex = '/^[^.]+/';
			$matches = [];
			$matched = preg_match($subdomainRegex, $_SERVER['HTTP_HOST'], $matches);
			if ($matched) {
				$subdomain = $matches[0];
			}
			$loginPageURL = 'https://' . preg_replace($subdomainRegex, 'ww2', $_SERVER['HTTP_HOST']) .
					 '/';
			header(
					'Location: ' . $loginPageURL . '?login_subdomain=' . $subdomain . '&login_path=' .
							 urlencode($_SERVER['REQUEST_URI']));
			die();
		}
	}

	/**
	 * Updates the page title.
	 *
	 * @param string $title
	 *        	Page title shown in &lt;title&gt; tag.
	 */
	public function setTitle($title) {
		$this->title = $title;
	}

	/**
	 * Appends $str to the contents of the HTML &lt;head&gt;.
	 *
	 * @param string $str
	 *        	Text/HTML to append.
	 */
	public function appendToHead($str) {
		$this->headAppend .= $str;
	}

	/**
	 * Appends $str to the contents of the HTML &lt;body&gt;.
	 *
	 * @param string $str
	 *        	Text/HTML to append.
	 */
	public function appendtoBody($str) {
		$this->bodyAppend .= $str;
	}

	/**
	 * Prints header part of content wrapper.
	 */
	public function printHeader() {
		$template = self::$templates[$this->templateName];
		require_once $template['path'] . $template['file-header'];
	}

	/**
	 * Prints footer part of content wrapper.
	 */
	public function printFooter() {
		$template = self::$templates[$this->templateName];
		require_once $template['path'] . $template['file-footer'];
	}

}
?>
