<?php

include 'php_serial.class.php';
	/**
		* @global Boolean $GLOBALS['serial'] variable appel de la class serial
		*/
	$GLOBALS['serial'] = False;

	/**
		* Cette class permet de gérer un module gsm (sim800 ou sim900) via PHP.<br>
		* pour qu'elle fonction correctement il faut autoriser les port com<br>
		* linux : <b>sudo usermod -a -G dialout www-data</b>
		*
		* @package    GSM_SIM800L
		* @author David AUVRÉ <d.auvre@free.fr>
		* @thanks Rémy Sanchez <thenux@gmail.com> pour la class php_serial
		* @version 1.0.0
		* @todo Class en cours de réalisation
		*
		*/
class Gsm {

	/**
	  * Etat de l'initialiation de l'ouverture du port com
	  *
	  * @access private
		* @var bool $_etat_com
		*/
	var $_etat_com = False ;

	/**
     * Initialisation de la com du module gsm (sim800 ou sim900)
     *
     * @param String $com com de dialogue avec le module par defaut /dev/ttyS1
     * @param Int $baud vitesse de communication par defaut 9600 bauds
     *
     * @return Bool
     */
	public function Init($com = '/dev/ttyS1',$baud = 115200){
		if($GLOBALS['serial'] == false){
			$GLOBALS['serial'] = new PhpSerial;
			$GLOBALS['serial']->deviceSet($com);
			$GLOBALS['serial']->confBaudRate($baud);
			$GLOBALS['serial']->deviceOpen('w+');
			if($GLOBALS['serial']->_dState == 2){
					$this->_etat_com = true;
				if($this->AtCommand('ATE0','OK')){
					$this->AtCommand('AT+CGREG=0','OK');
					$this->AtCommand('AT+CREG=0','OK');
					return $this->_etat_com ;
				}
				else{
					return $this->_etat_com = false;
				}
			}
			else{
				return $this->_etat_com = false;
			}
		}
		elseif($GLOBALS['serial']->_dState == 2){
			return $this->_etat_com = true;

		}
	}

	/**
     * Envoie de commande AT module gsm (sim800 ou sim900)
     *
     * @link https://drive.google.com/folderview?id=0B2FlHcQRPIV1Z1hvNU5OcmNiX0E&usp=sharing Documentation sim 900 et 800
     * @param String $requete Commande AT a envoyer
     * @param String $reponse Réponse attendue par defaut : OK
     * @param Int $timeout Time-out de réponseen seconde par défaut : 1 seconde
     * @return Bool
     */
	public function AtCommand($requete='',$reponse = 'OK',$timeout = 1){
		if($this->_etat_com === true){
			$chr = false;
			$fin	 = $this->_timestamp_sec() + $timeout;
			$read_port='';
			$GLOBALS['serial']->sendMessage($requete."\r\n");
			while($chr === False && $fin > $this->_timestamp_sec()){
				$read_port .= $GLOBALS['serial']->readPort();
				$chr = stripos($read_port, $reponse );
			}
			if($chr !== False){
				echo '<br>CDM : '.$requete.'<br>chr = '.$chr.'<br>REP : '.$read_port.'<br>';

			sleep(0.5);
				return true;
			}
			else{
				echo '<br>CDM : '.$requete.'<br>chr = '.$chr.'<br>REP : '.$read_port.'<br>';

				return false;
			}
		}
		else{
			return false;
		}
	}

	/**
     * Initialisation de la 3g
     *
     * @return Bool
     */
	public function Init_3g(){

		if($this->_etat_com === true){
			$_timeout_3g = $this->_timestamp_sec()+30 ;
			$_etat_reseau = False ;
			if($this->AtCommand('AT+SAPBR=2,1','0.0.0.0',2) === true){
				while (($_etat_reseau = $this->AtCommand('AT+CGREG?','+CGREG: 0,5')) === false && $_timeout_3g >= $this->_timestamp_sec()){sleep(0.5);}
				if ($_etat_reseau == True){
					$_init = ($_etat_reseau == True) ? $this->AtCommand('AT+CGATT=1','OK',5) : False;
					$_init = ($_init == true) ? $this->AtCommand('AT+SAPBR=3,1,"CONTYPE","GPRS"') : False;
					$_init = ($_init == true) ? $this->AtCommand('AT+SAPBR=3,1,"APN","free"') : False;
					$_init = ($_init == true) ? $this->AtCommand('AT+SAPBR=3,1,"USER","Free"') : False;
					$_init = ($_init == true) ? $this->AtCommand('AT+SAPBR=1,1','OK',190) : False;
					return $_init;
				}
				else{
					return False;
				}
			}
			else{
				return True;
			}
		}
		else{
			return True;
		}
	}

	/**
     * Envoi de requete 3g
     *
     * @param String $http Lien a envoyer exemple : def-gboard.franceserv.fr/req_box.php?id_box=10N_001&fdl=AL_F1_Z120_A101"
     * @return Bool
     */
	public function Send_3g($http,$type=0){
		if($this->AtCommand('AT+CGREG?','+CGREG: 0,5') === true){
			$_etat = ($this->_etat_com === true) ? True : False;
			$_etat = ($_etat == true) ? $this->AtCommand('AT+HTTPINIT') : False;
			$_etat = ($_etat == true) ? $this->AtCommand('AT+HTTPPARA="TIMEOUT",120') : False;
			$_etat = ($_etat == true) ? $this->AtCommand('AT+HTTPPARA="CID",1') : False;
			$_etat = ($_etat == true) ? $this->AtCommand('AT+HTTPPARA="UA","BOX e-TECK"') : False;
			$_etat = ($_etat == True) ? $this->AtCommand('AT+HTTPPARA="URL","'.$http.'"') : False;
			$_etat = ($_etat == True) ? $this->AtCommand('AT+HTTPACTION='.$type,'200',125) : False;
			$_etat = ($_etat == True) ? $this->AtCommand('AT+HTTPTERM') : False;
			return $_etat ;
		}

	}

	/**
     * Retourne le signal gsm de 0 -> 4
     *
     * @return Int
     */
	public function Signal(){
			sleep(0.5);
		$explode = array();
		$gain = array();
		if($this->_etat_com == true){
			$GLOBALS['serial']->sendMessage("AT+CSQ\r\n");
			$explode = explode(':', $GLOBALS['serial']->readPort() );
			$gain = explode(',', $explode[1]);
			return round($gain[0]/7.75);
		}
	}

/////////////////////////// fonction interne ///////////////////////////


	/**
     * Convertion microseconde en seconde pour le timeout
     *
     * @return Int
     */
	private function _timestamp_sec(){
		$result = explode(".", microtime(true));
		return $result[0];
	}
}

?>
