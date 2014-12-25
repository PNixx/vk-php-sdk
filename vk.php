<?php

/**
 * @version 1.0.0
 * @author  Odintsov S.A. https://github.com/PNixx
 */
class Vk {

	/**
	 * Токен доступа
	 * @var string
	 */
	private $access_token;

	/**
	 * Версия API VKontakte
	 * @var string
	 */
	private $v = '5.27';

	/**
	 * Ссылка на api vk
	 * @var string
	 */
	private $url = "https://api.vk.com/method/";

	/**
	 * Проверяет по кукам, авторизован ли юзер
	 * Полезна для приложений VK в iframe
	 * @param int $app_id
	 * @return object
	 */
	static public function isAuth($app_id) {

		$session = array();
		$member = false;
		$valid_keys = array('expire', 'mid', 'secret', 'sid', 'sig');
		$app_cookie = $_COOKIE['vk_app_' . $app_id];
		if( $app_cookie ) {
			$session_data = explode('&', $app_cookie, 10);
			foreach( $session_data as $pair ) {
				list($key, $value) = explode('=', $pair, 2);
				if( empty($key) || empty($value) || !in_array($key, $valid_keys) ) {
					continue;
				}
				$session[$key] = $value;
			}
			foreach( $valid_keys as $key ) {
				if( !isset($session[$key]) ) {
					return $member;
				}
			}
			ksort($session);

			$sign = '';
			foreach( $session as $key => $value ) {
				if( $key != 'sig' ) {
					$sign .= ($key . '=' . $value);
				}
			}
			$sign .= $app_id;
			$sign = md5($sign);
			if( $session['sig'] == $sign && $session['expire'] > time() ) {
				$member = array(
					'id'     => intval($session['mid']),
					'secret' => $session['secret'],
					'sid'    => $session['sid']
				);
			}
		}

		return (object)array(
			'member'  => $member,
			'session' => $session
		);
	}

	/**
	 * Конструктор
	 * @param string $access_token
	 */
	public function __construct($access_token) {

		$this->access_token = $access_token;
	}

	/**
	 * Делает запрос к Api VK
	 * @param string     $method Метод
	 * @param array|null $params Параметры запроса
	 * @param bool       $post   Отправить запрос как post
	 * @param int        $try    Количество попыток
	 * @return bool|mixed
	 * @throws Exception
	 */
	public function method($method, array $params = [], $post = false, $try = 2) {

		//Если в параметрах не указана версия, ставим по умолчанию
		if( isset($params['v']) == false ) {
			$params['v'] = $this->v;
		}

		//Генерируем строку запроса
		$p = http_build_query($params);

		//Генерируем заголовок для POST запроса
		$context = null;
		if( $post ) {
			$context = stream_context_create(array(
				'http' => array(
					'method'  => 'POST',
					'header'  => 'Content-Type: application/x-www-form-urlencoded' . PHP_EOL,
					'content' => $p,
				),
			));
		}

		//Пытаемся выполнить запрос
		$request = 0;
		while( $request < $try ) {
			try {

				//Выполняем запрос
				$response = file_get_contents($this->url . $method . "?" . ($post == false ? ($p ? $p . "&" : "") : "") . "access_token=" . $this->access_token, null, $context);
				if( $response ) {
					return json_decode($response);
				}
			} catch( Exception $e ) {
				$request++;
				if( $request < $try ) {
					usleep(500);
				} else {
					throw $e;
				}
			}
		}

		return false;
	}

	/**
	 * Загружает mp3 файл в VK
	 * @param string   $file     Полный путь к файлу в файловой системе
	 * @param int|null $group_id Идентификатор группы, если загружаем файл в группу
	 * @return array|bool|mixed
	 * @throws Exception
	 */
	public function uploadMp3($file, $group_id = null) {

		//Получаем сервер
		$server = $this->method("audio.getUploadServer");

		//Если ссылку на сервер не получили
		if( !isset($server->response->upload_url) ) {

			//И получили какую то не понятную ошибку, пробуем еще раз
			if( isset($server->error->error_code) && $server->error->error_code == 10 ) {

				//Спим пару секунд
				sleep(2);

				//Пробуем еще раз
				$server = $this->method("audio.getUploadServer");
			} else {
				throw new Exception("Error get upload server", 400);
			}
		}

		//Ссылка на загрузку
		$server = $server->response->upload_url;

		//Спим 1 сек перед отправкой на сервер
		sleep(1);

		//Отправляем файл на сервер
		$ch = curl_init($server);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, array('file' => class_exists("CURLFile", false) ? new CURLFile($file) : "@" . $file));
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data; charset=UTF-8'));
		$json = json_decode(curl_exec($ch));
		curl_close($ch);

		//Файл отправили, спим 1 сек, чтобы не забанили
		sleep(1);

		//Сохраняем файл
		$audio = $this->method("audio.save", array(
			"server" => $json->server,
			"audio"  => urlencode($json->audio),
			"hash"   => $json->hash,
			'v'      => '3.0'
		));

		//Проверяем сохранили ли файл
		if( isset($audio->error) || !isset($audio->response) ) {
			throw new Exception("audio.save error: " . $audio->error->error_code . ': ' . $audio->error->error_msg, 400);
		}

		if( isset($audio->response->aid) == false ) {
			print_r($audio);

			return false;
		}

		//Спим пол-секунды
		usleep(500);

		//Если загружали аудио в группу
		if( $group_id ) {
			$group_aid = $this->method("audio.add", array(
				"aid" => $audio->response->aid,
				"oid" => $audio->response->owner_id,
				"gid" => $group_id,
				'v'   => '3.0'
			));

			//Добавляли в группу, спим секунду
			usleep(500);
		}

		//Возвращаем ссылку на файл
		return array(
			"group_aid" => isset($group_aid) ? $group_aid->response : null,
			"aid"       => $audio->response->aid,
			"owner_id"  => $audio->response->owner_id,
			"link"      => "audio{$audio->response->owner_id}_{$audio->response->aid}",
			"url"       => $audio->response->url
		);
	}

	/**
	 * Загружает изображение на сервер VK
	 * @param string   $file     Полный путь к файлу в файловой системе
	 * @param int|null $group_id Если загружаем в группу
	 * @return string|bool
	 * @throws Exception
	 */
	public function uploadImage($file, $group_id = null) {

		$params = array('v' => '3.0');
		if( $group_id ) {
			$params['gid'] = $group_id;
		}

		//Получаем сервер для загрузки изображения
		$response = $this->method("photos.getWallUploadServer", $params);
		if( isset($response->response) == false ) {
			throw new Exception('Error get server url', 400);
		}
		$server = $response->response->upload_url;

		//Отправляем файл на сервер
		$ch = curl_init($server);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, array('photo' => class_exists("CURLFile", false) ? new CURLFile($file) : "@" . $file));
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data; charset=UTF-8'));
		$json = json_decode(curl_exec($ch));
		curl_close($ch);

		//Сохраняем файл на стену
		$photo = $this->method("photos.saveWallPhoto", array(
			"server" => $json->server,
			"photo"  => $json->photo,
			"hash"   => $json->hash,
			"gid"    => $group_id,
			"v"      => '3.0'
		));

		if( isset($photo->response[0]->id) ) {
			return $photo->response[0]->id;
		}

		return false;
	}

	/**
	 * Получить список альбомов группы или юзера
	 * @param $id
	 * @return array
	 */
	public function getAlbums($id) {
		$r = $this->method("audio.getAlbums", array(
			"owner_id" => $id,
			"v"        => '3.0'
		));
		$a = array();
		for( $i = 1; $i <= $r->response[0]; $i++ ) {
			$a[strtolower($r->response[$i]->title)] = $r->response[$i]->album_id;
		}

		return $a;
	}
}
