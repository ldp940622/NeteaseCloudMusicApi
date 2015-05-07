<?php
/**
 * NeteaseCloudMusicApi
 * @author Joseph Lee
 */
class Api {
	const refer = 'http://music.163.com';

	/**
	 * 封装curl操作
	 * @param  String $url  操作的URL
	 * @param  Array  $data POST参数,默认空
	 * @return Array        获得的JSON数据
	 */
	public function request($url, $data = null) {
		$ch = curl_init();
		curl_setopt_array($ch, [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_REFERER => self::refer]);
		if ($data) {
			curl_setopt_array($ch, [
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => $data]);
		}
		$html = curl_exec($ch);
		curl_close($ch);
		return json_decode($html, true);
	}

	/**
	 * 将String转换为byteArray
	 * @param  String $string 传入字符串
	 * @return Array          byteArray
	 */
	public static function getBytes($string) {
		$bytes = array();
		for ($i = 0; $i < strlen($string); $i++) {
			$bytes[] = ord($string[$i]);
		}
		return $bytes;
	}

	/**
	 * 将byte数组转成ascii编码的字符串
	 * @param  Array $bytes  byteArray
	 * @return String        ascii字符串
	 */
	public static function toStr($bytes) {
		$str = '';
		for ($i = 0; $i < count($bytes); $i++) {
			$str .= chr($bytes[$i]);
		}
		// var_dump($str);
		return $str;
	}
	/**
	 * 获得加密后的下载URL
	 * @param  int $id SongId
	 * @return String  mp3Url
	 */
	public function get_hd_mp3_url($id) {
		$byte1[] = self::getBytes('3go8&$8*3*3h0k(2)2'); //18
		$byte2[] = self::getBytes($id); //16
		$magic = $byte1[0];
		$song_id = $byte2[0];
		for ($i = 0; $i < count($song_id); $i++) {
			$song_id[$i] = $song_id[$i] ^ $magic[$i % count($magic)];
		}
		$result = base64_encode(md5(self::toStr($song_id), true));
		$result = str_replace('/', '_', $result);
		$result = str_replace('+', '-', $result);
		return "http://m1.music.126.net/" . $result . '/' . number_format($id, 0, '', '') . ".mp3";
	}

	/**
	 * 登录操作
	 * @param  String $username 用户名
	 * @param  String $password   密码
	 * @return Json       返回详细信息
	 */
	public function login($username, $password) {
		$url = 'http://music.163.com/api/login';
		$data = http_build_query(array(
			'username' => $username,
			'password' => md5($password),
			'rememberLogin' => 'true',
		));
		$result = null;
		$jsonArr = self::request($url, $data);
		if ($jsonArr['code'] === 200) {
			$result['status'] = 'success';
			$result['info'] = array();
			$result['info']['userId'] = $jsonArr['profile']['userId'];
			$result['info']['nickname'] = $jsonArr['profile']['nickname'];
		} else {
			$result['status'] = 'false';
		}
		return json_encode($result, JSON_UNESCAPED_UNICODE);
	}
	/**
	 * 根据userId获得歌单
	 * @param  int $userId 用户ID
	 * @return Json        歌单
	 */
	public function get_playlist_by_user($userId) {
		$url = 'http://music.163.com/api/user/playlist/?offset=0&limit=100&uid=' . $userId;
		$result = null;
		$jsonArr = self::request($url);
		$playlist = $jsonArr['playlist'];
		if ($playlist && $jsonArr['code'] === 200) {
			$result['status'] = 'success';
			$result['info'] = array();
			for ($i = 0; $i < count($playlist); $i++) {
				$arrayName = array(
					'name' => $playlist[$i]['name'],
					'playlistId' => $playlist[$i]['id'],
				);
				array_push($result['info'], $arrayName);
			}
		} else {
			$result['status'] = 'false';
		}
		return json_encode($result, JSON_UNESCAPED_UNICODE);
	}
	/**
	 * 根据歌单ID获得详细信息
	 * @param  int $playlistId 歌单id
	 * @return Json     details_list
	 */
	public function get_playlist_details($playlistId) {
		$url = 'http://music.163.com/api/playlist/detail?id=' . $playlistId;
		$result = null;
		$jsonArr = self::request($url);
		$playlist = $jsonArr['result']['tracks'];
		if ($jsonArr['code'] === 200) {
			$result['status'] = 'success';
			$result['info'] = array();
			for ($i = 0; $i < count($playlist); $i++) {
				$arrayName = array(
					'songId' => $playlist[$i]['id'],
					'songName' => $playlist[$i]['name'],
					'artistsId' => $playlist[$i]['artists'][0]['id'],
					'artistsName' => $playlist[$i]['artists'][0]['name'],
					'albumName' => $playlist[$i]['album']['name'],
					'albumId' => $playlist[$i]['album']['id'],
					'mp3Url' => self::get_hd_mp3_url(number_format($playlist[$i]['hMusic']['dfsId'], 0, '', '')),
				);
				array_push($result['info'], $arrayName);
			}
		} else {
			$result['status'] = 'false';
		}

		return json_encode($result, JSON_UNESCAPED_UNICODE);
	}

	/**
	 * 根据歌手名获得详细信息
	 * @param  String $name 歌手名
	 * @return Json       详细信息
	 */
	public function search_artist_by_name($name) {
		$url = 'http://music.163.com/api/search/get';
		$data = http_build_query(array(
			's' => $name,
			'type' => 100,
			'offset' => 0,
			'sub' => 'false',
			'limit' => 10,
		));
		$result = null;
		$jsonArr = self::request($url, $data);
		if ($jsonArr['code'] === 200) {
			$result['status'] = 'success';
			if ($jsonArr['result']['artistCount'] === 0) {
				$result['info'] = '没查找到歌手';
			} else {
				$artistsList = $jsonArr['result']['artists'];
				$result['info'] = array();
				for ($i = 0; $i < count($artistsList); $i++) {
					$artist = array(
						'artistId' => $artistsList[$i]['id'],
						'artistName' => $artistsList[$i]['name'],
					);
					// $result['info'] = $artist;
					array_push($result['info'], $artist);
				}
			}
		} else {
			$result['status'] = 'false';
		}
		return json_encode($result, JSON_UNESCAPED_UNICODE);
	}

	/**
	 * 根据专辑名获得详细信息
	 * @param  String $name 专辑名
	 * @return Json       详细信息
	 */
	public function search_album_by_name($name) {
		$url = 'http://music.163.com/api/search/get';
		$data = http_build_query(array(
			's' => $name,
			'type' => 10,
			'offset' => 0,
			'sub' => 'false',
			'limit' => 20,
		));
		$result = null;
		$jsonArr = self::request($url, $data);
		if ($jsonArr['code'] === 200) {
			$result['status'] = 'success';
			if ($jsonArr['result']['albumCount'] === 0) {
				$result['info'] = '没查找到专辑';
			} else {
				$albumsList = $jsonArr['result']['albums'];
				$result['info'] = array();
				for ($i = 0; $i < count($albumsList); $i++) {
					$album = array(
						'albumId' => $albumsList[$i]['id'],
						'albumName' => $albumsList[$i]['name'],
					);
					array_push($result['info'], $album);
				}
			}
		} else {
			$result['status'] = 'false';
		}
		return json_encode($result, JSON_UNESCAPED_UNICODE);
	}

	/**
	 * 根据歌曲名获得详细信息
	 * @param  String $name 歌曲名
	 * @return Json       详细信息
	 */
	public function search_song_by_name($name) {
		$url = 'http://music.163.com/api/search/get';
		$data = http_build_query(array(
			's' => $name,
			'type' => 1,
			'offset' => 0,
			'sub' => 'false',
			'limit' => 100,
		));
		$result = null;
		$jsonArr = self::request($url, $data);
		if ($jsonArr['code'] === 200) {
			$result['status'] = 'success';
			if ($jsonArr['result']['songCount'] === 0) {
				$result['info'] = '没查找到歌曲';
			} else {
				$songsList = $jsonArr['result']['songs'];
				$result['info'] = array();
				// 遍历歌曲
				for ($i = 0; $i < count($songsList); $i++) {
					$artistNames = array();
					// 遍历歌手
					foreach ($songsList[$i]['artists'] as $artist) {
						array_push($artistNames, $artist['name']);
					}
					$song = array(
						'songId' => $songsList[$i]['id'],
						'songName' => $songsList[$i]['name'],
						'artistNames' => $artistNames,
					);
					array_push($result['info'], $song);
				}
			}
		} else {
			$result['status'] = 'false';
		}
		return json_encode($result, JSON_UNESCAPED_UNICODE);
	}

	/**
	 * 根据歌手ID获得歌手专辑
	 * @param  Int $artistId 歌手ID
	 * @return Json         专辑信息
	 */
	public function get_artist_albums($artistId) {
		$result = null;
		$url = "http://music.163.com/api/artist/albums/$artistId?offset=0&limit=50";
		$jsonArr = self::request($url);
		if ($jsonArr['code'] === 200) {
			$result['status'] = 'success';
			$albumsList = $jsonArr['hotAlbums'];
			$result['info'] = array();
			for ($i = 0; $i < count($albumsList); $i++) {
				$album = array(
					'albumId' => $albumsList[$i]['id'],
					'albumName' => $albumsList[$i]['name']);
				array_push($result['info'], $album);
			}
		} else {
			$result['status'] = 'false';
		}
		return json_encode($result, JSON_UNESCAPED_UNICODE);
	}

	/**
	 * 根据专辑ID取得专辑歌曲详细信息
	 * @param  int $albumId 专辑ID
	 * @return Json       详细信息
	 */
	public function get_album_songs($albumId) {
		$result = null;
		$url = "http://music.163.com/api/album/$albumId/";
		$jsonArr = self::request($url);
		if ($jsonArr['code'] === 200) {
			$result['status'] = 'success';
			$songsList = $jsonArr['album']['songs'];
			$result['info'] = array();
			for ($i = 0; $i < count($songsList); $i++) {
				$song = array(
					'songId' => $songsList[$i]['id'],
					'songName' => $songsList[$i]['name'],
					'albumName' => $songsList[$i]['album']['name'],
					'albumName' => $songsList[$i]['album']['id'],
					'artistsId' => $songsList[$i]['artists'][0]['id'],
					'artistsName' => $songsList[$i]['artists'][0]['name'],
					'mp3Url' => self::get_hd_mp3_url(number_format($songsList[$i]['hMusic']['dfsId'], 0, '', '')));
				array_push($result['info'], $song);
			}
		} else {
			$result['status'] = 'false';
		}
		return json_encode($result, JSON_UNESCAPED_UNICODE);
	}
}
