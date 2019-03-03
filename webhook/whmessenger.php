<?php

/**
 * Programa para buscar os resultados da análise das redes sociais
 *
 * @version    1.0.0
 * @package    VocatioTelecom
 * @subpackage login
 * @author     Alcindo Schleder <alcindoschleder@gmail.com>
 * @copyright (c) date('Y'), Alcindo Schleder
 */

include_once '../../include/config.inc.php';

class FacebookMessenger extends Base {
    
    const FACEBOOK_ENV = 'FACEBOOK_APP_DATA';
    const FACEBOOK_URL = 'https://graph.facebook.com/v2.12/me/messages?access_token=';

    private $Credentials   = false;
    private $receiveData   = false;
    private $senderPSID    = false;
    private $recipientID   = false;
    private $message       = false;
    private $hubChallenge  = false;
    private $hubCheckToken = false;
    
    public function __construct($data) {
        parent::__construct();
        $this->setCredentials();
        $this->setData($data);
    }
    public function __destruct() {
        $this->receiveData = null;
        $this->Credentials = null;
        parent::__destruct();
    }
    
    /**
     * Busca as credenciais do SNAM para acessar a API
     */
    private function setCredentials() {
        $this->Credentials = $this->getJsonToArray(getenv(self::FACEBOOK_ENV));
    }
    private function checkData($field, $fid){
        return (((isset($this->receiveData['entry'])) && (isset($this->receiveData['entry'][0])) && 
                 (isset($this->receiveData['entry'][0]['messaging'])) && (isset($this->receiveData['entry'][0]['messaging'][0])) &&
                 (isset($this->receiveData['entry'][0]['messaging'][0][$field])) &&
                 (isset($this->receiveData['entry'][0]['messaging'][0][$field][$fid]))) ? 
                $this->receiveData['entry'][0]['messaging'][0][$field][$fid] : false);
    }
    private function setData($data) {
        if (is_array($data)):
            $this->receiveData   = $data;
            $this->hubChallenge  = ((isset($this->receiveData['request']['hub_challenge'])) ? $this->receiveData['request']['hub_challenge'] : false);
            $this->hubCheckToken = ((isset($this->receiveData['request']['hub_verify_token'])) ? $this->receiveData['request']['hub_verify_token'] : false);
            if ((!$this->hubChallenge) || (!$this->hubCheckToken)):
                $this->senderPSID    = $this->checkData('sender', 'id');
                $this->recipientID   = $this->checkData('recipient', 'id');
                $this->message       = $this->checkData('message', 'text');
            endif;
        endif;
    }
    private function getAccessToken(bool $test = false) {
        if ($test):
            return $this->Credentials['VocatioTelecomMessenger']['tchat_token'];
        else:        
            return $this->Credentials['VocatioTelecomMessenger']['icity_token'];
        endif;
    }
    /**
     * Função que retorna o token gerado para verificação da autenticidade dos dados recebidos
     * @return string Token gerada para a Vocatio Telecom
     */
    private function getToken() {
        return $this->Credentials['VocatioTelecomMessenger']['vktio_token'];
    }
    /**
     * Função que verifica se o token recebido pela snam é igual ao das credenciais 
     * @param string $Tk Token de verificação da Vocatio Telecom recebido pela snam
     * @return boolean
     */
    public function verifyToken($Tk) {
        if ($Tk == $this->Credentials['VocatioTelecomMessenger']['vktio_token']):
            return true;
        else:
            return false;
        endif; 
    }
    public function isVerification() {
        return (($this->hubChallenge) &&  ($this->hubCheckToken));
    }
    public function getSender() {
        return $this->senderPSID;
    }
    public function getRecipient() {
        return $this->recipientID;
    }
    public function getMessage() {
        return $this->message;
    }
    public function challengeVerification() {
        if (($this->isVerification()) && ($this->hubCheckToken == $this->getToken())):
            $this->result['data'] = array('challenge' => $this->hubChallenge);
        endif;
    }
    public function sendMessage() {
        $opts = array(
            'method' => 'POST',
            'url' => self::FACEBOOK_URL . $this->getAccessToken(true),
            'setHeader' => array(
                'Content-Type: application/json',
                'Content-Type: application/x-www-form-urlencoded'
            ),
            'data' => array (
                "messaging_type" => "RESPONSE",
                "recipient" => array(
                    "id" => $this->getSender()
                ),
                "message" => array(
                    "text" => $this->getMessage()
                )
            )
        );
        $S = "[Saída: Facebook Messenger]\n";
        $this::setLogData($opts, $S);
        $this->execCurl($opts);
    }
}

// init program
$init = new InitPage(filter_input_array(INPUT_POST), filter_input_array(INPUT_GET));
$init->setFilter(array('hub_challenge' => FILTER_SANITIZE_STRING,
                        'hub_verify_token' => FILTER_SANITIZE_STRING));
$req = $init->getResult();
$init = null;
$S = '[Entrada: request facebook messenger]' . "\n";
Base::setLogData($req, $S);

if ((!empty($req)) && (is_array($req)) && (isset($req['request']['hub_challenge']))):
    $data = $req;
else:
    $data = json_decode(file_get_contents('php://input'), true);
endif;    
$S = '[Entrada: facebook messenger]' . "\n";
Base::setLogData($data, $S);
$req = null;
$verify = new FacebookMessenger($data);
if (!$verify->isVerification()):
    $verify->sendMessage();
else:
    $verify->ChallengeVerification();
endif;
$res = $verify->getResult();
$verify = null;
if (isset($res['data']['challenge'])):
    echo($res['data']['challenge']);
endif;
$S = '[Fim do programa: facebook messenger]' . "\n";
Base::setLogData($res, $S);

// token da página Cidades Interativas id: 191138084767112
// EAACBdKzd6BQBAAx3O2zqYk4lo0mDMe6s1D4HCOFUZBH5Hf1AYqdzU4H1sP5fSRLu9DesLZB2bZAQL5qRTVKSbfNgo3KHFWjtZBtgqoZBoOKMhIGLg2TEPVxHMmTuCO1Bm6jOYa4Tf2nrEtgmptpeCuNc850ueAkE94xwsQxcS3gZDZD
/*

token da pagina de testes chatbot id: 2103972693166100
EAACBdKzd6BQBAO1y8krp9TmKnr83dML4poDIFfzoQjMCnVwoac6Ba9ClTu8knE5z138cKNVl5ijLuyyYh9m7MKRS3ZBWhbUQWOZAlOrGP2Mdr4e2ZBCScw8BlCzPZCFBqkCBfx5IneIOZAuCVJuTYoOtA4qwgY2ohEBatsZAAdzYXPo1xEO7V6
 
 */