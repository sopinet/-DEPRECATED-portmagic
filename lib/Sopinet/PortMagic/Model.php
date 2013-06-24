<?php
namespace Sopinet\PortMagic;

class Model {
	static $plugins;
	static $webs;
	static $about_key;
	static $token_key;
	static $profiles;

	// Init Plugin
	function initPlugins() {
		// Include files
		$i = 1;
		$level = dirname(__FILE__)."/../plugins/level".$i;
		while (file_exists($level)) {
		    if ($handle = opendir($level)) {
		        while (false !== ($file = readdir($handle))) {
		                if ($file != "." && $file != ".." && $file != '.svn' && $file[strlen($file)-1] != "~") {
		                       // require_once($level."/".$file);
											$temp = explode(".",$file);
											PModel::$plugins[$i][] = $temp[0];
		                }
		        }
		    }
			$i++;
			$level = dirname(__FILE__)."/../plugins/level".$i;
		}
	}

	function initWebs($data) {
		$i = 0;
		foreach($data['webs'] as $d) {
			$plugin = PModel::whatpluginis($d);
			PModel::$webs[$i] = new $plugin($d);
			$i++;
		}
	}
	
	function initAbout($data) {
		PModel::$about_key = $data['about']['client_id'];
		$i = 0;
		foreach($data['about']['profiles'] as $pro) {
			PModel::$profiles[$i] = PModel::processProfile($pro);
			$i++;
		}
	}
	
	function post_to_url($url, $data) { 
		$fields = ''; 
		foreach($data as $key => $value) { 
			$fields .= $key . '=' . $value . '&'; 
		} 
		rtrim($fields, '&'); 
		$post = curl_init(); 
		curl_setopt($post, CURLOPT_URL, $url); 
		curl_setopt($post, CURLOPT_POST, count($data)); 
		curl_setopt($post, CURLOPT_POSTFIELDS, $fields); 
		curl_setopt($post, CURLOPT_RETURNTRANSFER, 1); 
		$result = curl_exec($post); 
		curl_close($post);
		
		return $result;
	}

	function proccessIMG($url) {
// ORIGINAL:			return $url;

		// TODO: Hacer configurable desde fuente

		$dir = PFrameWork::$config->get('dir') . "cache/profiles/";
		if (!file_exists($dir)) mkdir($dir);
		$file = $dir . md5($url) . ".jpg";
		if (!file_exists($file) || (time() - filemtime($file) > PFrameWork::$config->get('cachetime'))) {
			PModel::saveFileURL($file, $url);

			// WEB INTERESANTE: http://www.rpublica.net/imagemagick/artisticas.html#sepia-tone

			$file_efx = $dir . md5($url). "_ex.jpg";
			// pencil: $command_thumb = "convert -define jpeg:size=300x300 ".$file." -thumbnail 300x220^ -colorspace Gray -negate -edge 1 -negate -blur 0x.5 -gravity center -extent 300x220 ".$file_efx;
			// sepia: */ $command_thumb = "convert -define jpeg:size=300x300 ".$file." -thumbnail 300x220^ -sepia-tone 80% -gravity center -extent 300x220 ".$file_efx;
			// azul + sepia: $command_thumb = "convert -define jpeg:size=300x300 ".$file." -thumbnail 300x220^ -sepia-tone 70% -fill blue -tint 80% -gravity center -extent 300x220 ".$file_efx;
			// azul: $command_thumb = "convert -define jpeg:size=300x300 ".$file." -thumbnail 300x220^ -fill blue -tint 60% -gravity center -extent 300x220 ".$file_efx;
			/* sketch:  $command_thumb = "convert -define jpeg:size=300x300 ".$file." -thumbnail 300x220^ -sketch 0x20+120 -gravity center -extent 300x220 ".$file_efx;*/
			// solarize: $command_thumb = "convert -define jpeg:size=300x300 ".$file." -thumbnail 300x220^ -solarize 55 -gravity center -extent 300x220 ".$file_efx;
			// colorize sopinet
			// $command_thumb = "convert -define jpeg:size=300x300 ".$file." -thumbnail 300x220^ -colorize 99,0,0 -gravity center -extent 300x220 ".$file_efx;

			/* Blanco y negro */ $command_thumb = "convert -define jpeg:size=300x300 ".$file." -thumbnail 300x220^ -gravity center -extent 300x220 ".$file_efx;	
			
/*echo $command_thumb;
exit();*/
			exec($command_thumb, $output);
		}
		$ret = "cache/profiles/".md5($url). "_ex.jpg";
		return $ret;
	}
	
	// TODO: Temporizador de caché
	// time() - filemtime($this->cacheFileName)) < $this->cacheTime
	function processProfile($profile) {
		$url_data = "https://api.about.me/api/v2/json/user/view/".$profile;
		$dir = PFrameWork::$config->get('dir') . "cache/profiles/";
		if (!file_exists($dir)) mkdir($dir);
		$file = $dir . md5($url_data);

		if (!file_exists($file) || (time() - filemtime($file) > PFrameWork::$config->get('cachetime'))) {
			$data = array(
					"extended" => "true",
					"client_id" => PModel::$about_key				
					);

			$ret = PModel::post_to_url($url_data, $data);
			$fp = fopen($file, 'w');
			fwrite($fp, $ret);
			fclose($fp);
		}
		return PModel::getArray($file);
	}

	function getAvatar($profile) {
		if ($profile['avatar'] == "") $url = $profile["thumbnail_291x187"];
		else $url = $profile["avatar"];
		return PModel::proccessIMG($url);
	}

	// What is?
	function whatpluginis($url)
	{
		foreach(array_reverse(PModel::$plugins) as $level) {
			foreach($level as $plugin) {
				if (call_user_func($plugin."::isIt", $url) == true) return new $plugin($url);
			}
		}
		return false;
	}

	function getDomainFromUrl($url) {
		$domain = trim($url);
		if(substr(strtolower($domain), 0, 7) == "http://") $domain = substr($domain, 7);
		if(substr(strtolower($domain), 0, 4) == "www.") $domain = substr($domain, 4);
		$temp = explode("/",$domain);
		return $temp[0];
	}

	function getNameFromUrl($url) {
		$domain = trim($url);
		if(substr(strtolower($domain), 0, 7) == "http://") $domain = substr($domain, 7);
		$temp = explode(".",$domain);
		return ucfirst($temp[0]);
	}

	function readFile($file) {
		$fh = fopen($file, "rb");
		$data = fread($fh, filesize($file));
		fclose($fh);
		return $data;
	}

	function writeFile($file, $content) {
		$fh = fopen($file, 'w');
		fwrite($fh, $content); 
		fclose($fh);
		return true;
	}

	function getImage($url, $dimx, $dimy) {
		$wk = PFrameWork::$config->get('dir') . PFrameWork::$config->get('script_wk');
		$name = md5($url);

		$imgdir = PFrameWork::$config->get('dir') . "cache/images/";
		if (!file_exists($imgdir)) mkdir($imgdir);

		$dir = $imgdir.$dimx."x".$dimy;
		if (!file_exists($dir)) mkdir($dir);
		$file = $dir."/".$name.".png";

		if (!file_exists($file)) {
/*			if ($dimx < 400) {
				$dx = $dimx * 2;
				$dy = $dimy * 2;
				$command = "convert -define jpeg:size=".$dx."x".$dy)." ".$file_thum." -thumbnail 100x100^ -gravity center -extent 100x100 ".$file_big;
			} else {*/
				$command = $wk." --quality 90 --width ".$dimx." ".$url." ".$file;
//			}
			exec($command, $output);
		}
		return PFrameWork::$config->get('url') . "cache/images/".$dimx."x".$dimy."/". $name . ".png";
	}
	
	function getTopCropThumbnail($url, $ox, $oy, $dimx, $dimy) {
		$wk = PFrameWork::$config->get('dir') . PFrameWork::$config->get('script_wk');
		$name = md5($url);

		$dir = PFrameWork::$config->get('dir') . "cache/images/".$dimx."x".$dimy;
		$diro = PFrameWork::$config->get('dir') . "cache/images/".$ox."x".$oy;
		if (!file_exists($dir)) mkdir($dir);
		if (!file_exists($diro)) mkdir($diro);
		
		$file = $diro."/".$name.".png";
		$file_thumb = $dir."/".$name.".png";
		
		if (!file_exists($file)) {
			$file = PModel::getImage($url, $ox, $oy);
		}
		
		if (!file_exists($file_thumb)) {
			$command = "convert ".$file." -crop ".$dimx."x".$dimy."+0+0^ ".$file_thumb;
			exec($command, $output);			
		}
		return PFrameWork::$config->get('url') . "cache/images/".$dimx."x".$dimy."/". $name . ".png";
	}

	function getThumbnail($url, $ox, $oy, $dimx, $dimy) {
		$wk = PFrameWork::$config->get('dir') . PFrameWork::$config->get('script_wk');
		$name = md5($url);

		$dir = PFrameWork::$config->get('dir') . "cache/images/".$dimx."x".$dimy;
		$diro = PFrameWork::$config->get('dir') . "cache/images/".$ox."x".$oy;
		if (!file_exists($dir)) mkdir($dir);
		if (!file_exists($diro)) mkdir($diro);

		$file = $diro."/".$name.".png";
		$file_thumb = $dir."/".$name.".png";

		if (!file_exists($file)) {
			$file = PModel::getImage($url, $ox, $oy);
		}

		if (!file_exists($file_thumb)) {
			$dx = $dimx * 2;
			$dy = $dimy * 2;
			$command = "convert -define jpeg:size=".$dx."x".$dy." ".$file." -thumbnail ".$dimx."x".$dimy."^ -gravity center -extent ".$dimx."x".$dimy." ".$file_thumb;
			exec($command, $output);
		}
		return PFrameWork::$config->get('url') . "cache/images/".$dimx."x".$dimy."/". $name . ".png";
	}

	function getImageFromSkill($skill) {
		return PFrameWork::$config->get('url') . "skills/" . $skill . ".png";
	}

	function getTitleFromSkill($skill) {
		return $skill;
	}

	function getDescFromSkill($skill) {
		$file = PFrameWork::$config->get('dir') . "skills/" . $skill . ".txt";
		return PModel::getFile($file);
	}

	function getThumbnailFromImage($image_url, $dimx, $dimy) {
		$dir = PFrameWork::$config->get('dir') . "cache/images/original";
		$dirt = PFrameWork::$config->get('dir') . "cache/images/".$dimx."x".$dimy;

		if (!file_exists($dir)) mkdir($dir);
		if (!file_exists($dirt)) mkdir($dirt);

		$temp = explode(".",$image_url);
		$name = md5($image_url);
		$file = $dir . "/" . $name . "." . substr(strrchr($image_url,'.'),1);
		$file_thumb = $dirt . "/" . $name . "." . substr(strrchr($image_url,'.'),1);

		if (!file_exists($file)) {
			PModel::saveFileURL($file, $image_url);
		}

		if (!file_exists($file_thumb)) {
			$dx = $dimx * 2;
			$dy = $dimy * 2;
			$command = "convert -define jpeg:size=".$dx."x".$dy." ".$file." -thumbnail ".$dimx."x".$dimy."^ -gravity center -extent ".$dimx."x".$dimy." ".$file_thumb;
			exec($command, $output);
		}

		return PFrameWork::$config->get('url') . "cache/images/".$dimx."x".$dimy."/". $name . "." . substr(strrchr($image_url,'.'),1);
	}

	// Get images From URL
	function getImages($url, $config) {
		$wk = PFrameWork::$config->get('dir') . PFrameWork::$config->get('script_wk');
		$name = md5($url);

		$file_ = PFrameWork::$config->get('dir') . "cache/images/".$name.".png";
		if (!file_exists($file_big)) {
			$command_pdf = $wk." --quality 90 --width 1024 ".$url." ".$file_big;
			exec($command_pdf, $output);
		}

		$file_mobile = $config->get('dir') . "cache/mobile/".$name.".png";
		if (!file_exists($file_mobile)) {
			$command_pdf_mobile = $wk." --quality 90 --width 400 ".$url." ".$file_mobile;
			exec($command_pdf_mobile, $output);
		}

		$file_thumb = $config->get('dir') . "cache/thumb/".$name.".png";
		if (!file_exists($file_thumb)) {
			$command_thumb = "convert -define jpeg:size=200x200 ".$file_thumb." -thumbnail 100x100^ -gravity center -extent 100x100 ".$file_big;
			exec($command_thumb, $output);
		}

		$data['big'] = $config->get('url') . "cache/big/" . $name . ".png";
		$data['thumb'] = $config->get('url') . "cache/thumb/" . $name . ".png";
		$data['mobile'] = $config->get('url') . "cache/mobile/" . $name . ".png";
		return $data;
	}

	function getSafeKey($array, $key) {
		if (is_array($array) && array_key_exists($key, $array)) return $array[$key];
		else return "";
	}

	// Get metadata from URL
	function getMetadata($url) {
		$name = md5($url);

		$dir = PFrameWork::$config->get('dir') . "cache/metadata/";
		if (!file_exists($dir)) mkdir($dir);

		$file = $dir.$name.".json";

		if (!file_exists($file)) {
			$data = get_meta_tags($url);
			$tow = json_encode($data);
			$fp = fopen($file, 'w');
			fwrite($fp, $tow);
			fclose($fp);
			return $data;
		} else {
			return PModel::getArray($file);
		}
	}

	function getContent($url) {
		$name = md5($url);
		$dir = PFrameWork::$config->get('dir') . "cache/content/";
		if (!file_exists($dir)) mkdir($dir);
		$file = $dir.$name.".html";
		
		if (!file_exists($file)) {
			$content = file_get_contents($url);
			$fp = fopen($file, 'w');
			fwrite($fp, $content);
			fclose($fp);
			return $content;
		} else {
			return PModel::getFile($file);
		}
	}

	function file_web_exists($url)
	{
		$dir = PFrameWork::$config->get('dir') . "cache/infofile";
		if (!file_exists($dir)) mkdir($dir);
		$name = md5($url);
		$file = $dir . '/' . $name . '.info';
		if (!file_exists($file)) {
			$fp = curl_init($url);
			$ret = curl_setopt($fp, CURLOPT_RETURNTRANSFER, 1); 
			$ret = curl_setopt($fp, CURLOPT_TIMEOUT, 30); 
			$ret = curl_exec($fp); 
			$info = curl_getinfo($fp, CURLINFO_HTTP_CODE); 
			curl_close($fp);
			$fp = fopen($file, 'w');
			fwrite($fp, $info);
			fclose($fp);
		} else {
			$info = PModel::getFile($file);
		}
		if($info == 404)
		{
			return false;
		}
		else
		{
			return true;
		}
	}

	function getWhoisDomain($url)
	{
		$dir = PFrameWork::$config->get('dir') . "cache/whoisdomain";
		if (!file_exists($dir)) mkdir($dir);
		$name = md5($url);
		$file = $dir . "/" . $name . ".whois";
		if (!file_exists($file)) {
			require_once(PFrameWork::$config->get('dir') . "externals/phpwhois/whois.main.php");
			$domain = PModel::getDomainFromUrl($url);
			$whois = new Whois();
			$result = $whois->Lookup($domain);
			$fp = fopen($file, 'w');
			$tow = json_encode($result);
			fwrite($fp, $tow);
			fclose($fp);
			return $result;
		}	else {
			return PModel::getArray($file);
		}
	}

	// Get Title
/*
	function getTitle($Url){
		$name = md5($url);
		$file = $config->get('dir') . "cache/title/".$name.".text";

		if (!file_exists($file)) {
			$content = PModel::getContent($url);
		    preg_match("/\<title\>(.*)\<\/title\>/",$str,$title);
			$fp = fopen($file, 'w');
			fwrite($fp, $title[1]);
			fclose($fp);
			return $title[1];
		} else {
			return PModel::getFile($file);
		}
	}
*/

	function getArray($file) {
		return json_decode(PModel::getFile($file), true);
	}

	// Get file
	function getFile($file) {
		$string = file_get_contents($file);
		return $string;
	}

	function saveFileURL($file, $url) {
		// TODO: Se puede activar... file_put_contents($file, file_get_contents($url));
		$ch = curl_init($url);
		$fp = fopen($file, 'wb');
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_exec($ch);
		curl_close($ch);
		fclose($fp);
	}

	function getFilesPHP($dir) {
		if ($handle = opendir($dir)) {
		  while (false !== ($entry = readdir($handle))) {
		      if ($entry != "." && $entry != "..") {
						$temp = pathinfo($dir . '/' . $entry);
						if ($temp['extension'] == 'php') {
							$files[] = $entry;
						}
		      }
		  }
		  closedir($handle);
		}
		return $files;
	}

	function getPages($layout, $ext) {
		$dir = PFrameWork::$config->get('dir') . "layout/" . $layout . '/' . $ext;
		$pages = PModel::getFilesPHP($dir);
		return $pages;
	}
}
?>