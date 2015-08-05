<?php

/**
 * VKontakte Exception class
 * @author  Odintsov S.A.
 * @link https://github.com/PNixx
 */
class VkException extends Exception {

	/**
	 * Код ошибки VK
	 * @var int
	 */
	protected $error_code;

	/**
	 * Коды ошибок
	 * @link https://vk.com/dev/errors
	 */
	const ERROR_UNKNOWN = 1;
	const ERROR_APP_OFF = 2;
	const ERROR_UNKNOWN_METHOD = 3;
	const ERROR_INVALID_SIGNATURE = 4;
	const ERROR_UNAUTHORIZED = 5;
	const ERROR_MANY_REQUESTS = 6;
	const ERROR_NOT_PERMISSION = 7;
	const ERROR_UNKNOWN_REQUEST = 8;
	const ERROR_MATCH_SAME_TYPE = 9;
	const ERROR_SERVER = 10;
	const ERROR_TEST_APP = 11;
	const ERROR_CAPTCHA = 14;
	const ERROR_ACCESS_DENIED = 15;
	const ERROR_HTTPS_REQUIRED = 16;
	const ERROR_VALIDATION_REQUIRED = 17;
	const ERROR_DENIED_NOT_STANDALONE = 20;
	const ERROR_ONLY_STANDALONE = 21;
	const ERROR_METHOD_DISABLED = 23;
	const ERROR_CONFIRMATION_REQUERED = 24;
	const ERROR_REQUIRED_PARAM_BLANK = 100;
	const ERROR_INCORRECT_APP_ID = 101;
	const ERROR_INCORRECT_USER_ID = 113;
	const ERROR_INCORRECT_TIMESTAMP = 150;
	const ERROR_ACCESS_ALBUM_DENIED = 200;
	const ERROR_ACCESS_AUDIO_DENIED = 201;
	const ERROR_ACCESS_GROUP_DENIED = 203;
	const ERROR_ALBUM_IS_FULL = 300;

	//Превышено кол-во попыток получить данные
	const ERROR_EXCEEDED_ATTEMPTS = 1000;

	/**
	 * @var array|null
	 */
	private $response;

	/**
	 * Конструктор ошибки
	 * @param string     $message  Сообщение
	 * @param int        $code     Код ошибки
	 * @param array|null $response Ответ запроса
	 */
	public function __construct($message, $code, $response = null) {
		$this->response = $response;
		$this->error_code = $code;
		parent::__construct($code . ': ' . $message, 500);
	}

	/**
	 * Получение ответа запроса
	 * @return stdClass
	 */
	public function getResponse() {
		return $this->response;
	}

	/**
	 * Код ошибки
	 * @return int
	 */
	public function getErrorCode() {
		return $this->error_code;
	}
}