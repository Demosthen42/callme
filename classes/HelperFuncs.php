<?php
/**
* Helpers class for working with API  
* @author Автор: ViStep.RU
* @version 1.0
* @copyright: ViStep.RU (admin@vistep.ru)
**/

class HelperFuncs {

	/**
	 * Get Internal number by using USER_ID.
	 *
	 * @param int $userid
	 *
	 * @return int internal user number
	 */
	public function getIntNumByUSER_ID($userid){
        $this->writeToLog(NULL, 'getIntNumByUSER_ID');
	    $result = $this->getBitrixApi(array("ID" => $userid), 'user.get');
        $this->writeToLog($result, 'getIntNumByUSER_ID');
	    if ($result){

	        return $result['result'][0]['UF_PHONE_INNER'];
	    } else {
	        return false;
	    }

	}

	/**
	 * Get USER_ID by Internal number.
	 *
	 * @param int $intNum
	 *
	 * @return int user id
	 */
	public function getUSER_IDByIntNum($intNum){ 
	    $result = $this->getBitrixApi(array('FILTER' => array ('UF_PHONE_INNER' => $intNum,),), 'user.get');
	    if ($result){
	        return $result['result'][0]['ID'];
	    } else {
	        return false;
	    }
	}

	/**
	 * Upload recorded file to Bitrix24.
	 *
	 * @param string $call_id
	 * @param string $recordingfile
	 * @param string $duration
	 * @param string $intNum
	 *
	 * @return int internal user number
	 */
	public function uploadRecordedFile($call_id, $recordedfile, $intNum, $duration, $disposition){
		switch ($disposition) {
            case 'ANSWER':
            case 'ANSWERED':
		 		$sipcode = 200; // успешный звонок
		 		break;
            case 'NO ANSWER':
		 		$sipcode = 304; // нет ответа
		 		break;
		 	case 'BUSY':
				$sipcode =  486; //  занято
		 		break;		 	
		 	default:
		 		if(empty($disposition)) $sipcode = 304; //если пустой пришел, то поставим неотвечено
				else $sipcode = 603; // отклонено, когда все остальное
		 		break;
		}

	    $result = $this->getBitrixApi(array(
			    	'USER_PHONE_INNER' => $intNum,
					'CALL_ID' => $call_id, //идентификатор звонка из результатов вызова метода telephony.externalCall.register
					'STATUS_CODE' => $sipcode, 
//					'CALL_START_DATE' => date("Y-m-d H:i:s"),
					'DURATION' => $duration, //длительность звонка в секундах
					'RECORD_URL' => $recordedfile //url на запись звонка для сохранения в Битрикс24
					), 'telephony.externalcall.finish');
	    if ($result){
	        return $result;
	    } else {
	        return false;
	    }
    
	}
//    загрузка аудиофайла
	public function uploadRecorderedFileTruth($call_id, $recordedfile, $recordUrl){
        $result = $this->getBitrixApi(array(
            'CALL_ID' => $call_id, //идентификатор звонка из результатов вызова метода telephony.externalCall.register
            'RECORD_URL' => $recordUrl, //url на запись звонка для сохранения в Битрикс24
            'FILENAME' => $recordedfile
        ), 'telephony.externalCall.attachRecord');
        if ($result){
            return $result;
        } else {
            return false;
        }
    }

	/**
	 * Run Bitrix24 REST API method telephony.externalcall.register.json  
	 *
	 * @param int $exten (${EXTEN} from the Asterisk server, i.e. internal number)
	 * @param int $callerid (${CALLERID(num)} from the Asterisk server, i.e. number which called us)
	 *
	 * @return array  like this:
	 * Array
	 *	(
	 *	    [result] => Array
	 *	        (
	 *	            [CALL_ID] => externalCall.cf1649fa0f4479870b76a0686f4a7058.1513888745
	 *	            [CRM_CREATED_LEAD] => 
	 *	            [CRM_ENTITY_TYPE] => LEAD
	 *	            [CRM_ENTITY_ID] => 24
	 *	        )
	 *	)
	 * We need only CALL_ID
	 */
	public function runInputCall($exten, $callerid, $line, $crm_source=null){
	    if (substr($callerid,0,1) == "9" and !(strlen($callerid) == 10)){
            $callerid = substr($callerid, 1);
        }
	    if (strlen($callerid) == 7){
            $callerid = "8342".$callerid;
        }
	    $data = array(
            'USER_PHONE_INNER' => $exten,
            //'USER_ID' => $argv[1],
            'PHONE_NUMBER' => "+7".substr($callerid, -10),
            'LINE_NUMBER' => $line,
            'TYPE' => 2,
            'CRM_CREATE' => 0,
            'SHOW' => 1,
        );
	    if (!is_null($crm_source)) {
	        $data['CRM_SOURCE'] = $crm_source;
        }
	    $result = $this->getBitrixApi($data, 'telephony.externalcall.register');
	    $this->writeToLog($result, 'runInputCall result');
	    echo var_dump($result);
	    if ($result){
	        return $result['result']['CALL_ID'];
	    } else {
	        return false;
	    }
    
	}

    /**
     * Run Bitrix24 REST API method telephony.externalcall.register.json
     *
     * @param int $exten (${EXTEN} from the Asterisk server, i.e. internal number)
     * @param int $callerid (${CALLERID(num)} from the Asterisk server, i.e. number which called us)
     *
     * @return array  like this:
     * Array
     *	(
     *	    [result] => Array
     *	        (
     *	            [CALL_ID] => externalCall.cf1649fa0f4479870b76a0686f4a7058.1513888745
     *	            [CRM_CREATED_LEAD] =>
     *	            [CRM_ENTITY_TYPE] => LEAD
     *	            [CRM_ENTITY_ID] => 24
     *	        )
     *	)
     * We need only CALL_ID
     */
    public function runOutputCall($exten, $callerid, $line){
        if (substr($callerid,0,1) == "9" and !(strlen($callerid) == 10)){
            $callerid = substr($callerid, 1);
        }
        if (strlen($callerid) == 7){
            $callerid = "8342".$callerid;
        }
        $result = $this->getBitrixApi(array(
            'USER_PHONE_INNER' => $exten,
            //'USER_ID' => $argv[1],
            'PHONE_NUMBER' => "+7".substr($callerid, -10),
            'LINE_NUMBER' => $line,
            'TYPE' => 1,
//            'CALL_START_DATE' => date("Y-m-d H:i:s"),
            'CRM_CREATE' => 0,
            'SHOW' => 1,
        ), 'telephony.externalcall.register');
        echo var_dump($result);
        $this->writeToLog($result, 'runOutputCall result');
        if ($result){
            return $result['result']['CALL_ID'];
        } else {
            return false;
        }

    }

	/**
	 * Run Bitrix24 REST API method user.get.json return only online users array
	 *
	 *
	 * @return array  like this:
	 *	Array
	 *	(
	 *	    [result] => Array
	 *	        (
	 *	            [0] => Array
	 *	                (
	 *	                    [ID] => 1
	 *	                    [ACTIVE] => 1
	 *	                    [EMAIL] => admin@your-admin.pro
	 *	                    [NAME] => 
	 *	                    [LAST_NAME] => 
	 *	                    [SECOND_NAME] => 
	 *	                    [PERSONAL_GENDER] => 
	 *	                    [PERSONAL_PROFESSION] => 
	 *	                    [PERSONAL_WWW] => 
	 *	                    [PERSONAL_BIRTHDAY] => 
	 *	                    [PERSONAL_PHOTO] => 
	 *	                    [PERSONAL_ICQ] => 
	 *	                    [PERSONAL_PHONE] => 
	 *	                    [PERSONAL_FAX] => 
	 *	                    [PERSONAL_MOBILE] => 
	 *	                    [PERSONAL_PAGER] => 
	 *	                    [PERSONAL_STREET] => 
	 *	                    [PERSONAL_CITY] => 
	 *	                    [PERSONAL_STATE] => 
	 *	                    [PERSONAL_ZIP] => 
	 *	                    [PERSONAL_COUNTRY] => 
	 *	                    [WORK_COMPANY] => 
	 *	                    [WORK_POSITION] => 
	 *	                    [WORK_PHONE] => 
	 *	                    [UF_DEPARTMENT] => Array
	 *	                        (
	 *	                            [0] => 1
	 *	                        )
     *
	 *	                    [UF_INTERESTS] => 
	 *	                    [UF_SKILLS] => 
	 *	                    [UF_WEB_SITES] => 
	 *	                    [UF_XING] => 
	 *	                    [UF_LINKEDIN] => 
	 *	                    [UF_FACEBOOK] => 
	 *	                    [UF_TWITTER] => 
	 *	                    [UF_SKYPE] => 
	 *	                    [UF_DISTRICT] => 
	 *	                    [UF_PHONE_INNER] => 555
	 *	                )
 	 *
	 *		        )
     *
	 *	    [total] => 1
	 *	)
	 */
	public function getUsersOnline(){
	    $result = $this->getBitrixApi(array(
			'FILTER' => array ('IS_ONLINE' => 'Y',),
			), 'user.get');

	    if ($result){
	    	if (isset($result['total']) && $result['total']>0) 
	    		return $result['result'];
	    	else return false;
	    } else {
	        return false;
	    }
    
	}

	/**
	 * Get CRM contact name by phone
	 *
	 * @param string $phone
	 *
	 * @return string or extNum on fail 
	 */
	public function getCrmContactNameByExtNum($extNum){
		$result = $this->getBitrixApi(array(
						'FILTER' => array ('PHONE' => $extNum,),
						'SELECT' => array ('NAME', 'LAST_NAME',),
					), 'crm.contact.list');
		$FullName = $extNum;
		if ($result) {
			if (isset($result['total']) && $result['total']>0) $FullName = $this->translit($result['result'][0]['NAME'].'_'.$result['result'][0]['LAST_NAME']);
		}
		return $FullName;
	}

	/**
	 * Show input call data for online users
	 *
	 * @param string $call_id
	 *
	 * @return bool 
	 */
	public function showInputCallForOnline($call_id){
		$online_users = $this->getUsersOnline();
		if ($online_users){
			foreach ($online_users as $user) {
				$result = $this->getBitrixApi(array(
					'CALL_ID' => $call_id,
					'USER_ID' => $user['ID'],
					), 'telephony.externalcall.show');
			}
			return true;
		} else 
			return false;
	}

	/**
	 * Show input call data for user with internal number
	 *
	 * @param int $intNum (user internal number)
	 * @param int $call_id 
	 *
	 * @return bool 
	 */
	public function showInputCall($intNum, $call_id){
		$user_id = $this->getUSER_IDByIntNum($intNum);
		if ($user_id){
			$result = $this->getBitrixApi(array(
						'CALL_ID' => $call_id,
						'USER_ID' => $user_id,
						), 'telephony.externalcall.show');
			return $result;
		} else 
			return false;
	}

    /**
     * Show input call data for user with internal number
     *
     * @param int $intNum (user internal number)
     * @param int $call_id
     *
     * @return bool
     */
    public function showOutputCall($intNum, $call_id){
        $user_id = $this->getUSER_IDByIntNum($intNum);
        if ($user_id){
            $result = $this->getBitrixApi(array(
                'CALL_ID' => $call_id,
                'USER_ID' => $user_id,
            ), 'telephony.externalcall.show');
            return $result;
        } else
            return false;
    }

	/**
	 * Hide input call data for all except user with internal number.
	 *
	 * @param int $intNum (user internal number)
	 * @param int $call_id 
	 *
	 * @return bool 
	 */
	public function hideInputCallExcept($intNum, $call_id){
		$user_id = $this->getUSER_IDByIntNum($intNum);
		$online_users = $this->getUsersOnline();
		if (($user_id) && ($online_users)){
			foreach ($online_users as $user) {
				if ($user['ID']!=$user_id){
					$result = $this->getBitrixApi(array(
						'CALL_ID' => $call_id,
						'USER_ID' => $user['ID'],
						), 'telephony.externalcall.hide');
				}
			}
			return true;
		} else 
			return false;
	}

	/**
	 * Hide input call data for user with internal number
	 *
	 * @param int $intNum (user internal number)
	 * @param int $call_id 
	 *
	 * @return bool 
	 */
	public function hideInputCall($intNum, $call_id){
		$user_id = $this->getUSER_IDByIntNum($intNum);
		if ($user_id){
			$result = $this->getBitrixApi(array(
						'CALL_ID' => $call_id,
						'USER_ID' => $user_id,
						), 'telephony.externalcall.hide');
			return $result;
		} else 
			return false;
	}

    public function crmStatusList(){
        $result = $this->getBitrixApi(array(
            '' => '',
        ), 'crm.status.list');
        return $result;
    }

    public function crmStatusEntityTypes(){
        $result = $this->getBitrixApi(array(
            '' => '',
        ), 'crm.status.entity.types');
        return $result;
    }

	/**
	 * Check string for json data.
	 *
	 * @param string $string
	 *
	 * @return bool 
	 */
	public function isJson($string) {
	    json_decode($string);
	    return (json_last_error() == JSON_ERROR_NONE);
	}

	/**
	 * Api requests to Bitrix24 
	 *
	 * @param array $data
	 * @param string $method
	 * @param string $url
	 *
	 * @return array or false 
	 */

	public function getBitrixApi($data, $method){
		$url = $this->getConfig('bitrixApiUrl');
		if (!$url) return false;
	    $queryUrl = $url.$method.'.json';
	    $queryData = http_build_query($data);
	    $curl = curl_init();
	    curl_setopt_array($curl, array(
	    CURLOPT_SSL_VERIFYPEER => 0,
	    CURLOPT_POST => 1,
	    CURLOPT_HEADER => 0,
	    CURLOPT_RETURNTRANSFER => 1,
	    CURLOPT_URL => $queryUrl,
	    CURLOPT_POSTFIELDS => $queryData,
	        ));
	    $result = curl_exec($curl);
	    curl_close($curl);
	    
	    if ($this->isJson($result)){
	        $result = json_decode($result, true);
	        return $result;
	    } else {
	        return false;
	    }
	}

	/**
	 * Write data to log file.
	 *
	 * @param mixed  $data
	 * @param string $title
	 *
	 * @return bool
	 */
	public function writeToLog($data, $title = '') {
		$debug = $this->getConfig('CallMeDEBUG');
		if($debug){
		    $log = "\n------------------------\n";
		    $log .= date("Y.m.d G:i:s") . "\n";
		    $log .= (strlen($title) > 0 ? $title : 'DEBUG') . "\n";
		    $log .= print_r($data, 1);
		    $log .= "\n------------------------\n";
		    file_put_contents(getcwd() . '/logs/CallMe.log', $log, FILE_APPEND);
		    return true;
	    }
	    else return;
	}

	/**
	 * Remove item from array.
	 *
	 * @param array $data
	 * @param mixed $needle
	 *
	 * @return array
	 */
	public function removeItemFromArray(&$data,$needle,$what) {

		if($what === 'value') {
			if (($key = array_search($needle, $data)) !== false) {
       	 		unset($data[$key]);
       		}
    	}

    	elseif($what === 'key') {
    		if (array_key_exists($needle, $data)) {
       	 		unset($data[$needle]);
       		}
       	}

        //return $data;
	}



	/**
	 * Return config value.
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function getConfig($key) {
        $config = require(__DIR__.'/../config.php');
		if (is_array($config)){
			return $config[$key];
		} else return false;
	}

	/**
	 * Translit string.
	 *
	 * @param string $string
	 *
	 * @return string
	 */
  	public function translit($string) {
	    $converter = array(
	        'а' => 'a',   'б' => 'b',   'в' => 'v',
	        'г' => 'g',   'д' => 'd',   'е' => 'e',
	        'ё' => 'e',   'ж' => 'zh',  'з' => 'z',
	        'и' => 'i',   'й' => 'y',   'к' => 'k',
	        'л' => 'l',   'м' => 'm',   'н' => 'n',
	        'о' => 'o',   'п' => 'p',   'р' => 'r',
	        'с' => 's',   'т' => 't',   'у' => 'u',
	        'ф' => 'f',   'х' => 'h',   'ц' => 'c',
	        'ч' => 'ch',  'ш' => 'sh',  'щ' => 'sch',
	        'ь' => '\'',  'ы' => 'y',   'ъ' => '\'',
	        'э' => 'e',   'ю' => 'yu',  'я' => 'ya',
	        
	        'А' => 'A',   'Б' => 'B',   'В' => 'V',
	        'Г' => 'G',   'Д' => 'D',   'Е' => 'E',
	        'Ё' => 'E',   'Ж' => 'Zh',  'З' => 'Z',
	        'И' => 'I',   'Й' => 'Y',   'К' => 'K',
	        'Л' => 'L',   'М' => 'M',   'Н' => 'N',
	        'О' => 'O',   'П' => 'P',   'Р' => 'R',
	        'С' => 'S',   'Т' => 'T',   'У' => 'U',
	        'Ф' => 'F',   'Х' => 'H',   'Ц' => 'C',
	        'Ч' => 'Ch',  'Ш' => 'Sh',  'Щ' => 'Sch',
	        'Ь' => '\'',  'Ы' => 'Y',   'Ъ' => '\'',
	        'Э' => 'E',   'Ю' => 'Yu',  'Я' => 'Ya',
	    );
	    return strtr($string, $converter);
  	}

}