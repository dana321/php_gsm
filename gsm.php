<?php

include 'php_serial.class.php';
	/**
		* @global Boolean $GLOBALS['serial'] variable appel de la class serial
		*/
	$GLOBALS['serial'] = False;

	/**
		* @package    GSM_SIM800L
		* @author David AUVRÉ <d.auvre[@]free.fr>
		* @thanks Rémy Sanchez <thenux@gmail.com> pour la class php_serial
		* @deprecated 1.0.0
		*
		* Cette class permet de gérer un module gsm (sim800 ou sim900) via PHP.<br>
		* pour qu'elle fonction correctement il faut autoriser les port com<br>
		* linux : <b>sudo usermod -a -G dialout www-data</b>
		*
		*/
class Gsm {

	/**
		* @var bool $_etat_com Etat de l'initialiation de l'ouverture du port com
		*/
	var $_etat_com = False ;

	/**
     * Init de la com du module gsm (sim800 ou sim900)
     *
     * @param String $com com de dialogue avec le module par defaut /dev/ttyS1
     * @param Int $baud vitesse de communication par defaut 19200
     *
     * @return Bool
     */
	public function Init($com = '/dev/ttyS1',$baud = 19200){
		if($GLOBALS['serial'] == false){
			$GLOBALS['serial'] = new PhpSerial;
			$GLOBALS['serial']->deviceSet($com);
			$GLOBALS['serial']->confBaudRate($baud);
			$GLOBALS['serial']->deviceOpen('w+');
			if($GLOBALS['serial']->_dState == 2){
					$this->_etat_com = true;
				if($this->AtCommand('ATE0','OK') == true){
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
     * Envoie de commande AT module gsm (sim800 ou sim900)<br>
     * @filesource
     * ../doc/SIM800+Series_AT+Command+Manual_V1.09.pdf
     * <a href="../doc/sim900 AT_Commands_v1.11.pdf">SIM 900</a><BR>
     *
     * @param String $requete Commande AT a envoyer
     * @param String $reponse Réponse attendue par defaut : OK
     * @param Int $timeout Time-out de réponseen seconde par défaut : 1 seconde
     *
     *
     * @return Bool
     *
     */
	public function AtCommand($requete,$reponse = 'OK',$timeout = 1){
		if($this->_etat_com == true){
			$chr = false;
			$debut = $this->_timestamp_sec();
			$fin	 = $debut + $timeout;
			$GLOBALS['serial']->sendMessage($requete."\r\n");
			while($chr == False && $fin > $this->_timestamp_sec()){
				$chr = stripos($GLOBALS['serial']->readPort(), $reponse);
			}
			if($chr  >= 1){
				return true;
			}
			else{
				return false;
			}
		}
		else{
			return false;
		}
	}

	public function Signal(){
		if($this->_etat_com == true){
			$GLOBALS['serial']->sendMessage("AT+CSQ\r\n");
			$explode = explode(':', $GLOBALS['serial']->readPort());
			$gain = explode(',', $explode[1]);
			return round($gain[0]/7.75);
		}
	}

/////////////////////////// fonction interne ///////////////////////////
/**
     * Envoie de commande AT module gsm (sim800 ou sim900)<br>
     * <a href="../doc/SIM800+Series_AT+Command+Manual_V1.09.pdf">SIM 800</a><BR>
     * @link ../doc/SIM800+Series_AT+Command+Manual_V1.09.pdf
     * <a href="../doc/sim900 AT_Commands_v1.11.pdf">SIM 900</a><BR>
     *
     * @param String $requete Commande AT a envoyer
     * @param String $reponse Réponse attendue par defaut : OK
     * @param Int $timeout Time-out de réponseen seconde par défaut : 1 seconde
     *
     *
     * @return Bool
     *
     */
	private function _timestamp_sec(){
		$result = explode(".", microtime(true));
		return $result[0];
	}
}
?>
