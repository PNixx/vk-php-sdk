<?php

/**
 * VKontakte Exception class
 * @author  Odintsov S.A. https://github.com/PNixx
 */
class VkException extends Exception {

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
		parent::__construct($message, $code);
	}

	/**
	 * Получение ответа запроса
	 * @return array|null
	 */
	public function getResponse() {
		return $this->response;
	}
}