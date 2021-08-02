<?php 
return array(

	'CallMeDEBUG' => 1, // дебаг сообщения в логе: 1 - пишем, 0 - не пишем
	'tech' => 'SIP', 
	'authToken' => '', //токен авторизации битрикса
        'bitrixApiUrl' => '', //url к api битрикса (входящий вебхук)
	'extentions' => array(), // список внешних номеров, через запятую
	'context' => 'DefaultOutgoingRule', //исходящий контекст для оригинации звонка
	'asterisk' => array( // настройки для подключения к астериску
		    'host' => 'localhost',
		    'scheme' => 'tcp://',
		    'port' => 5038,
		    'username' => '',
		    'secret' => '',
		    'connect_timeout' => 10000,
		    'read_timeout' => 10000
		),
	'listener_timeout' => 30000, //скорость обработки событий от asterisk
    'bx24' => array(
        'default_user_number' => '405',
        

    ),
    'bx24_crm_source' => array(
        'default_crm_source' => '1'
    ),
    'user_show_cards' => array( ), //список внутренних номеров, через запятую

);
