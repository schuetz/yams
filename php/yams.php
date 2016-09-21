<?php

class YAMS {

	protected
	$host = '',
	$path = '',
	$baseurl = '',
	$lang = [
		'active' => 'de',
		'list' => []
	],
	$page = 'home',
	$pid = 0,
	$slug = '',
	$title = 'Home',
	$nav = [
		'type' => 'main',
		'filename' => 'nav'
	];
	
	protected $option = [];
	
	public function __construct($options = null) {
		
		$this->host = 'http://'.$_SERVER['HTTP_HOST'];
		$this->path = dirname($_SERVER['PHP_SELF']);
		
		if ($this->path !== '/') $this->path .= '/';
		$this->baseurl = $this->host.$this->path;
		
		$this->nav['xml'] = $this->loadXML('var/'.$this->nav['filename'].'.xml');
		$this->setLanguageList();
		$this->init();
		
		//$this->redirect();
		
		if (is_array($options)) {
			foreach ($options as $option => $value) {
				$this->$option = $value;
			}
		}
		
	}
	
	public function __get($property) {
		if (property_exists($this, $property)) {
			return $this->$property;
		}
	}
	
	protected function setLanguageList() {
		
		foreach($this->nav['xml'] as $lang => $pages) {
			$this->lang['list'][] = $lang;
		}
		
	}
	
	protected function init() {
		
		$slug = (isset($_GET['slug'])) ? xss_clean($_GET['slug']) : '';
		if (!empty($slug)) {
			$parts = explode('/', $slug);
			if (in_array($parts[0], $this->lang['list'])) {
				$this->lang['active'] = array_shift($parts);
				if (!empty($parts)) {
					$this->page = implode('/', $parts);
				}
			} else {
				$this->page = $slug;
			}
		} else {
			//$this->page = trim($this->nav['xml']->{$this->lang['active']}->main->item[0]['href']->__toString());
		}
		$this->slug = $this->page;
		$this->page = str_replace('/','_',$this->page);
		
		$inNav = $this->nav['xml']->xpath('//item[@href="'.$this->slug.'"]');
		if (count($inNav) > 0) {
			$this->title = trim($inNav[0]->__toString());
		} else {
			//$this->title = trim($this->nav['xml']->{$this->lang['active']}->main->item[0]->__toString());
		}
		
	}
	
	/*
	* @param string $file
	* @return SimpleXMLElement
	*/
	protected function loadXML($file) {
		
		if (file_exists($file)) {
			$data = new SimpleXMLElement($file,null,true);
			return $data;
		}
		
	}
	
	/*
	* @param SimpleXMLElement $navitem
	* @param string $lang
	* @return array
	*/
	function getMenuItemAttributes($navitem, $lang = null) {
		$label = trim($navitem->__toString());
		if (count($this->lang['list']) > 1 && !$lang) {
			$href = $this->lang['active'].'/'.$navitem['href'];
		} else if (!empty($lang)) {
			$href = $lang.'/'.$navitem['href'];
			$label = $lang;
		} else {
			$href = $navitem['href'];
		}
		$active = ($navitem['href'] == $this->slug) ? true : false;
		if ($active) {
			$this->pid = intval($navitem['id']->__toString());
			$class = ' class="active"';
		} else {
			$class = '';
		}
		return [
			'label' => $label,
			'href' => $href,
			'class' => $class
		];
	}
	
	/*
	* @param string $menu
	* @param string $class
	* @return string
	*/	
	public function getMenuList($menu, $class = null) {
		
		$nav = (!$class) ? '<menu>' : '<menu class="'.$class.'">';
		
		if ($menu !== 'lang') {
			foreach ($this->nav['xml']->{$this->lang['active']}->$menu->children() as $navitem) {
				$attr = $this->getMenuItemAttributes($navitem);
				$nav .= '<li'.$attr['class'].'><a href="'.$attr['href'].'">'.$attr['label'].'</a>';
				if ($navitem->count()) {
					$nav .= '<menu>';
					foreach ($navitem->children() as $navitem) {
						$attr = $this->getMenuItemAttributes($navitem);
						$nav .= '<li'.$attr['class'].'><a href="'.$attr['href'].'">'.$attr['label'].'</a></li>';
					}
					$nav .= '</menu>';
				}
				$nav .= '</li>';
				
			}
		} else if ($menu === 'lang') {
			foreach ($this->lang['list'] as $lang) {
				if ($this->pid !== 0) {
					$navitem = $this->nav['xml']->xpath('//'.$lang.'//item[@id="'.strval($this->pid).'"]');
					if (!empty($navitem)) {
						$attr = $this->getMenuItemAttributes($navitem[0], $lang);
						$nav .= '<li'.$attr['class'].'><a href="'.$attr['href'].'">'.$attr['label'].'</a></li>';
					}
				} else {
					$class = ($lang === $this->lang['active']) ? ' class="active"' : '';
					$nav .= '<li'.$class.'><a href="'.$lang.'">'.$lang.'</a></li>';
				}
			}
		}
		
		$nav .= '</menu>';
		
		return $nav;
		
	}
	
	
	/* ! TODO */
/*
	protected function redirect() {
		
		global $nav;
		if (!file_exists('pages/'.$this->lang.'/'.$this->page.'.php')) {
			header('Location: '.$this->baseurl.$this->lang.'/'.current($nav['main'][$this->lang]));
			exit;
		} else if (!in_array($this->lang,$this->lang_available)) {
			$this->lang = $this->lang_available[0];
			header('Location: '.$this->baseurl.$this->lang.'/'.current($nav['main'][$this->lang]));
			exit;
		}
		
	}
*/
	

}
	
?>