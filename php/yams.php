<?php

class YAMS {

	protected
	$host = '',
	$path = '',
	$baseurl = '',
	$template = [
		'path' => 'pages/',
		'suffix' => '.php'
	],
	$lang = [
		'active' => 'de',
		'list' => []
	],
	$page = [
		'id' => 0,
		'title' => 'Home',
		'file' => 'home',
		'template' => '',
		'slug' => '',
		'notfound' => false
	],
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
					$this->page['file'] = implode('/', $parts);
				}
			} else {
				$this->page['file'] = $slug;
			}
		}
		$this->page['slug'] = $this->page['file'];
		$this->page['file'] = str_replace('/','_',$this->page['file']);
		$this->page['template'] = $this->template['path'].$this->lang['active'].'/'.$this->page['file'].$this->template['suffix'];
		
		if (!file_exists($this->page['template'])) {
			$this->page['notfound'] = true;
			$this->page['title'] = '404';
			$this->page['template'] = $this->template['path'].$this->lang['active'].'/404'.$this->template['suffix'];
		}
		
		$navitem = $this->nav['xml']->xpath('//item[@href="'.$this->page['slug'].'"]');
		if (count($navitem) > 0 && !$this->page['notfound']) {
			$page_attributes = $this->getMenuItemAttributes($navitem[0]);
			foreach ($page_attributes as $key => $value) {
				$this->page[$key] = $value;
			}
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
		$description = ($navitem != '') ? trim($navitem->__toString()) : '';
		$href = $navitem['href']->__toString();
		$id = intval($navitem['id']->__toString());
		$title = $navitem['title']->__toString();
		$time = ($navitem['time']) ? $navitem['time']->__toString() : '';
		if (count($this->lang['list']) > 1 && !$lang) {
			$href = $this->lang['active'].'/'.$href;
		} else if (!empty($lang)) {
			$href = $lang.'/'.$href;
			$description = $lang;
		} else {
			$href = $href;
		}
		$active = ($href == $this->page['slug']) ? true : false;
		if ($active) {
			$this->page['id'] = $id;
			$class = ' class="active"';
		} else {
			$class = '';
		}
		return [
			'id' => $id,
			'title' => $title,
			'description' => $description,
			'time' => $time,
			'href' => $href,
			'active' => $active,
			'class' => $class
		];
	}
	
	/*
	* @param string $menu
	* @return array
	*/	
	public function getMenuList($menu) {
		
		$nav = [];
		
		if ($menu !== 'lang') {
			foreach ($this->nav['xml']->{$this->lang['active']}->$menu->children() as $navitem) {
				$attr = $this->getMenuItemAttributes($navitem);
				$nav[$attr['id']] = $attr;
				if ($navitem->count()) {
					$nav[$attr['id']]['sub'] = [];
					foreach ($navitem->children() as $navitem) {
						$attr = $this->getMenuItemAttributes($navitem);
						$nav[$attr['id']]['sub'][] = $attr;
					}
				}
			}
		} else if ($menu === 'lang') {
			foreach ($this->lang['list'] as $lang) {
				if ($this->page['id'] !== 0) {
					$navitem = $this->nav['xml']->xpath('//'.$lang.'//item[@id="'.strval($this->page['id']).'"]');
					if (!empty($navitem)) {
						$attr = $this->getMenuItemAttributes($navitem[0], $lang);
						$nav[$attr['id']] = $attr;
					}
				} else {
					$active = ($lang === $this->lang['active']) ? true : false;
					$nav[$attr['id']] = [
						'title' => $lang,
						'href' => $lang,
						'active' => $active
					];
				}
			}
		}
		
		return $nav;
		
	}
	
	/*
	* @param string $menu
	* @param string $class
	* @return string
	*/	
	public function getMenuListHtml($menu, $class = null) {
		
		$nav = (!$class) ? '<ul>' : '<ul class="'.$class.'">';
		
		if ($menu !== 'lang') {
			foreach ($this->nav['xml']->{$this->lang['active']}->$menu->children() as $navitem) {
				$attr = $this->getMenuItemAttributes($navitem);
				$nav .= '<li'.$attr['class'].'><a href="'.$attr['href'].'">'.$attr['title'].'</a>';
				if ($navitem->count()) {
					$nav .= '<ul>';
					foreach ($navitem->children() as $navitem) {
						$attr = $this->getMenuItemAttributes($navitem);
						$nav .= '<li'.$attr['class'].'><a href="'.$attr['href'].'">'.$attr['title'].'</a></li>';
					}
					$nav .= '</ul>';
				}
				$nav .= '</li>';
				
			}
		} else if ($menu === 'lang') {
			foreach ($this->lang['list'] as $lang) {
				if ($this->page['id'] !== 0) {
					$navitem = $this->nav['xml']->xpath('//'.$lang.'//item[@id="'.strval($this->page['id']).'"]');
					if (!empty($navitem)) {
						$attr = $this->getMenuItemAttributes($navitem[0], $lang);
						$nav .= '<li'.$attr['class'].'><a href="'.$attr['href'].'">'.$attr['title'].'</a></li>';
					}
				} else {
					$class = ($lang === $this->lang['active']) ? ' class="active"' : '';
					$nav .= '<li'.$class.'><a href="'.$lang.'">'.$lang.'</a></li>';
				}
			}
		}
		
		$nav .= '</ul>';
		
		return $nav;
		
	}
	
	/*
	* @param int $id
	* @return string
	*/	
	public function getPageUrl($id) {
		$navitem = $this->nav['xml']->xpath('//item[@id="'.$id.'"]');
		if ($navitem) {
			$pageUrl = $navitem[0]['href']->__toString();
			return $pageUrl;
		}
		return '';
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